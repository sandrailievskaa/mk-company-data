<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Enums\OfferStatus;
use App\Enums\SectorEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 2])
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->extraAttributes(['class' => 'mk-offer-create-layout'])
                    ->schema([
                        Group::make([
                            ViewField::make('create_back_link')
                                ->dehydrated(false)
                                ->view('filament.offers.create-back-link')
                                ->columnSpanFull(),

                            Section::make('Чекор 1 · Детали за понудата')
                                ->description('Примарна компанија и краток контекст за AI.')
                                ->icon(Heroicon::OutlinedDocumentText)
                                ->components([
                                    Select::make('company_id')
                                        ->label('Компанија')
                                        ->relationship(name: 'company', titleAttribute: 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->prefixIcon(Heroicon::OutlinedBuildingOffice),
                                    Textarea::make('additional_information')
                                        ->label('Краток опис / контекст')
                                        ->helperText('Се користи за AI; не се зачувува како посебно поле.')
                                        ->rows(5)
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'mk-offer-form-section mk-offer-create__card']),

                            Section::make('✦ AI препорачани компании')
                                ->description('Кандидати по сектор (топ 50 по активност).')
                                ->icon(Heroicon::OutlinedSparkles)
                                ->components([
                                    TextInput::make('recommendation_title')
                                        ->label('Наслов (за препораки)')
                                        ->helperText('Не се зачувува; само за AI пребарување.')
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    Select::make('recommendation_sector')
                                        ->label('Сектор (за кандидати)')
                                        ->options(SectorEnum::class)
                                        ->helperText('Ако празно — се користи секторот на избраната компанија.')
                                        ->dehydrated(false)
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->columnSpanFull()
                                        ->prefixIcon(Heroicon::OutlinedBriefcase),
                                    ViewField::make('ai_recommendations_view')
                                        ->dehydrated(false)
                                        ->view('filament.offers.ai-recommend-companies')
                                        ->columnSpanFull(),
                                ])
                                ->headerActions([
                                    Action::make('ai_recommend_companies')
                                        ->label('Препорачај')
                                        ->icon(Heroicon::OutlinedPlay)
                                        ->action(fn ($livewire) => $livewire->generateCompanyRecommendations()),
                                ])
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'mk-offer-form-section mk-offer-form-section--ai mk-offer-create__card']),

                            Section::make('Наслов')
                                ->description('Краток наслов на понудата; AI може да го предложи.')
                                ->icon(Heroicon::OutlinedHashtag)
                                ->components([
                                    TextInput::make('title')
                                        ->hiddenLabel()
                                        ->required()
                                        ->maxLength(500)
                                        ->columnSpanFull(),
                                    SchemaActions::make([
                                        Action::make('generate_offer_content')
                                            ->label('✦ Генерирај со AI')
                                            ->icon(Heroicon::OutlinedSparkles)
                                            ->action(fn ($livewire) => $livewire->generateOfferContent()),
                                    ])
                                        ->alignment(Alignment::Start)
                                        ->fullWidth()
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'mk-offer-create-gen-ai']),
                                ])
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'mk-offer-form-section mk-offer-create__card']),
                        ])
                            ->columns(1)
                            ->columnSpan(['default' => 1, 'xl' => 1])
                            ->extraAttributes(['class' => 'mk-offer-create__col mk-offer-create__col--left']),

                        Group::make([
                            Section::make('AI излез')
                                ->description('Содржина на понудата (HTML). Генерирај или уреди рачно.')
                                ->icon(Heroicon::OutlinedCpuChip)
                                ->components([
                                    ViewField::make('offer_content_loading')
                                        ->view('filament.offers.offer-generate-loading')
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    ViewField::make('ai_output_placeholder')
                                        ->view('filament.offers.ai-output-placeholder')
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                    RichEditor::make('content')
                                        ->label('Содржина')
                                        ->required()
                                        ->columnSpanFull()
                                        ->toolbarButtons([
                                            'blockquote',
                                            'bold',
                                            'bulletList',
                                            'h2',
                                            'h3',
                                            'italic',
                                            'link',
                                            'orderedList',
                                            'redo',
                                            'undo',
                                        ])
                                        ->extraInputAttributes(['class' => 'mk-offer-richtext']),
                                ])
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'mk-offer-form-section mk-offer-create__card mk-offer-create__card--ai-out']),
                        ])
                            ->columns(1)
                            ->columnSpan(['default' => 1, 'xl' => 1])
                            ->extraAttributes(['class' => 'mk-offer-create__col mk-offer-create__col--right']),
                    ]),

                Section::make('Понудата')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->visibleOn('edit')
                    ->components([
                        Select::make('company_id')
                            ->label('Компанија')
                            ->relationship(name: 'company', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedBuildingOffice),
                        TextInput::make('title')
                            ->label('Наслов')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->label('Содржина')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'undo',
                            ])
                            ->extraInputAttributes(['class' => 'mk-offer-richtext']),
                        Select::make('status')
                            ->label('Статус')
                            ->options(OfferStatus::class)
                            ->required()
                            ->default(OfferStatus::Pending)
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->prefixIcon(Heroicon::OutlinedTag),
                    ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mk-offer-form-section']),
            ]);
    }
}
