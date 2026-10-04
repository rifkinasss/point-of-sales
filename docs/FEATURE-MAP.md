# Feature Map

Status capability berdasarkan source aktual setelah presentation migration.

| Feature | Status | Evidence |
|---|---|---|
| Authentication | Available | `app/Http/Controllers/Auth/` |
| Role dan access | Available | `app/Enums/UserRole.php`, Livewire guards, Admin panel provider |
| Product, category, unit | Available | Product-related Filament resources dan models |
| Inventory balance dan stock movement | Available | `Inventory`, `StockMovement`, inventory actions |
| Cashier shift | Available | `App\Livewire\Cashier\OpenShift`, `CloseShift`, `CashierShift` |
| Checkout dan payment | Available | `App\Livewire\Cashier\PosCheckout`, `App\Livewire\Pos\Checkout`, sale actions |
| Refund penuh | Available | `RefundSaleAction`, Sales table action; hanya `paid -> refunded`, role admin/owner/manager |
| Void transaksi pending | Available | `VoidSaleAction`, Sales table action; hanya `pending_payment -> voided`, role admin/owner/manager |
| Cash register ledger | Available | `CashMovement`, `CashBalanceCalculator`, opening/payment/refund/cash in/cash out integration |
| Partial return | Missing | Tidak ditemukan action atau UI partial return |
| Split payment | Available | Sale 1:N Payment, `AddPaymentAction`, dan payment modal pada `Cashier\PosCheckout` mendukung alokasi bertahap |
| Manual payment methods | Available | Tunai, manual QRIS, dan manual bank transfer; semua memakai `AddPaymentAction` |
| Payment gateway integration | Deferred | Dynamic QRIS, virtual account, webhook, automatic settlement, dan E-Wallet integration belum diimplementasikan |
| Purchasing dan receiving | Partial | Purchase resources/actions tersedia; workflow item perlu validasi lebih lanjut |
| Sales report | Partial | Admin Filament report tersedia; manager/owner operational report modules belum lengkap |
| Cash movement ledger | Available | `CashMovement` dan shift reconciliation tersedia |
| Redeem Code | Keep | Dipakai checkout, payment, provider, dan migrations |
| Announcements | Needs verification | Provider, trait, config, view, dan migrations aktif |
| Account switching | Keep | Plugin dan linked-account migrations aktif pada panel |
| Subscription Manager | Needs verification | Page, models, plans, invoices, dan activation flow aktif |

## Sale lifecycle

```text
PENDING_PAYMENT -> PAID -> REFUNDED
        \-> VOIDED
```

Transition lain ditolak oleh `SaleStatus::canTransitionTo()` dan `Sale::transitionTo()`.

Payment tetap `pending` pada level sale sampai `sum(completed payments) == grand_total`; overpayment ditolak.

## Cash movement types

`opening`, `sale`, `refund`, `cash_in`, dan `cash_out` adalah tipe canonical. Cash movement non-cash payment tidak dibuat.

## Presentation boundary

| Role | UI | Entry point |
|---|---|---|
| Admin | Filament back-office | `/admin` |
| Cashier | Livewire operational workspace | `/cashier/*` |
| Manager | Livewire overview baseline | `/manager` |
| Owner | Livewire executive overview baseline | `/owner` |
