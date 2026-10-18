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
                    ->label(__('Наслов'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->iconColor('primary')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label(__('Компанија'))
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
                    ->label(__('Преглед'))
                    ->icon(Heroicon::OutlinedEye),
                EditAction::make()
                    ->label(__('Уреди'))
                    ->icon(Heroicon::OutlinedPencil),
            ])
            ->emptyStateHeading(__('Нема понуди'))
            ->emptyStateDescription(__('Креирајте понуда и прегледајте ги препорачаните компании.'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->striped();
    }
}
