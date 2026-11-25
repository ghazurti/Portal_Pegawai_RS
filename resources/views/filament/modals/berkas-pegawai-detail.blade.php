<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800/60">
        <div class="space-y-0.5">
            <div class="font-semibold text-gray-900 dark:text-white">{{ $pegawai->name }}</div>
            <div class="text-xs text-gray-600 dark:text-gray-300">
                NIP: {{ $pegawai->nip ?: '-' }} &middot;
                Status: <strong>{{ $pegawai->labelStatusKepegawaian() }}</strong> &middot;
                Unit: {{ $pegawai->unit?->nama ?: '-' }} &middot;
                Profesi: {{ $pegawai->kategoriProfesi?->nama ?: '-' }}
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-500 dark:text-gray-400">Kelengkapan Wajib</div>
            <div class="text-lg font-bold text-teal-600 dark:text-teal-400">
                {{ $ringkasan['disetujui'] }}/{{ $ringkasan['wajib'] }} ({{ number_format($ringkasan['persen'], 1) }}%)
            </div>
        </div>
    </div>

    @if ($wajibList->isEmpty())
        <p class="text-sm text-gray-500">Belum ada berkas wajib untuk status/profesi pegawai ini.</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">Jenis Berkas</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">File Terunggah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($wajibList as $jb)
                        @php
                            $files = $berkasGroups->get($jb->id, collect());
                        @endphp
                        <tr>
                            <td class="px-3 py-2.5 align-top">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $jb->nama }}</div>
                                <div class="text-xs text-gray-500">{{ $jb->kode }}</div>
                            </td>
                            <td class="px-3 py-2.5 align-top">
                                @if ($files->isEmpty())
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Belum Upload</span>
                                @else
                                    @foreach ($files as $f)
                                        @php
                                            $f->setRelation('jenisBerkas', $jb);
                                            $st = $f->statusEfektif();
                                            $badge = match($st) {
                                                'disetujui' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                'hampir_habis' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                                'menunggu' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                                default => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                            };
                                            $lbl = match($st) {
                                                'disetujui' => 'Disetujui',
                                                'hampir_habis' => 'Hampir Habis',
                                                'menunggu' => 'Menunggu',
                                                'kedaluwarsa' => 'Kedaluwarsa',
                                                'ditolak' => 'Perlu Revisi',
                                                default => ucfirst($st),
                                            };
                                        @endphp
                                        <div class="mb-1">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ $lbl }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-3 py-2.5 align-top">
                                @if ($files->isEmpty())
                                    <span class="text-xs text-gray-400">-</span>
                                @else
                                    <div class="space-y-1.5">
                                        @foreach ($files as $f)
                                            <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 px-2.5 py-1.5 text-xs dark:border-gray-700">
                                                <div>
                                                    <div class="font-medium text-gray-800 dark:text-gray-200">
                                                        {{ $f->judul_dokumen ?: ($f->nama_file_asli ?: basename((string) $f->file_path)) }}
                                                    </div>
                                                    @if ($f->nomor_dokumen || $f->tgl_berakhir)
                                                        <div class="text-[11px] text-gray-500">
                                                            @if ($f->nomor_dokumen) No: {{ $f->nomor_dokumen }} @endif
                                                            @if ($f->tgl_berakhir) &middot; Exp: {{ $f->tgl_berakhir->format('d-m-Y') }} @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                <a
                                                    href="{{ route('file.berkas', $f) }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="inline-flex items-center rounded bg-teal-600 px-2 py-1 text-xs font-medium text-white hover:bg-teal-700"
                                                >
                                                    Buka File
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
