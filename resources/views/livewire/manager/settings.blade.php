<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">Akun manager</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight text-foreground">Pengaturan Akun</h1>
        <p class="mt-1 text-sm text-muted">Kelola informasi pribadi, keamanan, dan akses cabang Anda.</p>
    </div>

    @if ($successMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
            {{ $successMessage }}
        </div>
    @endif

    <nav class="flex flex-wrap gap-2 border-b border-border pb-3" aria-label="Navigasi pengaturan akun">
        @foreach ([['profile', 'Profil'], ['security', 'Keamanan'], ['access', 'Akses Cabang']] as [$key, $label])
            <a href="{{ route('manager.settings.'.$key) }}"
                class="nl-focus rounded-lg px-3 py-2 text-sm font-bold {{ $section === $key ? 'bg-primary-soft text-primary' : 'text-muted hover:bg-background hover:text-foreground' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if ($section === 'profile')
        <section class="rounded-xl border border-border bg-surface p-5 shadow-sm lg:p-6" aria-labelledby="profile-title">
            <div class="flex items-start gap-4 border-b border-border pb-5">
                <div class="grid size-12 shrink-0 place-items-center rounded-full bg-primary-soft text-lg font-black text-primary">
                    {{ collect(explode(' ', trim($manager->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                </div>
                <div>
                    <h2 id="profile-title" class="text-lg font-bold text-foreground">Profil</h2>
                    <p class="mt-1 text-sm text-muted">Informasi ini digunakan untuk identitas Anda di workspace Manager.</p>
                </div>
            </div>
            <form class="mt-5 space-y-4" wire:submit="saveProfile">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-secondary" for="manager-name">Nama lengkap</label>
                    <input id="manager-name" class="w-full rounded-lg border border-border bg-surface px-4 py-3 text-foreground placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/15" wire:model="name" type="text" autocomplete="name">
                    @error('name')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-secondary" for="manager-email">Email</label>
                    <input id="manager-email" class="w-full rounded-lg border border-border bg-surface px-4 py-3 text-foreground placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/15" wire:model="email" type="email" autocomplete="email">
                    @error('email')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end pt-2">
                    <button class="nl-focus inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" type="submit">
                        <span wire:loading.remove wire:target="saveProfile">Simpan perubahan</span>
                        <span wire:loading wire:target="saveProfile">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </section>
    @elseif ($section === 'security')
        <section class="rounded-xl border border-border bg-surface p-5 shadow-sm lg:p-6" aria-labelledby="security-title">
            <div class="border-b border-border pb-5">
                <h2 id="security-title" class="text-lg font-bold text-foreground">Keamanan</h2>
                <p class="mt-1 text-sm text-muted">Perbarui password akun tanpa mengubah sesi atau akses cabang.</p>
            </div>
            <form class="mt-5 max-w-xl space-y-4" wire:submit="changePassword">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-secondary" for="current-password">Password saat ini</label>
                    <div class="relative" x-data="{ visible: false }"><input id="current-password" class="w-full rounded-lg border border-border bg-surface px-4 py-3 pr-12 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/15" wire:model="currentPassword" x-bind:type="visible ? 'text' : 'password'" autocomplete="current-password"><button class="nl-focus absolute inset-y-0 right-0 grid w-11 place-items-center text-muted hover:text-primary" type="button" x-on:click="visible = !visible" x-bind:aria-label="visible ? 'Sembunyikan password' : 'Tampilkan password'"><x-filament::icon icon="heroicon-o-eye" class="size-4" aria-hidden="true" /></button></div>
                    @error('currentPassword')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-secondary" for="new-password">Password baru</label>
                    <div class="relative" x-data="{ visible: false }"><input id="new-password" class="w-full rounded-lg border border-border bg-surface px-4 py-3 pr-12 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/15" wire:model="newPassword" x-bind:type="visible ? 'text' : 'password'" autocomplete="new-password"><button class="nl-focus absolute inset-y-0 right-0 grid w-11 place-items-center text-muted hover:text-primary" type="button" x-on:click="visible = !visible" x-bind:aria-label="visible ? 'Sembunyikan password' : 'Tampilkan password'"><x-filament::icon icon="heroicon-o-eye" class="size-4" aria-hidden="true" /></button></div>
                    @error('newPassword')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-secondary" for="new-password-confirmation">Konfirmasi password baru</label>
                    <div class="relative" x-data="{ visible: false }"><input id="new-password-confirmation" class="w-full rounded-lg border border-border bg-surface px-4 py-3 pr-12 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/15" wire:model="newPasswordConfirmation" x-bind:type="visible ? 'text' : 'password'" autocomplete="new-password"><button class="nl-focus absolute inset-y-0 right-0 grid w-11 place-items-center text-muted hover:text-primary" type="button" x-on:click="visible = !visible" x-bind:aria-label="visible ? 'Sembunyikan password' : 'Tampilkan password'"><x-filament::icon icon="heroicon-o-eye" class="size-4" aria-hidden="true" /></button></div>
                    @error('newPasswordConfirmation')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end pt-2">
                    <button class="nl-focus inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" type="submit">
                        <span wire:loading.remove wire:target="changePassword">Ubah password</span>
                        <span wire:loading wire:target="changePassword">Menyimpan...</span>
                    </button>
                </div>
            </form>
            <div class="mt-8 grid gap-3 border-t border-border pt-5 sm:grid-cols-2">
                <div class="rounded-lg bg-background p-4"><p class="text-xs font-semibold uppercase tracking-wide text-muted">Peran</p><p class="mt-1 text-sm font-bold text-foreground">{{ $manager->role?->label() ?? 'Manager' }}</p></div>
                <div class="rounded-lg bg-background p-4"><p class="text-xs font-semibold uppercase tracking-wide text-muted">Login terakhir</p><p class="mt-1 text-sm font-bold text-foreground">{{ $manager->last_login_at ? app(\App\Services\BrowserTimezone::class)->format($manager->last_login_at, 'd M Y · H:i T') : 'Belum tersedia' }}</p></div>
            </div>
        </section>
    @else
        <section class="rounded-xl border border-border bg-surface p-5 shadow-sm lg:p-6" aria-labelledby="access-title">
            <div class="border-b border-border pb-5">
                <h2 id="access-title" class="text-lg font-bold text-foreground">Akses Cabang</h2>
                <p class="mt-1 text-sm text-muted">Daftar store yang dapat Anda kelola melalui workspace Manager.</p>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($stores as $store)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-foreground">{{ $store->name }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $store->branch_code ?: 'Store utama' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($store->pivot?->is_default)
                                <span class="rounded-full bg-primary-soft px-2.5 py-1 text-xs font-bold text-primary">Default</span>
                            @endif
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Aktif</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-muted">Belum ada akses cabang.</p>
                @endforelse
            </div>
            <p class="mt-5 text-xs text-muted">Akses cabang dikelola oleh Owner atau Administrator.</p>
        </section>
    @endif
</div>
