<?php

namespace App\Livewire\Owner\Products;

use App\Actions\Owner\CreateProductAction;
use App\Actions\Owner\UpdateProductAction;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Queries\OwnerProductQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?string $productPublicId = null;
    public string $storePublicId = '';
    public string $categoryPublicId = '';
    public string $unitPublicId = '';
    public string $name = '';
    public string $sku = '';
    public string $barcode = '';
    public string $sellingPrice = '';
    public string $costPrice = '';
    public string $minimumStock = '0';
    public string $productType = 'stockable';
    public bool $isActive = true;
    public array $groups = [];

    public function mount(Product|string|null $product = null): void
    {
        $owner = $this->authorizeOwner();
        if (! $product) { $this->storePublicId = (string) app(ActiveStoreContext::class)->current($owner)?->public_id; return; }
        $item = app(OwnerProductQuery::class)->find($owner, $product instanceof Product ? $product->public_id : $product); abort_unless($item, 404);
        $this->productPublicId = $item->public_id; $this->storePublicId = (string) $item->store?->public_id;
        $this->name = $item->name; $this->sku = $item->sku; $this->barcode = (string) ($item->barcode ?? ''); $this->categoryPublicId = (string) ($item->category?->public_id ?? ''); $this->unitPublicId = (string) ($item->unit?->public_id ?? '');
        $this->sellingPrice = number_format((int) $item->selling_price, 0, ',', '.'); $this->costPrice = number_format((int) $item->cost_price, 0, ',', '.'); $this->minimumStock = (string) $item->minimum_stock; $this->productType = $item->product_type->value; $this->isActive = $item->is_active;
        $this->groups = $item->optionGroups->map(fn ($group) => ['public_id' => $group->public_id, 'name' => $group->name, 'is_required' => $group->is_required, 'values' => $group->values->map(fn ($value) => ['public_id' => $value->public_id, 'name' => $value->name, 'price_adjustment' => (string) $value->price_adjustment, 'is_default' => $value->is_default])->values()->all()])->values()->all();
    }

    public function addGroup(): void { $this->groups[] = ['name' => '', 'is_required' => false, 'values' => [['name' => '', 'price_adjustment' => '0', 'is_default' => false]]]; }
    public function removeGroup(int $index): void { unset($this->groups[$index]); $this->groups = array_values($this->groups); }
    public function addValue(int $group): void { $this->groups[$group]['values'][] = ['name' => '', 'price_adjustment' => '0', 'is_default' => false]; }
    public function removeValue(int $group, int $value): void { unset($this->groups[$group]['values'][$value]); $this->groups[$group]['values'] = array_values($this->groups[$group]['values']); }

    public function save(): void
    {
        $owner = $this->authorizeOwner(); $this->normalizeMoney(); $data = $this->validate($this->rules());
        $stores = app(ActiveStoreContext::class)->availableStores($owner); $store = $stores->firstWhere('public_id', $this->storePublicId); abort_unless($store, 404);
        $attributes = ['category_id' => $this->resolveCategory($owner, $store->id), 'unit_id' => $this->resolveUnit($owner, $store->id), 'name' => trim($data['name']), 'sku' => trim($data['sku']), 'barcode' => filled($data['barcode']) ? trim($data['barcode']) : null, 'product_type' => $data['productType'], 'cost_price' => (int) $this->money($data['costPrice']), 'selling_price' => (int) $this->money($data['sellingPrice']), 'minimum_stock' => (int) $data['minimumStock'], 'is_active' => (bool) $data['isActive']];
        if ($this->productPublicId) {
            $product = app(OwnerProductQuery::class)->find($owner, $this->productPublicId); abort_unless($product, 404); $product = app(UpdateProductAction::class)->execute($owner, $product, $attributes, $this->groups);
        } else { $product = app(CreateProductAction::class)->execute($owner, $store, $attributes, $this->groups); }
        session()->flash('status', 'Produk berhasil disimpan.'); $this->redirectRoute('owner.products.show', ['product' => $product->public_id]);
    }

    public function render()
    {
        $owner = $this->authorizeOwner(); $query = app(OwnerProductQuery::class);
        return view('livewire.owner.products.form', ['editing' => (bool) $this->productPublicId, 'stores' => app(ActiveStoreContext::class)->availableStores($owner), 'categories' => $query->categories($owner, $this->storePublicId, true), 'units' => $query->units($owner, $this->storePublicId)])->layout('components.layouts.owner', ['title' => $this->productPublicId ? 'Edit Produk' : 'Tambah Produk', 'activeNav' => 'products']);
    }

    private function normalizeMoney(): void { $this->sellingPrice = (string) $this->money($this->sellingPrice); $this->costPrice = (string) $this->money($this->costPrice); }
    private function money(string $value): int { return (int) preg_replace('/\D+/', '', $value); }
    private function resolveCategory(User $owner, int $storeId): ?int { if (! filled($this->categoryPublicId)) return null; return app(OwnerProductQuery::class)->categories($owner, $this->storePublicId, true)->firstWhere('public_id', $this->categoryPublicId)?->id; }
    private function resolveUnit(User $owner, int $storeId): int { return (int) (app(OwnerProductQuery::class)->units($owner, $this->storePublicId)->firstWhere('public_id', $this->unitPublicId)?->id ?: abort(422, 'Unit produk tidak valid.')); }
    private function rules(): array { $sku = Rule::unique('products', 'sku'); $barcode = Rule::unique('products', 'barcode'); if ($this->productPublicId) { $sku->ignore($this->productPublicId, 'public_id'); $barcode->ignore($this->productPublicId, 'public_id'); } return ['storePublicId' => ['required', 'string'], 'name' => ['required', 'string', 'max:180'], 'sku' => ['required', 'string', 'max:80', $sku], 'barcode' => ['nullable', 'string', 'max:100', $barcode], 'productType' => ['required', Rule::enum(ProductType::class)], 'sellingPrice' => ['required', 'integer', 'min:0'], 'costPrice' => ['required', 'integer', 'min:0'], 'minimumStock' => ['required', 'integer', 'min:0'], 'isActive' => ['boolean']]; }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
