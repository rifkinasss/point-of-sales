# Current Architecture

Status: baseline setelah Phase 0 cleanup.

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

## Panels

- `/admin`: administrator platform.
- `/owner`: owner toko.
- `/manager`: manager toko.
- `/cashier`: workflow shift dan checkout kasir.

Panel discovery berada di `app/Providers/Filament/*PanelProvider.php`. Checkout produksi menggunakan `app/Filament/Cashier/Pages/PosCheckout.php` dan action sale; page demo sudah dihapus pada Phase 0.

## Business boundaries yang terdeteksi

 - Sale: `app/Actions/Sale/`.
 - Sale lifecycle: `SaleStatus` mengizinkan `pending_payment -> paid`, `pending_payment -> voided`, dan `paid -> refunded`; transition dijaga oleh `Sale::transitionTo()`.
 - Sale calculation: `app/Services/SaleCalculator.php` menghitung ulang item dari master product/modifier dan reward redeem code di server. `PosCheckout` hanya menampilkan preview.
 - Payment/inventory: `ProcessPaymentAction` mengunci sale dan shift, membuat satu payment, lalu mengurangi inventory setelah payment tervalidasi.
 - Void/refund: `VoidSaleAction` hanya menerima pending sale; `RefundSaleAction` hanya menerima paid sale dan membalik stock movement secara atomic.
 - Invoice: `InvoiceNumberGenerator` menghasilkan identifier publik `POS-YYYYMMDD-ULID`; primary key sale tetap terpisah dan invoice memiliki unique constraint.
- Inventory: `app/Actions/Inventory/`.
- Shift: `app/Actions/Shift/`.
- Persistence: `app/Models/` dan `database/migrations/`.
- UI: `app/Filament/` dan `resources/views/`.

Dokumen ini hanya mencatat struktur yang ditemukan; belum menetapkan arsitektur refactor final.

## Sale calculation baseline

Formula authoritative: gross subtotal - item discount total - promotion discount total - transaction discount total + tax total (saat ini 0) = grand total, minimum 0.

`SaleItem` menyimpan snapshot nama, SKU, unit, harga, modifier, diskon, kuantitas, dan line total sehingga perubahan master product tidak mengubah transaksi historis.

## Payment invariant

Satu sale memiliki maksimal satu payment melalui unique constraint `payments.sale_id`. Sale menjadi `paid` hanya setelah payment dan stock movement berhasil dalam transaction yang sama. Retry payment pada sale non-pending ditolak.
