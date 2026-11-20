<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GantiPasswordPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Ruang Saya';
    protected static ?string $navigationLabel = 'Profil & Kata Sandi';
    protected static ?string $title = 'Profil Akun & Ganti Kata Sandi';
    protected static ?string $slug = 'ganti-password';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.ganti-password';

    public ?array $data = [];
    public ?array $profilData = [];

    protected function getForms(): array
    {
        return ['form', 'profilForm'];
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->form->fill();
        $this->profilForm->fill([
            'name' => $user->name,
            'email' => $user->email,
            'nip' => $user->nip,
            'no_hp' => $user->no_hp,
        ]);
    }

    public function profilForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profil Akun Saya')
                    ->description('Anda dapat melengkapi NIP serta mengubah email login atau nomor HP pribadi secara mandiri.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP / NIK Pegawai')
                            ->placeholder('Isi NIP Anda agar bisa login dengan NIP')
                            ->maxLength(50)
                            ->rule(fn () => Rule::unique('users', 'nip')->ignore(Auth::id())),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Login')
                            ->email()
                            ->required()
                            ->maxLength(191)
                            ->rule(fn () => Rule::unique('users', 'email')->ignore(Auth::id())),
                        Forms\Components\TextInput::make('no_hp')
                            ->label('Nomor WhatsApp / HP')
                            ->maxLength(30),
                    ]),
            ])
            ->statePath('profilData');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Perbarui Kata Sandi')
                    ->description('Gunakan minimal 8 karakter dan berbeda dari kata sandi sebelumnya.')
                    ->schema([
                        Forms\Components\TextInput::make('password_lama')
                            ->label('Kata Sandi Saat Ini')
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(),
                        Forms\Components\TextInput::make('password_baru')
                            ->label('Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::min(8))
                            ->different('password_lama')
                            ->same('password_baru_confirmation'),
                        Forms\Components\TextInput::make('password_baru_confirmation')
                            ->label('Konfirmasi Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function simpanProfil(): void
    {
        $state = $this->profilForm->getState();

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'email' => strtolower(trim((string) $state['email'])),
            'nip' => filled($state['nip'] ?? null) ? trim((string) $state['nip']) : null,
            'no_hp' => filled($state['no_hp'] ?? null) ? trim((string) $state['no_hp']) : null,
        ]);
        Auth::setUser($user->fresh());

        ActivityLog::catat('perbarui_profil', $user, 'Memperbarui email/NIP/no HP akun');

        Notification::make()
            ->title('Profil akun berhasil diperbarui')
            ->success()
            ->send();
    }

    public function simpan(): void
    {
        $state = $this->form->getState();

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'password' => $state['password_baru'],
            'wajib_ganti_password' => false,
        ]);
        $user = $user->fresh();
        Auth::setUser($user);

        if (request()->hasSession()) {
            request()->session()->put('password_hash_' . Auth::getDefaultDriver(), $user->getAuthPassword());
        }

        ActivityLog::catat('ganti_password', $user, 'Mengganti kata sandi akun');

        $this->form->fill();

        Notification::make()
            ->title('Kata sandi berhasil diperbarui')
            ->success()
            ->send();

        $this->redirect('/portal');
    }
}
