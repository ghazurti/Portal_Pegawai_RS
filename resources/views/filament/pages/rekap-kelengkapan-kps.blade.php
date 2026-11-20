<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300">
                <span>Pegawai Aktif: <strong>{{ $totalPegawai }}</strong></span>
                <span>Rata-rata Kelengkapan: <strong>{{ $rataPersen }}%</strong></span>
                <span>Lengkap 100%: <strong>{{ $lengkapSeratus }}</strong></span>
                <span>Ada Revisi / Expired / Hampir Habis: <strong>{{ $perluPerhatian }}</strong></span>
            </div>
            <x-filament::button tag="a" size="sm" color="primary" href="{{ $exportUrl }}">
                Ekspor CSV Rekap KPS
            </x-filament::button>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Cari Nama / NIP / Jabatan</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="cari"
                    placeholder="Ketik NIP atau nama pegawai..."
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Filter Unit</label>
                @if ($khususKaru)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Khusus Ruangan Anda: {{ $namaUnitKaru }}
                    </div>
                @else
                    <select
                        wire:model.live="unitId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}">{{ $u->nama }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Filter Profesi</label>
                <select
                    wire:model.live="profesiId"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Semua Profesi</option>
                    @foreach ($profesis as $pr)
                        <option value="{{ $pr->id }}">{{ $pr->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        @if ($rows->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Tidak ada pegawai aktif yang sesuai dengan filter. Tambahkan pegawai di menu Master Pegawai atau ubah filter unit/profesi.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs uppercase text-gray-600 dark:border-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2.5">NIP & Nama</th>
                            <th class="px-3 py-2.5">Unit / Profesi</th>
                            <th class="px-3 py-2.5 text-center">Wajib</th>
                            <th class="px-3 py-2.5 text-center">Disetujui</th>
                            <th class="px-3 py-2.5 text-center">Menunggu</th>
                            <th class="px-3 py-2.5 text-center">Revisi</th>
                            <th class="px-3 py-2.5 text-center">Belum Upload</th>
                            <th class="px-3 py-2.5 text-center">Hampir Habis / Exp</th>
                            <th class="px-3 py-2.5 text-right">% Lengkap</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($rows as $row)
                            @php
                                $u = $row['user'];
                                $r = $row['ringkasan'];
                                $warna = $r['persen'] >= 100.0 ? 'success' : ($r['persen'] >= 60.0 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $u->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $u->nip ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-gray-700 dark:text-gray-300">
                                    <div>{{ $u->unit?->nama ?: '-' }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">{{ $u->kategoriProfesi?->nama ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-center">{{ $r['wajib'] }}</td>
                                <td class="px-3 py-2.5 text-center font-medium text-teal-700 dark:text-teal-400">{{ $r['disetujui'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['menunggu'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['ditolak'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['belum_upload'] }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    {{ $r['hampir_habis'] }} / {{ $r['kedaluwarsa'] }}
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <x-filament::badge :color="$warna">{{ $r['persen'] }}%</x-filament::badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
