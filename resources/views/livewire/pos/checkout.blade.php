<div class="cashier-pos-workspace pos-screen pos-checkout-workspace nl-page" x-data="{ customerOpen: false, adjustmentsOpen: false, modifierOpen: false, memberOpen: false, lastTrigger: null, closePanel(name) { this[name] = false; this.$nextTick(() => this.lastTrigger?.focus()); }, rememberTrigger(element) { this.lastTrigger = element; } }" x-on:keydown.window="const isEditing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName) || event.target.isContentEditable; if (! isEditing && event.key === 'F2') { event.preventDefault(); $refs.productSearch.focus() } if (! isEditing && event.key === 'F8' && ! $refs.payButton.disabled) { event.preventDefault(); $refs.payButton.click() }" x-on:keydown.escape.window="if (modifierOpen) { modifierOpen = false; $wire.cancelProductOptions() } else if (memberOpen) { memberOpen = false; $wire.cancelNewMember() } else if (adjustmentsOpen) { closePanel('adjustmentsOpen') } else if (customerOpen) { closePanel('customerOpen') }" x-on:product-options-opened.window="modifierOpen = true; $nextTick(() => $refs.modifierDialog?.focus())" x-on:member-created.window="memberOpen = false; customerOpen = false">
        <header class="pos-workstation-header mx-auto w-full max-w-[1440px] rounded-xl border border-border bg-surface px-4 py-3 shadow-sm sm:px-5">
            <div class="pos-workstation-brand flex min-w-0 items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary text-sm font-black text-white">N</span>
                <div class="min-w-0"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-primary">NasLabs POS</p><h1 class="truncate text-base font-black tracking-tight text-foreground dark:text-white sm:text-lg">{{ auth()->user()?->store?->name ?? 'Store aktif' }}</h1><p class="mt-0.5 truncate text-xs text-muted">Cashier · Shift mulai <x-local-date-time :value="$this->activeShift?->opened_at" format="H:i" /></p></div>
            </div>
            <div class="hidden min-w-0 flex-1 justify-center px-6 md:flex"><div class="text-center"><p class="text-xs font-bold text-secondary dark:text-slate-200">Workspace Kasir</p><p class="mt-0.5 text-[11px] text-muted">F2 Cari <span class="px-1 text-border">•</span> F8 Bayar <span class="px-1 text-border">•</span> Esc Tutup dialog</p></div></div>
            <div class="pos-workstation-actions flex shrink-0 items-center gap-2"><span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/15 dark:text-emerald-300"><x-filament::icon icon="heroicon-o-check-circle" class="size-4" aria-hidden="true" /> Shift aktif</span><a class="nl-focus inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 transition hover:border-amber-300 hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200" href="{{ url('/cashier/close-shift') }}"><x-filament::icon icon="heroicon-o-lock-closed" class="size-4" aria-hidden="true" /> Tutup shift</a><form method="POST" action="{{ route('auth.logout') }}">@csrf<button aria-label="Keluar dari akun" class="nl-focus inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-2 text-xs font-bold text-secondary hover:border-primary hover:text-primary dark:border-white/15 dark:bg-white/5 dark:text-slate-200" type="submit"><x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="size-4" aria-hidden="true" /> Keluar</button></form></div>
        </header>
        <div class="pos-checkout-columns mx-auto grid w-full max-w-[1440px] gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(22rem,1fr)] lg:gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(23rem,1fr)] xl:gap-8">
        <section class="pos-catalog min-w-0">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-primary">TRANSAKSI BARU</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight">Pilih produk</h2>
                </div>
                <p class="rounded-md bg-primary-soft px-3 py-1.5 text-xs font-bold text-primary">{{ $this->products->count() }} produk tersedia</p>
            </div>

            <div class="pos-product-search flex min-h-12 items-center gap-3 rounded-lg border border-border bg-surface px-4 focus-within:border-primary focus-within:ring-4 focus-within:ring-primary/10 dark:border-white/15 dark:bg-white/5"><x-filament::icon icon="heroicon-o-magnifying-glass" class="size-4 shrink-0 text-muted" aria-hidden="true" /><input class="min-h-10 min-w-0 flex-1 rounded-lg border-0 bg-transparent px-0 text-sm text-foreground outline-none placeholder:text-muted focus:ring-0 dark:text-white" x-ref="productSearch" id="pos-product-search" wire:model.live.debounce.300ms="search" wire:keydown.enter.prevent="scanProduct" placeholder="Cari produk, SKU, atau scan barcode" type="search"></div>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted"><span>Enter untuk barcode</span><span><kbd>F2</kbd> cari produk</span><span><kbd>F8</kbd> bayar</span></div>
            <div wire:loading.flex wire:target="search" class="mt-2 items-center gap-2 text-xs font-semibold text-primary"><span class="size-2 animate-pulse rounded-full bg-primary"></span>Mencari produk…</div>

            <div class="mt-4 flex gap-2 overflow-x-auto pb-2">
                <button class="nl-focus min-h-11 shrink-0 rounded-md border px-4 py-2 text-sm font-semibold transition {{ $selectedCategoryId === null ? 'border-primary bg-primary text-white' : 'border-border bg-surface text-secondary hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}" type="button" wire:click="selectCategory" wire:loading.attr="disabled" wire:target="selectCategory">Semua</button>
                @foreach ($this->categories as $category)
                    <button class="nl-focus min-h-11 shrink-0 rounded-md border px-4 py-2 text-sm font-semibold transition {{ $selectedCategoryId === $category->id ? 'border-primary bg-primary text-white' : 'border-border bg-surface text-secondary hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}" type="button" wire:click="selectCategory({{ $category->id }})" wire:loading.attr="disabled" wire:target="selectCategory">{{ $category->name }}</button>
                @endforeach
            </div>
            <div wire:loading.flex wire:target="selectCategory" class="mt-1 items-center gap-2 text-xs font-semibold text-muted">Memuat kategori…</div>

            <div class="pos-product-grid mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->products as $product)
                    <x-pos.product-card :product="$product" :price="$this->displayPrice($product)" />
                @empty
                    <div class="col-span-full rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted dark:border-white/15">Produk tidak ditemukan. Coba kata kunci atau kategori lain.</div>
                @endforelse
            </div>
        </section>

        <x-filament::section class="pos-cart-panel lg:sticky lg:top-5">
            <x-slot name="heading"><span class="flex items-center justify-between"><span>PESANAN</span><span class="rounded-md bg-primary-soft px-2.5 py-1 text-xs font-bold text-primary">{{ count($cart) }} item</span></span></x-slot>

            <div class="mb-4">
                <div class="mb-2 flex items-center justify-between gap-3"><div class="flex min-w-0 items-center gap-2"><x-filament::icon icon="heroicon-o-user" class="size-4 shrink-0 text-muted" aria-hidden="true" /><div><p class="text-xs font-semibold uppercase tracking-wide text-muted">Pelanggan</p>@if ($selectedCustomer = $this->selectedCustomer)<p class="mt-0.5 truncate text-sm font-bold">{{ $selectedCustomer->name }}</p>@else<p class="mt-0.5 text-sm font-semibold text-secondary">Walk-in Customer</p>@endif</div></div><button x-ref="customerTrigger" class="nl-focus inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-xs font-bold text-primary hover:bg-primary-soft" type="button" x-on:click="rememberTrigger($event.currentTarget); customerOpen = true; $nextTick(() => $refs.customerSearch?.focus())">Ubah <x-filament::icon icon="heroicon-o-chevron-right" class="size-4 shrink-0" aria-hidden="true" /></button></div>
                <div x-show="customerOpen" x-cloak x-transition.opacity.duration.150ms class="pos-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4" :class="memberOpen ? 'bg-slate-950/50' : ''" x-on:click="closePanel('customerOpen')" role="presentation">
                    <section class="pos-modal-surface flex max-h-[70vh] w-full max-w-xl flex-col rounded-xl border border-border bg-surface shadow-xl dark:border-white/10 dark:bg-gray-900" x-on:click.stop role="dialog" aria-modal="true" aria-labelledby="customer-selector-title">
                        <div class="pos-modal-header flex items-start justify-between gap-4 border-b border-border p-5 dark:border-white/10"><div><p class="text-xs font-bold uppercase tracking-wide text-primary">PELANGGAN</p><h2 id="customer-selector-title" class="mt-1 text-xl font-black">Pilih pelanggan</h2><p class="mt-1 text-sm text-muted">Cari pelanggan atau scan kartu member</p></div><button class="pos-modal-close nl-focus grid min-h-10 min-w-10 place-items-center rounded-lg text-muted hover:bg-gray-100 dark:hover:bg-white/10" type="button" x-on:click="closePanel('customerOpen')" aria-label="Tutup pilih pelanggan"><x-filament::icon icon="heroicon-o-x-mark" class="size-5" aria-hidden="true" /></button></div>
                        <div class="pos-modal-body min-h-0 flex-1 overflow-y-auto p-5">
                            <div class="relative"><x-filament::icon icon="heroicon-o-magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" aria-hidden="true" /><x-filament::input x-ref="customerSearch" class="pl-9" wire:model.live.debounce.250ms="customerSearch" placeholder="Cari nama, telepon, atau member ID" type="search" /></div>
                            <div class="mt-3 flex items-center gap-2"><x-filament::input.wrapper class="min-w-0 flex-1"><x-filament::input x-ref="customerCardCode" wire:model="customerCardCode" wire:keydown.enter.prevent="scanCustomerCard" placeholder="Scan kartu member" /></x-filament::input.wrapper><button class="nl-focus inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-md border border-border px-3 text-xs font-bold text-secondary hover:border-primary hover:text-primary" type="button" wire:click="scanCustomerCard" x-on:click="$refs.customerCardCode?.focus()"><x-filament::icon icon="heroicon-o-qr-code" class="size-4" aria-hidden="true" /> Scan kartu</button></div>
                            <div class="mt-4 space-y-2">
                                <button class="nl-focus flex min-h-12 w-full items-center justify-between gap-3 rounded-md border border-border px-3 text-left hover:bg-primary-soft" type="button" wire:click="clearCustomer" x-on:click="closePanel('customerOpen')"><span class="flex min-w-0 items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary-soft text-primary"><x-filament::icon icon="heroicon-o-user" class="size-4" aria-hidden="true" /></span><span><span class="block text-sm font-bold">Walk-in Customer</span><span class="block text-xs text-muted">Tanpa pelanggan terdaftar</span></span></span><x-filament::icon icon="heroicon-o-chevron-right" class="size-4 shrink-0 text-muted" aria-hidden="true" /></button>
                                @if ($selectedCustomer = $this->selectedCustomer)
                                    <div class="flex items-center justify-between gap-3 rounded-md border border-primary-soft-strong bg-primary-soft px-3 py-2"><span class="flex min-w-0 items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-surface text-primary"><x-filament::icon icon="heroicon-o-user" class="size-4" aria-hidden="true" /></span><span class="min-w-0"><span class="block truncate text-sm font-bold">{{ $selectedCustomer->name }}</span><span class="block text-xs text-primary">{{ $selectedCustomer->code }} · {{ $selectedCustomer->points }} poin</span></span></span><span class="text-xs font-semibold text-primary">Terpilih</span></div>
                                @endif
                                @if ($this->customerMatches->isNotEmpty())
                                    @foreach ($this->customerMatches as $customer)
                                        <button class="nl-focus flex min-h-12 w-full items-center justify-between gap-3 rounded-md border border-border px-3 text-left hover:bg-primary-soft" type="button" wire:click="selectCustomer({{ $customer->id }})" x-on:click="customerOpen = false; $nextTick(() => $refs.customerTrigger?.focus())"><span class="flex min-w-0 items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-background text-muted"><x-filament::icon icon="heroicon-o-user" class="size-4" aria-hidden="true" /></span><span class="min-w-0"><span class="block truncate text-sm font-bold">{{ $customer->name }}</span><span class="block truncate text-xs text-muted">{{ $customer->code }} @if($customer->phone) · {{ $customer->phone }} @endif</span></span></span><x-filament::icon icon="heroicon-o-chevron-right" class="size-4 shrink-0 text-muted" aria-hidden="true" /></button>
                                    @endforeach
                                @elseif (mb_strlen($customerSearch) >= 2)
                                    <p class="px-1 py-4 text-center text-sm text-muted">Tidak ada pelanggan ditemukan.</p>
                                @endif
                            </div>
                        </div>
                        <div class="pos-modal-footer flex items-center justify-end border-t border-border p-4 dark:border-white/10"><button class="nl-focus inline-flex min-h-10 items-center gap-1.5 rounded-md px-3 text-xs font-bold text-primary hover:bg-primary-soft" type="button" wire:click="openNewMemberModal" x-on:click="memberOpen = true"><x-filament::icon icon="heroicon-o-user-plus" class="size-4" aria-hidden="true" /> Member baru</button></div>
                    </section>
                </div>
            </div>

            <div class="pos-cart-items max-h-[38vh] space-y-3 overflow-y-auto pe-1 lg:max-h-[calc(100dvh-22rem)]">
                @forelse ($cart as $index => $item)
                    <div class="rounded-lg border border-border p-3 dark:border-white/10">
                        <div class="min-w-0">
                            <div class="font-semibold text-foreground dark:text-white">{{ $item['name'] }}</div>
                            <div class="text-xs text-muted">Rp {{ number_format($item['price'] + $item['modifier_total'], 0, ',', '.') }} / unit</div>
                            @foreach ($item['modifiers'] as $modifier)
                                <div class="mt-1 text-xs text-primary">{{ $modifier['group'] }}: {{ $modifier['name'] }}{{ $modifier['price_adjustment'] ? ' (+Rp '.number_format($modifier['price_adjustment'], 0, ',', '.').')' : '' }}</div>
                            @endforeach
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <x-filament::button class="!min-h-9 !min-w-9 !p-0" color="gray" size="sm" type="button" wire:click="decreaseQuantity({{ $index }})" wire:loading.attr="disabled" wire:target="decreaseQuantity" aria-label="Kurangi jumlah"><x-filament::icon icon="heroicon-o-minus" class="size-4" aria-hidden="true" /></x-filament::button>
                                <span class="min-w-6 text-center">{{ $item['quantity'] }}</span>
                                <x-filament::button class="!min-h-9 !min-w-9 !p-0" color="gray" size="sm" type="button" wire:click="increaseQuantity({{ $index }})" wire:loading.attr="disabled" wire:target="increaseQuantity" aria-label="Tambah jumlah"><x-filament::icon icon="heroicon-o-plus" class="size-4" aria-hidden="true" /></x-filament::button>
                            </div>
                            <span class="font-semibold text-foreground dark:text-white">Rp {{ number_format((int) ($this->saleCalculation?->items[$index]['line_total'] ?? 0), 0, ',', '.') }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2"><x-filament::icon icon="heroicon-o-adjustments-horizontal" class="size-4 text-primary" aria-hidden="true" /><label class="text-xs font-semibold text-secondary" for="item-discount-{{ $index }}">Diskon item</label></div>
                            <x-filament::input.wrapper prefix="Rp" class="w-32">
                                <x-filament::input id="item-discount-{{ $index }}" min="0" type="number" wire:model.live="cart.{{ $index }}.discount" />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="mt-2 flex items-center gap-2 border-t border-border pt-2 dark:border-white/10"><button class="nl-focus inline-flex min-h-9 items-center gap-2 rounded-lg bg-primary-soft px-2.5 text-xs font-semibold text-primary hover:bg-primary-soft-strong" type="button" wire:click="editItemOptions({{ $index }})"><x-filament::icon icon="heroicon-o-adjustments-horizontal" class="size-4 shrink-0" aria-hidden="true" /> Ubah opsi</button><button class="nl-focus inline-flex min-h-9 items-center gap-2 rounded-lg px-2.5 text-xs font-semibold text-muted hover:bg-red-50 hover:text-danger-600" type="button" wire:click="removeItem({{ $index }})"><x-filament::icon icon="heroicon-o-trash" class="size-4 shrink-0" aria-hidden="true" /> Hapus</button></div>
                    </div>
                @empty
                    <div class="pos-empty-cart py-7 text-center"><span class="mx-auto grid size-10 place-items-center rounded-lg bg-primary-soft text-primary"><x-filament::icon icon="heroicon-o-shopping-cart" class="size-5" aria-hidden="true" /></span><p class="mt-3 text-sm font-bold text-secondary">Pesanan masih kosong</p><p class="mt-1 text-xs text-muted">Pilih produk untuk memulai pesanan.</p></div>
                @endforelse
            </div>

            <div class="mt-4 space-y-2 border-t border-border pt-4 text-sm dark:border-white/10">
                <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Diskon item</span><span>− Rp {{ number_format($this->itemDiscountTotal, 0, ',', '.') }}</span></div>
                <div>
                    <button x-ref="adjustmentsTrigger" class="nl-focus flex min-h-11 w-full items-center justify-between rounded-md px-2 text-left text-xs font-bold text-primary hover:bg-primary-soft" type="button" x-on:click="rememberTrigger($event.currentTarget); adjustmentsOpen = true; $nextTick(() => $refs.transactionDiscount?.focus())"><span class="inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-tag" class="size-4" aria-hidden="true" /> Diskon & promosi</span><span class="inline-flex items-center gap-1">Tambah <x-filament::icon icon="heroicon-o-chevron-right" class="size-4" aria-hidden="true" /></span></button>
                    <div x-show="adjustmentsOpen" x-cloak x-transition.opacity.duration.150ms class="pos-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4" x-on:click="closePanel('adjustmentsOpen')" role="presentation">
                        <section class="pos-modal-surface w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl dark:border-white/10 dark:bg-gray-900" x-on:click.stop role="dialog" aria-modal="true" aria-labelledby="adjustments-title">
                            <div class="pos-modal-header flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wide text-primary">PENYESUAIAN</p><h2 id="adjustments-title" class="mt-1 text-xl font-black">Diskon & promosi</h2><p class="mt-1 text-sm text-muted">Atur diskon transaksi atau gunakan kode promosi.</p></div><button class="pos-modal-close nl-focus grid min-h-10 min-w-10 place-items-center rounded-lg text-muted hover:bg-gray-100 dark:hover:bg-white/10" type="button" x-on:click="closePanel('adjustmentsOpen')" aria-label="Tutup diskon dan promosi"><x-filament::icon icon="heroicon-o-x-mark" class="size-4" aria-hidden="true" /></button></div>
                            <div class="pos-modal-body mt-6 space-y-4">
                                <div>
                                    <label class="mb-1 block text-xs text-muted" for="transaction-discount">Diskon transaksi</label>
                                    <x-filament::input.wrapper prefix="Rp">
                                        <x-filament::input x-ref="transactionDiscount" id="transaction-discount" min="0" type="number" wire:model.live="manualTransactionDiscount" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted" for="promotion-code">Kode promosi</label>
                                    <div class="flex gap-2">
                                        <x-filament::input.wrapper class="flex-1"><x-filament::input id="promotion-code" wire:model="promotionCode" /></x-filament::input.wrapper>
                                        <x-filament::button color="gray" type="button" wire:click="applyPromotion">Pakai</x-filament::button>
                                    </div>
                                    @if ($redeemCodeId)
                                        <div class="mt-2 flex items-center justify-between text-success-600">
                                            <span>Promo {{ strtoupper($promotionCode) }}: − Rp {{ number_format($this->promotionDiscount, 0, ',', '.') }}</span>
                                            <button class="nl-focus min-h-11 rounded-md px-2 text-xs underline" type="button" wire:click="removePromotion">Hapus</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="pos-modal-footer mt-6 flex items-center justify-between gap-3 border-t border-border pt-5 dark:border-white/10"><button class="nl-focus min-h-11 rounded-lg border border-border bg-surface px-4 py-2 font-semibold text-secondary hover:border-primary hover:text-primary" type="button" x-on:click="closePanel('adjustmentsOpen')">Batal</button><button class="nl-focus min-h-11 rounded-lg bg-primary px-4 py-2 font-bold text-white hover:bg-primary-hover" type="button" wire:click="applyPromotion" x-on:click="adjustmentsOpen = false">Terapkan</button></div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-border pt-4 text-base font-bold dark:border-white/10">
                <span>Total</span>
                <span class="text-lg">Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
            </div>

            @if (count($cart) > 0)
                <button class="nl-focus mt-4 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg text-center text-xs font-semibold text-secondary hover:bg-red-50 hover:text-danger-600" type="button" wire:click="clearCart" wire:confirm="Kosongkan semua item dari keranjang?"><x-filament::icon icon="heroicon-o-trash" class="size-4 shrink-0" aria-hidden="true" /> Kosongkan pesanan</button>
            @endif

            <x-filament::button x-ref="payButton" class="pos-pay-button mt-3 min-h-[52px] w-full rounded-lg bg-primary px-4 text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 dark:disabled:bg-white/10" size="lg" wire:click="createPendingSale" wire:loading.attr="disabled" wire:target="createPendingSale" :disabled="count($cart) === 0 || $this->saleCalculation === null">
                <span class="inline-flex items-center justify-center gap-2"><x-filament::icon icon="heroicon-o-credit-card" class="size-[18px] shrink-0" aria-hidden="true" /> Bayar · Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
            </x-filament::button>
            <div wire:loading.flex wire:target="createPendingSale,addProduct,scanProduct" class="mt-3 items-center justify-center gap-2 text-xs font-semibold text-primary"><span class="size-2 animate-pulse rounded-full bg-primary"></span>Memproses transaksi…</div>
        </x-filament::section>
        </div>

    @if ($productOptionsOpen && $this->productForOptions)
        <div x-show="modifierOpen" x-cloak x-on:click="modifierOpen = false; $wire.cancelProductOptions()" class="pos-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4" wire:key="product-options-dialog-{{ $productForOptionsId }}" role="presentation">
            <div x-ref="modifierDialog" tabindex="-1" x-init="$nextTick(() => $el.focus())" class="pos-modal-surface w-full max-w-xl rounded-xl border border-border bg-surface p-6 shadow-xl outline-none dark:border-white/10 dark:bg-gray-900" x-on:click.stop role="dialog" aria-modal="true" aria-labelledby="product-options-title">
                <form wire:submit="confirmProductOptions">
                <div class="pos-modal-header flex items-start justify-between gap-4">
                    <div><p class="text-sm font-medium text-primary">SESUAIKAN PESANAN</p><h2 id="product-options-title" class="mt-1 text-xl font-bold">{{ $this->productForOptions->name }}</h2></div>
                    <button class="nl-focus min-h-11 min-w-11 rounded-lg p-2 text-muted hover:bg-gray-100 dark:hover:bg-white/10" type="button" wire:click="cancelProductOptions" x-on:click="modifierOpen = false" aria-label="Tutup pilihan produk"><x-filament::icon icon="heroicon-o-x-mark" class="size-4" aria-hidden="true" /></button>
                </div>

                <div class="pos-modal-body mt-6 space-y-5" role="group" aria-label="Pilihan modifier">
                    @foreach ($this->productForOptions->optionGroups as $group)
                        @if ($group->values->where('is_active', true)->isNotEmpty())
                            <div class="space-y-2" role="group" aria-labelledby="modifier-group-{{ $group->id }}">
                                <div class="flex items-center justify-between gap-4"><span id="modifier-group-{{ $group->id }}" class="min-w-0 text-sm font-semibold text-foreground">{{ $group->name }}</span><span class="shrink-0 text-xs font-medium text-muted">{{ $group->is_required ? 'Wajib' : 'Opsional' }}</span></div>
                                <div class="space-y-2">
                                    @foreach ($group->values->where('is_active', true) as $value)
                                        <button wire:key="modifier-option-{{ $group->id }}-{{ $value->id }}" class="nl-focus flex min-h-12 w-full cursor-pointer items-center justify-between gap-4 rounded-lg border px-4 py-3 text-left transition {{ $this->isOptionSelected($group->id, $value->id) ? 'border-primary bg-primary-soft text-foreground' : 'border-border bg-surface hover:border-primary hover:bg-background' }}" type="button" role="{{ $group->maximum_selections === 1 ? 'radio' : 'checkbox' }}" aria-checked="{{ $this->isOptionSelected($group->id, $value->id) ? 'true' : 'false' }}" aria-label="{{ $value->name }}" wire:click="{{ $group->maximum_selections === 1 ? 'selectSingleOption('.$group->id.', '.$value->id.')' : 'toggleOption('.$group->id.', '.$value->id.')' }}"><span class="flex min-w-0 items-center gap-3"><span aria-hidden="true" class="grid size-5 shrink-0 place-items-center rounded-full border {{ $this->isOptionSelected($group->id, $value->id) ? 'border-primary bg-primary' : 'border-border bg-surface' }}">@if ($this->isOptionSelected($group->id, $value->id))<span class="size-2 rounded-full bg-white"></span>@endif</span><span class="min-w-0 break-words text-sm font-medium leading-5">{{ $value->name }}</span></span><span class="shrink-0 whitespace-nowrap text-sm font-medium text-muted">{{ $value->price_adjustment > 0 ? '+Rp '.number_format($value->price_adjustment, 0, ',', '.') : ($value->price_adjustment < 0 ? '-Rp '.number_format(abs($value->price_adjustment), 0, ',', '.') : 'Gratis') }}</span></button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="pos-modal-footer mt-6 flex gap-3 border-t border-border pt-5 dark:border-white/10"><button class="nl-focus min-h-11 flex-1 rounded-lg border border-border px-4 py-2 font-medium text-secondary hover:border-primary hover:text-primary" type="button" wire:click="cancelProductOptions" x-on:click="modifierOpen = false">Batal</button><button class="nl-focus min-h-11 flex-1 rounded-lg bg-primary px-4 py-2 font-medium text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50" type="submit" wire:loading.attr="disabled" wire:target="confirmProductOptions" @disabled(! $this->canConfirmProductOptions())><span wire:loading.remove wire:target="confirmProductOptions">{{ $editingCartIndex !== null ? 'Simpan perubahan' : 'Tambahkan ke keranjang' }}</span><span wire:loading wire:target="confirmProductOptions">Menyimpan…</span></button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($newMemberModalOpen)
        <div x-show="memberOpen" x-cloak x-on:click="memberOpen = false; $wire.cancelNewMember()" class="pos-modal-overlay fixed inset-0 z-[60] flex items-center justify-center p-4" wire:key="new-member-dialog" role="presentation">
            <div x-ref="memberDialog" tabindex="-1" x-init="$nextTick(() => $refs.memberName?.focus())" class="pos-modal-surface w-full max-w-xl rounded-xl border border-border bg-surface p-6 shadow-xl outline-none dark:border-white/10 dark:bg-gray-900" x-on:click.stop role="dialog" aria-modal="true" aria-labelledby="new-member-title">
                <form wire:submit="createMember">
                    <div class="pos-modal-header flex items-start justify-between gap-4"><div><p class="text-sm font-bold text-primary">MEMBER BARU</p><h2 id="new-member-title" class="mt-1 text-xl font-black">Daftarkan pelanggan</h2><p class="mt-1 text-sm text-muted">Kode member dibuat otomatis dan langsung dipilih.</p></div><button class="pos-modal-close nl-focus min-h-11 min-w-11 rounded-lg p-2 text-muted hover:bg-gray-100 dark:hover:bg-white/10" type="button" wire:click="cancelNewMember" x-on:click="memberOpen = false" aria-label="Tutup pendaftaran member"><x-filament::icon icon="heroicon-o-x-mark" class="size-4" aria-hidden="true" /></button></div>
                    <div class="pos-modal-body mt-6 space-y-4"><div><label class="mb-1 block text-sm font-semibold">Nama pelanggan</label><x-filament::input.wrapper><x-filament::input x-ref="memberName" wire:model="newMemberName" placeholder="Contoh: Andi Pratama" /></x-filament::input.wrapper>@error('newMemberName')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-semibold">Nomor WhatsApp</label><x-filament::input.wrapper><x-filament::input wire:model="newMemberPhone" placeholder="0812…" type="tel" /></x-filament::input.wrapper>@error('newMemberPhone')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-semibold">Email <span class="font-normal text-gray-500">(opsional)</span></label><x-filament::input.wrapper><x-filament::input wire:model="newMemberEmail" placeholder="nama@email.com" type="email" /></x-filament::input.wrapper>@error('newMemberEmail')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div></div>
                    <div class="pos-modal-footer mt-6 flex gap-3 border-t border-border pt-5 dark:border-white/10"><button class="nl-focus min-h-11 flex-1 rounded-lg border border-border px-4 py-2 font-semibold text-secondary hover:border-primary hover:text-primary" type="button" wire:click="cancelNewMember" x-on:click="memberOpen = false">Batal</button><button class="nl-focus min-h-11 flex-1 rounded-lg bg-primary px-4 py-2 font-bold text-white hover:bg-primary-hover" type="submit" wire:loading.attr="disabled" wire:target="createMember"><span wire:loading.remove wire:target="createMember">Buat & pilih member</span><span wire:loading wire:target="createMember">Menyimpan…</span></button></div>
                </form>
            </div>
        </div>
    @endif

</div>
