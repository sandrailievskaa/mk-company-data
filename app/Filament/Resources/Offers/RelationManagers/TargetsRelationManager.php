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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')
                ->label(__('Компанија'))
                ->relationship('company', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Textarea::make('reason')
                ->label(__('Причина (од AI)'))
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('Компанија'))
                    ->searchable(),

                TextColumn::make('company.city')
                    ->label(__('Град'))
                    ->visibleFrom('md')
                    ->toggleable(),

                TextColumn::make('company.email')
                    ->label(__('Е-пошта'))
                    ->visibleFrom('sm')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('reason')
                    ->label(__('Причина'))
                    ->wrap()
                    ->limit(140),

                TextColumn::make('created_at')
                    ->label(__('Додадено'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Додај таргет'))
                    ->modalHeading(__('Додај таргет компанија'))
                    ->modalSubmitActionLabel(__('Зачувај'))
                    ->modalCancelActionLabel(__('Откажи')),
            ])
            ->actions([
                EditAction::make()
                    ->label(__('Уреди'))
                    ->modalHeading(__('Уреди таргет'))
                    ->modalSubmitActionLabel(__('Зачувај промени'))
                    ->modalCancelActionLabel(__('Откажи')),
                DeleteAction::make()
                    ->label(__('Избриши'))
                    ->modalHeading(__('Избриши таргет'))
                    ->modalSubmitActionLabel(__('Избриши'))
                    ->modalCancelActionLabel(__('Откажи')),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(__('Избриши избрани'))
                    ->modalHeading(__('Избриши избрани таргети'))
                    ->modalSubmitActionLabel(__('Избриши'))
                    ->modalCancelActionLabel(__('Откажи')),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->striped();
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Target компании');
    }
}
