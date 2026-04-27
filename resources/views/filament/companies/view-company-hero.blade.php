@php
    use App\Filament\Resources\Companies\CompanyResource;
    use App\Filament\Resources\Offers\OfferResource;
    use App\Models\Company;
    use Illuminate\Support\Str;

    /** @var \Filament\Infolists\Components\Entry $entry */
    $record = $entry->getRecord();
@endphp
@if ($record instanceof Company)
    @php
        $initial = Str::upper(Str::substr($record->name, 0, 1));
        $ref = 'КП-'.str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
        $editUrl = CompanyResource::getUrl('edit', ['record' => $record]);
        $newOfferUrl = OfferResource::getUrl('create').'?company_id='.$record->id;
        $location = collect([$record->city, $record->address])
            ->filter()
            ->implode(' · ');
        $slug = $record->sector ? strtolower($record->sector->name) : '';
    @endphp

    <div class="mk-cv-hero">
        <div class="mk-cv-hero__identity">
            <div class="mk-cv-hero__avatar" aria-hidden="true">{{ $initial }}</div>
            <div class="mk-cv-hero__main">
                <div class="mk-cv-hero__tags">
                    <span class="mk-cv-hero__ref-pill">{{ $ref }}</span>
                    @if ($record->sector)
                        <span class="mk-sector-pill mk-sector-pill--{{ e($slug) }}">{{ $record->sector->getLabel() }}</span>
                    @endif
                </div>
                <h1 class="mk-cv-hero__title">{{ $record->name }}</h1>
                <div class="mk-cv-hero__meta" role="list">
                    @if (filled($location))
                        <span class="mk-cv-hero__meta-item" role="listitem">
                            <span class="mk-cv-hero__meta-icon" aria-hidden="true">◎</span>
                            {{ $location }}
                        </span>
                    @endif
                    @if (filled($record->email))
                        <a href="mailto:{{ e($record->email) }}" class="mk-cv-hero__meta-item mk-cv-hero__meta-item--link" role="listitem">
                            <span class="mk-cv-hero__meta-icon" aria-hidden="true">✉</span>
                            {{ $record->email }}
                        </a>
                    @endif
                    @if (filled($record->phone))
                        <a href="tel:{{ e(preg_replace('/\s+/', '', (string) $record->phone)) }}" class="mk-cv-hero__meta-item mk-cv-hero__meta-item--link" role="listitem">
                            <span class="mk-cv-hero__meta-icon" aria-hidden="true">☎</span>
                            {{ $record->phone }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="mk-cv-hero__actions">
            <a href="{{ $editUrl }}" wire:navigate class="mk-cv-btn mk-cv-btn--outline">Уреди</a>
            <a href="{{ $newOfferUrl }}" wire:navigate class="mk-cv-btn mk-cv-btn--primary">
                <span class="mk-cv-btn__spark" aria-hidden="true">✦</span> Нова понуда
            </a>
        </div>
    </div>
@endif
