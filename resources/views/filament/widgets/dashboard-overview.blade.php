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
            <a class="mk-dashboard__cta" href="{{ $newOfferUrl }}">{{ __('✦ Нова AI понуда') }}</a>
        </div>
    </header>

    <div class="mk-dashboard__grid">
        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">{{ __('Вкупно компании') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m8 0H9m12 0v18" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ \App\Support\Fmt::num($s['totalCompanies']) }}</p>
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
                {{ __(':pct од пр. седмина', ['pct' => $s['companiesWowFormatted']]) }}
            </p>
        </article>

        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label" title="{{ __('Делот од компании со внесена е-пошта') }}">{{ __('Покриеност на е-пошта') }}</span>
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
                {{ __(':delta п.п. од :initial%, :count е-пошта (:inferred претпоставени)', ['delta' => \App\Support\Fmt::signed($s['emailDelta']), 'initial' => $s['initialEmailFormatted'], 'count' => \App\Support\Fmt::num($s['withEmail']), 'inferred' => \App\Support\Fmt::num($s['inferredEmails'])]) }}
            </p>
        </article>

        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span
                    class="mk-dashboard__label"
                    title="{{ __('Со activity_index > :value (од config)', ['value' => $s['activeActivityIndexThresholdFormatted']]) }}">{{ __('Активни') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 6.75 12 5.25m0 0 1.5-1.5M12 5.25v13.5m8.25-6.75-6.75 6.75-1.5-1.5" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ \App\Support\Fmt::num($s['activeCompanies']) }}</p>
            <p class="mk-dashboard__delta mk-dashboard__delta--neutral">
                <span class="mk-dashboard__arrow" aria-hidden="true">→</span>
                {{ __(':pct% од вкупно', ['pct' => $s['activeShareFormatted']]) }}
            </p>
        </article>

        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">{{ __('Стапка дупликати') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.75A2.25 2.25 0 0 1 10.5 4.5h3A2.25 2.25 0 0 1 15.75 6.75V7.5M8.25 7.5h7.5M6 10.5h12m-1.5 4.5H7.5a2.25 2.25 0 0 0-2.25 2.25V19.5A2.25 2.25 0 0 0 7.5 21.75h9a2.25 2.25 0 0 0 2.25-2.25V17.25A2.25 2.25 0 0 0 16.5 15Z" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value mk-dashboard__value--with-pct">
                {{ $s['dupRateFormatted'] }}@if($s['dupRateFormatted'] !== '—')<span class="mk-dashboard__pct">%</span>@endif
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
                    {{ __(':delta п.п. од реф. стапка', ['delta' => \App\Support\Fmt::signed((float) $s['dupDelta'])]) }}
                </p>
            @else
                <p class="mk-dashboard__delta mk-dashboard__delta--muted" title="{{ __('Постави реф. вредност: BASELINE_DUPLICATE_RATE. Понизок % е подобро.') }}">
                    {{ __('Без реф. стапка') }}
                </p>
            @endif
        </article>
    </div>

    @php($c = $campaign)
    <div class="mk-dashboard__grid mk-dashboard__grid--compact" aria-label="{{ __('Понуди и испраќања') }}">
        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">{{ __('Подготвени понуди') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ \App\Support\Fmt::num($c['offers']) }}</p>
            <p class="mk-dashboard__delta mk-dashboard__delta--muted">{{ __('Вкупно креирани') }}</p>
        </article>
        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">{{ __('Испратени') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ \App\Support\Fmt::num($c['delivered']) }}</p>
            <p class="mk-dashboard__delta {{ $c['testMode'] ? 'mk-dashboard__delta--neutral' : 'mk-dashboard__delta--pos' }}">{{ $c['testMode'] ? __('Пробен режим: пораките одат во лог') : __('Доставени до е-пошта') }}</p>
        </article>
        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">{{ __('Отворени') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ \App\Support\Fmt::num($c['opened']) }}</p>
            <p class="mk-dashboard__delta mk-dashboard__delta--neutral">{{ $c['openRate'] === null ? __('Нема испратени') : $c['openRate'].__('% од испратените') }}</p>
        </article>
        <article class="mk-dashboard__card">
            <div class="mk-dashboard__card-top">
                <span class="mk-dashboard__label">{{ __('Неуспешни') }}</span>
                <span class="mk-dashboard__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                </span>
            </div>
            <p class="mk-dashboard__value">{{ \App\Support\Fmt::num($c['failed']) }}</p>
            <p class="mk-dashboard__delta {{ $c['failed'] > 0 ? 'mk-dashboard__delta--neg' : 'mk-dashboard__delta--muted' }}">{{ __('Се повторуваат од понудата') }}</p>
        </article>
    </div>
</div>
