<?php
namespace App\Filament\Resources\Purchases\Pages;
use App\Filament\Resources\Purchases\PurchaseResource; use Filament\Resources\Pages\EditRecord;
class EditPurchase extends EditRecord { protected static string $resource = PurchaseResource::class; protected function mutateFormDataBeforeSave(array $data): array { $subtotal = collect($data['items'] ?? [])->sum(fn ($item) => ((int) ($item['quantity'] ?? 0)) * ((int) ($item['unit_cost'] ?? 0))); $data['subtotal'] = $subtotal; $data['grand_total'] = $subtotal; return $data; } protected function afterSave(): void { $this->record->items()->each(fn ($item) => $item->update(['line_total' => $item->quantity * $item->unit_cost])); } }
