<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\CashMovementType;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::query()->updateOrCreate(['slug' => 'naslabs-market'], [
                'name' => 'NasLabs Market', 'phone' => '0812-3456-7890', 'email' => 'market@naslabs.id',
                'address' => 'Jl. Sudirman No. 88, Samarinda', 'currency' => 'IDR', 'timezone' => 'Asia/Makassar',
                'invoice_prefix' => 'NLM', 'tax_percentage' => 11, 'receipt_footer' => 'Terima kasih sudah berbelanja di NasLabs Market.',
                'receipt_show_cashier' => true, 'receipt_show_tax' => true,
            ]);
            Store::query()->updateOrCreate(['slug' => 'borneo-kiosk'], [
                'name' => 'Borneo Kiosk', 'phone' => '0852-1111-2233', 'email' => 'halo@borneokiosk.id',
                'address' => 'Jl. Pahlawan No. 12, Balikpapan', 'currency' => 'IDR', 'timezone' => 'Asia/Makassar',
                'invoice_prefix' => 'BRN', 'tax_percentage' => 0, 'receipt_footer' => 'Sampai jumpa kembali.',
                'receipt_show_cashier' => true, 'receipt_show_tax' => false,
            ]);

            $owner = $this->user('Nadia Owner', 'owner@naslabs.id', UserRole::Owner, $store->id);
            $manager = $this->user('Raka Manager', 'manager@naslabs.id', UserRole::Manager, $store->id);
            $cashier = $this->user('Sinta Kasir', 'kasir@naslabs.id', UserRole::Cashier, $store->id);
            $this->user('Administrator NasLabs', 'admin@naslabs.id', UserRole::Admin, null);

            $food = Category::withoutGlobalScopes()->updateOrCreate(['name' => 'Makanan'], ['store_id' => $store->id, 'is_active' => true]);
            $drink = Category::withoutGlobalScopes()->updateOrCreate(['name' => 'Minuman'], ['store_id' => $store->id, 'is_active' => true]);
            $unit = Unit::withoutGlobalScopes()->updateOrCreate(['name' => 'Pcs'], ['store_id' => $store->id, 'symbol' => 'pcs', 'is_active' => true]);
            $items = [
                [$food, 'Nasi Goreng Spesial', 'NLM-FOOD-001', '8991000000011', 18000, 25000, 45],
                [$food, 'Roti Cokelat', 'NLM-FOOD-002', '8991000000028', 6500, 10000, 32],
                [$drink, 'Es Teh Manis', 'NLM-DRINK-001', '8991000000035', 3000, 7000, 80],
                [$drink, 'Kopi Susu Gula Aren', 'NLM-DRINK-002', '8991000000042', 12000, 20000, 26],
            ];
            $products = [];
            foreach ($items as [$category, $name, $sku, $barcode, $cost, $price, $stock]) {
                $product = Product::withoutGlobalScopes()->updateOrCreate(['sku' => $sku], [
                    'store_id' => $store->id, 'category_id' => $category->id, 'unit_id' => $unit->id, 'name' => $name, 'barcode' => $barcode,
                    'product_type' => 'stockable', 'cost_price' => $cost, 'selling_price' => $price, 'minimum_stock' => 10, 'is_active' => true,
                ]);
                $inventory = Inventory::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0]);
                if (! $product->stockMovements()->exists()) {
                    $inventory->update(['quantity' => $stock]);
                    $product->stockMovements()->create([
                        'store_id' => $store->id,
                        'type' => StockMovementType::InitialIn,
                        'quantity_change' => $stock,
                        'quantity_before' => 0,
                        'quantity_after' => $stock,
                        'reference_type' => 'seed_initial_stock',
                        'reason' => 'Stok awal development seed',
                        'performed_by' => $owner->id,
                        'occurred_at' => now(),
                    ]);
                }
                $products[] = $product;
            }

            $this->optionGroup($products[2], 'Level gula', true, [
                ['Normal sugar', 0, true],
                ['Less sugar', 0, false],
                ['No sugar', 0, false],
            ]);
            $this->optionGroup($products[2], 'Es', true, [
                ['Ice normal', 0, true],
                ['Less ice', 0, false],
                ['No ice', 0, false],
            ]);
            $this->optionGroup($products[3], 'Level gula', true, [
                ['Normal sugar', 0, true],
                ['Less sugar', 0, false],
                ['No sugar', 0, false],
            ]);
            $this->optionGroup($products[3], 'Add-on', false, [
                ['Extra espresso', 5000, false],
                ['Extra jelly', 3000, false],
            ]);

            $supplier = Supplier::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'name' => 'PT Pangan Sejahtera'], ['phone' => '0811-5555-6677', 'email' => 'sales@pangansejahtera.id', 'address' => 'Samarinda', 'is_active' => true]);
            Customer::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'code' => 'MBR-001'], ['name' => 'Andi Pratama', 'phone' => '0813-9000-1122', 'points' => 125, 'credit_limit' => 500000, 'credit_balance' => 0, 'is_active' => true]);
            Customer::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'code' => 'MBR-002'], ['name' => 'Maya Sari', 'phone' => '0851-7000-3344', 'points' => 78, 'credit_limit' => 250000, 'credit_balance' => 50000, 'is_active' => true]);
            DB::table('expenses')->updateOrInsert(['store_id' => $store->id, 'category' => 'Listrik', 'description' => 'Tagihan listrik bulan berjalan'], ['amount' => 350000, 'occurred_at' => now()->subDays(2), 'created_by' => $owner->id, 'updated_at' => now(), 'created_at' => now()]);
            DB::table('expenses')->updateOrInsert(['store_id' => $store->id, 'category' => 'Operasional', 'description' => 'Pengadaan kemasan'], ['amount' => 125000, 'occurred_at' => now()->subDay(), 'created_by' => $manager->id, 'updated_at' => now(), 'created_at' => now()]);
            DB::table('purchases')->updateOrInsert(['store_id' => $store->id, 'purchase_number' => 'PO-DEMO-001'], ['supplier_id' => $supplier->id, 'status' => 'received', 'subtotal' => 630000, 'discount_total' => 30000, 'grand_total' => 600000, 'received_at' => now()->subDays(7), 'received_by' => $manager->id, 'note' => 'Pembelian stok demo', 'updated_at' => now(), 'created_at' => now()]);

            $shift = CashierShift::withoutGlobalScopes()->updateOrCreate(['user_id' => $cashier->id, 'opened_at' => now()->startOfDay()], ['store_id' => $store->id, 'status' => ShiftStatus::Open, 'opening_cash' => 300000, 'opening_note' => 'Shift demo']);
            CashMovement::withoutGlobalScopes()->firstOrCreate([
                'shift_id' => $shift->id,
                'type' => CashMovementType::Opening,
                'reference_type' => null,
                'reference_id' => null,
            ], [
                'store_id' => $store->id,
                'user_id' => $cashier->id,
                'amount' => 300000,
                'note' => 'Shift demo',
            ]);
            foreach ([0 => 42000, 1 => 57000, 2 => 68000, 3 => 51000, 4 => 74000, 5 => 63000, 6 => 89000] as $daysAgo => $total) {
                $date = now()->subDays($daysAgo)->setTime(10, 15);
                $sale = Sale::withoutGlobalScopes()->updateOrCreate(['invoice_number' => 'NLM-DEMO-'.str_pad((string) (7 - $daysAgo), 3, '0', STR_PAD_LEFT)], [
                    'store_id' => $store->id, 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id, 'status' => SaleStatus::Paid,
                    'subtotal' => $total, 'item_discount_total' => 0, 'promotion_discount_total' => 0, 'transaction_discount_total' => 0, 'grand_total' => $total, 'completed_at' => $date,
                ]);
                $sale->items()->delete();
                $product = $products[$daysAgo % count($products)];
                $quantity = max(1, intdiv($total, $product->selling_price));
                $sale->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'unit_name' => $unit->name, 'quantity' => $quantity, 'unit_price' => $product->selling_price, 'discount_amount' => 0, 'line_total' => $total]);
                $payment = Payment::query()->updateOrCreate(['sale_id' => $sale->id], ['method' => $daysAgo % 2 ? PaymentMethod::Qris : PaymentMethod::Cash, 'amount_due' => $total, 'amount_received' => $total, 'change_amount' => 0, 'paid_at' => $date, 'received_by' => $cashier->id]);
                if ($payment->method->isCash()) {
                    CashMovement::withoutGlobalScopes()->firstOrCreate([
                        'shift_id' => $shift->id,
                        'type' => CashMovementType::Sale,
                        'reference_type' => 'payment',
                        'reference_id' => $payment->id,
                    ], [
                        'store_id' => $store->id,
                        'user_id' => $cashier->id,
                        'amount' => $payment->amount_due,
                        'note' => 'Seed '.$sale->invoice_number,
                    ]);
                }
            }
        });
    }

    private function user(string $name, string $email, UserRole $role, ?int $storeId): User
    {
        return User::query()->updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('password'), 'role' => $role, 'store_id' => $storeId, 'is_active' => true, 'email_verified_at' => now()]);
    }

    /** @param array<int, array{0: string, 1: int, 2: bool}> $values */
    private function optionGroup(Product $product, string $name, bool $required, array $values): void
    {
        $group = ProductOptionGroup::query()->updateOrCreate([
            'product_id' => $product->id,
            'name' => $name,
        ], [
            'is_required' => $required,
            'minimum_selections' => $required ? 1 : 0,
            'maximum_selections' => $name === 'Add-on' ? 3 : 1,
        ]);

        foreach ($values as $sortOrder => [$valueName, $priceAdjustment, $isDefault]) {
            $group->values()->updateOrCreate(['name' => $valueName], [
                'price_adjustment' => $priceAdjustment,
                'is_default' => $isDefault,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
