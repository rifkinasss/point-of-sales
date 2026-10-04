<?php
namespace App\Filament\Resources\Purchases\Pages;
use App\Filament\Resources\Purchases\PurchaseResource; use Filament\Resources\Pages\CreateRecord;
class CreatePurchase extends CreateRecord { protected static string $resource = PurchaseResource::class; protected function mutateFormDataBeforeCreate(array $data): array { $items = $data['items'] ?? []; $subtotal = collect($items)->sum(fn ($item) => ((int) ($item['quantity'] ?? 0)) * ((int) ($item['unit_cost'] ?? 0))); $data['subtotal'] = $subtotal; $data['grand_total'] = $subtotal; $data['status'] = 'draft'; return $data; } protected function afterCreate(): void { $this->record->items()->each(fn ($item) => $item->update(['line_total' => $item->quantity * $item->unit_cost])); } }
