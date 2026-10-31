@php
    use App\Support\Fmt;

    $maxCoverage = max(1.0, (float) collect($sectors)->max('coverage'));
    $top = $funnel['steps'][0]['count'] ?? 0;
@endphp

<div class="mk-breakdown">
    <section class="mk-panel mk-breakdown__card">
        <header class="mk-panel__head">
            <div>
                <h2 class="mk-dash-activity__title">
                    {{ __('Покриеност на е-пошта по сектор') }}
                    <x-mk-why :title="__('Покриеност на е-пошта по сектор')">{{ __('Колку од компаниите во секој сектор имаат пронајдена е-пошта. Така гледаш каде најдобро ќе стигнеш со една понуда. Секторите со помалку од :n компании се малку примерок.', ['n' => $smallSample]) }}</x-mk-why>
                </h2>
                <p class="mk-panel__sub">{{ __('Подредено од најдобра покриеност. Бројот е број на компании.') }}</p>
            </div>
        </header>

        @if (count($sectors) === 0)
            <p class="mk-breakdown__empty">{{ __('Нема компании во базата.') }}</p>
        @else
            <ul class="mk-breakdown__list" role="list">
                @foreach ($sectors as $sector)
                    <li class="mk-breakdown__row" title="{{ __('Просечен индекс на активност: :v', ['v' => Fmt::num($sector['avgIndex'], 2)]) }}">
                        <span class="mk-breakdown__name">
                            {{ $sector['label'] }}
                            @if ($sector['small'])
                                <span class="mk-breakdown__flag">{{ __('мал примерок') }}</span>
                            @endif
                        </span>
                        <span class="mk-breakdown__bar" role="img" aria-label="{{ Fmt::num($sector['coverage'], 1) }}%">
                            <i style="width: {{ round(100 * $sector['coverage'] / $maxCoverage) }}%"></i>
                        </span>
                        <span class="mk-breakdown__val">
                            <strong>{{ Fmt::num($sector['coverage'], 1, true) }}%</strong>
                            <small>{{ Fmt::num($sector['withEmail']) }} / {{ Fmt::num($sector['count']) }}</small>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mk-panel mk-breakdown__card">
        <header class="mk-panel__head">
            <div>
                <h2 class="mk-dash-activity__title">
                    {{ __('Од компанија до употреблив контакт') }}
                    <x-mk-why :title="__('Од компанија до употреблив контакт')">{{ __('Колку компании остануваат на секој чекор. Се броат само е-пошти пронајдени на вистински веб-сајт, а претпоставените адреси не се вклучени.') }}</x-mk-why>
                </h2>
                <p class="mk-panel__sub">{{ __('Секој чекор е дел од сите компании.') }}</p>
            </div>
        </header>

        <ol class="mk-funnel" role="list">
            @foreach ($funnel['steps'] as $step)
                <li class="mk-funnel__step">
                    <div class="mk-funnel__top">
                        <span class="mk-funnel__label">{{ $step['label'] }}</span>
                        <span class="mk-funnel__val">
                            <strong>{{ Fmt::num($step['count']) }}</strong>
                            @if ($step['key'] !== 'total')
                                <small>{{ Fmt::num($step['pct'], 1, true) }}%</small>
                            @endif
                        </span>
                    </div>
                    <span class="mk-funnel__bar mk-funnel__bar--{{ $step['key'] }}">
                        <i style="width: {{ $top > 0 ? max(1.5, round(100 * $step['count'] / $top, 1)) : 0 }}%"></i>
                    </span>
                </li>
            @endforeach
        </ol>

        <p class="mk-funnel__note">
            {{ $funnel['inferred'] > 0
                ? __('Претпоставени адреси (не се бројат): :n', ['n' => Fmt::num($funnel['inferred'])])
                : __('Претпоставени адреси: 0. Секоја е-пошта е пронајдена, не погодена.') }}
        </p>
    </section>
</div>
