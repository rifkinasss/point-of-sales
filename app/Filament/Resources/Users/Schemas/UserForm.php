<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\Store;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Kosongkan saat edit untuk mempertahankan password saat ini.'),
                Select::make('role')
                    ->label('Role')
                    ->options(fn (?\App\Models\User $record): array => UserResource::assignableOptions($record))
                    ->default('cashier')
                    ->live()
                    ->disabled(fn (?\App\Models\User $record): bool => $record instanceof \App\Models\User && ($record->role === UserRole::Admin || $record->is(auth()->user())))
                    ->required(),
                Select::make('store_public_ids')
                    ->label('Store access')
                    ->options(fn (): array => Store::query()->withoutGlobalScopes()->orderBy('name')->get()->mapWithKeys(fn (Store $store): array => [
                        $store->public_id => $store->name.($store->branch_name ? ' - '.$store->branch_name : ''),
                    ])->all())
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::Manager->value)
                    ->required(fn (Get $get): bool => $get('role') === UserRole::Manager->value)
                    ->helperText('Manager dapat memiliki akses ke beberapa store.'),
                Select::make('store_public_id')
                    ->label('Store operasional')
                    ->options(fn (): array => Store::query()->withoutGlobalScopes()->orderBy('name')->get()->mapWithKeys(fn (Store $store): array => [
                        $store->public_id => $store->name.($store->branch_name ? ' - '.$store->branch_name : ''),
                    ])->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Owner->value, UserRole::Cashier->value], true))
                    ->required(fn (Get $get): bool => in_array($get('role'), [UserRole::Owner->value, UserRole::Cashier->value], true)),
                Toggle::make('is_active')
                    ->label('Akun aktif')
                    ->default(true)
                    ->disabled(fn (?\App\Models\User $record): bool => $record instanceof \App\Models\User && $record->is(auth()->user())),
            ]);
    }
}
