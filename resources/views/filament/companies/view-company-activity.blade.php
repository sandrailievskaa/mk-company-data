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
    @endphp
    <div class="mk-cv-card mk-cv-activity">
        <div class="mk-cv-card__kicker">скор</div>
        <h2 class="mk-cv-card__title">Индекс на активност</h2>
        <div class="mk-cv-activity__body">
            <div class="mk-cv-activity__chart" style="--mk-p: {{ $pct }};" role="img" aria-label="Индекс на активност {{ $pct }} од 100">
                <div class="mk-cv-activity__ring" aria-hidden="true"></div>
                <div class="mk-cv-activity__hole" aria-hidden="true"></div>
                <div class="mk-cv-activity__score">
                    <span class="mk-cv-activity__score-val">{{ $pct }}</span>
                    <span class="mk-cv-activity__score-suffix">/100</span>
                </div>
            </div>
            <div class="mk-cv-activity__grid">
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">Нормиран индекс</div>
                    <div class="mk-cv-activity__stat-v">{{ number_format($v, 2, ',', '') }}</div>
                </div>
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">Скрапирања</div>
                    <div class="mk-cv-activity__stat-v">{{ (int) $record->scrape_count }}</div>
                </div>
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">Понуди</div>
                    <div class="mk-cv-activity__stat-v">{{ $offers }}</div>
                </div>
                <div class="mk-cv-activity__stat">
                    <div class="mk-cv-activity__stat-k">Статус на запис</div>
                    <div class="mk-cv-activity__stat-v">
                        @php
                            $q = $record->data_quality_flag !== null ? strtolower(trim($record->data_quality_flag)) : '';
                            $qLabel = match (true) {
                                $q === '' || $q === 'ok' => 'OK',
                                $q === 'duplicate' => 'Дупликат',
                                $q === 'inactive' => 'Неактивна',
                                $q === 'inconsistent' => 'Неконзистентна',
                                default => $record->data_quality_flag,
                            };
                        @endphp
                        <span class="mk-cv-pill @if($q !== '' && $q !== 'ok') mk-cv-pill--warn @else mk-cv-pill--ok @endif">{{ $qLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
        <p class="mk-cv-activity__foot">
            Вредноста е од 0 до 1 и се нормира на 0–100 за приказ. Се ажурира при скрапирање и обработка на податоци.
        </p>
    </div>
@endif
