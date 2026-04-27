@php
    $s = $stats;
    $deltaClass = function (?string $tone) {
        if ($tone === null) {
            return 'mk-dashboard__delta--muted';
        }
        if ($tone === 'neutral') {
            return 'mk-dashboard__delta--neutral';
        }

        return $tone === 'good' ? 'mk-dashboard__delta--pos' : 'mk-dashboard__delta--neg';
    };
@endphp

<div class="mk-dashboard">
    <header class="mk-dashboard__head">
        <div class="mk-dashboard__head-main">
            <h1 class="mk-dashboard__h1">{{ $s['greeting'] }}, {{ $s['userName'] }}.</h1>
            <p class="mk-dashboard__sub">{{ $s['subtitle'] }}</p>
        </div>
        <div class="mk-dashboard__head-actions">
            <a class="mk-dashboard__cta" href="{{ $newOfferUrl }}">✦ Нова AI понуда</a>
        </div>
    </header>

    <div class="mk-dashboard__grid">
        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">Вкупно компании</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m8 0H9m12 0v18" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ number_format($s['totalCompanies']) }}</p>
            <p class="mk-dashboard__delta {{ $deltaClass($s['companiesDeltaTone'] ?? 'neutral') }}">
                <span class="mk-dashboard__arrow" aria-hidden="true">
                    @if(($s['companiesDeltaTone'] ?? '') === 'good')
                        ↑
                    @elseif(($s['companiesDeltaTone'] ?? '') === 'bad')
                        ↓
                    @else
                        →
                    @endif
                </span>
                {{ $s['companiesWowFormatted'] }} од пр. седмина
            </p>
        </article>

        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label" title="Делот од компании со внесен e-mail">Покриеност на e-пошта</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H15a2.25 2.25 0 0 1-2.12-1.5l-1.2-3.6a.75.75 0 0 0-1.42 0l-1.2 3.6a2.25 2.25 0 0 1-2.12 1.5H4.5a2.25 2.25 0 0 1-2.25-2.25V6.75A2.25 2.25 0 0 1 4.5 4.5h15a2.25 2.25 0 0 1 2.25 2.25Z" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value mk-dashboard__value--with-pct">
                {{ $s['emailCoverageFormatted'] }}<span class="mk-dashboard__pct">%</span>
            </p>
            <p class="mk-dashboard__delta {{ $deltaClass($s['emailDeltaTone'] ?? 'neutral') }}">
                <span class="mk-dashboard__arrow" aria-hidden="true">
                    @if(($s['emailDeltaTone'] ?? '') === 'good')
                        ↑
                    @elseif(($s['emailDeltaTone'] ?? '') === 'bad')
                        ↓
                    @else
                        →
                    @endif
                </span>
                {{ sprintf('%+.1f', $s['emailDelta']) }} п.п. од {{ $s['initialEmailFormatted'] }}%, {{ number_format($s['withEmail']) }} e-mail
            </p>
        </article>

        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span
                    class="mk-dashboard__label"
                    title="Со activity_index &gt; {{ $s['activeActivityIndexThresholdFormatted'] }} (од config)">Активни</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 6.75 12 5.25m0 0 1.5-1.5M12 5.25v13.5m8.25-6.75-6.75 6.75-1.5-1.5" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ number_format($s['activeCompanies']) }}</p>
            <p class="mk-dashboard__delta mk-dashboard__delta--neutral">
                <span class="mk-dashboard__arrow" aria-hidden="true">→</span>
                {{ $s['activeShareFormatted'] }}% од вкупно
            </p>
        </article>

        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">Стапка дупликати</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.75A2.25 2.25 0 0 1 10.5 4.5h3A2.25 2.25 0 0 1 15.75 6.75V7.5M8.25 7.5h7.5M6 10.5h12m-1.5 4.5H7.5a2.25 2.25 0 0 0-2.25 2.25V19.5A2.25 2.25 0 0 0 7.5 21.75h9a2.25 2.25 0 0 0 2.25-2.25V17.25A2.25 2.25 0 0 0 16.5 15Z" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value mk-dashboard__value--with-pct">
                {{ $s['dupRateFormatted'] }}<span class="mk-dashboard__pct">%</span>
            </p>
            @if($s['dupHasBaseline'] && $s['dupDelta'] !== null)
                <p class="mk-dashboard__delta {{ $deltaClass($s['dupDeltaTone'] ?? 'neutral') }}">
                    <span class="mk-dashboard__arrow" aria-hidden="true">
                        @if(($s['dupDeltaTone'] ?? '') === 'good')
                            ↓
                        @elseif(($s['dupDeltaTone'] ?? '') === 'bad')
                            ↑
                        @else
                            →
                        @endif
                    </span>
                    {{ sprintf('%+.1f', (float) $s['dupDelta']) }} п.п. од реф. стапка
                </p>
            @else
                <p class="mk-dashboard__delta mk-dashboard__delta--muted" title="Постави ref. вредност: BASELINE_DUPLICATE_RATE. Понизок % е подобро.">
                    Без реф. стапка
                </p>
            @endif
        </article>
    </div>
</div>
