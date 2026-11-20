<x-filament-panels::page>
    @if (auth()->user()?->wajib_ganti_password)
        <x-filament::section>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-300">
                Demi keamanan akun, Anda wajib mengganti kata sandi awal sebelum menggunakan menu lain di portal (Anda juga dapat sekaligus melengkapi NIP dan email Anda di bawah).
            </p>
        </x-filament::section>
    @endif

    <form wire:submit="simpanProfil" class="space-y-6">
        {{ $this->profilForm }}

        <div>
            <x-filament::button type="submit" color="gray">
                Simpan Profil Akun
            </x-filament::button>
        </div>
    </form>

    <form wire:submit="simpan" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Simpan Kata Sandi Baru
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
