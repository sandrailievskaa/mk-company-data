<?php

namespace App\Filament\Resources\Offers\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Наслов')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->iconColor('primary')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label('Компанија')
                    ->icon(Heroicon::OutlinedBuildingOffice)
                    ->iconColor('primary')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->icon(Heroicon::OutlinedCalendar)
                    ->iconColor('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->icon(Heroicon::OutlinedClock)
                    ->iconColor('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед')
                    ->icon(Heroicon::OutlinedEye),
                EditAction::make()
                    ->label('Уреди')
                    ->icon(Heroicon::OutlinedPencil),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->striped();
    }
}
