<x-filament-panels::page>
    <div class="pos-screen pos-checkout-workspace" x-data x-on:keydown.window="if (event.key === 'F2') { event.preventDefault(); $refs.productSearch.focus() } if (event.key === 'F4') { event.preventDefault(); $refs.customerSearch.focus() } if (event.key === 'F6') { event.preventDefault(); $refs.transactionDiscount.focus() } if (event.key === 'F8') { event.preventDefault(); $refs.payButton.click() }">
        <header class="pos-workstation-header">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/25"><x-filament::icon icon="heroicon-o-shopping-bag" class="size-6" /></span>
                <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">POS · Shift aktif</p><h1 class="truncate text-xl font-black tracking-tight text-gray-950 dark:text-white">Kasir <span class="font-medium text-gray-400">— {{ auth()->user()?->name }}</span></h1></div>
            </div>
            <div class="flex items-center gap-2"><span class="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 sm:inline-flex dark:bg-emerald-500/15 dark:text-emerald-300"><span class="mr-1.5 size-2 rounded-full bg-emerald-500"></span>Kasir siap</span><a class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200" href="{{ url('/cashier/close-shift') }}"><x-filament::icon icon="heroicon-o-lock-closed" class="size-4" /><span class="hidden sm:inline">Tutup shift</span></a></div>
        </header>
        <div class="pos-checkout-columns grid gap-6 xl:grid-cols-[minmax(0,1fr)_29rem]">
        <section class="pos-catalog min-w-0">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-indigo-600 dark:text-indigo-300">TRANSAKSI BARU</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight">Pilih produk</h2>
                </div>
                <p class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500 dark:bg-white/10 dark:text-gray-300">{{ $this->products->count() }} produk tersedia</p>
            </div>

            <x-filament::input.wrapper class="pos-product-search">
                <x-filament::input
                    x-ref="productSearch"
                    id="pos-product-search"
                    wire:model.live.debounce.300ms="search"
                    wire:keydown.enter.prevent="scanProduct"
                    placeholder="Cari atau scan barcode produk"
                    type="search"
                />
            </x-filament::input.wrapper>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500"><span>Enter untuk barcode</span><span><kbd>F2</kbd> cari produk</span><span><kbd>F4</kbd> pelanggan</span><span><kbd>F8</kbd> bayar</span></div>

            <div class="mt-4 flex gap-2 overflow-x-auto pb-2">
                <button class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $selectedCategoryId === null ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:text-indigo-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}" type="button" wire:click="selectCategory">Semua</button>
                @foreach ($this->categories as $category)
                    <button class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $selectedCategoryId === $category->id ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:text-indigo-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300' }}" type="button" wire:click="selectCategory({{ $category->id }})">{{ $category->name }}</button>
                @endforeach
            </div>

            <div class="pos-product-grid mt-5 grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                @forelse ($this->products as $product)
                    <x-pos.product-card :product="$product" :price="$this->displayPrice($product)" />
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-white/15">Produk tidak ditemukan. Coba kata kunci atau kategori lain.</div>
                @endforelse
            </div>
        </section>

        <x-filament::section class="pos-cart-panel xl:sticky xl:top-5">
            <x-slot name="heading"><span class="flex items-center justify-between"><span>Keranjang</span><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ count($cart) }} item</span></span></x-slot>

            <div class="mb-4">
                <div class="mb-1 flex items-center justify-between gap-2"><label class="block text-xs text-gray-500">Pelanggan / member (opsional)</label><button class="text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-300" type="button" wire:click="openNewMemberModal">+ Member baru</button></div>
                @if ($selectedCustomer = $this->selectedCustomer)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50 p-3 dark:border-indigo-400/20 dark:bg-indigo-500/10"><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $selectedCustomer->name }}</p><p class="text-xs text-indigo-700 dark:text-indigo-300">{{ $selectedCustomer->code }} · {{ $selectedCustomer->points }} poin</p></div><button class="text-xs font-bold text-danger-600 hover:underline" type="button" wire:click="clearCustomer">Ganti</button></div>
                @else
                    <div class="space-y-2"><x-filament::input.wrapper><x-filament::input x-ref="customerSearch" wire:model.live.debounce.250ms="customerSearch" placeholder="Cari nama, telepon, atau kode member" type="search" /></x-filament::input.wrapper><div class="flex gap-2"><x-filament::input.wrapper class="min-w-0 flex-1"><x-filament::input wire:model="customerCardCode" wire:keydown.enter.prevent="scanCustomerCard" placeholder="Scan kartu member" /></x-filament::input.wrapper><x-filament::button color="gray" icon="heroicon-o-qr-code" type="button" wire:click="scanCustomerCard">Scan</x-filament::button></div></div>
                    @if ($this->customerMatches->isNotEmpty())
                        <div class="mt-2 max-h-40 divide-y overflow-y-auto rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">@foreach ($this->customerMatches as $customer)<button class="flex w-full items-center justify-between gap-3 p-3 text-left hover:bg-indigo-50 dark:hover:bg-indigo-500/10" type="button" wire:click="selectCustomer({{ $customer->id }})"><span><span class="block text-sm font-bold">{{ $customer->name }}</span><span class="block text-xs text-gray-500">{{ $customer->code }} @if($customer->phone) · {{ $customer->phone }} @endif</span></span><span class="text-xs font-bold text-amber-600">{{ $customer->points }} poin</span></button>@endforeach</div>
                    @elseif (mb_strlen($customerSearch) >= 2)
                        <p class="mt-2 text-xs text-gray-500">Member tidak ditemukan.</p>
                    @endif
                @endif
            </div>

            <div class="max-h-[34vh] space-y-3 overflow-y-auto pe-1 xl:max-h-[36vh]">
                @forelse ($cart as $index => $item)
                    <div class="border-b border-gray-200 pb-3 dark:border-white/10">
                        <div class="flex justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ $item['name'] }}</div>
                                <div class="text-xs text-gray-500">Rp {{ number_format($item['price'] + $item['modifier_total'], 0, ',', '.') }}</div>
                                @foreach ($item['modifiers'] as $modifier)
                                    <div class="mt-1 text-xs text-indigo-600 dark:text-indigo-300">{{ $modifier['group'] }}: {{ $modifier['name'] }}{{ $modifier['price_adjustment'] ? ' (+Rp '.number_format($modifier['price_adjustment'], 0, ',', '.').')' : '' }}</div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3"><button class="text-sm text-indigo-600 hover:underline" type="button" wire:click="editItemOptions({{ $index }})">Ubah opsi</button><button class="text-sm text-danger-600" type="button" wire:click="removeItem({{ $index }})">Hapus</button></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-filament::button color="gray" size="sm" type="button" wire:click="decreaseQuantity({{ $index }})">−</x-filament::button>
                                <span class="min-w-6 text-center">{{ $item['quantity'] }}</span>
                                <x-filament::button color="gray" size="sm" type="button" wire:click="increaseQuantity({{ $index }})">+</x-filament::button>
                            </div>
                            <span class="font-medium">Rp {{ number_format((($item['price'] + $item['modifier_total']) * $item['quantity']) - min($item['discount'], ($item['price'] + $item['modifier_total']) * $item['quantity']), 0, ',', '.') }}</span>
                        </div>
                        <div class="mt-2">
                            <label class="text-xs text-gray-500" for="item-discount-{{ $index }}">Diskon item</label>
                            <x-filament::input.wrapper prefix="Rp" class="mt-1">
                                <x-filament::input id="item-discount-{{ $index }}" min="0" type="number" wire:model.live="cart.{{ $index }}.discount" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                @empty
                    <div class="pos-empty-cart py-8 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><x-filament::icon icon="heroicon-o-shopping-cart" class="size-6" /></span><p class="mt-3 text-sm font-bold text-gray-700 dark:text-gray-200">Keranjang masih kosong</p><p class="mt-1 text-xs text-gray-500">Pilih produk untuk memulai pesanan.</p></div>
                @endforelse
            </div>

            <div class="mt-6 space-y-3 border-t border-gray-200 pt-4 text-sm dark:border-white/10">
                <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Diskon item</span><span>− Rp {{ number_format($this->itemDiscountTotal, 0, ',', '.') }}</span></div>
                <div>
                    <label class="mb-1 block text-xs text-gray-500" for="transaction-discount">Diskon transaksi</label>
                    <x-filament::input.wrapper prefix="Rp">
                        <x-filament::input x-ref="transactionDiscount" id="transaction-discount" min="0" type="number" wire:model.live="manualTransactionDiscount" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-500" for="promotion-code">Kode promosi</label>
                    <div class="flex gap-2">
                        <x-filament::input.wrapper class="flex-1"><x-filament::input id="promotion-code" wire:model="promotionCode" /></x-filament::input.wrapper>
                        <x-filament::button color="gray" type="button" wire:click="applyPromotion">Pakai</x-filament::button>
                    </div>
                    @if ($redeemCodeId)
                        <div class="mt-2 flex items-center justify-between text-success-600">
                            <span>Promo {{ strtoupper($promotionCode) }}: − Rp {{ number_format($this->promotionDiscount, 0, ',', '.') }}</span>
                            <button class="text-xs underline" type="button" wire:click="removePromotion">Hapus</button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between text-lg font-semibold">
                <span>Total</span>
                <span>Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
            </div>

            <x-filament::button x-ref="payButton" class="pos-pay-button mt-4 w-full" size="lg" wire:click="createPendingSale" :disabled="count($cart) === 0">
                <x-filament::icon icon="heroicon-o-credit-card" class="mr-2 size-5" /> Lanjut ke pembayaran
            </x-filament::button>
            <div wire:loading.flex wire:target="createPendingSale,addProduct,scanProduct" class="mt-3 items-center justify-center gap-2 text-xs font-semibold text-indigo-600"><span class="size-2 animate-pulse rounded-full bg-indigo-600"></span>Memproses transaksi…</div>
        </x-filament::section>
        </div>
    </div>

    @if ($productOptionsOpen && $this->productForOptions)
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:key="product-options-dialog-{{ $productForOptionsId }}">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                <form wire:submit="confirmProductOptions" x-on:submit="open = false">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-sm font-medium text-indigo-600">SESUAIKAN PESANAN</p><h2 class="mt-1 text-xl font-bold">{{ $this->productForOptions->name }}</h2></div>
                    <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10" type="button" wire:click="cancelProductOptions" x-on:click="open = false"><x-filament::icon icon="heroicon-o-x-mark" class="size-5" /></button>
                </div>

                <div class="mt-6 space-y-5">
                    @foreach ($this->productForOptions->optionGroups as $group)
                        @if ($group->values->where('is_active', true)->isNotEmpty())
                            <div>
                                <div class="mb-2 flex items-center justify-between"><span class="font-semibold">{{ $group->name }}</span><span class="text-xs text-gray-500">{{ $group->is_required ? 'Wajib' : 'Opsional' }}</span></div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($group->values->where('is_active', true) as $value)
                                        <button class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-3 text-left {{ $this->isOptionSelected($group->id, $value->id) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/15' : 'border-gray-200 hover:border-indigo-300 dark:border-white/10' }}" type="button" wire:click="{{ $group->maximum_selections === 1 ? 'selectSingleOption('.$group->id.', '.$value->id.')' : 'toggleOption('.$group->id.', '.$value->id.')' }}"><span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full border {{ $this->isOptionSelected($group->id, $value->id) ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300' }}">{{ $this->isOptionSelected($group->id, $value->id) ? '✓' : '' }}</span><span>{{ $value->name }}</span></span><span class="text-sm text-gray-500">{{ $value->price_adjustment > 0 ? '+Rp '.number_format($value->price_adjustment, 0, ',', '.') : 'Gratis' }}</span></button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-6 flex gap-3"><button class="flex-1 rounded-lg border border-gray-300 px-4 py-2 font-medium" type="button" wire:click="cancelProductOptions" x-on:click="open = false">Batal</button><button class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white" type="submit" wire:loading.attr="disabled" wire:target="confirmProductOptions"><span wire:loading.remove wire:target="confirmProductOptions">{{ $editingCartIndex !== null ? 'Simpan perubahan' : 'Tambahkan ke keranjang' }}</span><span wire:loading wire:target="confirmProductOptions">Menyimpan…</span></button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($newMemberModalOpen)
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm" wire:key="new-member-dialog">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                <form wire:submit="createMember" x-on:submit="open = false">
                    <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-bold text-indigo-600">MEMBER BARU</p><h2 class="mt-1 text-xl font-black">Daftarkan pelanggan</h2><p class="mt-1 text-sm text-gray-500">Kode member dibuat otomatis dan langsung dipilih.</p></div><button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10" type="button" wire:click="cancelNewMember" x-on:click="open = false"><x-filament::icon icon="heroicon-o-x-mark" class="size-5" /></button></div>
                    <div class="mt-6 space-y-4"><div><label class="mb-1 block text-sm font-semibold">Nama pelanggan</label><x-filament::input.wrapper><x-filament::input wire:model="newMemberName" autofocus placeholder="Contoh: Andi Pratama" /></x-filament::input.wrapper>@error('newMemberName')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-semibold">Nomor WhatsApp</label><x-filament::input.wrapper><x-filament::input wire:model="newMemberPhone" placeholder="0812…" type="tel" /></x-filament::input.wrapper>@error('newMemberPhone')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-semibold">Email <span class="font-normal text-gray-500">(opsional)</span></label><x-filament::input.wrapper><x-filament::input wire:model="newMemberEmail" placeholder="nama@email.com" type="email" /></x-filament::input.wrapper>@error('newMemberEmail')<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div></div>
                    <div class="mt-6 flex gap-3"><button class="flex-1 rounded-lg border border-gray-300 px-4 py-2 font-semibold" type="button" wire:click="cancelNewMember" x-on:click="open = false">Batal</button><button class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white" type="submit" wire:loading.attr="disabled" wire:target="createMember"><span wire:loading.remove wire:target="createMember">Buat & pilih member</span><span wire:loading wire:target="createMember">Menyimpan…</span></button></div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
