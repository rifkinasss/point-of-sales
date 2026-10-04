# UI Architecture

Status: Phase 5A — Core POS Workspace.

## Boundary

```text
Filament resources/pages
        ↓ back-office workflows
Livewire Pos\Checkout
        ↓ UI state and user intent
CreatePendingSaleAction / SaleCalculator
        ↓ authoritative application/domain rules
Eloquent models and store scope
```

Filament tetap dipakai untuk katalog, customer management, settings, reports, dan resource administration. Livewire hanya menangani workspace kasir: product search, category filter, barcode handoff, cart, modifier selection, customer/promotion selection, dan trigger checkout.

## POS workspace

- Component and checkout state/actions: `app/Livewire/Pos/Checkout.php`
- View: `resources/views/livewire/pos/checkout.blade.php`
- Product card: `resources/views/components/pos/product-card.blade.php`
- Existing route shell: `app/Filament/Cashier/Pages/PosCheckout.php`

Search uses database filtering for name, SKU, and barcode with a bounded result set. Barcode Enter submits directly to the lookup flow. Product cards show price, inventory availability, and modifier affordance without exposing cost or persistence fields.

## Authority rules

The Livewire component may hold temporary UI cart state, but it does not become authoritative for price, discount, promotion, inventory, payment, or sale status. Preview totals call `SaleCalculator`; checkout calls `CreatePendingSaleAction`, which recalculates and persists the pending sale in the application layer.

Phase 5A hands off at:

```text
POS Checkout → pending Sale → existing PaymentPage
```

Payment redesign, shift redesign, dashboard, refund redesign, and reporting redesign remain outside this phase.
