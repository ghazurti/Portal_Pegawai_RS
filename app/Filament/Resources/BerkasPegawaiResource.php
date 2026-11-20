<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BerkasPegawaiResource\Pages;
use App\Models\ActivityLog;
use App\Models\BerkasPegawai;
use App\Models\User;
use App\Support\DocumentPreview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BerkasPegawaiResource extends Resource
{
    protected static ?string $model = BerkasPegawai::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Kepegawaian (KPS)';
    protected static ?string $navigationLabel = 'Verifikasi Berkas';
    protected static ?string $modelLabel = 'Berkas Pegawai';
    protected static ?string $pluralModelLabel = 'Verifikasi Berkas Pegawai';
    protected static ?string $slug = 'berkas-pegawais';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canVerifyBerkas();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = BerkasPegawai::query()->where('status_verifikasi', BerkasPegawai::STATUS_MENUNGGU)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Pegawai')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->disabledOn('edit'),
            Forms\Components\Select::make('jenis_berkas_id')
                ->label('Jenis Berkas')
                ->relationship('jenisBerkas', 'nama')
                ->searchable()
                ->preload()
                ->required()
                ->disabledOn('edit'),
            Forms\Components\TextInput::make('judul_dokumen')
                ->label('Nama / Judul Dokumen')
                ->maxLength(180),
            Forms\Components\TextInput::make('nomor_dokumen')
                ->label('Nomor Dokumen')
                ->maxLength(100),
            Forms\Components\DatePicker::make('tgl_terbit')
                ->label('Tanggal Terbit')
                ->native(false),
            Forms\Components\DatePicker::make('tgl_berakhir')
                ->label('Tanggal Berakhir')
                ->native(false),
            Forms\Components\Select::make('status_verifikasi')
                ->label('Status Verifikasi')
                ->options([
                    BerkasPegawai::STATUS_MENUNGGU => 'Menunggu Verifikasi',
                    BerkasPegawai::STATUS_DISETUJUI => 'Disetujui',
                    BerkasPegawai::STATUS_DITOLAK => 'Perlu Revisi',
                ])
                ->required(),
            Forms\Components\Textarea::make('catatan_verifikator')
                ->label('Catatan Verifikator')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user.unit', 'user.kategoriProfesi', 'jenisBerkas', 'verifikator']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable()
                    ->description(fn (BerkasPegawai $r) => ($r->user?->nip ?: '-') . ' | ' . ($r->user?->unit?->nama ?: '-')),
                Tables\Columns\TextColumn::make('jenisBerkas.nama')
                    ->label('Jenis Berkas')
                    ->description(fn (BerkasPegawai $r) => $r->judul_dokumen ?: null)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('judul_dokumen', 'like', "%{$search}%")
                                ->orWhereHas('jenisBerkas', fn (Builder $jb) => $jb->where('nama', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_dokumen')
                    ->label('No. Dokumen')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tgl_berakhir')
                    ->label('Berlaku s.d.')
                    ->date('d-m-Y')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_efektif')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (BerkasPegawai $r) => $r->labelStatusEfektif())
                    ->color(fn (BerkasPegawai $r) => $r->warnaStatusEfektif()),
                Tables\Columns\TextColumn::make('catatan_verifikator')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diunggah')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_khusus')
                    ->label('Status / Masa Berlaku')
                    ->options([
                        'menunggu' => 'Menunggu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Perlu Revisi',
                        'hampir_habis' => 'Hampir Habis (<= 90 Hari)',
                        'kedaluwarsa' => 'Kedaluwarsa',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $val = $data['value'] ?? null;
                        if (! $val) {
                            return $query;
                        }
                        if ($val === 'kedaluwarsa') {
                            return $query->whereNotNull('tgl_berakhir')->whereDate('tgl_berakhir', '<', now()->toDateString());
                        }
                        if ($val === 'hampir_habis') {
                            return $query->where('status_verifikasi', BerkasPegawai::STATUS_DISETUJUI)
                                ->whereNotNull('tgl_berakhir')
                                ->whereDate('tgl_berakhir', '>=', now()->toDateString())
                                ->whereDate('tgl_berakhir', '<=', now()->addDays(90)->toDateString());
                        }
                        return $query->where('status_verifikasi', $val);
                    }),
                Tables\Filters\SelectFilter::make('jenis_berkas_id')
                    ->label('Jenis Berkas')
                    ->relationship('jenisBerkas', 'nama'),
                Tables\Filters\SelectFilter::make('unit_pegawai')
                    ->label('Unit')
                    ->relationship('user.unit', 'nama'),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_file')
                    ->label('Lihat')
                    ->modalHeading(fn (BerkasPegawai $r) => ($r->judul_dokumen ?: ($r->jenisBerkas?->nama ?: 'Berkas Pegawai')) . ' - ' . ($r->user?->name ?: ''))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (BerkasPegawai $r) => DocumentPreview::view(
                        url: route('file.berkas', $r),
                        judul: $r->judul_dokumen ?: ($r->jenisBerkas?->nama ?: 'Berkas Pegawai'),
                        subjudul: trim(($r->user?->name ?: '-') . ($r->jenisBerkas?->nama ? ' | ' . $r->jenisBerkas->nama : '') . ($r->nomor_dokumen ? ' | No: ' . $r->nomor_dokumen : ''), ' |'),
                        namaFile: $r->nama_file_asli ?: basename((string) $r->file_path),
                        filePath: $r->file_path,
                    )),
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BerkasPegawai $r) => $r->status_verifikasi !== BerkasPegawai::STATUS_DISETUJUI)
                    ->action(function (BerkasPegawai $r) {
                        $r->update([
                            'status_verifikasi' => BerkasPegawai::STATUS_DISETUJUI,
                            'catatan_verifikator' => null,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                        ActivityLog::catat('verifikasi_setujui_berkas', $r, $r->user?->name . ' - ' . $r->jenisBerkas?->nama);
                        Notification::make()->title('Berkas disetujui')->success()->send();
                    }),
                Tables\Actions\Action::make('tolak')
                    ->label('Revisi')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('catatan_verifikator')
                            ->label('Alasan / Catatan Revisi')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (BerkasPegawai $r, array $data) {
                        $r->update([
                            'status_verifikasi' => BerkasPegawai::STATUS_DITOLAK,
                            'catatan_verifikator' => $data['catatan_verifikator'],
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                        ActivityLog::catat('verifikasi_tolak_berkas', $r, $r->user?->name . ' - ' . $data['catatan_verifikator']);
                        Notification::make()->title('Berkas dikembalikan untuk revisi')->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBerkasPegawais::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
