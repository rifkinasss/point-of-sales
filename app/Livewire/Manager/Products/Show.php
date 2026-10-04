<?php

namespace App\Livewire\Manager\Products;

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Queries\ManagerProductQuery;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public ?string $productPublicId = null;
    public bool $adjustmentModalOpen = false;
    public string $adjustmentDirection = 'in';
    public string $adjustmentQuantity = '';
    public string $adjustmentReason = '';

    public function mount(string|Product $product): void
    {
        $this->authorizeManager();
        $this->productPublicId = $product instanceof Product ? $product->public_id : $product;
        abort_unless($this->product instanceof Product, 404);
    }

    public function openAdjustment(): void
    {
        abort_unless($this->product?->product_type === ProductType::Stockable, 422);
        $this->resetValidation();
        $this->adjustmentModalOpen = true;
    }

    public function closeAdjustment(): void
    {
        $this->reset(['adjustmentQuantity', 'adjustmentReason']);
        $this->adjustmentDirection = 'in';
        $this->adjustmentModalOpen = false;
        $this->resetValidation();
    }

    public function saveAdjustment(): void
    {
        $manager = $this->authorizeManager();
        $data = $this->validate([
            'adjustmentDirection' => ['required', Rule::in(['in', 'out'])],
            'adjustmentQuantity' => ['required', 'integer', 'min:1'],
            'adjustmentReason' => ['required', 'string', 'max:1000'],
        ], [
            'adjustmentQuantity.min' => 'Jumlah penyesuaian harus lebih dari nol.',
            'adjustmentReason.required' => 'Alasan penyesuaian wajib diisi.',
        ]);
        $product = app(ManagerProductQuery::class)->find($manager, (string) $this->productPublicId);
        abort_unless($product instanceof Product, 404);

        try {
            app(AdjustStockAction::class)->handle($product, $manager, $data['adjustmentDirection'] === 'in' ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, (int) $data['adjustmentQuantity'], trim($data['adjustmentReason']));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'quantity' ? 'adjustmentQuantity' : $field, $message);
                }
            }
            return;
        }

        $this->closeAdjustment();
        session()->flash('status', 'Penyesuaian stok berhasil dicatat.');
    }

    public function render()
    {
        $this->authorizeManager();
        $product = $this->product;
        abort_unless($product instanceof Product, 404);
        $movements = StockMovement::query()->withoutGlobalScopes()->with('performedBy')->where('store_id', $product->store_id)->where('product_id', $product->id)->latest('occurred_at')->paginate(20, pageName: 'movementsPage');

        return view('livewire.manager.products.show', compact('product', 'movements'))->layout('components.layouts.manager', ['title' => 'Detail Produk', 'activeNav' => 'products']);
    }

    public function getProductProperty(): ?Product
    {
        $manager = $this->authorizeManager();
        return $this->productPublicId ? app(ManagerProductQuery::class)->find($manager, $this->productPublicId) : null;
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);
        return $user;
    }
}
