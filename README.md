# POS Kasir

Aplikasi Laravel + Filament full-stack untuk POS/Kasir. Source Laravel berada langsung di root project; tidak ada subdirectory `backend/` atau frontend terpisah yang digunakan runtime.

## Panel

- `/admin` — khusus admin.
- `/owner` — khusus owner.
- `/manager` — khusus manager.
- `/cashier` — khusus kasir.

## Menjalankan Lokal

```bash
composer install
php artisan migrate
php artisan pos:create-admin
php artisan serve
```

Buka `http://127.0.0.1:8000/auth/login`, lalu login dengan akun yang dibuat oleh command tersebut.

## Baseline Saat Ini

- Laravel 13 dan Filament 5.
- SQLite untuk local development/MVP.
- Hierarki role `admin`, `owner`, `manager`, dan `cashier` dengan pembatasan panel.
- Product, category, unit, inventory balance, stock movement, purchasing, sales, payment, refund, dan shift tersedia dalam tingkat kesiapan yang berbeda.
- Checkout produksi menggunakan `app/Filament/Cashier/Pages/PosCheckout.php`; page kasir demo telah dihapus pada Phase 0.

## Dokumentasi Baseline

- [Current Architecture](docs/CURRENT-ARCHITECTURE.md)
- [Feature Map](docs/FEATURE-MAP.md)
- [Technical Debt](docs/TECHNICAL-DEBT.md)
