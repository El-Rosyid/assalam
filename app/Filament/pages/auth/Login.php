<?php
namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function getHeading(): string
{
    return 'Selamat Datang';
}

public function getSubHeading(): string
{
    return 'Silakan masuk ke akun Anda';
}

public function getTitle(): string
{
    return '';
}

public function hasLogo(): bool
{
    return false;
}

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username') // Ganti dari email ke username
                    ->label('Username')
                    ->required()
                    ->autofocus(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }
    
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'], // Ubah ke username
            'password' => $data['password'],
            
        ];
    }

    public function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
