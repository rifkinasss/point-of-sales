<x-filament-panels::page>
    @if ($sale)
        <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ $sale->invoice_number }}</x-slot>

                <div class="space-y-3 text-sm">
                    @foreach ($sale->items as $item)
                        <div class="flex justify-between gap-4">
                            <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                            <span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex justify-between border-t border-gray-200 pt-4 text-lg font-semibold dark:border-white/10">
                    <span>Total</span>
                    <span>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                </div>
                <div class="mt-2 flex justify-between text-sm text-gray-500">
                    <span>Sisa pembayaran</span>
                    <span>Rp {{ number_format($this->remainingDue(), 0, ',', '.') }}</span>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Pembayaran</x-slot>

                <form class="space-y-5" wire:submit="processPayment">
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-400/20 dark:bg-indigo-500/10">
                        <div class="flex items-center justify-between gap-3"><div><p class="text-sm font-bold text-indigo-950 dark:text-indigo-100">Member pelanggan</p><p class="text-xs text-indigo-700 dark:text-indigo-300">Cari, pilih, atau scan kartu sebelum bayar.</p></div><x-filament::icon icon="heroicon-o-identification" class="size-6 text-indigo-600" /></div>

                        @if ($sale->customer)
                            <div class="mt-3 flex items-center justify-between gap-3 rounded-xl bg-white p-3 shadow-sm dark:bg-gray-900"><div class="min-w-0"><p class="truncate font-bold">{{ $sale->customer->name }}</p><p class="text-xs text-gray-500">{{ $sale->customer->code }} · {{ $sale->customer->points }} poin @if($sale->customer->phone) · {{ $sale->customer->phone }} @endif</p></div><button class="text-xs font-bold text-danger-600 hover:underline" type="button" wire:click="clearMember">Ganti</button></div>
                        @else
                            <div class="mt-3 grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                <x-filament::input.wrapper>
                                    <x-filament::input wire:model.live.debounce.250ms="memberSearch" placeholder="Cari nama, telepon, atau kode member" type="search" />
                                </x-filament::input.wrapper>
                                <div class="flex gap-2"><x-filament::input.wrapper class="min-w-0"><x-filament::input wire:model="memberCardCode" wire:keydown.enter.prevent="scanMemberCard" placeholder="Scan kartu" /></x-filament::input.wrapper><x-filament::button color="gray" type="button" wire:click="scanMemberCard" icon="heroicon-o-qr-code">Scan</x-filament::button></div>
                            </div>
                            @if ($memberSearch !== '' && mb_strlen($memberSearch) < 2)
                                <p class="mt-2 text-xs text-gray-500">Ketik minimal 2 karakter untuk mencari member.</p>
                            @endif
                            @if ($this->memberMatches->isNotEmpty())
                                <div class="mt-2 max-h-44 divide-y overflow-y-auto rounded-xl border border-indigo-100 bg-white dark:border-white/10 dark:bg-gray-900">
                                    @foreach ($this->memberMatches as $member)
                                        <button class="flex w-full items-center justify-between gap-3 p-3 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-500/10" type="button" wire:click="selectMember({{ $member->id }})"><span><span class="block text-sm font-bold">{{ $member->name }}</span><span class="block text-xs text-gray-500">{{ $member->code }} @if($member->phone) · {{ $member->phone }} @endif</span></span><span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">{{ $member->points }} poin</span></button>
                                    @endforeach
                                </div>
                            @elseif (mb_strlen($memberSearch) >= 2)
                                <p class="mt-2 text-xs text-gray-500">Member tidak ditemukan. Coba nama, nomor telepon, atau kode kartu.</p>
                            @endif
                        @endif
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Metode pembayaran</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['cash' => 'Tunai', 'bank_transfer' => 'Transfer', 'qris' => 'QRIS', 'e_wallet' => 'E-wallet'] as $value => $label)
                                <x-filament::button
                                    :color="$method === $value ? 'primary' : 'gray'"
                                    type="button"
                                    wire:click="$set('method', '{{ $value }}')"
                                >
                                    {{ $label }}
                                </x-filament::button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium" for="amount-to-pay">Nominal pembayaran</label>
                        <x-filament::input.wrapper prefix="Rp">
                            <x-filament::input id="amount-to-pay" min="1" type="number" wire:model="amountToPay" />
                        </x-filament::input.wrapper>
                    </div>

                    @if ($method === 'cash')
                        <div>
                            <label class="mb-2 block text-sm font-medium" for="amount-received">Uang diterima</label>
                            <x-filament::input.wrapper prefix="Rp">
                                <x-filament::input id="amount-received" min="0" type="number" wire:model="amountReceived" />
                            </x-filament::input.wrapper>
                        </div>
                    @endif

                    @if ($method !== 'cash')
                        <div>
                            <label class="mb-2 block text-sm font-medium" for="reference-number">Nomor referensi (opsional)</label>
                            <x-filament::input.wrapper>
                                <x-filament::input id="reference-number" type="text" wire:model="referenceNumber" />
                            </x-filament::input.wrapper>
                        </div>
                    @endif

                    @if ($method === 'cash' && $amountReceived >= $amountToPay)
                        <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">
                            Kembalian: <strong>Rp {{ number_format($amountReceived - $amountToPay, 0, ',', '.') }}</strong>
                        </div>
                    @endif

                    <x-filament::button class="w-full" size="lg" type="submit">
                        Konfirmasi pembayaran
                    </x-filament::button>
                </form>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
