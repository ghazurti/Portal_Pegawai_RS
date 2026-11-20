<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
        <div class="min-w-0 space-y-0.5">
            @if ($subjudul)
                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $subjudul }}</div>
            @endif
            @if ($namaFile)
                <div class="truncate font-mono text-xs text-gray-500 dark:text-gray-400">{{ $namaFile }}</div>
            @endif
        </div>

        <x-filament::button
            tag="a"
            size="xs"
            color="gray"
            href="{{ $url }}"
            target="_blank"
            rel="noopener"
        >
            Buka di Tab Baru
        </x-filament::button>
    </div>

    @if ($isImage)
        <div class="flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
            <img
                src="{{ $url }}"
                alt="{{ $judul }}"
                class="rounded-lg shadow-sm"
                style="max-height: {{ $heightCss }}; max-width: 100%; width: auto; height: auto; display: block; margin: 0 auto; object-fit: contain;"
            />
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
            <iframe
                src="{{ $url }}#view=FitH&toolbar=1&navpanes=0"
                title="Pratinjau {{ $judul }}"
                style="width: 100%; height: {{ $heightCss }}; min-height: 560px; display: block; border: 0; background: #ffffff;"
            ></iframe>
        </div>
    @endif

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Pratinjau tampil langsung di dalam aplikasi tanpa perlu mengunduh file. Jika browser memblokir tampilan PDF tertanam, klik Buka di Tab Baru.
    </p>
</div>
