<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                Toggle::make('change_password')
                    ->dehydrated(false)
                    ->live(),
                TextInput::make('password')
                    ->password()
                    ->visible(function (callable $get) {
                        return $get('change_password');
                    }),
            ]);
    }
}
