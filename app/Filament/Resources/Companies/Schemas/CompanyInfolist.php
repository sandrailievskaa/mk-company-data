<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                ViewEntry::make('company_view_nav')
                    ->view('filament.companies.view-company-nav')
                    ->hiddenLabel()
                    ->columnSpanFull(),

                Section::make()
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mk-offer-view-hero mk-company-view-hero'])
                    ->components([
                        ViewEntry::make('company_view_hero')
                            ->view('filament.companies.view-company-hero')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ]),

                Grid::make(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'mk-company-view-split'])
                    ->schema([
                        ViewEntry::make('company_view_activity')
                            ->view('filament.companies.view-company-activity')
                            ->hiddenLabel()
                            ->columnSpan(['default' => 1, 'lg' => 8]),
                        ViewEntry::make('company_view_sidebar')
                            ->view('filament.companies.view-company-sidebar')
                            ->hiddenLabel()
                            ->columnSpan(['default' => 1, 'lg' => 4]),
                    ]),

                ViewEntry::make('company_view_timeline')
                    ->view('filament.companies.view-company-timeline')
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]);
    }
}
