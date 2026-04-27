<div class="mk-insights-row">
    <div class="mk-insights-row__main">
        @include('filament.widgets.activity-index-distribution', [
            'distribution' => $distribution,
            'subtitle' => $subtitle,
        ])
    </div>
    <div class="mk-insights-row__side">
        @include('filament.widgets.top-activity-companies', [
            'companies' => $companies,
            'allUrl' => $allUrl,
        ])
    </div>
</div>
