<?php

namespace App\Livewire\Manager\Promotions;

use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\ManagerPromotionQuery;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $type = '';
    public string $applicability = '';
    public bool $createModalOpen = false;
    public string $promotionName = '';
    public string $promotionType = 'percentage';
    public string $promotionValue = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public string $productPublicId = '';
    public string $memberTier = '';
    public bool $isActive = true;

    public function mount(): void { $this->authorizeManager(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedType(): void { $this->resetPage(); }
    public function updatedApplicability(): void { $this->resetPage(); }

    public function openCreate(): void { $this->resetValidation(); $this->createModalOpen = true; }
    public function closeCreate(): void { $this->reset(['promotionName', 'promotionValue', 'startsAt', 'endsAt', 'productPublicId', 'memberTier']); $this->promotionType = 'percentage'; $this->isActive = true; $this->createModalOpen = false; $this->resetValidation(); }

    public function saveCreate(): void
    {
        $manager = $this->authorizeManager();
        $data = $this->validate($this->formRules(), $this->messages());
        if (! $this->validateValue()) {
            return;
        }
        $productId = $this->resolveProductId($manager, $data['productPublicId'] ?? '');
        Promotion::query()->create(['store_id' => $manager->store_id, 'product_id' => $productId, 'name' => trim($data['promotionName']), 'type' => $data['promotionType'], 'value' => (int) $data['promotionValue'], 'member_tier' => filled($data['memberTier']) ? $data['memberTier'] : null, 'starts_at' => $data['startsAt'] ?: null, 'ends_at' => $data['endsAt'] ?: null, 'is_active' => $data['isActive']]);
        $this->closeCreate();
        session()->flash('status', 'Promo berhasil dibuat.');
    }

    public function render()
    {
        $manager = $this->authorizeManager(); $query = app(ManagerPromotionQuery::class);
        return view('livewire.manager.promotions.index', ['promotions' => $query->paginate($manager, ['search' => $this->search, 'status' => $this->status, 'type' => $this->type, 'applicability' => $this->applicability, 'perPage' => 20]), 'metrics' => $query->metrics($manager), 'products' => $query->products($manager)])->layout('components.layouts.manager', ['title' => 'Promo', 'activeNav' => 'promotions']);
    }

    private function formRules(): array { return ['promotionName' => ['required', 'string', 'max:255'], 'promotionType' => ['required', Rule::in(['percentage', 'fixed', 'fixed_price'])], 'promotionValue' => ['required', 'integer', 'min:0'], 'startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'productPublicId' => ['nullable', 'string'], 'memberTier' => ['nullable', Rule::in(['', 'regular', 'silver', 'gold', 'platinum'])], 'isActive' => ['boolean']]; }
    private function messages(): array { return ['promotionName.required' => 'Nama promo wajib diisi.', 'endsAt.after' => 'Waktu berakhir harus setelah waktu mulai.']; }
    private function validateValue(): bool
    {
        if ($this->promotionType === 'percentage' && (int) $this->promotionValue > 100) {
            $this->addError('promotionValue', 'Persentase promo maksimal 100%.');

            return false;
        }

        return true;
    }
    private function resolveProductId(User $manager, string $publicId): ?int { if (! filled($publicId)) return null; $product = app(\App\Queries\ManagerProductQuery::class)->find($manager, $publicId); abort_unless($product, 404); return $product->id; }
    private function authorizeManager(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403); return $user; }
}
