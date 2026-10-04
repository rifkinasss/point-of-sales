<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\Promotion;
use Furic\FilamentRedeemCodes\Models\RedeemCode;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SaleCalculator
{
    /**
     * @param  array<int, array{product_id: int, quantity: int, discount_amount?: int, modifier_value_ids?: array<int, int>}>  $items
     */
    public function calculate(array $items, int $transactionDiscount = 0, ?RedeemCode $redeemCode = null, ?int $storeId = null, ?string $customerTier = null): SaleCalculation
    {
        if ($items === [] || $transactionDiscount < 0) {
            throw ValidationException::withMessages([
                'items' => 'Keranjang harus berisi item dan diskon transaksi tidak boleh negatif.',
            ]);
        }

        $snapshots = [];
        $subtotal = 0;
        $itemDiscountTotal = 0;

        foreach ($items as $item) {
            $product = Product::query()
                ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
                ->with('unit')
                ->find($item['product_id'] ?? null);
            $quantity = (int) ($item['quantity'] ?? 0);
            $discountAmount = (int) ($item['discount_amount'] ?? 0);

            if (! $product || ! $product->is_active || $quantity < 1) {
                throw ValidationException::withMessages([
                    'items' => 'Produk harus aktif dan kuantitas harus lebih dari nol.',
                ]);
            }

            $modifierValues = ProductOptionValue::query()
                ->with('group')
                ->whereIn('id', $item['modifier_value_ids'] ?? [])
                ->where('is_active', true)
                ->whereHas('group', fn ($query) => $query->where('product_id', $product->id))
                ->get();
            $modifierTotal = (int) $modifierValues->sum('price_adjustment');
            $unitPrice = $this->priceFor($product, $storeId, $customerTier);
            $lineSubtotal = ($unitPrice + $modifierTotal) * $quantity;

            if ($discountAmount < 0 || $discountAmount > $lineSubtotal) {
                throw ValidationException::withMessages(['items' => 'Diskon item tidak valid.']);
            }

            $snapshots[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit_name' => $product->unit->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'modifier_total' => $modifierTotal,
                'modifiers' => $modifierValues->map(fn (ProductOptionValue $value): array => [
                    'group' => $value->group->name,
                    'name' => $value->name,
                    'price_adjustment' => $value->price_adjustment,
                ])->all(),
                'discount_amount' => $discountAmount,
                'line_total' => $lineSubtotal - $discountAmount,
            ];

            $subtotal += $lineSubtotal;
            $itemDiscountTotal += $discountAmount;
        }

        $afterItemDiscount = $subtotal - $itemDiscountTotal;

        if ($transactionDiscount > $afterItemDiscount) {
            throw ValidationException::withMessages([
                'transaction_discount' => 'Diskon transaksi melebihi total keranjang.',
            ]);
        }

        $taxTotal = 0;
        $promotionDiscountTotal = $redeemCode
            ? $this->promotionDiscount($redeemCode, max(0, $afterItemDiscount - $transactionDiscount))
            : 0;

        if ($redeemCode && $promotionDiscountTotal < 1) {
            throw ValidationException::withMessages(['promotion' => 'Kode promosi tidak memiliki diskon POS yang valid.']);
        }

        $grandTotal = max(0, $afterItemDiscount - $transactionDiscount - $promotionDiscountTotal + $taxTotal);

        return new SaleCalculation(
            items: $snapshots,
            subtotal: $subtotal,
            itemDiscountTotal: $itemDiscountTotal,
            promotionDiscountTotal: $promotionDiscountTotal,
            transactionDiscountTotal: $transactionDiscount,
            taxTotal: $taxTotal,
            grandTotal: $grandTotal,
        );
    }

    private function promotionDiscount(RedeemCode $redeemCode, int $base): int
    {
        $rewards = $redeemCode->campaign?->rewards ?? new Collection();
        $fixed = (int) $rewards
            ->filter(fn ($reward): bool => in_array($reward->getRawOriginal('type'), ['discount_amount', 'diskon_nominal'], true))
            ->sum('amount');
        $percentage = min(100, (int) $rewards
            ->filter(fn ($reward): bool => in_array($reward->getRawOriginal('type'), ['discount_percentage', 'diskon_persen'], true))
            ->sum('amount'));

        return min($base, (int) round($base * $percentage / 100) + $fixed);
    }

    private function priceFor(Product $product, ?int $storeId, ?string $customerTier): int
    {
        $promotion = Promotion::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('product_id')->orWhere('product_id', $product->id))
            ->where(fn ($query) => $query->whereNull('member_tier')->orWhere('member_tier', $customerTier))
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('value')
            ->first();

        if (! $promotion) {
            return $product->selling_price;
        }

        return match ($promotion->type) {
            'percentage' => max(0, $product->selling_price - (int) round($product->selling_price * min(100, $promotion->value) / 100)),
            'fixed' => max(0, $product->selling_price - $promotion->value),
            'fixed_price' => $promotion->value,
            default => $product->selling_price,
        };
    }
}
