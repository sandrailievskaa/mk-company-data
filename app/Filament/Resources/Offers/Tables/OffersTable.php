<?php

namespace App\Filament\Resources\Offers\Tables;

use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Наслов')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Предмет')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sent_offers_count')
                    ->label('Испратени')
                    ->counts('sentOffers')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Креирана')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна')
                    ->placeholder('Сите')
                    ->trueLabel('Само активни')
                    ->falseLabel('Само неактивни'),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('send')
                    ->label('Испрати')
                    ->icon('heroicon-o-paper-airplane')
                    ->url(fn ($record) => \App\Filament\Resources\Offers\OfferResource::getUrl('send', ['record' => $record]))
                    ->color('success'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
