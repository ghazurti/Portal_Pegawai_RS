<x-filament-panels::page>
    @if (! $unit)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Akun Anda belum terhubung ke Unit / Ruangan mana pun. Hubungi SDM atau Admin IT untuk mengatur ruangan Anda.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $unit->nama }} ({{ $unit->kode }})
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bidang: {{ $unit->bidang ?: '-' }} |
                        @if ($bisaUpload)
                            Anda memiliki akses mengunggah dan memperbarui berkas ruangan ini.
                        @else
                            Mode baca (pengunggahan berkas ruangan dikelola Kepala Ruangan / Tim Mutu).
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @if ($unitsPilihan->isNotEmpty())
                        <select
                            wire:model.live="selectedUnitId"
                            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            @foreach ($unitsPilihan as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->nama }}</option>
                            @endforeach
                        </select>
                    @endif
                    <x-filament::badge :color="$ringkasan['persen'] >= 100.0 ? 'success' : ($ringkasan['ditolak'] > 0 || $ringkasan['kedaluwarsa'] > 0 ? 'danger' : 'warning')">
                        {{ $ringkasan['disetujui'] }} / {{ $ringkasan['wajib'] }} Berkas Disetujui ({{ $ringkasan['persen'] }}%)
                    </x-filament::badge>
                </div>
            </div>

            <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-2.5 rounded-full bg-teal-600 transition-all"
                    style="width: {{ min(100, max(0, $ringkasan['persen'])) }}%"
                ></div>
            </div>

            <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400">
                <span>Menunggu Verifikasi: <strong>{{ $ringkasan['menunggu'] }}</strong></span>
                <span>Perlu Revisi: <strong>{{ $ringkasan['ditolak'] }}</strong></span>
                <span>Belum Upload: <strong>{{ $ringkasan['belum_upload'] }}</strong></span>
                <span>Hampir Habis: <strong>{{ $ringkasan['hampir_habis'] }}</strong></span>
                <span>Kedaluwarsa: <strong>{{ $ringkasan['kedaluwarsa'] }}</strong></span>
            </div>
        </x-filament::section>

        <x-filament::section heading="Daftar Berkas Wajib Ruangan / Unit">
            @if ($items->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Belum ada daftar jenis berkas wajib ruangan. Hubungi Tim Mutu untuk mengaturnya di Master Berkas Ruangan.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs uppercase text-gray-600 dark:border-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2.5">Jenis Berkas Ruangan</th>
                                <th class="px-3 py-2.5">Nomor & Masa Berlaku</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5">Catatan Tim Mutu</th>
                                <th class="px-3 py-2.5 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($items as $item)
                                @php
                                    $jb = $item['jenis'];
                                    $b = $item['berkas'];
                                    $list = $item['berkas_list'];
                                @endphp

                                @if ($jb->boleh_banyak)
                                    <tr>
                                        <td colspan="5" class="px-2 py-3">
                                            <div class="rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                                <div class="flex flex-col gap-2 border-b border-gray-200 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ $jb->nama }}</span>
                                                            <x-filament::badge color="info">Multi-berkas</x-filament::badge>
                                                            <x-filament::badge color="gray">{{ $list->count() }} berkas</x-filament::badge>
                                                            @if ($item['terpenuhi'])
                                                                <x-filament::badge color="success">Terpenuhi</x-filament::badge>
                                                            @elseif ($list->isEmpty())
                                                                <x-filament::badge color="gray">Belum Upload</x-filament::badge>
                                                            @else
                                                                <x-filament::badge color="warning">Menunggu Persetujuan</x-filament::badge>
                                                            @endif
                                                        </div>
                                                        <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $jb->wajib_semua_unit ? 'Wajib seluruh unit' : 'Wajib unit tertentu' }}
                                                            @if ($jb->memiliki_masa_berlaku)
                                                                | Pengingat {{ $jb->hari_pengingat }} hari sebelum habis
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if ($bisaUpload)
                                                        <div class="shrink-0">
                                                            {{ ($this->tambahBerkasRuanganAction)(['unitId' => $unit->id, 'jenisId' => $jb->id]) }}
                                                        </div>
                                                    @endif
                                                </div>

                                                @if ($list->isEmpty())
                                                    <p class="px-3 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                                        Belum ada berkas ruangan diunggah.
                                                    </p>
                                                @else
                                                    <div class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-950">
                                                        @foreach ($list as $bItem)
                                                            <div class="flex flex-col gap-2 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                                                                <div class="min-w-0 space-y-0.5">
                                                                    <div class="font-medium text-gray-900 dark:text-white">
                                                                        {{ $bItem->judul_dokumen ?: ($bItem->nama_file_asli ?: 'Dokumen tanpa judul') }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                                                        No: {{ $bItem->nomor_dokumen ?: '-' }}
                                                                        @if ($bItem->tgl_terbit)
                                                                            | Terbit: {{ $bItem->tgl_terbit->format('d-m-Y') }}
                                                                        @endif
                                                                        @if ($jb->memiliki_masa_berlaku)
                                                                            | Berlaku s.d.: {{ $bItem->tgl_berakhir?->format('d-m-Y') ?: '-' }}
                                                                        @endif
                                                                        @if ($bItem->catatan_verifikator)
                                                                            | Catatan: {{ $bItem->catatan_verifikator }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="flex shrink-0 flex-wrap items-center gap-2">
                                                                    <x-filament::badge :color="$bItem->warnaStatusEfektif()">
                                                                        {{ $bItem->labelStatusEfektif() }}
                                                                    </x-filament::badge>
                                                                    {{ ($this->previewBerkasRuanganAction)(['berkasRuanganId' => $bItem->id]) }}
                                                                    @if ($bisaUpload)
                                                                        {{ ($this->editBerkasRuanganAction)(['berkasRuanganId' => $bItem->id]) }}
                                                                        {{ ($this->hapusBerkasRuanganAction)(['berkasRuanganId' => $bItem->id]) }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td class="px-3 py-3 align-top">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $jb->nama }}</div>
                                            @if ($b?->judul_dokumen)
                                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $b->judul_dokumen }}</div>
                                            @endif
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $jb->wajib_semua_unit ? 'Wajib seluruh unit' : 'Wajib unit tertentu' }}
                                                @if ($jb->memiliki_masa_berlaku)
                                                    | Pengingat {{ $jb->hari_pengingat }} hari sebelum habis
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-top text-xs text-gray-700 dark:text-gray-300">
                                            <div>No: {{ $b?->nomor_dokumen ?: '-' }}</div>
                                            @if ($jb->memiliki_masa_berlaku)
                                                <div>Berlaku s.d.: {{ $b?->tgl_berakhir?->format('d-m-Y') ?: '-' }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <x-filament::badge :color="$item['status_warna']">
                                                {{ $item['status_label'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-3 py-3 align-top text-xs text-gray-700 dark:text-gray-300">
                                            {{ $b?->catatan_verifikator ?: '-' }}
                                        </td>
                                        <td class="px-3 py-3 align-top text-right">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @if ($b)
                                                    {{ ($this->previewBerkasRuanganAction)(['berkasRuanganId' => $b->id]) }}
                                                @endif
                                                @if ($bisaUpload)
                                                    {{ ($this->uploadBerkasRuanganAction)(['unitId' => $unit->id, 'jenisId' => $jb->id]) }}
                                                    @if ($b)
                                                        {{ ($this->hapusBerkasRuanganAction)(['berkasRuanganId' => $b->id]) }}
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
