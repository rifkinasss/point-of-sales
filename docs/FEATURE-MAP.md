# Feature Map

Status capability berdasarkan source saat Phase 0.

| Feature | Status | Evidence |
|---|---|---|
| Authentication | Available | `app/Http/Controllers/Auth/` |
| Role dan panel access | Available | `app/Enums/UserRole.php`, panel providers |
| Product, category, unit | Available | Product-related Filament resources dan models |
| Inventory balance dan stock movement | Available | `Inventory`, `StockMovement`, inventory actions |
| Cashier shift | Available | `OpenShift`, `CloseShift`, `CashierShift` |
| Checkout dan payment | Available | `PosCheckout`, `PaymentPage`, sale actions |
| Refund penuh | Available | `RefundSaleAction`, Sales table action; hanya `paid -> refunded`, role admin/owner/manager |
| Void transaksi pending | Available | `VoidSaleAction`, Sales table action; hanya `pending_payment -> voided`, role admin/owner/manager |
| Cash register ledger | Available | `CashMovement`, `CashBalanceCalculator`, opening/payment/refund/cash in/cash out integration |
| Partial return | Missing | Tidak ditemukan action atau UI partial return |
| Split payment | Missing | `payments.sale_id` unique; satu payment per sale |
| Purchasing dan receiving | Partial | Purchase resources/actions tersedia; workflow item perlu validasi lebih lanjut |
| Sales report | Partial | Report page tersedia, filter dan role coverage belum lengkap |
| Cash movement ledger | Missing | Tidak ada cash movement model/table/action |
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

## Cash movement types

`opening`, `sale`, `refund`, `cash_in`, dan `cash_out` adalah tipe canonical. Cash movement non-cash payment tidak dibuat.
