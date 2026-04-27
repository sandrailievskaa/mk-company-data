@php
    use App\Enums\OfferStatus;
    use App\Filament\Resources\Offers\OfferResource;
    use Illuminate\Support\Str;
@endphp

@php
    $paginator = $this->getPaginatedOffers();
@endphp

<div class="mk-offers-list-inner">
    @if ($paginator->isEmpty())
        <div class="mk-offers-empty">
            <p>Нема креирани понуди.</p>
        </div>
    @else
        <div class="mk-offers-grid">
            @foreach ($paginator as $offer)
                @php
                    $status = $offer->status instanceof OfferStatus
                        ? $offer->status
                        : (OfferStatus::tryFrom((string) ($offer->getAttributes()['status'] ?? 'pending')) ?? OfferStatus::Pending);
                    $slug = 'mk-offer-status--' . $status->value;
                    $company = $offer->company;
                    $preview = Str::of(strip_tags((string) $offer->content))->squish();
                    if ($preview->isEmpty()) {
                        $previewText = 'Нема преглед…';
                    } else {
                        $previewText = (string) $preview->limit(180, '…', true);
                    }
                @endphp
                <a
                    href="{{ OfferResource::getUrl('view', ['record' => $offer]) }}"
                    class="mk-offer-card"
                    wire:navigate
                >
                    <div class="mk-offer-card__top">
                        <span class="mk-offer-card__company">
                            {{ $company?->name ?? '—' }}
                        </span>
                        @if ($company?->sector)
                            <span class="mk-sector-pill mk-sector-pill--{{ strtolower($company->sector->name) }}">
                                {{ $company->sector->getLabel() }}
                            </span>
                        @endif
                    </div>
                    <div class="mk-offer-card__body">
                        <h3 class="mk-offer-card__title">
                            {{ $offer->title }}
                        </h3>
                        <p class="mk-offer-card__preview">
                            {{ $previewText }}
                        </p>
                    </div>
                    <div class="mk-offer-card__foot">
                        <span class="mk-offer-badge {{ $slug }}">
                            {{ $status->getLabel() }}
                        </span>
                        <time
                            class="mk-offer-card__date"
                            datetime="{{ $offer->created_at?->toIso8601String() }}"
                        >
                            {{ $offer->created_at?->translatedFormat('d M Y, H:i') }}
                        </time>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mk-offers-pagination">
            {{ $paginator->onEachSide(1)->links() }}
        </div>
    @endif
</div>
