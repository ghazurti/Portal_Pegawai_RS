<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class GantiPasswordPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Ruang Saya';
    protected static ?string $navigationLabel = 'Ganti Kata Sandi';
    protected static ?string $title = 'Ganti Kata Sandi Akun';
    protected static ?string $slug = 'ganti-password';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.ganti-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
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
