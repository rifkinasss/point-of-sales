# Current Architecture

Status: presentation boundary after cashier and management workspace migration.

## Runtime layout

Project ini adalah satu aplikasi Laravel + Filament. Source Laravel berada langsung di root project; tidak ada subdirectory `backend/` atau frontend terpisah yang digunakan runtime.

```text
Filament panels/pages/resources
        ↓
Actions dan middleware
        ↓
Eloquent models + store scope
        ↓
SQLite / database driver sesuai environment
```

## Runtime entry points

- `/admin`: Filament administrator back-office.
- `/owner`: Livewire owner overview.
- `/manager`: Livewire manager operational overview.
- `/cashier/*`: Livewire cashier workflow.

Only `AdminPanelProvider` remains registered. Cashier, manager, and owner entry points use Laravel routes and Livewire components.

## Business boundaries yang terdeteksi

 - Sale: `app/Actions/Sale/`.
 - Sale lifecycle: `SaleStatus` mengizinkan `pending_payment -> paid`, `pending_payment -> voided`, dan `paid -> refunded`; transition dijaga oleh `Sale::transitionTo()`.
 - Sale calculation: `app/Services/SaleCalculator.php` menghitung ulang item dari master product/modifier dan reward redeem code di server. `PosCheckout` hanya menampilkan preview.
- Payment/inventory: `AddPaymentAction` mengunci sale dan shift, mencatat satu allocation payment, dan memanggil `CompleteSalePaymentAction` hanya saat total payment terpenuhi sebelum inventory dikurangi.
- Cash register: `CashMovement` adalah append-only application ledger untuk opening, cash sale, refund cash, cash in, dan cash out. `CashBalanceCalculator` menjadi sumber expected cash.
- Payment: `Sale 1:N Payment`. `PaymentAllocator` menghitung completed payment, total paid, remaining due, dan breakdown method. Sale baru menjadi `paid` setelah completed payments tepat memenuhi grand total.
 - Void/refund: `VoidSaleAction` hanya menerima pending sale; `RefundSaleAction` hanya menerima paid sale dan membalik stock movement secara atomic.
 - Invoice: `InvoiceNumberGenerator` menghasilkan identifier publik `POS-YYYYMMDD-ULID`; primary key sale tetap terpisah dan invoice memiliki unique constraint.
- Inventory: `app/Actions/Inventory/`.
- Shift: `app/Actions/Shift/`.
- Persistence: `app/Models/` dan `database/migrations/`.
- UI back-office: `app/Filament/` and `resources/views/filament/` through the admin panel.
- Cashier UI: `app/Livewire/Cashier/`, `app/Livewire/Pos/Checkout.php`, and `resources/views/livewire/`.
- Manager/owner read-only UI: `app/Livewire/Manager/`, `app/Livewire/Owner/`, and `app/Queries/`.

Dokumen ini hanya mencatat struktur yang ditemukan; belum menetapkan arsitektur refactor final.

## Sale calculation baseline

Formula authoritative: gross subtotal - item discount total - promotion discount total - transaction discount total + tax total (saat ini 0) = grand total, minimum 0.

`SaleItem` menyimpan snapshot nama, SKU, unit, harga, modifier, diskon, kuantitas, dan line total sehingga perubahan master product tidak mengubah transaksi historis.

## UI boundary

```text
Admin   -> Filament
Cashier -> Livewire
Manager -> Livewire
Owner   -> Livewire
```

Livewire components call shared Actions, Services, and Queries. Canonical sale, payment, inventory, and cash-ledger rules remain outside the UI layer.

## Identifier boundary

Numeric primary keys and foreign keys are internal implementation details. Database relationships such as `sales.customer_id`, `payments.sale_id`, and `sale_items.product_id` remain numeric and are not exposed as public identifiers.

POS browser/Livewire surfaces use non-sequential identifiers:

| Entity | Public identifier | Scope |
| --- | --- | --- |
| Sale / receipt | `invoice_number` (`POS-YYYYMMDD-ULID`) | Receipt route and payment query |
| Product | `public_id` ULID | Product cards and cart state |
| Category | `public_id` ULID | Category filter state |
| Customer | `public_id` ULID | Customer selector and cart state |
| Modifier group/value | `public_id` ULID | Modifier selection state |
| Redeem code | immutable promotion code | Promotion input; no internal redeem-code ID in Livewire state |

All public identifier lookups still enforce authentication, cashier ownership, and current-store scope. ULID/unpredictability reduces enumeration risk but is not an authorization mechanism. Filament admin internals may continue to use numeric record IDs where the framework requires them.

## Payment invariant

Satu sale dapat memiliki beberapa payment completed melalui `payments.sale_id`; totalnya tidak boleh melebihi grand total. Sale menjadi `paid` hanya setelah total allocation dan stock movement berhasil dalam transaction yang sama. Retry payment pada sale non-pending ditolak.

## Cash reconciliation

Formula authoritative:

```text
expected cash = opening + cash sales + cash in - cash refunds - cash out
```

Closing menyimpan `expected_cash`, `closing_cash_actual`, dan `cash_difference` pada shift. `CLOSING` bukan tipe movement; snapshot close tetap terpisah dari ledger.
