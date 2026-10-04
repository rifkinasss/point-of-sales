# UI Architecture

Status: role-based presentation boundary baseline.

## Boundary

```text
Admin Filament / Livewire role workspaces
        ↓ user intent
Actions / Services / Queries / Policies
        ↓ application and read-model boundaries
Eloquent models and store scope
```

Filament is restricted to admin back-office resources and pages. Livewire owns cashier operations plus manager and owner workspace entry points.

## POS workspace

- Component and checkout state/actions: `app/Livewire/Pos/Checkout.php`
- View: `resources/views/livewire/pos/checkout.blade.php`
- Product card: `resources/views/components/pos/product-card.blade.php`
- Cashier route entry points: `routes/web.php` (`/cashier/open-shift`, `/cashier/pos-checkout`, `/cashier/payment-page`, `/cashier/receipt-page`, `/cashier/close-shift`)
- Shared cashier layout: `resources/views/components/layouts/cashier.blade.php`
- Shared manager/owner layout: `resources/views/components/layouts/workspace.blade.php`

Search uses database filtering for name, SKU, and barcode with a bounded result set. Barcode Enter submits directly to the lookup flow. Product cards show price, inventory availability, and modifier affordance without exposing cost or persistence fields.

## Authority rules

The Livewire component may hold temporary UI cart state, but it does not become authoritative for price, discount, promotion, inventory, payment, or sale status. Preview totals call `SaleCalculator`; checkout calls `CreatePendingSaleAction`, which recalculates and persists the pending sale in the application layer.

Cashier flow:

```text
Open Shift → POS → Payment → Receipt → POS → Close Shift
```

Manager and owner currently expose read-only overview baselines through `ManagerDashboardQuery` and `OwnerDashboardQuery`. Detailed monitoring/report modules remain deferred until their query and authorization scope is specified.
