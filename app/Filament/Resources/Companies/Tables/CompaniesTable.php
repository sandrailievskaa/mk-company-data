<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\SectorEnum;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Име на компанија')->searchable(),
                Tables\Columns\TextColumn::make('sector')->label('Сектор')->searchable(),
                Tables\Columns\TextColumn::make('city')->label('Град')->searchable(),
                Tables\Columns\TextColumn::make('address')->label('Адреса')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Е-пошта')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Телефонски број')->searchable(),
                Tables\Columns\TextColumn::make('activity_index')
                    ->label('Индекс на активност')
                    ->sortable()
                    ->default(0),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sector')
                    ->label('Сектор')
                    ->options(SectorEnum::class),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Град')
                    ->options(function () {
                        return Company::query()
                            ->whereNotNull('city')
                            ->where('city', '!=', '')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city')
                            ->toArray();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Уреди'),
            ])
            ->toolbarActions([]);
    }
}
