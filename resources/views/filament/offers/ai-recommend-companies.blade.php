<div class="space-y-4">
    @if (! empty($this->aiRecommendationError))
        <div class="mk-offer-reco-alert mk-offer-reco-alert--err">
            {{ $this->aiRecommendationError }}
        </div>
    @endif

    <div
        class="mk-offer-reco-loading"
        role="status"
        wire:loading
        wire:target="ai_recommend_companies,generateCompanyRecommendations"
    >
        <span class="mk-offer-reco-loading__spinner" aria-hidden="true"></span>
        <p class="mk-offer-reco-loading__text">Се анализираат најдобрите компании по индекс на активност...</p>
    </div>

    @if (empty($this->aiRecommendations))
        <p class="mk-offer-reco-hint">
            Сè уште нема препораки. Кликни <strong>„Препорачај“</strong> за AI предлози.
        </p>
    @endif

    @if (! empty($this->aiRecommendations))
        <div class="mk-offer-reco-table-outer">
            <table class="mk-offer-reco-table">
                <thead>
                    <tr>
                        <th scope="col">Компанија</th>
                        <th scope="col">Сектор</th>
                        <th scope="col">Активност (индекс)</th>
                        <th scope="col">Образложение</th>
                        <th scope="col">Одбери</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->aiRecommendations as $rec)
                        <tr>
                            <td class="mk-offer-reco-table__name">
                                {{ $rec['name'] ?? ('#' . $rec['company_id']) }}
                            </td>
                            <td>
                                {{ $rec['sector_label'] ?? ($rec['sector'] ?? '—') }}
                            </td>
                            <td>
                                @isset($rec['activity_index'])
                                    {{ number_format((float) $rec['activity_index'], 4, '.', '') }}
                                @else
                                    —
                                @endisset
                            </td>
                            <td class="mk-offer-reco-table__reason">
                                {{ $rec['reason'] ?? '—' }}
                            </td>
                            <td class="mk-offer-reco-table__sel">
                                <input
                                    type="checkbox"
                                    class="mk-offer-reco-checkbox"
                                    wire:model.live="aiRecommendationSelections.{{ $rec['company_id'] }}"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mk-offer-reco-footnote">
            Означените цели ќе бидат зачувани откако ќе ја <strong>креираш понудата</strong> (листа во <em>offer_targets</em>).
        </p>
    @endif
</div>
