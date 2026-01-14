<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\SectorEnum;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('sector'),
                Tables\Columns\TextColumn::make('city')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sector')
                    ->options(SectorEnum::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
