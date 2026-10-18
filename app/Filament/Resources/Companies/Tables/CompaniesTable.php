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
                    ->label(__('Име на компанија'))
                    ->weight(FontWeight::Bold)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sector')
                    ->label(__('Сектор'))
                    ->searchable()
                    ->visibleFrom('lg')
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
                    ->label(__('Град'))
                    ->wrap()
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('address')
                    ->label(__('Адреса'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Е-пошта'))
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function (?string $state, Company $record): HtmlString {
                        if (blank($state)) {
                            return new HtmlString('<span class="mk-companies-email mk-companies-email--empty">—</span>');
                        }

                        $safe = e($state);
                        $guess = $record->email_source?->isInferred()
                            ? '<span class="mk-companies-email__guess" title="'.e(__('Генерирана адреса, непроверена')).'">'.e(__('претпоставена')).'</span>'
                            : '';

                        return new HtmlString(
                            '<span class="mk-companies-email">'.
                            '<span class="mk-companies-email__icon" aria-hidden="true">✓</span>'.
                            '<span class="mk-companies-email__addr">'.$safe.'</span>'.
                            $guess.
                            '</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('email_source')
                    ->label(__('Извор на е-пошта'))
                    ->badge()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('website')
                    ->label(__('Веб-сајт'))
                    ->url(fn (Company $record): ?string => $record->website, shouldOpenInNewTab: true)
                    ->formatStateUsing(fn (?string $state): string => $state ? preg_replace('#^https?://(www\.)?#i', '', rtrim($state, '/')) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Телефон'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('edb')
                    ->label(__('ЕДБ'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('activity_index')
                    ->label(__('Индекс на активност'))
                    ->sortable()
                    ->visibleFrom('md')
                    ->default(0)
                    ->html()
                    ->formatStateUsing(function ($state): HtmlString {
                        $v = is_numeric($state) ? (float) $state : 0.0;
                        $v = max(0.0, min(1.0, $v));
                        $pct = (int) round($v * 100);
                        $tier = $v >= 0.6 ? 'high' : ($v >= 0.35 ? 'mid' : 'low');

                        return new HtmlString(
                            '<span class="mk-activity-cell mk-activity-cell--stacked mk-activity-cell--'.$tier.'" title="'.$pct.'%">'.
                            '<span class="mk-activity-cell__track" role="presentation">'.
                            '<span class="mk-activity-cell__fill" style="width: '.$pct.'%;"></span>'.
                            '</span>'.
                            '<span class="mk-activity-cell__val">'.e(\App\Support\Fmt::num($v, 2)).'</span>'.
                            '</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('data_quality_flag')
                    ->label(__('Квалитет'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->html()
                    ->formatStateUsing(function (?string $state): HtmlString {
                        $s = $state !== null ? strtolower(trim($state)) : '';

                        if ($s === '' || $s === 'ok') {
                            return new HtmlString('<span class="mk-quality-flag mk-quality-flag--none">—</span>');
                        }

                        $label = match ($s) {
                            'duplicate' => __('⚠ Дупликат'),
                            'inactive' => __('⚠ Неактивна'),
                            'inconsistent' => __('⚠ Неконзистентна'),
                            default => '⚠ '.e($s),
                        };

                        return new HtmlString(
                            '<span class="mk-quality-flag mk-quality-flag--warn">'.$label.'</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('data_quality_note')
                    ->label(__('Забелешка (AI)'))
                    ->toggleable(isToggledHiddenByDefault: true)
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
                            '<span class="mk-ai-note__cta"><span class="mk-ai-note__cta-more">'.e(__('Прикажи повеќе')).'</span>'.
                            '<span class="mk-ai-note__cta-less">'.e(__('Прикажи помалку')).'</span></span>'.
                            '</summary>'.
                            '<div class="mk-ai-note__body" onclick="event.stopPropagation()">'.e($text).'</div>'.
                            '</details>'
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sector')
                    ->label(__('Сектор'))
                    ->options(SectorEnum::class)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->placeholder(__('Сектор'))
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\SelectFilter::make('email_source')
                    ->label(__('Извор на е-пошта'))
                    ->options(EmailSource::class)
                    ->native(false)
                    ->placeholder(__('Извор'))
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\TernaryFilter::make('has_email')
                    ->label(__('Е-пошта'))
                    ->placeholder(__('Е-пошта: сите'))
                    ->trueLabel(__('Со е-пошта'))
                    ->falseLabel(__('Без е-пошта'))
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
                    ->label(__('Веб-сајт'))
                    ->placeholder(__('Веб-сајт: сите'))
                    ->trueLabel(__('Со веб-сајт'))
                    ->falseLabel(__('Без веб-сајт'))
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
                    ->label(__('Квалитет'))
                    ->options([
                        'ok' => 'OK',
                        'duplicate' => __('Дупликат'),
                        'inactive' => __('Неактивна'),
                        'inconsistent' => __('Неконзистентна'),
                    ])
                    ->native(false)
                    ->placeholder(__('Квалитет'))
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
                Tables\Filters\SelectFilter::make('city')
                    ->label(__('Град'))
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
                    ->placeholder(__('Град'))
                    ->modifyFormFieldUsing(function (Select $field): void {
                        $field->hiddenLabel();
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->emptyStateHeading(__('Нема компании за овие филтри'))
            ->emptyStateDescription(__('Промени ги филтрите или собери нови компании од „Пајплајн“.'))
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice)
            ->deferFilters(false)
            ->filtersFormColumns(['default' => 2, 'lg' => 3, 'xl' => 6])
            ->searchPlaceholder(__('Пребарај компании, сектори, градови…'))
            ->recordUrl(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label(__('Преглед')),
                EditAction::make()
                    ->label(__('Уреди')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markQualityOk')
                        ->label(__('Означи како OK'))
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->requiresConfirmation()
                        ->modalDescription(__('Го отстранува AI-знамето за квалитет од избраните записи.'))
                        ->action(fn (Collection $records) => $records->each->update([
                            'data_quality_flag' => 'ok',
                            'data_quality_note' => null,
                        ]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label(__('Отстрани избрани'))
                        ->modalHeading(__('Отстрани избрани компании'))
                        ->modalDescription(__('Записите се бришат трајно заедно со нивните целни компании и испраќања. Прво прегледај ги означените како дупликат/неактивни.'))
                        ->modalSubmitActionLabel(__('Отстрани'))
                        ->modalCancelActionLabel(__('Откажи')),
                ])->label(__('Квалитет на податоци')),
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
