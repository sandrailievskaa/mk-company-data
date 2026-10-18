@php
    use App\Models\Company;

    /** @var \Filament\Infolists\Components\Entry $entry */
    $record = $entry->getRecord();
@endphp
@if ($record instanceof Company)
    @php
        $v = is_numeric($record->activity_index) ? (float) $record->activity_index : 0.0;
        $v = max(0.0, min(1.0, $v));
        $pct = (int) round($v * 100);
        $offers = (int) $record->offers_count;
        $calculator = app(\App\Services\ActivityIndex\ActivityIndexCalculator::class);
        $parts = $calculator->components($record, $calculator->frequencyScale());
        $weights = config('activity_index.weights');
    @endphp
    <div class="mk-cv-card mk-cv-activity">
        <div class="mk-cv-card__kicker">{{ __('скор') }}</div>
        <h2 class="mk-cv-card__title">{{ __('Индекс на активност') }}</h2>
        <div class="mk-cv-activity__body">
            <div class="mk-cv-activity__chart" style="--mk-p: {{ $pct }};" role="img" aria-label="{{ __('Индекс на активност :pct од 100', ['pct' => $pct]) }}">
                <div class="mk-cv-activity__ring" aria-hidden="true"></div>
                <div class="mk-cv-activity__hole" aria-hidden="true"></div>
                <div class="mk-cv-activity__score">
                    <span class="mk-cv-activity__score-val">{{ $pct }}</span>
                    <span class="mk-cv-activity__score-suffix">/100</span>
                </div>
            </div>
            <div class="mk-cv-activity__grid">
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">{{ __('Нормиран индекс') }}</div>
                    <div class="mk-cv-activity__stat-v">{{ \App\Support\Fmt::num($v, 2) }}</div>
                </div>
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">{{ __('Скрејпања') }}</div>
                    <div class="mk-cv-activity__stat-v">{{ (int) $record->scrape_count }}</div>
                </div>
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">{{ __('Понуди') }}</div>
                    <div class="mk-cv-activity__stat-v">{{ $offers }}</div>
                </div>
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">{{ __('Статус на запис') }}</div>
                    <div class="mk-cv-activity__stat-v">
                        @php
                            $q = $record->data_quality_flag !== null ? strtolower(trim($record->data_quality_flag)) : '';
                            $qLabel = match (true) {
                                $q === '' || $q === 'ok' => 'OK',
                                $q === 'duplicate' => __('Дупликат'),
                                $q === 'inactive' => __('Неактивна'),
                                $q === 'inconsistent' => __('Неконзистентна'),
                                default => $record->data_quality_flag,
                            };
                        @endphp
                        <span class="mk-cv-pill @if($q !== '' && $q !== 'ok') mk-cv-pill--warn @else mk-cv-pill--ok @endif">{{ $qLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="mk-cv-activity__grid" aria-label="{{ __('Составни делови на индексот') }}">
            <div class="mk-cv-activity__stat">
                <div class="mk-cv-activity__stat-k">{{ __('E · е-пошта (тежина :w)', ['w' => \App\Support\Fmt::num($weights['email'], 1)]) }}</div>
                <div class="mk-cv-activity__stat-v">{{ \App\Support\Fmt::num($parts['email'], 2) }}</div>
            </div>
            <div class="mk-cv-activity__stat">
                <div class="mk-cv-activity__stat-k">{{ __('P · јавни извори (тежина :w)', ['w' => \App\Support\Fmt::num($weights['presence'], 1)]) }}</div>
                <div class="mk-cv-activity__stat-v">{{ \App\Support\Fmt::num($parts['presence'], 2) }}</div>
            </div>
            <div class="mk-cv-activity__stat">
                <div class="mk-cv-activity__stat-k">{{ __('F · фреквенција (тежина :w)', ['w' => \App\Support\Fmt::num($weights['frequency'], 1)]) }}</div>
                <div class="mk-cv-activity__stat-v">{{ \App\Support\Fmt::num($parts['frequency'], 2) }}</div>
            </div>
        </div>
        <p class="mk-cv-activity__foot">
            {{ __('Индекс = :e·E + :p·P + :f·F. Вредноста е од 0 до 1 и се нормира на 0–100 за приказ. Се ажурира при скрејпање и обработка на податоци.', ['e' => \App\Support\Fmt::num($weights['email'], 1), 'p' => \App\Support\Fmt::num($weights['presence'], 1), 'f' => \App\Support\Fmt::num($weights['frequency'], 1)]) }}
        </p>
    </div>
@endif
