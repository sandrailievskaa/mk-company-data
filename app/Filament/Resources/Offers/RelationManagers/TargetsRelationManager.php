<?php

namespace App\Filament\Resources\Offers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TargetsRelationManager extends RelationManager
{
    protected static string $relationship = 'targets';

    protected static ?string $title = 'Target компании';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')
                ->label('Компанија')
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Textarea::make('reason')
                ->label('Причина (од AI)')
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Компанија')
                    ->searchable(),

                TextColumn::make('company.city')
                    ->label('Град')
                    ->toggleable(),

                TextColumn::make('company.email')
                    ->label('Е-пошта')
                    ->toggleable(),

                TextColumn::make('reason')
                    ->label('Причина')
                    ->wrap()
                    ->limit(140),

                TextColumn::make('created_at')
                    ->label('Додадено')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Додај таргет')
                    ->modalHeading('Додај таргет компанија')
                    ->modalSubmitActionLabel('Зачувај')
                    ->modalCancelActionLabel('Откажи'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Уреди')
                    ->modalHeading('Уреди таргет')
                    ->modalSubmitActionLabel('Зачувај промени')
                    ->modalCancelActionLabel('Откажи'),
                DeleteAction::make()
                    ->label('Избриши')
                    ->modalHeading('Избриши таргет')
                    ->modalSubmitActionLabel('Избриши')
                    ->modalCancelActionLabel('Откажи'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Избриши избрани')
                    ->modalHeading('Избриши избрани таргети')
                    ->modalSubmitActionLabel('Избриши')
                    ->modalCancelActionLabel('Откажи'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->striped();
    }
}

