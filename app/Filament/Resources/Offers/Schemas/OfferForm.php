<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Enums\SectorEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Компанија')
                    ->relationship(name: 'company', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->prefixIcon(Heroicon::OutlinedBuildingOffice),
                Textarea::make('additional_information')
                    ->label('Дополнителни информации')
                    ->helperText('Внесете дополнителни информации за понудата. Ова ќе се користи за генерирање на содржината на понудата.')
                    ->rows(5)
                    ->columnSpanFull(),

                TextInput::make('recommendation_title')
                    ->label('Наслов (за AI препораки)')
                    ->helperText('Се користи само за препораките и не се зачувува.')
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Select::make('recommendation_sector')
                    ->label('Сектор (за AI препораки)')
                    ->options(SectorEnum::class)
                    ->helperText('Секторот се користи за избор на топ 50 компании по activity_index.')
                    ->dehydrated(false)
                    ->prefixIcon(Heroicon::OutlinedBriefcase),

                SchemaActions::make([
                    Action::make('ai_recommend_companies')
                        ->label('AI препорачај компании')
                        ->action(function ($livewire) {
                            $livewire->generateCompanyRecommendations();
                        }),
                ]),

                ViewField::make('ai_recommendations_view')
                    ->label('Препораки')
                    ->dehydrated(false)
                    ->view('filament.offers.ai-recommend-companies')
                    ->columnSpanFull()
            ]);
    }
}
