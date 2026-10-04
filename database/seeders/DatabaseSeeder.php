<?php

namespace Database\Seeders;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Category;
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
                'store_code' => 'NAS', 'branch_code' => null, 'invoice_prefix' => 'NLM', 'tax_percentage' => 11, 'receipt_footer' => 'Terima kasih sudah berbelanja di NasLabs Market.',
                'receipt_show_cashier' => true, 'receipt_show_tax' => true,
            ]);
            Store::query()->updateOrCreate(['slug' => 'borneo-kiosk'], [
                'name' => 'Borneo Kiosk', 'phone' => '0852-1111-2233', 'email' => 'halo@borneokiosk.id',
                'address' => 'Jl. Pahlawan No. 12, Balikpapan', 'currency' => 'IDR', 'timezone' => 'Asia/Makassar',
                'store_code' => 'BRN', 'branch_code' => null, 'invoice_prefix' => 'BRN', 'tax_percentage' => 0, 'receipt_footer' => 'Sampai jumpa kembali.',
                'receipt_show_cashier' => true, 'receipt_show_tax' => false,
            ]);

            $owner = $this->user('Nadia Owner', 'owner@naslabs.my.id', UserRole::Owner, $store->id);
            $manager = $this->user('Raka Manager', 'manager@naslabs.my.id', UserRole::Manager, $store->id);
            $cashier = $this->user('Sinta Kasir', 'cashier@naslabs.my.id', UserRole::Cashier, $store->id);
            $this->user('Administrator NasLabs', 'admin@naslabs.my.id', UserRole::Admin, null);

            $categories = [];
            foreach (['Makanan', 'Minuman', 'Kopi', 'Snack', 'Dessert'] as $categoryName) {
                $categories[$categoryName] = Category::withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->id, 'name' => $categoryName],
                    ['is_active' => true],
                );
            }
            $unit = Unit::withoutGlobalScopes()->updateOrCreate(['name' => 'Pcs'], ['store_id' => $store->id, 'symbol' => 'pcs', 'is_active' => true]);

            $sugar = [
                ['Normal sugar', 0, true], ['Less sugar', 0, false], ['No sugar', 0, false],
            ];
            $ice = [
                ['Ice normal', 0, true], ['Less ice', 0, false], ['No ice', 0, false],
            ];
            $serving = [
                ['Hot', 0, true], ['Ice', 0, false],
            ];
            $sauces = [
                ['Spicy sauce', 0, true], ['Cheese sauce', 3000, false],
            ];
            $catalog = [
                ['Makanan', 'Nasi Goreng Spesial', 'FOD-001', 25000, 45, []],
                ['Makanan', 'Nasi Goreng Ayam', 'FOD-002', 23000, 35, []],
                ['Makanan', 'Nasi Ayam Geprek', 'FOD-003', 24000, 30, []],
                ['Makanan', 'Nasi Ayam Teriyaki', 'FOD-004', 27000, 24, []],
                ['Makanan', 'Mie Goreng', 'FOD-005', 18000, 38, []],
                ['Makanan', 'Mie Kuah', 'FOD-006', 18000, 28, []],
                ['Makanan', 'Rice Bowl Chicken', 'FOD-007', 28000, 22, []],
                ['Makanan', 'Sandwich Ayam', 'FOD-008', 22000, 20, []],
                ['Minuman', 'Es Teh Manis', 'DRK-001', 7000, 80, [['Level gula', true, $sugar], ['Es', true, $ice]]],
                ['Minuman', 'Lemon Tea', 'DRK-002', 12000, 55, [['Level gula', true, $sugar], ['Es', true, $ice]]],
                ['Minuman', 'Thai Tea', 'DRK-003', 15000, 42, [['Level gula', true, $sugar], ['Es', true, $ice]]],
                ['Minuman', 'Matcha Latte', 'DRK-004', 20000, 30, [['Level gula', true, $sugar], ['Es', true, $ice]]],
                ['Minuman', 'Chocolate Milk', 'DRK-005', 18000, 33, [['Level gula', true, $sugar], ['Es', true, $ice]]],
                ['Minuman', 'Air Mineral', 'DRK-006', 5000, 100, []],
                ['Minuman', 'Soda Lemon', 'DRK-007', 12000, 40, []],
                ['Minuman', 'Jus Jeruk', 'DRK-008', 15000, 32, []],
                ['Kopi', 'Americano', 'COF-001', 18000, 40, [['Penyajian', true, $serving], ['Level gula', true, $sugar]]],
                ['Kopi', 'Cafe Latte', 'COF-002', 22000, 34, [['Penyajian', true, $serving], ['Level gula', true, $sugar]]],
                ['Kopi', 'Cappuccino', 'COF-003', 22000, 28, [['Penyajian', true, $serving], ['Level gula', true, $sugar]]],
                ['Kopi', 'Kopi Susu Gula Aren', 'COF-004', 20000, 26, [['Level gula', true, $sugar], ['Es', true, $ice]]],
                ['Kopi', 'Caramel Latte', 'COF-005', 25000, 24, [['Penyajian', true, $serving], ['Level gula', true, $sugar]]],
                ['Kopi', 'Vanilla Latte', 'COF-006', 24000, 25, [['Penyajian', true, $serving], ['Level gula', true, $sugar]]],
                ['Snack', 'Roti Cokelat', 'SNK-001', 10000, 32, []],
                ['Snack', 'Roti Keju', 'SNK-002', 11000, 29, []],
                ['Snack', 'French Fries', 'SNK-003', 15000, 40, [['Saus', false, $sauces]]],
                ['Snack', 'Onion Rings', 'SNK-004', 16000, 25, []],
                ['Snack', 'Chicken Nugget', 'SNK-005', 18000, 30, [['Saus', false, $sauces]]],
                ['Snack', 'Pisang Cokelat', 'SNK-006', 14000, 35, []],
                ['Snack', 'Cireng', 'SNK-007', 12000, 32, []],
                ['Snack', 'Tahu Crispy', 'SNK-008', 13000, 30, []],
                ['Dessert', 'Brownies', 'DST-001', 15000, 22, []],
                ['Dessert', 'Cheesecake Slice', 'DST-002', 22000, 16, []],
                ['Dessert', 'Pudding Cokelat', 'DST-003', 12000, 24, []],
                ['Dessert', 'Ice Cream Vanilla', 'DST-004', 13000, 28, []],
                ['Dessert', 'Ice Cream Chocolate', 'DST-005', 13000, 27, []],
                ['Dessert', 'Croffle', 'DST-006', 18000, 20, [['Saus', false, [['Chocolate sauce', 3000, false], ['Caramel sauce', 3000, false]]]]],
            ];
            $products = [];
            foreach ($catalog as $index => [$categoryName, $name, $sku, $price, $stock, $modifierGroups]) {
                $cost = (int) round($price * 0.6);
                $barcode = '899100'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
                $product = Product::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'name' => $name], [
                    'category_id' => $categories[$categoryName]->id, 'unit_id' => $unit->id, 'sku' => $sku, 'barcode' => $barcode,
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

                $groupNames = array_column($modifierGroups, 0);
                $groupsQuery = ProductOptionGroup::query()->where('product_id', $product->id);
                $groupNames === [] ? $groupsQuery->delete() : $groupsQuery->whereNotIn('name', $groupNames)->delete();
                foreach ($modifierGroups as [$groupName, $required, $values]) {
                    $this->optionGroup($product, $groupName, $required, $values);
                }
                $products[] = $product;
            }

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
                $date = now()->subDays($daysAgo)->setTimezone($store->timezone)->setTime(10, 15);
                $businessDate = $date->toDateString();
                $sequence = 7 - $daysAgo;
                $invoiceNumber = $store->store_code.'-'.$date->format('Ymd').'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
                $sale = Sale::withoutGlobalScopes()->updateOrCreate(['invoice_number' => $invoiceNumber], [
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

                DB::table('invoice_sequences')->updateOrInsert([
                    'store_id' => $store->id,
                    'branch_code' => $store->branch_code,
                    'business_date' => $businessDate,
                ], [
                    'scope_key' => $store->id.'|'.($store->branch_code ?: '-').'|'.$businessDate,
                    'last_sequence' => $sequence,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        });

        $this->call([
            DevelopmentUserSeeder::class,
        ]);
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
