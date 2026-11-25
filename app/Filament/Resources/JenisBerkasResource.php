<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisBerkasResource\Pages;
use App\Models\JenisBerkas;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JenisBerkasResource extends Resource
{
    protected static ?string $model = JenisBerkas::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Kepegawaian (KPS)';
    protected static ?string $navigationLabel = 'Master Jenis Berkas';
    protected static ?string $modelLabel = 'Jenis Berkas';
    protected static ?string $pluralModelLabel = 'Master Jenis Berkas';
    protected static ?string $slug = 'jenis-berkas';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canVerifyBerkas();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode')
                ->label('Kode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(30),
            Forms\Components\TextInput::make('nama')
                ->label('Nama Jenis Berkas')
                ->required()
                ->maxLength(150),
            Forms\Components\TextInput::make('urutan')
                ->label('Urutan Tampil')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('hari_pengingat')
                ->label('Pengingat Sebelum Habis (Hari)')
                ->numeric()
                ->default(90),
            Forms\Components\Toggle::make('wajib_semua')
                ->label('Wajib untuk semua pegawai'),
            Forms\Components\Toggle::make('memiliki_masa_berlaku')
                ->label('Memiliki tanggal berakhir (masa berlaku)'),
            Forms\Components\Toggle::make('boleh_banyak')
                ->label('Boleh banyak berkas (multi-upload)')
                ->helperText('Aktifkan untuk berkas seperti sertifikat pelatihan, SK tambahan, dll.'),
            Forms\Components\Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
            Forms\Components\Select::make('kategoriProfesis')
                ->label('Wajib untuk Profesi Tertentu')
                ->relationship('kategoriProfesis', 'nama')
                ->multiple()
                ->preload()
                ->helperText('Kosongkan jika sudah dicentang "Wajib untuk semua pegawai".')
                ->columnSpanFull(),
            Forms\Components\CheckboxList::make('status_kepegawaian_wajib')
                ->label('Wajib untuk Status Kepegawaian Tertentu')
                ->options(User::STATUS_KEPEGAWAIAN)
                ->columns(2)
                ->helperText('Kosongkan jika berlaku untuk semua status kepegawaian (PNS, PPPK Penuh/Paruh Waktu, Kontrak).')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('keterangan')
                ->label('Keterangan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('kode')->label('Kode')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nama')->label('Jenis Berkas')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('wajib_semua')->label('Semua')->boolean(),
                Tables\Columns\TextColumn::make('kategoriProfesis.nama')->label('Profesi Wajib')->badge()->separator(', '),
                Tables\Columns\TextColumn::make('status_kepegawaian_wajib')
                    ->label('Status Wajib')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) {
                            return 'Semua Status';
                        }
                        $items = is_array($state) ? $state : [$state];
                        return collect($items)->map(fn ($s) => User::STATUS_KEPEGAWAIAN[$s] ?? $s)->implode(', ');
                    })
                    ->placeholder('Semua Status'),
                Tables\Columns\IconColumn::make('memiliki_masa_berlaku')->label('Masa Berlaku')->boolean(),
                Tables\Columns\IconColumn::make('boleh_banyak')->label('Multi')->boolean(),
                Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->before(function (Tables\Actions\DeleteAction $action, JenisBerkas $record) {
                        if ($record->berkasPegawais()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak dapat dihapus')
                                ->body('Jenis berkas ini sudah dipakai oleh berkas pegawai. Nonaktifkan saja agar arsip lama tetap aman.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $dihapus = 0;
                        $dilewati = 0;
                        foreach ($records as $record) {
                            if ($record->berkasPegawais()->exists()) {
                                $dilewati++;
                                continue;
                            }
                            $record->delete();
                            $dihapus++;
                        }
                        \Filament\Notifications\Notification::make()
                            ->title("{$dihapus} jenis berkas dihapus" . ($dilewati > 0 ? " ({$dilewati} dilewati karena masih dipakai)" : ''))
                            ->status($dilewati > 0 ? 'warning' : 'success')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJenisBerkas::route('/'),
        ];
    }
}
