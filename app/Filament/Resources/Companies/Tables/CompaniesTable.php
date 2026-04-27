<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\SectorEnum;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
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
            ->deferFilters(false)
            ->filtersFormColumns(2)
            ->searchPlaceholder('Пребарај компании, сектори, градови...')
            ->recordUrl(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед'),
                EditAction::make()
                    ->label('Уреди'),
            ])
            ->toolbarActions([]);
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
