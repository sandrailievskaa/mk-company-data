<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\SectorEnum;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Име на компанија')
                    ->icon(Heroicon::OutlinedBuildingOffice)
                    ->iconColor('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_quality_flag')
                    ->label('Квалитет')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'duplicate' => 'Дупликат',
                        'inactive' => 'Неактивна',
                        'inconsistent' => 'Неконзистентна',
                        default => 'OK',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'duplicate' => 'warning',
                        'inactive' => 'danger',
                        'inconsistent' => 'info',
                        default => 'success',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sector')
                    ->label('Сектор')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->iconColor('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Град')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->iconColor('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Адреса')
                    ->icon(Heroicon::OutlinedHome)
                    ->iconColor('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Е-пошта')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->iconColor('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефонски број')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activity_index')
                    ->label('Индекс на активност')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->iconColor('success')
                    ->sortable()
                    ->default(0),
                Tables\Columns\TextColumn::make('data_quality_note')
                    ->label('Забелешка (AI)')
                    ->wrap()
                    ->limit(120)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sector')
                    ->label('Сектор')
                    ->options(SectorEnum::class),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Град')
                    ->options(function () {
                        return Company::query()
                            ->whereNotNull('city')
                            ->where('city', '!=', '')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city')
                            ->toArray();
                    }),
                Tables\Filters\Filter::make('flagged')
                    ->label('Само означени')
                    ->query(fn (Builder $query) => $query->where('data_quality_flag', '!=', 'ok')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
