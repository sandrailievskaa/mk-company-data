@php
    $c = app(\App\Services\AiUsage\AiCreditsDisplay::class)->forCurrentMonth();
    $used = $c['used_credits'];
    $total = $c['budget_credits'];
    $usedTokens = $c['used_tokens'];
    $totalTokens = $c['total_tokens'];
    $periodLabel = $c['period_label'];
    $bySource = $c['by_source'];

    $sourceLabels = [
        \App\Services\AiUsage\AiUsageLogSource::RECOMMENDATION => 'AI препораки',
        \App\Services\AiUsage\AiUsageLogSource::OFFER => 'Ген. понуди',
        \App\Services\AiUsage\AiUsageLogSource::VALIDATION => 'Валид. на податоци',
    ];

    arsort($bySource, SORT_NUMERIC);
    $tokensPerCredit = max(1, (int) config('ai_credits.tokens_per_credit', 1000));
    $percent = $total > 0 ? min(100, max(0, (int) round(($used / $total) * 100))) : 0;
    $isOver = $used > $total;
    $fmt = static fn (int $n): string => number_format($n, 0, ',', ' ');
@endphp

<div class="mk-sidebar-credits" aria-label="AI кредити">
    <div class="mk-sidebar-credits-head">
        <span class="mk-sidebar-credits-icon" aria-hidden="true">✦</span>
        <div class="mk-sidebar-credits-head-titles">
            <div class="mk-sidebar-credits-name">AI кредити</div>
        </div>
    </div>

    <div
        class="mk-sidebar-credits-meter"
        role="progressbar"
        aria-label="Искористени кредити"
        aria-valuenow="{{ $used }}"
        aria-valuemin="0"
        aria-valuemax="{{ $total }}"
    >
        <div
            @class(['mk-sidebar-credits-meter-bar', 'mk-sidebar-credits-meter-bar--over' => $isOver])
            style="width: {{ $percent }}%"
        ></div>
    </div>

    <div class="mk-sidebar-credits-foot">
        <div class="mk-sidebar-credits-stats" title="{{ $periodLabel }}">
            <span class="mk-sidebar-credits-stats-used">{{ $fmt($used) }}</span>
            <span class="mk-sidebar-credits-stats-sep" aria-hidden="true"> / </span>
            <span class="mk-sidebar-credits-stats-total">{{ $fmt($total) }}</span>
        </div>
    </div>

    <p class="mk-sidebar-credits-hint">
        {{ $periodLabel }} ·
        ≈ {{ $fmt($usedTokens) }} / {{ $fmt($totalTokens) }} токени
        (1 кред. = {{ $fmt($tokensPerCredit) }} ток.)
    </p>

    @if (count($bySource) > 0)
        <ul class="mk-sidebar-credits-breakdown">
            @foreach ($bySource as $key => $tok)
                @if ((int) $tok <= 0)
                    @continue
                @endif
                <li>
                    <span class="mk-sidebar-credits-breakdown-label">
                        {{ $sourceLabels[$key] ?? $key }}
                    </span>
                    <span class="mk-sidebar-credits-breakdown-value">
                        @php
                            $creditsForSource = (int) floor($tok / $tokensPerCredit);
                        @endphp
                        @if ($creditsForSource > 0)
                            ~{{ $creditsForSource }} кред.
                        @else
                            ~{{ $fmt((int) $tok) }} ток.
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @elseif ($usedTokens === 0)
        <p class="mk-sidebar-credits-empty">Нема AI повици снимени за овој месец.</p>
    @endif
</div>
