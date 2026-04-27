@php
    use App\Filament\Resources\Companies\CompanyResource;
@endphp

<a
    href="{{ CompanyResource::getUrl('index') }}"
    wire:navigate
    class="mk-offer-view__back"
>
    ← Сите компании
</a>
