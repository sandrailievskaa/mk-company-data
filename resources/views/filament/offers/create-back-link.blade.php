@php
    use App\Filament\Resources\Offers\OfferResource;
@endphp

<a
    href="{{ OfferResource::getUrl('index') }}"
    wire:navigate
    class="mk-offer-create__back"
>
    ← Сите понуди
</a>
