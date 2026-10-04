<?php

namespace App\Filament\Cashier\Pages;

use App\Actions\Sale\CreatePendingSaleAction;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\Promotion;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Furic\FilamentRedeemCodes\Models\RedeemCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PosCheckout extends Page
{
    protected string $view = 'filament.cashier.pages.pos-checkout';

    protected static ?string $navigationLabel = 'Kasir';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $title = null;

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $search = '';

    public ?int $selectedCategoryId = null;

    public ?int $customerId = null;

    /** Pencarian member di checkout, agar tidak perlu memuat seluruh pelanggan dalam select. */
    public string $customerSearch = '';

    /** Kode barcode/QR kartu member yang masuk dari scanner USB. */
    public string $customerCardCode = '';

    public bool $newMemberModalOpen = false;

    public string $newMemberName = '';

    public ?string $newMemberPhone = null;

    public ?string $newMemberEmail = null;

    /** @var array<int, array<string, mixed>> */
    public array $cart = [];

    public ?int $productForOptionsId = null;

    /** Kept independently from the computed product so the dialog closes reliably after Livewire updates. */
    public bool $productOptionsOpen = false;

    /** Index item keranjang yang sedang diubah; null berarti item baru. */
    public ?int $editingCartIndex = null;

    /** @var array<int, int|array<int, bool>|null> */
    public array $selectedOptionValueIds = [];

    public int $manualTransactionDiscount = 0;

    public string $promotionCode = '';

    public ?int $redeemCodeId = null;

    public int $promotionFlatDiscount = 0;

    public int $promotionPercentageDiscount = 0;

    public function mount(): void
    {
        if (! $this->currentShift()) {
            Notification::make()->warning()->title('Buka shift terlebih dahulu.')->send();
            $this->redirect('/cashier/open-shift');
        }
    }

    /** @return Collection<int, Product> */
    public function getProductsProperty(): Collection
    {
        $search = trim($this->search);

        return Product::query()
            ->with(['inventory', 'optionGroups.values'])
            ->where('is_active', true)
            ->when($this->selectedCategoryId, fn ($query) => $query->where('category_id', $this->selectedCategoryId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('barcode', $search)
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('barcode = ? desc', [$search])
            ->limit(12)
            ->get();
    }

    /** @return Collection<int, Category> */
    public function getCategoriesProperty(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(?int $categoryId = null): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()->with(['inventory', 'optionGroups.values'])->where('is_active', true)->find($productId);

        if (! $product) {
            throw ValidationException::withMessages(['product' => 'Produk tidak tersedia.']);
        }

        $groups = $product->optionGroups->filter(fn ($group): bool => $group->values->where('is_active', true)->isNotEmpty());

        if ($groups->isNotEmpty()) {
            $this->productForOptionsId = $product->id;
            $this->productOptionsOpen = true;
            $this->selectedOptionValueIds = [];

            foreach ($groups as $group) {
                $defaults = $group->values->where('is_active', true)->where('is_default', true)->pluck('id')->all();
                $this->selectedOptionValueIds[$group->id] = $group->maximum_selections === 1
                    ? ($defaults[0] ?? null)
                    : array_fill_keys($defaults, true);
            }

            return;
        }

        $this->addProductToCart($product);
    }

    public function confirmProductOptions(): void
    {
        $product = $this->productForOptions;

        if (! $product) {
            return;
        }

        $selectedValueIds = [];
        foreach ($product->optionGroups as $group) {
            $ids = $this->selectedIdsForGroup($group->id);

            if ($group->is_required && count($ids) < max(1, $group->minimum_selections)) {
                Notification::make()->warning()->title("Pilih opsi {$group->name} terlebih dahulu.")->send();

                return;
            }

            if (count($ids) > $group->maximum_selections) {
                Notification::make()->warning()->title("Pilihan {$group->name} melebihi batas.")->send();

                return;
            }

            $selectedValueIds = [...$selectedValueIds, ...$ids];
        }

        $values = ProductOptionValue::query()
            ->with('group')
            ->whereIn('id', $selectedValueIds)
            ->whereHas('group', fn ($query) => $query->where('product_id', $product->id))
            ->where('is_active', true)
            ->get();

        $modifiers = $values->map(fn (ProductOptionValue $value): array => ['group' => $value->group->name, 'name' => $value->name, 'price_adjustment' => $value->price_adjustment])->all();
        $modifierValueIds = $values->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if ($this->editingCartIndex !== null && isset($this->cart[$this->editingCartIndex])) {
            $this->cart[$this->editingCartIndex] = [
                ...$this->cart[$this->editingCartIndex],
                'modifier_total' => (int) $values->sum('price_adjustment'),
                'modifiers' => $modifiers,
                'modifier_value_ids' => $modifierValueIds,
                'modifier_signature' => collect($modifierValueIds)->sort()->implode(','),
            ];
        } else {
            $this->addProductToCart($product, $modifiers, $modifierValueIds);
        }

        $this->cancelProductOptions();
    }

    public function cancelProductOptions(): void
    {
        $this->productOptionsOpen = false;
        $this->productForOptionsId = null;
        $this->selectedOptionValueIds = [];
        $this->editingCartIndex = null;
    }

    public function editItemOptions(int $index): void
    {
        $item = $this->cart[$index] ?? null;

        if (! $item) {
            return;
        }

        $product = Product::query()->with('optionGroups.values')->find($item['product_id']);

        if (! $product || $product->optionGroups->isEmpty()) {
            return;
        }

        $selectedValueIds = collect($item['modifier_value_ids'] ?? [])->map(fn ($id): int => (int) $id)->all();
        $this->productForOptionsId = $product->id;
        $this->productOptionsOpen = true;
        $this->editingCartIndex = $index;
        $this->selectedOptionValueIds = [];

        foreach ($product->optionGroups as $group) {
            $ids = $group->values->pluck('id')->intersect($selectedValueIds)->map(fn ($id): int => (int) $id)->all();
            $this->selectedOptionValueIds[$group->id] = $group->maximum_selections === 1 ? ($ids[0] ?? null) : array_fill_keys($ids, true);
        }
    }

    public function selectSingleOption(int $groupId, int $valueId): void
    {
        $this->selectedOptionValueIds[$groupId] = $valueId;
    }

    public function toggleOption(int $groupId, int $valueId): void
    {
        $selected = $this->selectedOptionValueIds[$groupId] ?? [];
        $selected = is_array($selected) ? $selected : [];
        $selected[$valueId] = ! ($selected[$valueId] ?? false);
        $this->selectedOptionValueIds[$groupId] = $selected;
    }

    public function isOptionSelected(int $groupId, int $valueId): bool
    {
        return in_array($valueId, $this->selectedIdsForGroup($groupId), true);
    }

    public function getProductForOptionsProperty(): ?Product
    {
        return $this->productForOptionsId
            ? Product::query()->with('optionGroups.values')->find($this->productForOptionsId)
            : null;
    }

    /** @param array<int, array<string, mixed>> $modifiers @param array<int, int> $modifierValueIds */
    private function addProductToCart(Product $product, array $modifiers = [], array $modifierValueIds = []): void
    {
        $modifierTotal = (int) collect($modifiers)->sum('price_adjustment');
        $modifierSignature = implode(',', $modifierValueIds);
        $index = collect($this->cart)->search(fn (array $item): bool => $item['product_id'] === $product->id && $item['modifier_signature'] === $modifierSignature);

        if ($index !== false) {
            $this->cart[$index]['quantity']++;
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $this->priceFor($product),
                'quantity' => 1,
                'discount' => 0,
                'modifier_total' => $modifierTotal,
                'modifiers' => $modifiers,
                'modifier_value_ids' => $modifierValueIds,
                'modifier_signature' => $modifierSignature,
            ];
        }

        $this->search = '';
    }

    public function displayPrice(Product $product): int
    {
        return $this->priceFor($product);
    }

    private function priceFor(Product $product): int
    {
        $tier = $this->selectedCustomer?->tier;
        $promotion = Promotion::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('product_id')->orWhere('product_id', $product->id))
            ->where(fn ($query) => $query->whereNull('member_tier')->orWhere('member_tier', $tier))
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('value')->first();
        if (! $promotion) return $product->selling_price;
        return match ($promotion->type) { 'percentage' => max(0, $product->selling_price - (int) round($product->selling_price * min(100, $promotion->value) / 100)), 'fixed' => max(0, $product->selling_price - $promotion->value), 'fixed_price' => $promotion->value, default => $product->selling_price };
    }

    public function increaseQuantity(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity']++;
        }
    }

    public function decreaseQuantity(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        if ($this->cart[$index]['quantity'] === 1) {
            $this->removeItem($index);

            return;
        }

        $this->cart[$index]['quantity']--;
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function createPendingSale(): void
    {
        $shift = $this->currentShift();

        if (! $shift) {
            throw ValidationException::withMessages(['shift' => 'Tidak ada shift aktif.']);
        }

        $sale = app(CreatePendingSaleAction::class)->handle(
            $shift,
            $this->currentUser(),
            collect($this->cart)
                ->map(fn (array $item): array => [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'discount_amount' => min($item['discount'], ($item['price'] + $item['modifier_total']) * $item['quantity']),
                    'modifier_value_ids' => $item['modifier_value_ids'],
                ])
                ->all(),
            $this->manualTransactionDiscount,
            null,
            $this->redeemCodeId,
            $this->redeemCodeId ? strtoupper(trim($this->promotionCode)) : null,
            $this->customerId,
        );

        $this->cart = [];
        $this->redirect("/cashier/payment-page?sale={$sale->id}");
    }

    public function getSubtotalProperty(): int
    {
        return (int) collect($this->cart)->sum(
            fn (array $item): int => ($item['price'] + $item['modifier_total']) * $item['quantity'],
        );
    }

    public function getItemDiscountTotalProperty(): int
    {
        return (int) collect($this->cart)->sum(fn (array $item): int => min(max(0, (int) $item['discount']), ($item['price'] + $item['modifier_total']) * $item['quantity']));
    }

    public function getPromotionDiscountProperty(): int
    {
        $base = max(0, $this->subtotal - $this->itemDiscountTotal - $this->manualTransactionDiscount);
        $percentage = min($base, (int) round($base * $this->promotionPercentageDiscount / 100));

        return min($base, $percentage + $this->promotionFlatDiscount);
    }

    public function getTransactionDiscountProperty(): int
    {
        return min(
            max(0, $this->subtotal - $this->itemDiscountTotal),
            max(0, $this->manualTransactionDiscount) + $this->promotionDiscount,
        );
    }

    public function getGrandTotalProperty(): int
    {
        return max(0, $this->subtotal - $this->itemDiscountTotal - $this->transactionDiscount);
    }

    public function getSelectedCustomerProperty(): ?Customer
    {
        return $this->customerId
            ? Customer::query()->where('is_active', true)->find($this->customerId)
            : null;
    }

    /** @return Collection<int, Customer> */
    public function getCustomerMatchesProperty(): Collection
    {
        $keyword = trim($this->customerSearch);

        if (mb_strlen($keyword) < 2) {
            return new Collection;
        }

        return Customer::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('code', 'like', "%{$keyword}%")
                ->orWhere('card_number', 'like', "%{$keyword}%"))
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->where('is_active', true)->find($customerId);

        if (! $customer) {
            Notification::make()->danger()->title('Pelanggan tidak ditemukan atau tidak aktif.')->send();

            return;
        }

        $this->customerId = $customer->id;
        $this->customerSearch = '';
        $this->customerCardCode = '';
        Notification::make()->success()->title('Member '.$customer->name.' dipilih.')->send();
    }

    public function scanCustomerCard(): void
    {
        $code = trim($this->customerCardCode);

        if ($code === '') {
            Notification::make()->warning()->title('Masukkan atau scan kode kartu member terlebih dahulu.')->send();

            return;
        }

        $customer = Customer::query()->where('is_active', true)->where(fn ($query) => $query->where('code', $code)->orWhere('card_number', $code))->first();

        if (! $customer) {
            Notification::make()->warning()->title('Kartu member tidak dikenali.')->body('Kode kartu harus sama dengan kode member pelanggan.')->send();

            return;
        }

        $this->selectCustomer($customer->id);
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerSearch = '';
        $this->customerCardCode = '';
    }

    public function openNewMemberModal(): void
    {
        $this->newMemberModalOpen = true;
    }

    public function cancelNewMember(): void
    {
        $this->newMemberModalOpen = false;
        $this->reset(['newMemberName', 'newMemberPhone', 'newMemberEmail']);
    }

    public function createMember(): void
    {
        $data = $this->validate([
            'newMemberName' => ['required', 'string', 'max:255'],
            'newMemberPhone' => ['nullable', 'string', 'max:30'],
            'newMemberEmail' => ['nullable', 'email', 'max:255'],
        ]);

        $number = ((int) Customer::query()->max('id')) + 1;
        do {
            $code = 'MBR-'.str_pad((string) $number++, 3, '0', STR_PAD_LEFT);
        } while (Customer::query()->where('code', $code)->exists());

        $customer = Customer::query()->create([
            'code' => $code,
            'card_number' => $code,
            'tier' => 'regular',
            'name' => $data['newMemberName'],
            'phone' => $data['newMemberPhone'],
            'email' => $data['newMemberEmail'],
            'points' => 0,
            'is_active' => true,
        ]);

        $this->customerId = $customer->id;
        $this->customerSearch = '';
        $this->customerCardCode = '';
        $this->cancelNewMember();
        Notification::make()->success()->title('Member baru berhasil dibuat.')->body("Kode kartu: {$customer->code}")->send();
    }

    public function applyPromotion(): void
    {
        $code = RedeemCode::query()->with('campaign.rewards')->where('code', strtoupper(trim($this->promotionCode)))->first();

        if (! $code || (! $code->reusable && $code->redeemed_at) || ! $code->campaign?->isActive()) {
            $this->resetPromotion();
            Notification::make()->danger()->title('Kode promosi tidak valid atau sudah berakhir.')->send();

            return;
        }

        $this->redeemCodeId = $code->id;
        $this->promotionFlatDiscount = (int) $code->campaign->rewards
            ->filter(fn ($reward): bool => in_array($reward->getRawOriginal('type'), ['discount_amount', 'diskon_nominal'], true))
            ->sum('amount');
        $this->promotionPercentageDiscount = min(100, (int) $code->campaign->rewards
            ->filter(fn ($reward): bool => in_array($reward->getRawOriginal('type'), ['discount_percentage', 'diskon_persen'], true))
            ->sum('amount'));

        if ($this->promotionDiscount < 1) {
            $this->resetPromotion();
            Notification::make()->warning()->title('Kode ini tidak memiliki hadiah diskon POS.')->send();

            return;
        }

        Notification::make()->success()->title('Kode promosi diterapkan.')->body('Potongan Rp '.number_format($this->promotionDiscount, 0, ',', '.'))->send();
    }

    public function removePromotion(): void
    {
        $this->resetPromotion();
    }

    private function resetPromotion(): void
    {
        $this->redeemCodeId = null;
        $this->promotionFlatDiscount = 0;
        $this->promotionPercentageDiscount = 0;
    }

    /** @return array<int, int> */
    private function selectedIdsForGroup(int $groupId): array
    {
        $selection = $this->selectedOptionValueIds[$groupId] ?? [];

        if (! is_array($selection)) {
            return $selection ? [(int) $selection] : [];
        }

        return collect($selection)->filter()->keys()->map(fn ($id): int => (int) $id)->all();
    }

    private function currentUser(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }

    private function currentShift(): ?CashierShift
    {
        return CashierShift::query()
            ->where('user_id', $this->currentUser()->id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->first();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists();
    }
}
