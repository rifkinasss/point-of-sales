<?php

namespace App\Livewire\Pos;

use App\Actions\Sale\CreatePendingSaleAction;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleCalculator;
use Filament\Notifications\Notification;
use Furic\FilamentRedeemCodes\Models\RedeemCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Checkout extends Component
{
    public string $search = '';

    public ?string $selectedCategoryPublicId = null;

    public ?string $customerPublicId = null;

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

    public ?string $productForOptionsPublicId = null;

    /** Kept independently from the computed product so the dialog closes reliably after Livewire updates. */
    public bool $productOptionsOpen = false;

    /** Index item keranjang yang sedang diubah; null berarti item baru. */
    public ?int $editingCartIndex = null;

    /** @var array<string, string|array<string, bool>|null> */
    public array $selectedOptionValuePublicIds = [];

    public int $manualTransactionDiscount = 0;

    public string $promotionCode = '';

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
            ->where('store_id', $this->currentUser()->store_id)
            ->when($this->selectedCategoryPublicId, function ($query): void {
                $categoryId = Category::query()->where('public_id', $this->selectedCategoryPublicId)->value('id');

                $query->where('category_id', $categoryId ?: 0);
            })
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

    public function selectCategory(?string $categoryPublicId = null): void
    {
        $this->selectedCategoryPublicId = $categoryPublicId;
    }

    public function scanProduct(): void
    {
        $barcode = trim($this->search);

        if ($barcode === '') {
            return;
        }

        $product = Product::query()
            ->with(['inventory', 'optionGroups.values'])
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('barcode', $barcode)
                ->orWhere('sku', $barcode))
            ->first();

        if (! $product) {
            Notification::make()
                ->warning()
                ->title('Produk tidak ditemukan')
                ->body('Periksa barcode atau gunakan nama produk untuk mencari.')
                ->send();

            return;
        }

        $this->addProduct((string) $product->public_id);
    }

    public function addProduct(string $productPublicId): void
    {
        $product = $this->productForPublicId($productPublicId);

        if (! $product) {
            throw ValidationException::withMessages(['product' => 'Produk tidak tersedia.']);
        }

        $this->assertAvailableStock($product, 1);

        $groups = $product->optionGroups->filter(fn ($group): bool => $group->values->where('is_active', true)->isNotEmpty());

        if ($groups->isNotEmpty()) {
            $this->productForOptionsPublicId = $product->public_id;
            $this->productOptionsOpen = true;
            $this->selectedOptionValuePublicIds = [];

            foreach ($groups as $group) {
                $defaults = $group->values->where('is_active', true)->where('is_default', true)->pluck('public_id')->all();
                $this->selectedOptionValuePublicIds[$group->public_id] = $group->maximum_selections === 1
                    ? ($defaults[0] ?? null)
                    : array_fill_keys($defaults, true);
            }

            $this->dispatch('product-options-opened');

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
            $ids = $this->selectedIdsForGroup($group->public_id);

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
            ->whereIn('public_id', $selectedValueIds)
            ->whereHas('group', fn ($query) => $query->where('product_id', $product->id))
            ->where('is_active', true)
            ->get();

        $modifiers = $values->map(fn (ProductOptionValue $value): array => ['group' => $value->group->name, 'name' => $value->name, 'price_adjustment' => $value->price_adjustment])->all();
        $modifierValuePublicIds = $values->pluck('public_id')->map(fn ($id): string => (string) $id)->all();

        if ($this->editingCartIndex !== null && isset($this->cart[$this->editingCartIndex])) {
            $this->cart[$this->editingCartIndex] = [
                ...$this->cart[$this->editingCartIndex],
                'modifier_total' => (int) $values->sum('price_adjustment'),
                'modifiers' => $modifiers,
                'modifier_value_public_ids' => $modifierValuePublicIds,
                'modifier_signature' => collect($modifierValuePublicIds)->sort()->implode(','),
            ];
        } else {
            $this->addProductToCart($product, $modifiers, $modifierValuePublicIds);
        }

        $this->cancelProductOptions();
    }

    public function cancelProductOptions(): void
    {
        $this->productOptionsOpen = false;
        $this->productForOptionsPublicId = null;
        $this->selectedOptionValuePublicIds = [];
        $this->editingCartIndex = null;
    }

    public function editItemOptions(int $index): void
    {
        $item = $this->cart[$index] ?? null;

        if (! $item) {
            return;
        }

        $product = Product::query()->with('optionGroups.values')->where('public_id', $item['product_public_id'])->first();

        if (! $product || $product->optionGroups->isEmpty()) {
            return;
        }

        $selectedValuePublicIds = $item['modifier_value_public_ids'] ?? [];
        $this->productForOptionsPublicId = $product->public_id;
        $this->productOptionsOpen = true;
        $this->editingCartIndex = $index;
        $this->selectedOptionValuePublicIds = [];

        foreach ($product->optionGroups as $group) {
            $ids = $group->values->pluck('public_id')->intersect($selectedValuePublicIds)->map(fn ($id): string => (string) $id)->all();
            $this->selectedOptionValuePublicIds[$group->public_id] = $group->maximum_selections === 1 ? ($ids[0] ?? null) : array_fill_keys($ids, true);
        }

        $this->dispatch('product-options-opened');
    }

    public function selectSingleOption(string $groupPublicId, string $valuePublicId): void
    {
        $this->selectedOptionValuePublicIds[$groupPublicId] = $valuePublicId;
    }

    public function toggleOption(string $groupPublicId, string $valuePublicId): void
    {
        $selected = $this->selectedOptionValuePublicIds[$groupPublicId] ?? [];
        $selected = is_array($selected) ? $selected : [];
        $selected[$valuePublicId] = ! ($selected[$valuePublicId] ?? false);
        $this->selectedOptionValuePublicIds[$groupPublicId] = $selected;
    }

    public function selectOption(string $groupPublicId, string $valuePublicId, bool $single): void
    {
        $single ? $this->selectSingleOption($groupPublicId, $valuePublicId) : $this->toggleOption($groupPublicId, $valuePublicId);
    }

    public function isOptionSelected(string $groupPublicId, string $valuePublicId): bool
    {
        return in_array($valuePublicId, $this->selectedIdsForGroup($groupPublicId), true);
    }

    public function canConfirmProductOptions(): bool
    {
        $product = $this->productForOptions;

        if (! $product) {
            return false;
        }

        return $product->optionGroups->every(function ($group): bool {
            $selectedCount = count($this->selectedIdsForGroup($group->public_id));

            return ! $group->is_required || $selectedCount >= max(1, (int) $group->minimum_selections);
        });
    }

    public function getProductForOptionsProperty(): ?Product
    {
        return $this->productForOptionsPublicId
            ? Product::query()->with('optionGroups.values')->where('public_id', $this->productForOptionsPublicId)->first()
            : null;
    }

    /** @param array<int, array<string, mixed>> $modifiers @param array<int, string> $modifierValuePublicIds */
    private function addProductToCart(Product $product, array $modifiers = [], array $modifierValuePublicIds = []): void
    {
        $modifierTotal = (int) collect($modifiers)->sum('price_adjustment');
        $modifierSignature = implode(',', $modifierValuePublicIds);
        $index = collect($this->cart)->search(fn (array $item): bool => $item['product_public_id'] === $product->public_id && $item['modifier_signature'] === $modifierSignature);

        if ($index !== false) {
            $this->assertAvailableStock($product, (int) $this->cart[$index]['quantity'] + 1);
            $this->cart[$index]['quantity']++;
        } else {
            $this->assertAvailableStock($product, 1);
            $this->cart[] = [
                'product_public_id' => $product->public_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $this->priceFor($product),
                'quantity' => 1,
                'discount' => 0,
                'modifier_total' => $modifierTotal,
                'modifiers' => $modifiers,
                'modifier_value_public_ids' => $modifierValuePublicIds,
                'has_options' => $product->optionGroups
                    ->filter(fn (ProductOptionGroup $group): bool => $group->values->where('is_active', true)->isNotEmpty())
                    ->isNotEmpty(),
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
        if (! $promotion) {
            return $product->selling_price;
        }

        return match ($promotion->type) {
            'percentage' => max(0, $product->selling_price - (int) round($product->selling_price * min(100, $promotion->value) / 100)), 'fixed' => max(0, $product->selling_price - $promotion->value), 'fixed_price' => $promotion->value, default => $product->selling_price
        };
    }

    public function increaseQuantity(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $product = $this->productForPublicId((string) ($this->cart[$index]['product_public_id'] ?? ''));

        if (! $product) {
            $this->addError('cart', 'Produk pada pesanan tidak tersedia untuk toko ini.');

            return;
        }

        try {
            $this->assertAvailableStock($product, (int) $this->cart[$index]['quantity'] + 1);
            $this->cart[$index]['quantity']++;
        } catch (ValidationException $exception) {
            $this->addError('cart', 'Stok produk tidak mencukupi.');
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

    public function clearCart(): void
    {
        $this->cart = [];
        $this->resetPromotion();
        $this->manualTransactionDiscount = 0;
    }

    public function createPendingSale(): void
    {
        $sale = $this->createPendingSaleRecord();

        $this->cart = [];
        $this->redirect(route('cashier.payment', ['sale' => $sale->public_id]));
    }

    protected function createPendingSaleRecord(): Sale
    {
        $shift = $this->currentShift();

        if (! $shift) {
            throw ValidationException::withMessages(['shift' => 'Tidak ada shift aktif.']);
        }

        return app(CreatePendingSaleAction::class)->handle(
            $shift,
            $this->currentUser(),
            $this->cartForApplication(),
            $this->manualTransactionDiscount,
            null,
            $this->promotionCode !== '' ? RedeemCode::query()->where('code', strtoupper(trim($this->promotionCode)))->value('id') : null,
            $this->promotionCode !== '' ? strtoupper(trim($this->promotionCode)) : null,
            $this->customerPublicId ? Customer::query()->where('public_id', $this->customerPublicId)->value('id') : null,
        );
    }

    /** @return array<int, array{product_id: int, quantity: int, discount_amount: int, modifier_value_ids: array<int, int>}> */
    private function cartForApplication(): array
    {
        return collect($this->cart)->map(function (array $item): array {
            $product = $this->productForPublicId((string) $item['product_public_id']);

            if (! $product) {
                throw ValidationException::withMessages(['product' => 'Produk tidak tersedia untuk toko ini.']);
            }

            $modifierValueIds = ProductOptionValue::query()
                ->whereIn('public_id', $item['modifier_value_public_ids'] ?? [])
                ->whereHas('group', fn ($query) => $query->where('product_id', $product->id))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            return [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'discount_amount' => min($item['discount'], ($item['price'] + $item['modifier_total']) * $item['quantity']),
                'modifier_value_ids' => $modifierValueIds,
            ];
        })->all();
    }

    private function productForPublicId(string $productPublicId): ?Product
    {
        return Product::query()
            ->with(['inventory', 'optionGroups.values'])
            ->where('store_id', $this->currentUser()->store_id)
            ->where('is_active', true)
            ->where('public_id', $productPublicId)
            ->first();
    }

    private function assertAvailableStock(Product $product, int $requestedQuantity): void
    {
        if (! $product->product_type->tracksInventory()) {
            return;
        }

        $availableQuantity = (int) ($product->inventory?->quantity ?? 0);

        if ($availableQuantity < $requestedQuantity) {
            throw ValidationException::withMessages(['product' => 'Stok produk tidak mencukupi.']);
        }
    }

    public function getSubtotalProperty(): int
    {
        return (int) ($this->saleCalculation?->subtotal ?? 0);
    }

    public function getItemDiscountTotalProperty(): int
    {
        return (int) ($this->saleCalculation?->itemDiscountTotal ?? 0);
    }

    public function getPromotionDiscountProperty(): int
    {
        return (int) ($this->saleCalculation?->promotionDiscountTotal ?? 0);
    }

    public function getTransactionDiscountProperty(): int
    {
        return (int) ($this->saleCalculation?->transactionDiscountTotal ?? 0);
    }

    public function getGrandTotalProperty(): int
    {
        return (int) ($this->saleCalculation?->grandTotal ?? 0);
    }

    public function getSaleCalculationProperty(): mixed
    {
        if ($this->cart === []) {
            return null;
        }

        $shift = $this->currentShift();

        if (! $shift) {
            return null;
        }

        try {
            $redeemCode = $this->promotionCode !== ''
                ? RedeemCode::query()->with('campaign.rewards')->where('code', strtoupper(trim($this->promotionCode)))->first()
                : null;

            return app(SaleCalculator::class)->calculate(
                $this->cartForApplication(),
                max(0, (int) $this->manualTransactionDiscount),
                $redeemCode,
                $shift->store_id ?: $this->currentUser()->store_id,
                $this->selectedCustomer?->tier,
            );
        } catch (ValidationException) {
            return null;
        }
    }

    public function getSelectedCustomerProperty(): ?Customer
    {
        return $this->customerPublicId
            ? Customer::query()->where('is_active', true)->where('public_id', $this->customerPublicId)->first()
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

    public function selectCustomer(string $customerPublicId): void
    {
        $customer = Customer::query()->where('is_active', true)->where('public_id', $customerPublicId)->first();

        if (! $customer) {
            Notification::make()->danger()->title('Pelanggan tidak ditemukan atau tidak aktif.')->send();

            return;
        }

        $this->customerPublicId = $customer->public_id;
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

        $this->selectCustomer((string) $customer->public_id);
    }

    public function clearCustomer(): void
    {
        $this->customerPublicId = null;
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

        $this->customerPublicId = $customer->public_id;
        $this->customerSearch = '';
        $this->customerCardCode = '';
        $this->cancelNewMember();
        $this->dispatch('member-created');
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

        $this->promotionCode = strtoupper(trim($code->code));
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
        $this->promotionFlatDiscount = 0;
        $this->promotionPercentageDiscount = 0;
    }

    /** @return array<int, string> */
    private function selectedIdsForGroup(string $groupPublicId): array
    {
        $selection = $this->selectedOptionValuePublicIds[$groupPublicId] ?? [];

        if (! is_array($selection)) {
            return $selection ? [(string) $selection] : [];
        }

        return collect($selection)->filter()->keys()->map(fn ($id): string => (string) $id)->all();
    }

    private function currentUser(): User
    {
        $user = request()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }

    private function currentShift(): ?CashierShift
    {
        return CashierShift::query()
            ->where('user_id', $this->currentUser()->id)
            ->where('store_id', $this->currentUser()->store_id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->first();
    }

    public function getActiveShiftProperty(): ?CashierShift
    {
        return $this->currentShift();
    }
}
