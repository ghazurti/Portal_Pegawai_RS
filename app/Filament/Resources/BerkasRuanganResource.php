<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BerkasRuanganResource\Pages;
use App\Models\ActivityLog;
use App\Models\BerkasRuangan;
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

class BerkasRuanganResource extends Resource
{
    protected static ?string $model = BerkasRuangan::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Kelengkapan Ruangan';
    protected static ?string $navigationLabel = 'Verifikasi Berkas Ruangan';
    protected static ?string $modelLabel = 'Berkas Ruangan';
    protected static ?string $pluralModelLabel = 'Verifikasi Berkas Ruangan';
    protected static ?string $slug = 'berkas-ruangans';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return (bool) $user?->canVerifyBerkasRuangan();
    }

    public static function getNavigationBadge(): ?string
    {
        $hitung = static fn (): int => BerkasRuangan::query()
            ->where('status_verifikasi', BerkasRuangan::STATUS_MENUNGGU)
            ->count();

        $count = app()->runningUnitTests()
            ? $hitung()
            : \Illuminate\Support\Facades\Cache::remember('nav_badge_berkas_ruangan', 30, $hitung);

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('unit_id')
                ->label('Unit / Ruangan')
                ->relationship('unit', 'nama')
                ->disabled(),
            Forms\Components\Select::make('jenis_berkas_ruangan_id')
                ->label('Jenis Berkas Ruangan')
                ->relationship('jenisBerkasRuangan', 'nama')
                ->disabled(),
            Forms\Components\TextInput::make('judul_dokumen')
                ->label('Nama / Judul Dokumen')
                ->maxLength(180),
            Forms\Components\TextInput::make('nomor_dokumen')
                ->label('Nomor Dokumen')
                ->maxLength(100),
            Forms\Components\Select::make('status_verifikasi')
                ->label('Status')
                ->options([
                    BerkasRuangan::STATUS_MENUNGGU => 'Menunggu Verifikasi',
                    BerkasRuangan::STATUS_DISETUJUI => 'Disetujui',
                    BerkasRuangan::STATUS_DITOLAK => 'Perlu Revisi',
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unit', 'jenisBerkasRuangan', 'uploader', 'verifikator']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('unit.nama')
                    ->label('Ruangan / Unit')
                    ->searchable()
                    ->sortable()
                    ->description(fn (BerkasRuangan $r) => ($r->unit?->kode ?: '-') . ' | Diunggah: ' . ($r->uploader?->name ?: '-')),
                Tables\Columns\TextColumn::make('jenisBerkasRuangan.nama')
                    ->label('Jenis Berkas Ruangan')
                    ->description(fn (BerkasRuangan $r) => $r->judul_dokumen ?: null)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('judul_dokumen', 'like', "%{$search}%")
                                ->orWhereHas('jenisBerkasRuangan', fn (Builder $jb) => $jb->where('nama', 'like', "%{$search}%"));
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
                    ->getStateUsing(fn (BerkasRuangan $r) => $r->labelStatusEfektif())
                    ->color(fn (BerkasRuangan $r) => $r->warnaStatusEfektif()),
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
                        'hampir_habis' => 'Hampir Habis (<= 60 Hari)',
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
                            return $query->where('status_verifikasi', BerkasRuangan::STATUS_DISETUJUI)
                                ->whereNotNull('tgl_berakhir')
                                ->whereDate('tgl_berakhir', '>=', now()->toDateString())
                                ->whereDate('tgl_berakhir', '<=', now()->addDays(60)->toDateString());
                        }
                        return $query->where('status_verifikasi', $val);
                    }),
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit / Ruangan')
                    ->relationship('unit', 'nama'),
                Tables\Filters\SelectFilter::make('jenis_berkas_ruangan_id')
                    ->label('Jenis Berkas')
                    ->relationship('jenisBerkasRuangan', 'nama'),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_file')
                    ->label('Lihat')
                    ->modalHeading(fn (BerkasRuangan $r) => ($r->judul_dokumen ?: ($r->jenisBerkasRuangan?->nama ?: 'Berkas Ruangan')) . ' - ' . ($r->unit?->nama ?: ''))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (BerkasRuangan $r) => DocumentPreview::view(
                        url: route('file.berkas-ruangan', $r),
                        judul: $r->judul_dokumen ?: ($r->jenisBerkasRuangan?->nama ?: 'Berkas Ruangan'),
                        subjudul: trim(($r->unit?->nama ?: '-') . ($r->jenisBerkasRuangan?->nama ? ' | ' . $r->jenisBerkasRuangan->nama : '') . ($r->nomor_dokumen ? ' | No: ' . $r->nomor_dokumen : ''), ' |'),
                        namaFile: $r->nama_file_asli ?: basename((string) $r->file_path),
                        filePath: $r->file_path,
                    )),
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BerkasRuangan $r) => $r->status_verifikasi !== BerkasRuangan::STATUS_DISETUJUI)
                    ->action(function (BerkasRuangan $r) {
                        $r->update([
                            'status_verifikasi' => BerkasRuangan::STATUS_DISETUJUI,
                            'catatan_verifikator' => null,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                        ActivityLog::catat('verifikasi_setujui_berkas_ruangan', $r, $r->unit?->nama . ' - ' . $r->jenisBerkasRuangan?->nama);
                        Notification::make()->title('Berkas ruangan disetujui')->success()->send();
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
                    ->action(function (BerkasRuangan $r, array $data) {
                        $r->update([
                            'status_verifikasi' => BerkasRuangan::STATUS_DITOLAK,
                            'catatan_verifikator' => $data['catatan_verifikator'],
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                        ActivityLog::catat('verifikasi_tolak_berkas_ruangan', $r, $r->unit?->nama . ' - ' . $data['catatan_verifikator']);
                        Notification::make()->title('Berkas ruangan dikembalikan untuk revisi')->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBerkasRuangans::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
