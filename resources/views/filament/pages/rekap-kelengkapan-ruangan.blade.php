<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300">
                <span>Total Unit / Ruangan: <strong>{{ $totalUnit }}</strong></span>
                <span>Rata-rata Kelengkapan: <strong>{{ $rataPersen }}%</strong></span>
                <span>Lengkap 100%: <strong>{{ $lengkapSeratus }}</strong></span>
                <span>Ada Revisi / Expired / Hampir Habis: <strong>{{ $perluPerhatian }}</strong></span>
            </div>
            <x-filament::button tag="a" size="sm" color="primary" href="{{ $exportUrl }}">
                Ekspor CSV Rekap Ruangan
            </x-filament::button>
        </div>

        <div class="mt-4 max-w-md">
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Cari Unit / Ruangan / Bidang</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="cari"
                placeholder="Contoh: RANAP atau Instalasi Gawat Darurat"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            />
        </div>
    </x-filament::section>

    <x-filament::section>
        @if ($rows->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Tidak ada unit atau ruangan yang cocok dengan kata kunci pencarian.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs uppercase text-gray-600 dark:border-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2.5">Unit / Ruangan</th>
                            <th class="px-3 py-2.5">Bidang</th>
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
                                $u = $row['unit'];
                                $r = $row['ringkasan'];
                                $warna = $r['persen'] >= 100.0 ? 'success' : ($r['persen'] >= 60.0 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $u->nama }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Kode: {{ $u->kode }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-gray-700 dark:text-gray-300">{{ $u->bidang ?: '-' }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['wajib'] }}</td>
                                <td class="px-3 py-2.5 text-center font-medium text-teal-700 dark:text-teal-400">{{ $r['disetujui'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['menunggu'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['ditolak'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['belum_upload'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $r['hampir_habis'] }} / {{ $r['kedaluwarsa'] }}</td>
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
