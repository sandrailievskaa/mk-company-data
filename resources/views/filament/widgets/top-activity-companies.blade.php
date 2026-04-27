<div class="mk-panel mk-reco mk-dash-spec--reco">
    <header class="mk-panel__head">
        <div>
            <p class="mk-dash-reco__kicker">✦ AI препораки</p>
            <h2 class="mk-dash-reco__title">Најпогодни оваа недела</h2>
            <p class="mk-panel__sub mk-dash-reco__sub">Оценето според вашите активни понуди.</p>
        </div>
    </header>

    <ul class="mk-reco__list" role="list">
        @forelse($companies as $c)
            <li>
                <a class="mk-reco__row" href="{{ $c['editUrl'] }}">
                    <span class="mk-reco__avatar" aria-hidden="true">{{ $c['initial'] }}</span>
                    <span class="mk-reco__body">
                        <span class="mk-reco__name-row">
                            <span class="mk-reco__name">{{ $c['name'] }}</span>
                            <span class="mk-reco__score-badge">{{ $c['score'] }}</span>
                        </span>
                        <span class="mk-reco__blurb">{{ $c['blurb'] }}</span>
                    </span>
                    <span class="mk-reco__go" aria-hidden="true">↗</span>
                </a>
            </li>
        @empty
            <li class="mk-reco__empty">Нема компании во базата.</li>
        @endforelse
    </ul>

    <a class="mk-reco__all" href="{{ $allUrl }}">Сите компании →</a>
</div>
