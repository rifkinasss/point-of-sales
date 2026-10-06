<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Actions\Admin\ManageUserAccessAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;
use Packstub\AccountSwitcher\Filament\Actions\ImpersonateAction;
use Packstub\AccountSwitcher\AccountSwitcher;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),
                TextColumn::make('scope')
                    ->label('Scope')
                    ->state(fn (User $record): string => $record->role === UserRole::Manager
                        ? $record->managedStores->pluck('name')->join(', ')
                        : ($record->store?->name ?? 'Platform'))
                    ->wrap(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('last_login_at')
                    ->label('Aktivitas terakhir')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [
                    $role->value => $role->label(),
                ])->all()),
                TernaryFilter::make('is_active')->label('Status'),
            ])
            ->recordActions([
                ImpersonateAction::make()
                    ->visible(fn (User $record): bool => auth()->user() instanceof User && app(AccountSwitcher::class)->canImpersonate(auth()->user(), $record)),
                Action::make('resetPassword')
                    ->label('Atur ulang password')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user() instanceof User && ! auth()->user()->is($record))
                    ->action(function (User $record): void {
                        $admin = auth()->user();
                        if (! $admin instanceof User) return;
                        $status = app(ManageUserAccessAction::class)->sendPasswordReset($admin, $record);
                        $notification = Notification::make()
                            ->title($status === Password::RESET_LINK_SENT ? 'Link reset password dikirim' : 'Reset password tidak dapat dikirim');
                        $status === Password::RESET_LINK_SENT ? $notification->success() : $notification->warning();
                        $notification->send();
                    }),
                Action::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->is_active && auth()->user() instanceof User && ! auth()->user()->is($record))
                    ->action(function (User $record): void {
                        $admin = auth()->user();
                        if (! $admin instanceof User) return;
                        $stores = app(ManageUserAccessAction::class)->currentStores($record);
                        app(ManageUserAccessAction::class)->update($admin, $record, [
                            'name' => $record->name,
                            'email' => $record->email,
                            'role' => $record->role->value,
                            'is_active' => false,
                        ], collect($stores)->pluck('public_id')->all());
                        Notification::make()->success()->title('Akun dinonaktifkan')->send();
                    }),
                EditAction::make()
                    ->visible(fn (User $record): bool => UserResource::canEdit($record)),
            ])
            ->toolbarActions([]);
    }
}
