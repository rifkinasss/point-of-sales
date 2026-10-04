# Technical Debt

## Closed in Phase 1

- Promotion discount is calculated server-side from redeem-code rewards and included once in `grand_total`.
- Recipe refunds invert the original sale stock movements instead of restocking the finished product.
- Refunds are idempotent at the paid/refunded sale boundary.
- Purchase receiving rejects empty or invalid items before mutation and remains atomic.
- Development seed inventory now creates an initial stock movement together with the balance.

## Remaining debt

- README sebelumnya menggambarkan layout `backend/`; source aktual berada langsung di root project.
- Generated Filament panel cache sebelumnya memuat path lama; cache sudah dibersihkan pada Phase 0 melalui `php artisan optimize:clear`.
- Manager/owner workspace saat ini baru menyediakan read-only overview baseline; sales monitoring detail, shift review, inventory monitoring, reconciliation, dan reports belum dipisah menjadi modul Livewire penuh.
- Cash reconciliation ledger selesai pada Phase 3 melalui `CashMovement`; koreksi histori tetap harus berupa movement baru, bukan edit/delete.
- Payment sudah mendukung multi-payment per sale.
- Partial return belum tersedia; status void tersedia untuk pending sale.
- Partial refund tetap deferred; void sekarang tersedia hanya untuk sale pending.
- Report query dan authorization belum sepenuhnya sama dengan feature scope yang terdokumentasi.
- Subscription, announcements, dan account switching masih berada dalam aplikasi dan belum diambil keputusan produk untuk diarsipkan atau dikeluarkan.

## Phase 2 findings

- Tax belum menjadi field snapshot pada `sales`; calculator saat ini memakai `tax_total = 0`. Penambahan tax ditunda sampai aturan pajak dan schema disepakati.
- Product display price masih memakai lookup presentation untuk responsivitas, sedangkan subtotal, item discount, promotion discount, transaction discount, dan grand total preview kini memakai `SaleCalculator`; nilai final tetap dihitung ulang oleh action server-side.
- Payment split sudah tersedia dengan row lock dan state validation; distributed idempotency dan payment gateway tetap deferred.
- Payment portfolio scope saat ini: cash internal, manual QRIS, dan manual bank transfer. Dynamic QRIS, virtual account, payment gateway, provider webhook, automatic settlement verification, dan E-Wallet integration tetap `DEFERRED`.
- Authorization refund/void ditegakkan di action dan juga disembunyikan pada Sales table; policy class khusus belum ada.
- Inventory saat ini single-store per product (`inventories.product_id` unique), bukan ledger balance per store. Cross-store dijaga melalui store pada product/sale/shift/customer.
- Payment status sudah mendukung `pending`, `completed`, `failed`, dan `refunded`; refund per payment tetap deferred karena refund masih full-sale.
- Cashier payment, receipt, and close-shift presentation sudah dipindahkan ke Livewire; browser-level smoke coverage untuk seluruh flow masih perlu ditambahkan.
- Refund cash pada shift yang sudah ditutup belum memiliki kebijakan operasional khusus; post-close refund governance tetap deferred.
- Public identifier coverage is complete for current POS browser state (`Sale.public_id` plus ULIDs for product, category, customer, and modifier entities). `invoice_number` is a readable business reference only. Admin Filament and non-routed back-office foreign keys intentionally remain numeric; future public purchase, supplier, payment, shift, and cash-movement APIs need their own identifiers before being exposed.
- Branch master data is currently represented by optional `stores.branch_code`; a separate Branch aggregate and branch management workflow are deferred until the product needs multiple branches under one store tenant.

Dokumen ini bukan desain target dan tidak mengubah business logic.
