@php
    use App\Models\Company;

    /** @var \Filament\Infolists\Components\Entry $entry */
    $record = $entry->getRecord();
@endphp
@if ($record instanceof Company)
    <div class="mk-cv-card mk-cv-timeline">
        <div class="mk-cv-card__kicker">време</div>
        <h2 class="mk-cv-card__title">Историја на податоците</h2>
        <ol class="mk-cv-timeline__list">
            <li class="mk-cv-timeline__item">
                <div class="mk-cv-timeline__dot" aria-hidden="true"></div>
                <div class="mk-cv-timeline__content">
                    <span class="mk-cv-timeline__date">{{ $record->created_at?->translatedFormat('d.m.Y') ?? '—' }}</span>
                    <span class="mk-cv-timeline__tag">КРЕИРАЊЕ</span>
                    <p class="mk-cv-timeline__text">Записот е креиран во системот.</p>
                </div>
            </li>
            <li class="mk-cv-timeline__item">
                <div class="mk-cv-timeline__dot" aria-hidden="true"></div>
                <div class="mk-cv-timeline__content">
                    <span class="mk-cv-timeline__date">{{ $record->updated_at?->translatedFormat('d.m.Y H:i') ?? '—' }}</span>
                    <span class="mk-cv-timeline__tag">АЖУРИРАЊЕ</span>
                    <p class="mk-cv-timeline__text">Последна промена на полињата.</p>
                </div>
            </li>
            <li class="mk-cv-timeline__item">
                <div class="mk-cv-timeline__dot" aria-hidden="true"></div>
                <div class="mk-cv-timeline__content">
                    <span class="mk-cv-timeline__date">—</span>
                    <span class="mk-cv-timeline__tag">СКРАП</span>
                    <p class="mk-cv-timeline__text">Вкупно {{ (int) $record->scrape_count }} {{ ((int) $record->scrape_count) === 1 ? 'процес' : 'процеси' }} на скрапирање/обработка за овој запис.</p>
                </div>
            </li>
        </ol>
    </div>
@endif
