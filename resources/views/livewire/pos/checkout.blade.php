<div class="cashier-pos-workspace pos-screen pos-checkout-workspace nl-page" x-data x-on:keydown.window="const isEditing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName) || event.target.isContentEditable; if (! isEditing && event.key === 'F2') { event.preventDefault(); $refs.productSearch.focus() } if (! isEditing && event.key === 'F8' && ! $refs.payButton.disabled) { event.preventDefault(); $refs.payButton.click() }">
        <header class="pos-workstation-header mx-auto w-full max-w-[1440px]">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary text-sm font-black text-white">N</span>
                <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[0.12em] text-primary">NasLabs POS</p><h1 class="truncate text-lg font-black tracking-tight text-foreground dark:text-white">{{ auth()->user()?->store?->name ?? 'Store aktif' }} <span class="font-medium text-muted">· {{ auth()->user()?->name }}</span></h1><p class="mt-0.5 text-xs text-muted">Shift aktif sejak {{ $this->activeShift?->opened_at?->format('H:i') ?? '—' }}</p></div>
            </div>
            <div class="flex items-center gap-2"><span class="hidden rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 sm:inline-flex dark:bg-emerald-500/15 dark:text-emerald-300">Shift aktif</span><a class="nl-focus inline-flex items-center rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200" href="{{ url('/cashier/close-shift') }}">Tutup shift</a><form method="POST" action="{{ route('auth.logout') }}">@csrf<button class="nl-focus rounded-md border border-border bg-surface px-3 py-2 text-xs font-bold text-secondary hover:border-primary hover:text-primary dark:border-white/15 dark:bg-white/5 dark:text-slate-200" type="submit">Keluar</button></form></div>
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

            <div class="pos-product-search flex min-h-12 items-center gap-3 rounded-lg border border-border bg-surface px-4 focus-within:border-primary focus-within:ring-4 focus-within:ring-primary/10 dark:border-white/15 dark:bg-white/5"><x-filament::icon icon="heroicon-o-magnifying-glass" class="size-5 shrink-0 text-muted" /><input class="min-h-10 min-w-0 flex-1 rounded-lg border-0 bg-transparent px-0 text-sm text-foreground outline-none placeholder:text-muted focus:ring-0 dark:text-white" x-ref="productSearch" id="pos-product-search" wire:model.live.debounce.300ms="search" wire:keydown.enter.prevent="scanProduct" placeholder="Cari produk, SKU, atau scan barcode" type="search"></div>
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

            <div class="mb-4" x-data="{ customerOpen: false }">
                <div class="mb-2 flex items-center justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-muted">Pelanggan</p>@if ($selectedCustomer = $this->selectedCustomer)<p class="mt-0.5 truncate text-sm font-bold">{{ $selectedCustomer->name }}</p>@else<p class="mt-0.5 text-sm font-semibold text-secondary">Walk-in Customer</p>@endif</div><button class="nl-focus min-h-11 rounded-md px-3 text-xs font-bold text-primary hover:bg-primary-soft" type="button" x-on:click="customerOpen = ! customerOpen">Ubah</button></div>
                <div x-show="customerOpen" x-cloak x-transition.opacity.duration.150ms>
                <div class="mb-1 flex items-center justify-between gap-2"><label class="block text-xs text-muted">Cari pelanggan atau scan kartu</label><button class="nl-focus min-h-11 rounded-md px-2 text-xs font-bold text-primary hover:bg-primary-soft" type="button" wire:click="openNewMemberModal">+ Member baru</button></div>
                @if ($selectedCustomer = $this->selectedCustomer)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-primary-soft-strong bg-primary-soft p-3"><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $selectedCustomer->name }}</p><p class="text-xs text-primary">{{ $selectedCustomer->code }} · {{ $selectedCustomer->points }} poin</p></div><button class="nl-focus min-h-11 rounded-md px-2 text-xs font-bold text-danger-600 hover:bg-red-50" type="button" wire:click="clearCustomer">Ganti</button></div>
                @else
                    <div class="space-y-2"><x-filament::input.wrapper><x-filament::input x-ref="customerSearch" wire:model.live.debounce.250ms="customerSearch" placeholder="Cari nama, telepon, atau kode member" type="search" /></x-filament::input.wrapper><div class="flex gap-2"><x-filament::input.wrapper class="min-w-0 flex-1"><x-filament::input wire:model="customerCardCode" wire:keydown.enter.prevent="scanCustomerCard" placeholder="Scan kartu member" /></x-filament::input.wrapper><x-filament::button color="gray" icon="heroicon-o-qr-code" type="button" wire:click="scanCustomerCard">Scan</x-filament::button></div></div>
                    @if ($this->customerMatches->isNotEmpty())
                        <div class="mt-2 max-h-40 divide-y overflow-y-auto rounded-lg border border-border bg-surface">@foreach ($this->customerMatches as $customer)<button class="nl-focus flex min-h-11 w-full items-center justify-between gap-3 p-3 text-left hover:bg-primary-soft" type="button" wire:click="selectCustomer({{ $customer->id }})"><span><span class="block text-sm font-bold">{{ $customer->name }}</span><span class="block text-xs text-muted">{{ $customer->code }} @if($customer->phone) · {{ $customer->phone }} @endif</span></span><span class="text-xs font-bold text-amber-600">{{ $customer->points }} poin</span></button>@endforeach</div>
                    @elseif (mb_strlen($customerSearch) >= 2)
                        <p class="mt-2 text-xs text-muted">Member tidak ditemukan.</p>
                    @endif
                @endif
                </div>
            </div>

            <div class="pos-cart-items max-h-[38vh] space-y-3 overflow-y-auto pe-1 lg:max-h-[calc(100dvh-22rem)]">
                @forelse ($cart as $index => $item)
                    <div class="border-b border-gray-200 pb-3 dark:border-white/10">
                        <div class="flex justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ $item['name'] }}</div>
                                <div class="text-xs text-gray-500">Rp {{ number_format($item['price'] + $item['modifier_total'], 0, ',', '.') }}</div>
                                @foreach ($item['modifiers'] as $modifier)
                                    <div class="mt-1 text-xs text-primary">{{ $modifier['group'] }}: {{ $modifier['name'] }}{{ $modifier['price_adjustment'] ? ' (+Rp '.number_format($modifier['price_adjustment'], 0, ',', '.').')' : '' }}</div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3"><button class="nl-focus min-h-11 rounded-md px-2 text-sm text-primary hover:bg-primary-soft" type="button" wire:click="editItemOptions({{ $index }})">Ubah opsi</button><button class="nl-focus min-h-11 rounded-md px-2 text-sm text-danger-600 hover:bg-red-50" type="button" wire:click="removeItem({{ $index }})">Hapus</button></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-filament::button color="gray" size="sm" type="button" wire:click="decreaseQuantity({{ $index }})" wire:loading.attr="disabled" wire:target="decreaseQuantity">−</x-filament::button>
                                <span class="min-w-6 text-center">{{ $item['quantity'] }}</span>
                                <x-filament::button color="gray" size="sm" type="button" wire:click="increaseQuantity({{ $index }})" wire:loading.attr="disabled" wire:target="increaseQuantity">+</x-filament::button>
                            </div>
                            <span class="font-medium">Rp {{ number_format((int) ($this->saleCalculation?->items[$index]['line_total'] ?? 0), 0, ',', '.') }}</span>
                        </div>
                        <div class="mt-2">
                            <label class="text-xs text-gray-500" for="item-discount-{{ $index }}">Diskon item</label>
                            <x-filament::input.wrapper prefix="Rp" class="mt-1">
                                <x-filament::input id="item-discount-{{ $index }}" min="0" type="number" wire:model.live="cart.{{ $index }}.discount" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                @empty
                    <div class="pos-empty-cart py-7 text-center"><span class="mx-auto grid size-11 place-items-center rounded-lg bg-primary-soft text-primary"><x-filament::icon icon="heroicon-o-shopping-cart" class="size-5" /></span><p class="mt-3 text-sm font-bold text-secondary">Pesanan masih kosong</p><p class="mt-1 text-xs text-muted">Pilih produk untuk memulai pesanan.</p></div>
                @endforelse
            </div>

            <div class="mt-4 space-y-3 border-t border-border pt-4 text-sm dark:border-white/10">
                <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Diskon item</span><span>− Rp {{ number_format($this->itemDiscountTotal, 0, ',', '.') }}</span></div>
                <div x-data="{ adjustmentsOpen: false }">
                    <button class="nl-focus flex min-h-11 w-full items-center justify-between rounded-md px-2 text-left text-xs font-bold text-primary hover:bg-primary-soft" type="button" x-on:click="adjustmentsOpen = ! adjustmentsOpen"><span>Diskon & promosi</span><span x-text="adjustmentsOpen ? 'Tutup' : 'Tambah'"></span></button>
                    <div class="mt-2 space-y-3" x-show="adjustmentsOpen" x-cloak x-transition.opacity.duration.150ms>
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
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-border pt-4 text-lg font-black dark:border-white/10">
                <span>Total</span>
                <span>Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
            </div>

            @if (count($cart) > 0)
                <button class="nl-focus mt-3 min-h-11 w-full rounded-md text-center text-xs font-bold text-muted hover:bg-red-50 hover:text-danger-600" type="button" wire:click="clearCart" wire:confirm="Kosongkan semua item dari keranjang?">Kosongkan pesanan</button>
            @endif

            <x-filament::button x-ref="payButton" class="pos-pay-button mt-4 min-h-12 w-full" size="lg" wire:click="createPendingSale" wire:loading.attr="disabled" wire:target="createPendingSale" :disabled="count($cart) === 0 || $this->saleCalculation === null">
                <x-filament::icon icon="heroicon-o-credit-card" class="mr-2 size-5" /> Bayar · Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
            </x-filament::button>
            <div wire:loading.flex wire:target="createPendingSale,addProduct,scanProduct" class="mt-3 items-center justify-center gap-2 text-xs font-semibold text-primary"><span class="size-2 animate-pulse rounded-full bg-primary"></span>Memproses transaksi…</div>
        </x-filament::section>
        </div>
    </div>

    @if ($productOptionsOpen && $this->productForOptions)
        <div x-data="{ open: true }" x-on:keydown.escape.window="open = false; $wire.cancelProductOptions()" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:key="product-options-dialog-{{ $productForOptionsId }}">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                <form wire:submit="confirmProductOptions" x-on:submit="open = false">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-sm font-medium text-primary">SESUAIKAN PESANAN</p><h2 class="mt-1 text-xl font-bold">{{ $this->productForOptions->name }}</h2></div>
                    <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10" type="button" wire:click="cancelProductOptions" x-on:click="open = false"><x-filament::icon icon="heroicon-o-x-mark" class="size-5" /></button>
                </div>

                <div class="mt-6 space-y-5">
                    @foreach ($this->productForOptions->optionGroups as $group)
                        @if ($group->values->where('is_active', true)->isNotEmpty())
                            <div>
                                <div class="mb-2 flex items-center justify-between"><span class="font-semibold">{{ $group->name }}</span><span class="text-xs text-gray-500">{{ $group->is_required ? 'Wajib' : 'Opsional' }}</span></div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($group->values->where('is_active', true) as $value)
                                        <button class="nl-focus flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-lg border p-3 text-left {{ $this->isOptionSelected($group->id, $value->id) ? 'border-primary bg-primary-soft' : 'border-border hover:border-primary' }}" type="button" wire:click="{{ $group->maximum_selections === 1 ? 'selectSingleOption('.$group->id.', '.$value->id.')' : 'toggleOption('.$group->id.', '.$value->id.')' }}"><span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full border {{ $this->isOptionSelected($group->id, $value->id) ? 'border-primary bg-primary text-white' : 'border-border' }}">{{ $this->isOptionSelected($group->id, $value->id) ? '✓' : '' }}</span><span>{{ $value->name }}</span></span><span class="text-sm text-muted">{{ $value->price_adjustment > 0 ? '+Rp '.number_format($value->price_adjustment, 0, ',', '.') : 'Gratis' }}</span></button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-6 flex gap-3"><button class="nl-focus min-h-11 flex-1 rounded-lg border border-border px-4 py-2 font-medium" type="button" wire:click="cancelProductOptions" x-on:click="open = false">Batal</button><button class="nl-focus min-h-11 flex-1 rounded-lg bg-primary px-4 py-2 font-medium text-white hover:bg-primary-hover" type="submit" wire:loading.attr="disabled" wire:target="confirmProductOptions"><span wire:loading.remove wire:target="confirmProductOptions">{{ $editingCartIndex !== null ? 'Simpan perubahan' : 'Tambahkan ke keranjang' }}</span><span wire:loading wire:target="confirmProductOptions">Menyimpan…</span></button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($newMemberModalOpen)
        <div x-data="{ open: true }" x-on:keydown.escape.window="open = false; $wire.cancelNewMember()" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:key="new-member-dialog">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                <form wire:submit="createMember" x-on:submit="open = false">
                    <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-bold text-primary">MEMBER BARU</p><h2 class="mt-1 text-xl font-black">Daftarkan pelanggan</h2><p class="mt-1 text-sm text-muted">Kode member dibuat otomatis dan langsung dipilih.</p></div><button class="nl-focus min-h-11 min-w-11 rounded-lg p-2 text-muted hover:bg-gray-100 dark:hover:bg-white/10" type="button" wire:click="cancelNewMember" x-on:click="open = false"><x-filament::icon icon="heroicon-o-x-mark" class="size-5" /></button></div>
                    <div class="mt-6 space-y-4"><div><label class="mb-1 block text-sm font-semibold">Nama pelanggan</label><x-filament::input.wrapper><x-filament::input wire:model="newMemberName" autofocus placeholder="Contoh: Andi Pratama" /></x-filament::input.wrapper>@error('newMemberName')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-semibold">Nomor WhatsApp</label><x-filament::input.wrapper><x-filament::input wire:model="newMemberPhone" placeholder="0812…" type="tel" /></x-filament::input.wrapper>@error('newMemberPhone')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-semibold">Email <span class="font-normal text-gray-500">(opsional)</span></label><x-filament::input.wrapper><x-filament::input wire:model="newMemberEmail" placeholder="nama@email.com" type="email" /></x-filament::input.wrapper>@error('newMemberEmail')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div></div>
                    <div class="mt-6 flex gap-3"><button class="nl-focus min-h-11 flex-1 rounded-lg border border-border px-4 py-2 font-semibold" type="button" wire:click="cancelNewMember" x-on:click="open = false">Batal</button><button class="nl-focus min-h-11 flex-1 rounded-lg bg-primary px-4 py-2 font-bold text-white hover:bg-primary-hover" type="submit" wire:loading.attr="disabled" wire:target="createMember"><span wire:loading.remove wire:target="createMember">Buat & pilih member</span><span wire:loading wire:target="createMember">Menyimpan…</span></button></div>
                </form>
            </div>
        </div>
    @endif
