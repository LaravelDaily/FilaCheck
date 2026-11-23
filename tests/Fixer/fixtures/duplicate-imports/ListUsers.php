<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function table(Table $table): Table
    {
        $table = UserResource::table($table);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->latest());
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->whereNotNull('email_verified_at');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
