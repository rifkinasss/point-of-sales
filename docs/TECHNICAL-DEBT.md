# Technical Debt

## Closed in Phase 1

- Promotion discount is calculated server-side from redeem-code rewards and included once in `grand_total`.
- Recipe refunds invert the original sale stock movements instead of restocking the finished product.
- Refunds are idempotent at the paid/refunded sale boundary.
- Purchase receiving rejects empty or invalid items before mutation and remains atomic.
- Development seed inventory now creates an initial stock movement together with the balance.

## Remaining debt

- Repository root bukan Git worktree pada checkout ini, sehingga tidak ada branch, commit, atau diff baseline yang dapat diverifikasi.
- README sebelumnya menggambarkan layout `backend/`; source aktual berada langsung di root project.
- Generated Filament panel cache sebelumnya memuat path lama; cache sudah dibersihkan pada Phase 0 melalui `php artisan optimize:clear`.
- Business logic checkout masih besar di `PosCheckout` dan belum dipisahkan seluruhnya dari presentation state.
- Cash reconciliation ledger selesai pada Phase 3 melalui `CashMovement`; koreksi histori tetap harus berupa movement baru, bukan edit/delete.
- Payment sudah mendukung multi-payment per sale.
- Partial return belum tersedia; status void tersedia untuk pending sale.
- Partial refund tetap deferred; void sekarang tersedia hanya untuk sale pending.
- Report query dan authorization belum sepenuhnya sama dengan feature scope yang terdokumentasi.
- Subscription, announcements, dan account switching masih berada dalam aplikasi dan belum diambil keputusan produk untuk diarsipkan atau dikeluarkan.

## Phase 2 findings

- Tax belum menjadi field snapshot pada `sales`; calculator saat ini memakai `tax_total = 0`. Penambahan tax ditunda sampai aturan pajak dan schema disepakati.
- UI masih menghitung preview harga/promotion secara lokal untuk responsivitas, tetapi nilai authoritative dibuat ulang oleh `SaleCalculator` di server.
- Payment split sudah tersedia dengan row lock dan state validation; distributed idempotency dan payment gateway tetap deferred.
- Authorization refund/void ditegakkan di action dan juga disembunyikan pada Sales table; policy class khusus belum ada.
- Inventory saat ini single-store per product (`inventories.product_id` unique), bukan ledger balance per store. Cross-store dijaga melalui store pada product/sale/shift/customer.
- Payment status sudah mendukung `pending`, `completed`, `failed`, dan `refunded`; refund per payment tetap deferred karena refund masih full-sale.
- Cash in/out belum memiliki UI khusus; application actions tersedia dan UI minimal dapat ditambahkan tanpa mengubah POS layout.
- Refund cash pada shift yang sudah ditutup belum memiliki kebijakan operasional khusus; post-close refund governance tetap deferred.

Dokumen ini bukan desain target dan tidak mengubah business logic.
