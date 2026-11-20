<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PegawaiResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class PegawaiResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Kepegawaian (KPS)';
    protected static ?string $navigationLabel = 'Master Pegawai';
    protected static ?string $modelLabel = 'Pegawai';
    protected static ?string $pluralModelLabel = 'Data Pegawai';
    protected static ?string $slug = 'pegawais';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canVerifyBerkas();
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $record instanceof User && (bool) $user?->bolehKelolaPegawai($record);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nip')
                ->label('NIP / NIK Pegawai')
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(191),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(191),
            Forms\Components\TextInput::make('jabatan')
                ->label('Jabatan')
                ->maxLength(120),
            Forms\Components\Select::make('unit_id')
                ->label('Unit / Ruangan')
                ->relationship('unit', 'nama')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('kategori_profesi_id')
                ->label('Kategori Profesi (KPS)')
                ->relationship('kategoriProfesi', 'nama')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('role')
                ->label('Peran Akses')
                ->options(fn () => Auth::user()?->opsiRoleBolehDitetapkan() ?? [User::ROLE_PEGAWAI => User::ROLES[User::ROLE_PEGAWAI]])
                ->in(fn () => array_keys(Auth::user()?->opsiRoleBolehDitetapkan() ?? [User::ROLE_PEGAWAI => true]))
                ->default(User::ROLE_PEGAWAI)
                ->required(),
            Forms\Components\TextInput::make('no_hp')
                ->label('Nomor WhatsApp / HP')
                ->maxLength(30),
            Forms\Components\TextInput::make('password')
                ->label('Kata Sandi')
                ->password()
                ->revealable()
                ->rule(Password::min(8))
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->helperText('Minimal 8 karakter. Kosongkan saat edit jika tidak ingin mengubah kata sandi.'),
            Forms\Components\Toggle::make('aktif')
                ->label('Akun Aktif')
                ->default(true),
            Forms\Components\Toggle::make('wajib_ganti_password')
                ->label('Wajib ganti kata sandi saat login berikutnya')
                ->default(fn (string $operation): bool => $operation === 'create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('nip')->label('NIP')->searchable()->placeholder('-'),
                Tables\Columns\TextColumn::make('name')->label('Nama Pegawai')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('unit.nama')->label('Unit')->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('kategoriProfesi.nama')->label('Profesi')->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => User::ROLES[$state] ?? (string) $state),
                Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_id')->label('Unit')->relationship('unit', 'nama'),
                Tables\Filters\SelectFilter::make('kategori_profesi_id')->label('Profesi')->relationship('kategoriProfesi', 'nama'),
                Tables\Filters\SelectFilter::make('role')->label('Peran')->options(User::ROLES),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record) => (bool) Auth::user()?->bolehKelolaPegawai($record))
                    ->mutateFormDataUsing(function (User $record, array $data): array {
                        /** @var User|null $actor */
                        $actor = Auth::user();
                        abort_unless(
                            $actor && $actor->bolehKelolaPegawai($record) && ($actor->isAdminIt() || ($data['role'] ?? null) !== User::ROLE_ADMIN),
                            403
                        );

                        return $data;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePegawais::route('/'),
        ];
    }
}
