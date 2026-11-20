<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username, NIP, atau Email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->placeholder(null);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['login'] ?? ''));

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => strtolower($login),
                'password' => $data['password'] ?? '',
                'aktif' => true,
            ];
        }

        if ($login !== '' && ! User::query()->where('nip', $login)->exists()) {
            $emailKandidat = strtolower($login) . '@rs.local';
            if (User::query()->where('email', $emailKandidat)->exists()) {
                return [
                    'email' => $emailKandidat,
                    'password' => $data['password'] ?? '',
                    'aktif' => true,
                ];
            }
        }

        return [
            'nip' => $login,
            'password' => $data['password'] ?? '',
            'aktif' => true,
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'Username/NIP/Email atau kata sandi salah, atau akun tidak aktif.',
        ]);
    }
}
