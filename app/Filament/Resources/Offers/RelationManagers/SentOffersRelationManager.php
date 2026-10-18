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

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('15s')
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('Компанија'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('recipient_email')
                    ->label(__('Е-пошта'))
                    ->visibleFrom('md')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('Статус'))
                    ->badge(),
                TextColumn::make('sent_at')
                    ->label(__('Испратена'))
                    ->visibleFrom('sm')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                TextColumn::make('opened_at')
                    ->label(__('Отворена'))
                    ->visibleFrom('lg')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                TextColumn::make('error_message')
                    ->label(__('Грешка'))
                    ->visibleFrom('lg')
                    ->limit(60)
                    ->tooltip(fn (SentOffer $record): ?string => $record->error_message)
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label(__('Повтори'))
                    ->visible(fn (SentOffer $record): bool => $record->status === OfferStatus::Failed)
                    ->requiresConfirmation()
                    ->action(function (SentOffer $record): void {
                        $ok = app(SendOfferService::class)->retry($record);

                        Notification::make()
                            ->title($ok ? __('Понудата е испратена') : __('Испраќањето повторно не успеа'))
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('Сè уште нема испраќања'))
            ->emptyStateDescription(__('Користете „Испрати понуда“ на страницата на понудата.'))
            ->paginated([10, 25, 50]);
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Испраќања');
    }
}
