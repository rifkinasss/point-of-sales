@php
    use App\Enums\UserRole;
    use Packstub\AccountSwitcher\AccountSwitcher;

    $switcher = app(AccountSwitcher::class);
    $target = auth()->user();
    $impersonator = $switcher->impersonator();
    $active = $switcher->isImpersonating() && $target && $impersonator;
    $targetRole = $target?->role instanceof UserRole ? $target->role->label() : 'Pengguna';
@endphp

@if ($active)
    <div class="shrink-0 border-b border-blue-200 bg-slate-900 text-white" role="status" aria-label="Mode account switching">
        <div class="mx-auto flex min-h-12 max-w-screen-2xl items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="grid size-7 shrink-0 place-items-center rounded-md bg-blue-500/20 text-blue-200">
                    <x-filament::icon icon="heroicon-o-arrow-path-rounded-square" class="size-4" aria-hidden="true" />
                </span>
                <p class="min-w-0 truncate text-xs leading-5 sm:text-sm">
                    <span class="font-semibold">Anda sedang menggunakan akun {{ $targetRole }}</span>
                    <span class="hidden text-slate-300 sm:inline"> · {{ $target->email }} · Administrator</span>
                    <span class="sr-only">{{ $target->email }} · Administrator</span>
                </p>
            </div>
            <form method="POST" action="{{ route('account-switcher.switch-back') }}" class="shrink-0">
                @csrf
                <button type="submit" class="nl-focus inline-flex min-h-9 items-center gap-2 rounded-md border border-blue-200/20 bg-blue-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-500 sm:text-sm">
                    <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="size-4" aria-hidden="true" />
                    <span>Kembali ke Administrator</span>
                </button>
            </form>
        </div>
    </div>
@endif
