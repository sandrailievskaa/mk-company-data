<?php

namespace App\Filament\Resources\Offers\RelationManagers;

use App\Enums\OfferStatus;
use App\Models\SentOffer;
use App\Services\Offers\SendOfferService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SentOffersRelationManager extends RelationManager
{
    protected static string $relationship = 'sentOffers';

    protected static ?string $title = 'Испраќања';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('15s')
            ->columns([
                TextColumn::make('company.name')
                    ->label('Компанија')
                    ->searchable(),
                TextColumn::make('recipient_email')
                    ->label('Е-пошта')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('sent_at')
                    ->label('Испратена')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                TextColumn::make('opened_at')
                    ->label('Отворена')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                TextColumn::make('error_message')
                    ->label('Грешка')
                    ->limit(60)
                    ->tooltip(fn (SentOffer $record): ?string => $record->error_message)
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Повтори')
                    ->visible(fn (SentOffer $record): bool => $record->status === OfferStatus::Failed)
                    ->requiresConfirmation()
                    ->action(function (SentOffer $record): void {
                        $ok = app(SendOfferService::class)->retry($record);

                        Notification::make()
                            ->title($ok ? 'Понудата е испратена' : 'Испраќањето повторно не успеа')
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Сè уште нема испраќања')
            ->emptyStateDescription('Користете „Испрати понуда“ на страницата на понудата.')
            ->paginated([10, 25, 50]);
    }
}
