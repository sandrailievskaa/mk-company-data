@php
    $buckets = $distribution['buckets'] ?? [];
    $maxCount = max(1, (int) ($distribution['max_count'] ?? 1));
    $barTrackPx = 160;
@endphp

<div class="mk-panel mk-activity-distribution mk-dash-spec--activity">
    <header class="mk-panel__head">
        <div>
            <h2 class="mk-dash-activity__title">Индекс на активност</h2>
            <p class="mk-panel__sub mk-dash-activity__sub">{{ $subtitle }}</p>
        </div>
        <div class="mk-panel__legend" aria-hidden="true">
            <span class="mk-panel__dot"></span>
            <span>Број</span>
        </div>
    </header>

    <div class="mk-activity-distribution__plot">
        <div class="mk-activity-distribution__chart" role="img" aria-label="Број на компании по опсег на индекс на активност">
            <div class="mk-activity-distribution__y" aria-hidden="true">
                <span>{{ number_format($maxCount) }}</span>
                <span>{{ (int) round($maxCount / 2) }}</span>
                <span>0</span>
            </div>
            <div class="mk-activity-distribution__bars">
                @foreach($buckets as $b)
                    @php
                        $barPx = $maxCount > 0 ? (int) round($barTrackPx * ((int) $b['count'] / $maxCount)) : 0;
                        $barPx = max(2, min($barTrackPx, $barPx));
                    @endphp
                    <div class="mk-activity-distribution__col">
                        <div class="mk-activity-distribution__bar-outer" title="{{ number_format((int) $b['count']) }} компании" style="height: {{ $barTrackPx }}px;">
                            <div class="mk-activity-distribution__bar" style="height: {{ $barPx }}px;"></div>
                        </div>
                        <div class="mk-activity-distribution__xlabel">{{ $b['xlabel'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mk-activity-distribution__footer">
        @foreach($buckets as $b)
            <div class="mk-activity-distribution__stat">
                <span class="mk-activity-distribution__tier">{{ $b['tier'] }}</span>
                <span class="mk-activity-distribution__count">{{ number_format($b['count']) }}</span>
            </div>
        @endforeach
    </div>
</div>
