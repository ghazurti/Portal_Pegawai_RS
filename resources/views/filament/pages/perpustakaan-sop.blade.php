<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 pb-3 dark:border-gray-700">
            <x-filament::button
                size="sm"
                :color="$cakupanTab === 'semua_tampil' ? 'primary' : 'gray'"
                wire:click="$set('cakupanTab', 'semua_tampil')"
            >
                Semua Dokumen ({{ $totalTersedia }})
            </x-filament::button>
            <x-filament::button
                size="sm"
                :color="$cakupanTab === 'umum' ? 'primary' : 'gray'"
                wire:click="$set('cakupanTab', 'umum')"
            >
                Dokumen RS (Umum) ({{ $totalUmum }})
            </x-filament::button>
            <x-filament::button
                size="sm"
                :color="$cakupanTab === 'ruangan' ? 'primary' : 'gray'"
                wire:click="$set('cakupanTab', 'ruangan')"
            >
                Khusus Ruangan Saya: {{ $namaUnitUser }} ({{ $totalRuangan }})
            </x-filament::button>
        </div>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Total dapat diakses: <strong>{{ $totalTersedia }}</strong> |
                Sudah dibaca: <strong>{{ $totalSudahBaca }}</strong>
            </div>
            @if ($cakupanTab !== 'semua_tampil' || $cari !== '' || $kategoriId !== '' || $pokja !== '' || $statusBaca !== '')
                <x-filament::button size="xs" color="gray" wire:click="resetFilter">
                    Reset Filter
                </x-filament::button>
            @endif
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Cari Judul / Nomor</label>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="cari"
                    placeholder="Contoh: Cuci Tangan atau SOP-MRMIK"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                <select
                    wire:model.live="kategoriId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoris as $kat)
                        <option value="{{ $kat->id }}">{{ $kat->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Pokja Akreditasi</label>
                <select
                    wire:model.live="pokja"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Semua Pokja</option>
                    @foreach ($pokjaList as $pk)
                        <option value="{{ $pk }}">{{ $pk }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status Baca</label>
                <select
                    wire:model.live="statusBaca"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Semua Status</option>
                    <option value="belum">Belum Dibaca</option>
                    <option value="sudah">Sudah Dibaca</option>
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($dokumens->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Tidak ada dokumen berlaku pada tab/filter ini untuk unit {{ $namaUnitUser }}. Coba pindah ke tab Semua Dokumen atau klik Reset Filter.
            </p>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach ($dokumens as $dok)
                @php
                    $baca = $bacaMap->get($dok->versi_aktif_id);
                @endphp
                <x-filament::section>
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-filament::badge color="primary">
                                    {{ $dok->kategoriDokumen?->nama ?: 'Dokumen' }}
                                </x-filament::badge>
                                @if ($dok->pokja_akreditasi)
                                    <x-filament::badge color="gray">
                                        Pokja {{ $dok->pokja_akreditasi }}
                                    </x-filament::badge>
                                @endif
                                <x-filament::badge :color="$dok->cakupan_akses === 'semua' ? 'info' : 'warning'">
                                    {{ $dok->cakupan_akses === 'semua' ? 'Dokumen RS (Semua Pegawai)' : 'Khusus Unit' }}
                                </x-filament::badge>
                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                    {{ $dok->nomor_dokumen }} ({{ $dok->versiAktif?->nomor_versi ?: 'v1.0' }})
                                </span>
                            </div>

                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $dok->judul }}
                            </h3>

                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                Terbit: {{ $dok->tgl_terbit?->format('d-m-Y') ?: '-' }}
                                | Unit Pemilik: {{ $dok->unitPemilik?->nama ?: 'Rumah Sakit' }}
                                @if ($dok->tgl_review)
                                    | Review Berikutnya: {{ $dok->tgl_review->format('d-m-Y') }}
                                @endif
                            </p>

                            @if ($dok->ringkasan)
                                <p class="pt-1 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $dok->ringkasan }}
                                </p>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            {{ ($this->previewSopAction)(['dokumenId' => $dok->id]) }}

                            @if ($baca)
                                <x-filament::badge color="success">
                                    Sudah Dibaca ({{ $baca->dibaca_pada?->format('d-m-Y H:i') }})
                                </x-filament::badge>
                            @else
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    wire:click="tandaiSudahDibaca({{ $dok->id }})"
                                >
                                    Tandai Sudah Dibaca
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
