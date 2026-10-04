<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use BackedEnum;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.store-settings';

    protected static ?string $navigationLabel = 'Profil toko';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $title = 'Pengaturan Toko';

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $store = Store::query()->firstOrCreate([], [
            'name' => 'Toko Saya',
        ]);

        $this->form->fill($store->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas toko')
                    ->description('Informasi ini digunakan pada panel dan struk pelanggan.')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo toko')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('store-logos')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->maxImageWidth(1024)
                            ->maxImageHeight(1024)
                            ->optimize('webp', 85),
                        TextInput::make('name')
                            ->label('Nama toko')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('store_code')
                            ->label('Kode toko')
                            ->required()
                            ->length(3)
                            ->regex('/^[A-Z0-9]{3}$/')
                            ->helperText('Tepat 3 karakter uppercase, digunakan pada nomor invoice.'),
                        TextInput::make('branch_code')
                            ->label('Kode cabang (opsional)')
                            ->maxLength(7)
                            ->regex('/^[A-Z0-9]{1,7}$/')
                            ->helperText('Maksimal 7 karakter uppercase alfanumerik.'),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label('Nomor telepon / WhatsApp')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Email toko')
                            ->email()
                            ->maxLength(255),
                        MapPicker::make('location')
                            ->label('Lokasi toko')
                            ->helperText('Klik pada peta untuk menyimpan koordinat lokasi toko.')
                            ->center(-2.5489, 118.0149)
                            ->zoom(5)
                            ->autoCenter()
                            ->tileLayersUrl(TileLayer::OpenStreetMap)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Transaksi dan struk')
                    ->schema([
                        Select::make('currency')
                            ->label('Mata uang')
                            ->options(['IDR' => 'Rupiah (IDR)'])
                            ->required(),
                        Select::make('timezone')
                            ->label('Zona waktu')
                            ->options([
                                'Asia/Jakarta' => 'WIB — Asia/Jakarta',
                                'Asia/Makassar' => 'WITA — Asia/Makassar',
                                'Asia/Jayapura' => 'WIT — Asia/Jayapura',
                            ])
                            ->required(),
                        TextInput::make('invoice_prefix')
                            ->label('Awalan nomor invoice')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('tax_percentage')
                            ->label('Pajak standar')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        Textarea::make('receipt_footer')
                            ->label('Footer struk')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Toggle::make('receipt_show_cashier')
                            ->label('Tampilkan nama kasir pada struk'),
                        Toggle::make('receipt_show_tax')
                            ->label('Tampilkan informasi pajak pada struk'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        Store::query()->firstOrCreate([], [
            'name' => 'Toko Saya',
        ])->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Pengaturan toko disimpan')
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->role === UserRole::Owner;
    }
}
