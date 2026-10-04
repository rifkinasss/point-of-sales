<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Packstub\AccountSwitcher\Filament\Actions\ImpersonateAction;

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
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options([
                    UserRole::Owner->value => UserRole::Owner->label(),
                    UserRole::Manager->value => UserRole::Manager->label(),
                    UserRole::Cashier->value => UserRole::Cashier->label(),
                ]),
                TernaryFilter::make('is_active')->label('Status aktif'),
            ])
            ->recordActions([
                ImpersonateAction::make()
                    ->visible(fn (User $record): bool => auth()->user() instanceof User && auth()->user()->canImpersonate($record)),
                EditAction::make()
                    ->visible(fn (User $record): bool => UserResource::canEdit($record)),
            ])
            ->toolbarActions([]);
    }
}
