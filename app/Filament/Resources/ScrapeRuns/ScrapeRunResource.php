<?php

namespace App\Filament\Resources\ScrapeRuns;

use App\Enums\ScrapeRunStatus;
use App\Filament\Resources\ScrapeRuns\Pages\ListScrapeRuns;
use App\Models\ScrapeRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ScrapeRunResource extends Resource
{
    protected static ?string $model = ScrapeRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?int $navigationSort = 40;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('started_at')
                    ->label(__('Почеток'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sector')
                    ->label(__('Сектор'))
                    ->placeholder('—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Статус'))
                    ->badge()
                    ->formatStateUsing(fn (ScrapeRunStatus $state): string => match ($state) {
                        ScrapeRunStatus::Running => __('Во тек'),
                        ScrapeRunStatus::Completed => __('Завршено'),
                        ScrapeRunStatus::Failed => __('Неуспешно'),
                    })
                    ->color(fn (ScrapeRunStatus $state): string => match ($state) {
                        ScrapeRunStatus::Running => 'warning',
                        ScrapeRunStatus::Completed => 'success',
                        ScrapeRunStatus::Failed => 'danger',
                    }),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label(__('Траење'))
                    ->visibleFrom('sm')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : sprintf(__('%d:%02d мин'), intdiv($state, 60), $state % 60)),
                Tables\Columns\TextColumn::make('pages_fetched')->label(__('Страници'))->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('records_seen')->label(__('Видени'))->visibleFrom('md'),
                Tables\Columns\TextColumn::make('records_created')->label(__('Нови'))->visibleFrom('md'),
                Tables\Columns\TextColumn::make('records_updated')->label(__('Ажурирани'))->visibleFrom('xl'),
                Tables\Columns\TextColumn::make('emails_found')->label(__('Е-пошти'))->visibleFrom('xl'),
                Tables\Columns\TextColumn::make('error_message')
                    ->label(__('Грешка'))
                    ->visibleFrom('lg')
                    ->limit(60)
                    ->tooltip(fn (ScrapeRun $record): ?string => $record->error_message)
                    ->placeholder('—'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScrapeRuns::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Скрејпања');
    }

    public static function getModelLabel(): string
    {
        return __('Скрејпање');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Скрејпања');
    }
}
