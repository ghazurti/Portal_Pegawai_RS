<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokumenRsResource\Pages;
use App\Filament\Resources\DokumenRsResource\RelationManagers;
use App\Models\ActivityLog;
use App\Models\DokumenRs;
use App\Models\DokumenRsVersi;
use App\Models\User;
use App\Support\DocumentPreview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DokumenRsResource extends Resource
{
    protected static ?string $model = DokumenRs::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Bank Dokumen & SOP';
    protected static ?string $navigationLabel = 'Kelola SOP / SK / Pedoman';
    protected static ?string $modelLabel = 'Dokumen RS';
    protected static ?string $pluralModelLabel = 'Dokumen SOP, SK & Pedoman RS';
    protected static ?string $slug = 'dokumen-rs';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) ($user && ($user->canManageDokumen() || $user->isKepalaRuangan()));
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) ($user && ($user->canManageDokumen() || $user->isKepalaRuangan()));
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canAccessDokumenManager();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->isKepalaRuangan()) {
            $query->where('unit_pemilik_id', $user->unit_id ?? 0);
        } elseif ($user && $user->isKepalaBidang()) {
            $unitIds = $user->unitIdsDalamCakupan() ?? [];
            $query->whereIn('unit_pemilik_id', $unitIds === [] ? [0] : $unitIds);
        }
        return $query;
    }

    public static function form(Form $form): Form
    {
        /** @var User|null $user */
        $user = Auth::user();
        $isKaru = (bool) $user?->isKepalaRuangan();

        return $form->schema([
            Forms\Components\Section::make('Informasi Dokumen')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nomor_dokumen')
                        ->label('Nomor Dokumen')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(80),
                    Forms\Components\Select::make('kategori_dokumen_id')
                        ->label('Kategori Dokumen')
                        ->relationship('kategoriDokumen', 'nama')
                        ->required()
                        ->preload(),
                    Forms\Components\TextInput::make('judul')
                        ->label('Judul Dokumen')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('pokja_akreditasi')
                        ->label('Pokja Akreditasi')
                        ->placeholder('Contoh: MRMIK, KPS, SKP, PPI')
                        ->maxLength(40),
                    Forms\Components\Select::make('unit_pemilik_id')
                        ->label('Unit Pemilik / Penerbit')
                        ->relationship('unitPemilik', 'nama')
                        ->default(fn () => $isKaru ? $user?->unit_id : null)
                        ->disabled($isKaru)
                        ->dehydrated()
                        ->searchable()
                        ->preload(),
                    Forms\Components\DatePicker::make('tgl_terbit')
                        ->label('Tanggal Terbit')
                        ->default(now())
                        ->native(false)
                        ->required(),
                    Forms\Components\DatePicker::make('tgl_review')
                        ->label('Jadwal Review Berikutnya')
                        ->native(false),
                    Forms\Components\Select::make('status')
                        ->label('Status Dokumen')
                        ->options([
                            DokumenRs::STATUS_DRAFT => 'Draft (Belum Berlaku)',
                            DokumenRs::STATUS_BERLAKU => 'Berlaku',
                            DokumenRs::STATUS_ARSIP => 'Arsip / Tidak Berlaku',
                        ])
                        ->default(DokumenRs::STATUS_BERLAKU)
                        ->required(),
                    Forms\Components\Select::make('cakupan_akses')
                        ->label('Cakupan Akses Pegawai')
                        ->options([
                            DokumenRs::CAKUPAN_SEMUA => 'Semua Pegawai RS',
                            DokumenRs::CAKUPAN_UNIT => 'Khusus Unit Tertentu',
                        ])
                        ->default($isKaru ? DokumenRs::CAKUPAN_UNIT : DokumenRs::CAKUPAN_SEMUA)
                        ->disabled($isKaru)
                        ->dehydrated()
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('unitAkses')
                        ->label('Pilih Unit yang Berhak Mengakses')
                        ->relationship('unitAkses', 'nama')
                        ->multiple()
                        ->preload()
                        ->default(fn () => ($isKaru && $user?->unit_id) ? [$user->unit_id] : [])
                        ->disabled($isKaru)
                        ->visible(fn (Get $get) => ! $isKaru && $get('cakupan_akses') === DokumenRs::CAKUPAN_UNIT)
                        ->required(fn (Get $get) => ! $isKaru && $get('cakupan_akses') === DokumenRs::CAKUPAN_UNIT)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('ringkasan')
                        ->label('Ringkasan Isi / Tujuan Dokumen')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('File PDF Versi Awal')
                ->visibleOn('create')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nomor_versi_awal')
                        ->label('Nomor Versi Awal')
                        ->default('v1.0')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('ringkasan_revisi_awal')
                        ->label('Catatan Penerbitan')
                        ->default('Penerbitan awal dokumen')
                        ->maxLength(255),
                    Forms\Components\FileUpload::make('file_pdf_awal')
                        ->label('File PDF Dokumen (maks. 15 MB)')
                        ->disk('berkas_privat')
                        ->directory('dokumen-rs')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(15360)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Pratinjau Dokumen PDF Aktif')
                ->visible(fn (?DokumenRs $record) => (bool) $record?->versi_aktif_id)
                ->schema([
                    Forms\Components\Placeholder::make('pratinjau_pdf_aktif')
                        ->hiddenLabel()
                        ->content(function (?DokumenRs $record) {
                            if (! $record?->versi_aktif_id) {
                                return null;
                            }
                            $record->loadMissing('versiAktif');

                            return DocumentPreview::view(
                                url: route('file.dokumen-versi', $record->versi_aktif_id),
                                judul: $record->judul,
                                subjudul: $record->nomor_dokumen . ' (' . ($record->versiAktif?->nomor_versi ?: 'v1.0') . ')',
                                namaFile: basename((string) $record->versiAktif?->file_path),
                                filePath: $record->versiAktif?->file_path,
                                heightCss: '72vh',
                            );
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['kategoriDokumen', 'unitPemilik', 'versiAktif'])->withCount('bacas'))
            ->defaultSort('tgl_terbit', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_dokumen')
                    ->label('No. Dokumen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->limit(45),
                Tables\Columns\TextColumn::make('kategoriDokumen.nama')
                    ->label('Kategori')
                    ->badge(),
                Tables\Columns\TextColumn::make('pokja_akreditasi')
                    ->label('Pokja')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('versiAktif.nomor_versi')
                    ->label('Versi Aktif')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('cakupan_akses')
                    ->label('Akses')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === DokumenRs::CAKUPAN_SEMUA ? 'Semua Pegawai' : 'Unit Tertentu')
                    ->color(fn (string $state) => $state === DokumenRs::CAKUPAN_SEMUA ? 'info' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        DokumenRs::STATUS_BERLAKU => 'Berlaku',
                        DokumenRs::STATUS_DRAFT => 'Draft',
                        DokumenRs::STATUS_ARSIP => 'Arsip',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        DokumenRs::STATUS_BERLAKU => 'success',
                        DokumenRs::STATUS_DRAFT => 'gray',
                        DokumenRs::STATUS_ARSIP => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('bacas_count')
                    ->label('Dibaca')
                    ->numeric(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_dokumen_id')
                    ->label('Kategori')
                    ->relationship('kategoriDokumen', 'nama'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        DokumenRs::STATUS_BERLAKU => 'Berlaku',
                        DokumenRs::STATUS_DRAFT => 'Draft',
                        DokumenRs::STATUS_ARSIP => 'Arsip',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('bukaPdf')
                    ->label('Lihat PDF')
                    ->modalHeading(fn (DokumenRs $r) => $r->nomor_dokumen . ' - ' . $r->judul)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (DokumenRs $r) {
                        $r->loadMissing(['versiAktif', 'unitPemilik']);

                        return DocumentPreview::view(
                            url: route('file.dokumen-versi', $r->versi_aktif_id),
                            judul: $r->judul,
                            subjudul: $r->nomor_dokumen . ' (' . ($r->versiAktif?->nomor_versi ?: 'v1.0') . ') | Unit: ' . ($r->unitPemilik?->nama ?: 'Rumah Sakit'),
                            namaFile: basename((string) $r->versiAktif?->file_path),
                            filePath: $r->versiAktif?->file_path,
                        );
                    })
                    ->visible(fn (DokumenRs $r) => $r->versi_aktif_id !== null),
                Tables\Actions\Action::make('revisiVersi')
                    ->label('Revisi')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('nomor_versi')
                            ->label('Nomor Versi Baru')
                            ->placeholder('Contoh: v2.0')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\Textarea::make('ringkasan_revisi')
                            ->label('Ringkasan Perubahan')
                            ->required()
                            ->rows(2),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('File PDF Baru (maks. 15 MB)')
                            ->disk('berkas_privat')
                            ->directory('dokumen-rs')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(15360)
                            ->required(),
                    ])
                    ->action(function (DokumenRs $record, array $data) {
                        $versi = DokumenRsVersi::create([
                            'dokumen_rs_id' => $record->id,
                            'nomor_versi' => $data['nomor_versi'],
                            'file_path' => $data['file_path'],
                            'ringkasan_revisi' => $data['ringkasan_revisi'],
                            'uploaded_by' => Auth::id(),
                            'published_at' => now(),
                        ]);
                        $record->update(['versi_aktif_id' => $versi->id]);

                        ActivityLog::catat('revisi_versi_dokumen_rs', $record, $record->nomor_dokumen . ' -> ' . $versi->nomor_versi);

                        Notification::make()
                            ->title('Versi baru diterbitkan: ' . $versi->nomor_versi)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('eksporBaca')
                    ->label('Bukti Baca')
                    ->color('gray')
                    ->url(fn (DokumenRs $r) => route('export.sosialisasi-sop', $r)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->before(function (DokumenRs $record) {
                        $record->loadMissing('versis');
                        foreach ($record->versis as $v) {
                            if ($v->file_path) {
                                \Illuminate\Support\Facades\Storage::disk('berkas_privat')->delete($v->file_path);
                            }
                        }
                        $record->update(['versi_aktif_id' => null]);
                    })
                    ->after(function (DokumenRs $record) {
                        ActivityLog::catat('hapus_dokumen_rs', null, $record->nomor_dokumen . ' - ' . $record->judul);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $jumlah = 0;
                        foreach ($records as $record) {
                            $record->loadMissing('versis');
                            foreach ($record->versis as $v) {
                                if ($v->file_path) {
                                    \Illuminate\Support\Facades\Storage::disk('berkas_privat')->delete($v->file_path);
                                }
                            }
                            $record->update(['versi_aktif_id' => null]);
                            $record->delete();
                            $jumlah++;
                        }
                        ActivityLog::catat('hapus_massal_dokumen_rs', null, "Menghapus {$jumlah} dokumen RS");
                        Notification::make()->title("{$jumlah} dokumen RS dihapus")->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VersisRelationManager::class,
            RelationManagers\BacasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDokumenRs::route('/'),
            'create' => Pages\CreateDokumenRs::route('/create'),
            'edit' => Pages\EditDokumenRs::route('/{record}/edit'),
        ];
    }
}
