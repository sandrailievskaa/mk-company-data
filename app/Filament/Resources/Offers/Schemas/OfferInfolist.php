<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Enums\OfferStatus;
use App\Enums\SectorEnum;
use App\Models\Offer;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use ValueError;

class OfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                ViewEntry::make('offer_view_nav')
                    ->view('filament.offers.view-offer-nav')
                    ->columnSpanFull(),

                Section::make()
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mk-offer-view-hero'])
                    ->components([
                        Flex::make([
                            TextEntry::make('id')
                                ->hiddenLabel()
                                ->formatStateUsing(fn ($state): string => 'ПОН-'.str_pad((string) $state, 3, '0', STR_PAD_LEFT))
                                ->badge()
                                ->color(Color::Zinc),
                            TextEntry::make('company.sector')
                                ->hiddenLabel()
                                ->badge()
                                ->formatStateUsing(function ($state): ?string {
                                    if ($state instanceof SectorEnum) {
                                        return (string) $state->getLabel();
                                    }
                                    if (is_string($state) && $state !== '') {
                                        try {
                                            return (string) SectorEnum::from($state)->getLabel();
                                        } catch (ValueError) {
                                            return null;
                                        }
                                    }

                                    return null;
                                })
                                ->placeholder('—'),
                            TextEntry::make('ai_chip')
                                ->hiddenLabel()
                                ->state(fn (Offer $record): string => '✦ AI содржина')
                                ->icon(Heroicon::OutlinedSparkles)
                                ->badge()
                                ->color(Color::Violet),
                        ])
                            ->from('sm')
                            ->extraAttributes(['class' => 'mk-offer-view-hero__tags']),

                        TextEntry::make('title')
                            ->hiddenLabel()
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->extraAttributes(['class' => 'mk-offer-view-hero__title']),

                        TextEntry::make('content_excerpt')
                            ->hiddenLabel()
                            ->state(fn (Offer $record): string => Str::limit(
                                trim(preg_replace('/\s+/', ' ', strip_tags((string) ($record->content ?? ''))) ?? ''),
                                280,
                            ))
                            ->extraAttributes(['class' => 'mk-offer-view-hero__excerpt']),

                        Grid::make(['default' => 2, 'sm' => 3, 'lg' => 5])
                            ->extraAttributes(['class' => 'mk-offer-view-hero__metrics'])
                            ->schema([
                                TextEntry::make('target_count')
                                    ->label('Таргети')
                                    ->state(fn (Offer $record): int => $record->targets()->count()),
                                TextEntry::make('company.name')
                                    ->label('Компанија')
                                    ->placeholder('—'),
                                TextEntry::make('status')
                                    ->label('Статус')
                                    ->formatStateUsing(function ($state): string {
                                        if ($state instanceof OfferStatus) {
                                            return (string) $state->getLabel();
                                        }

                                        return (string) OfferStatus::from((string) $state)->getLabel();
                                    })
                                    ->badge()
                                    ->color(function ($state) {
                                        $s = $state instanceof OfferStatus ? $state : OfferStatus::from((string) $state);

                                        return match ($s) {
                                            OfferStatus::Pending => Color::Amber,
                                            OfferStatus::Sent => Color::Green,
                                            OfferStatus::Failed => Color::Red,
                                            OfferStatus::Opened => Color::Blue,
                                        };
                                    }),
                                TextEntry::make('created_at')
                                    ->label('Креирано')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),
                                TextEntry::make('updated_at')
                                    ->label('Ажурирано')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),
                            ]),
                    ]),

                Section::make('Содржина')
                    ->description('Целосен текст на понудата')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mk-offer-view-narrative'])
                    ->components([
                        TextEntry::make('content')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->html()
                            ->prose()
                            ->formatStateUsing(fn ($state) => (string) ($state ?? '')),
                    ]),
            ]);
    }
}
