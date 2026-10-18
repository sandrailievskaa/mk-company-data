<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\EmailSource;
use App\Enums\SectorEnum;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('activity_index', 'desc')
            ->extraAttributes(['class' => 'mk-companies-ta'])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Име на компанија')
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('sector')
                    ->label('Сектор')
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state): HtmlString {
                        if (! $state instanceof SectorEnum) {
                            return new HtmlString('<span class="mk-sector-pill mk-sector-pill--empty">—</span>');
                        }

                        $slug = strtolower($state->name);
                        $label = e($state->getLabel());

                        return new HtmlString(
                            '<span class="mk-sector-pill mk-sector-pill--'.$slug.'">'.$label.'</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('city')
                    ->label('Град')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Адреса')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Е-пошта')
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function (?string $state): HtmlString {
                        if (blank($state)) {
                            return new HtmlString('<span class="mk-companies-email mk-companies-email--empty">—</span>');
                        }

                        $safe = e($state);

                        return new HtmlString(
                            '<span class="mk-companies-email">'.
                            '<span class="mk-companies-email__icon" aria-hidden="true">✓</span>'.
                            '<span class="mk-companies-email__addr">'.$safe.'</span>'.
                            '</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('email_source')
                    ->label('Извор на е-пошта')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('website')
                    ->label('Веб-сајт')
                    ->url(fn (Company $record): ?string => $record->website, shouldOpenInNewTab: true)
                    ->formatStateUsing(fn (?string $state): string => $state ? preg_replace('#^https?://(www\.)?#i', '', rtrim($state, '/')) : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('edb')
                    ->label('ЕДБ')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('activity_index')
                    ->label('Индекс на активност')
                    ->sortable()
                    ->default(0)
                    ->html()
                    ->formatStateUsing(function ($state): HtmlString {
                        $v = is_numeric($state) ? (float) $state : 0.0;
                        $v = max(0.0, min(1.0, $v));
                        $pct = (int) round($v * 100);

                        return new HtmlString(
                            '<span class="mk-activity-cell">'.
                            '<span class="mk-activity-cell__track" role="presentation">'.
                            '<span class="mk-activity-cell__fill" style="width: '.$pct.'%;"></span>'.
                            '</span>'.
                            '<span class="mk-activity-cell__val">'.e(number_format($v, 2, '.', '')).'</span>'.
                            '</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('data_quality_flag')
                    ->label('Квалитет')
                    ->html()
                    ->formatStateUsing(function (?string $state): HtmlString {
                        $s = $state !== null ? strtolower(trim($state)) : '';

                        if ($s === '' || $s === 'ok') {
                            return new HtmlString('<span class="mk-quality-flag mk-quality-flag--none">—</span>');
                        }

                        $label = match ($s) {
                            'duplicate' => '⚠ Дупликат',
                            'inactive' => '⚠ Неактивна',
                            'inconsistent' => '⚠ Неконзистентна',
                            default => '⚠ '.e($s),
                        };

                        return new HtmlString(
                            '<span class="mk-quality-flag mk-quality-flag--warn">'.$label.'</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('data_quality_note')
                    ->label('Забелешка (AI)')
                    ->html()
                    ->formatStateUsing(function (?string $state): HtmlString {
                        $text = $state !== null ? trim($state) : '';
                        if ($text === '') {
                            return new HtmlString('<span class="mk-ai-note mk-ai-note--empty">—</span>');
                        }

                        $previewMax = 44;
                        if (mb_strlen($text) <= $previewMax) {
                            return new HtmlString(
                                '<div class="mk-ai-note mk-ai-note--plain">'.e($text).'</div>'
                            );
                        }

                        $preview = self::aiNotePreview($text, $previewMax);

                        return new HtmlString(
                            '<details class="mk-ai-note mk-ai-note--long">'.
                            '<summary class="mk-ai-note__summary" onclick="event.stopPropagation()">'.
                            '<span class="mk-ai-note__preview">'.e($preview).'</span>'.
                            '<span class="mk-ai-note__cta"><span class="mk-ai-note__cta-more">Прикажи повеќе</span>'.
                            '<span class="mk-ai-note__cta-less">Прикажи помалку</span></span>'.
                            '</summary>'.
                            '<div class="mk-ai-note__body" onclick="event.stopPropagation()">'.e($text).'</div>'.
                            '</details>'
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sector')
                    ->label('Сектор')
                    ->options(SectorEnum::class)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->placeholder('Сектор')
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\SelectFilter::make('email_source')
                    ->label('Извор на е-пошта')
                    ->options(EmailSource::class)
                    ->native(false)
                    ->placeholder('Извор на е-пошта')
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\TernaryFilter::make('has_email')
                    ->label('Е-пошта')
                    ->placeholder('Е-пошта: сите')
                    ->trueLabel('Со е-пошта')
                    ->falseLabel('Без е-пошта')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email'),
                        false: fn ($query) => $query->whereNull('email'),
                        blank: fn ($query) => $query,
                    )
                    ->native(false)
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\TernaryFilter::make('has_website')
                    ->label('Веб-сајт')
                    ->placeholder('Веб-сајт: сите')
                    ->trueLabel('Со веб-сајт')
                    ->falseLabel('Без веб-сајт')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('website'),
                        false: fn ($query) => $query->whereNull('website'),
                        blank: fn ($query) => $query,
                    )
                    ->native(false)
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\SelectFilter::make('data_quality_flag')
                    ->label('Квалитет')
                    ->options([
                        'ok' => 'OK',
                        'duplicate' => 'Дупликат',
                        'inactive' => 'Неактивна',
                        'inconsistent' => 'Неконзистентна',
                    ])
                    ->native(false)
                    ->placeholder('Квалитет')
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
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
                    })
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->placeholder('Град')
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->emptyStateHeading('Нема компании за овие филтри')
            ->emptyStateDescription('Променете ги филтрите или собери нови компании од „Пајплајн“.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice)
            ->deferFilters(false)
            ->filtersFormColumns(['default' => 2, 'lg' => 3, 'xl' => 6])
            ->searchPlaceholder('Пребарај компании, сектори, градови...')
            ->recordUrl(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед'),
                EditAction::make()
                    ->label('Уреди'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markQualityOk')
                        ->label('Означи како OK')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->requiresConfirmation()
                        ->modalDescription('Го отстранува AI-знамето за квалитет од избраните записи.')
                        ->action(fn (Collection $records) => $records->each->update([
                            'data_quality_flag' => 'ok',
                            'data_quality_note' => null,
                        ]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label('Отстрани избрани')
                        ->modalHeading('Отстрани избрани компании')
                        ->modalDescription('Записите се бришат трајно заедно со нивните таргети и испраќања. Прво прегледајте ги означените како дупликат/неактивни.')
                        ->modalSubmitActionLabel('Отстрани')
                        ->modalCancelActionLabel('Откажи'),
                ])->label('Квалитет на податоци'),
            ]);
    }

    /**
     * Краток преглед за табела: до $max знаци, сечење на последен празнински збор каде има смисла.
     */
    private static function aiNotePreview(string $text, int $max = 44): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $chunk = mb_substr($text, 0, $max);
        $lastSpace = mb_strrpos($chunk, ' ');
        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.4)) {
            $chunk = mb_substr($chunk, 0, $lastSpace);
        }

        $chunk = rtrim($chunk, " \t.,;:");

        return $chunk.'…';
    }
}
