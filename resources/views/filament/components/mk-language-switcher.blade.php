@php
    use Filament\Support\Icons\Heroicon;

    $current = app()->getLocale();
    $languages = [
        'mk' => ['code' => 'МК', 'label' => 'Македонски'],
        'en' => ['code' => 'EN', 'label' => 'English'],
    ];
@endphp

<div
    class="mk-lang-switcher"
    role="group"
    aria-label="{{ __('Јазик') }}"
>
    <span class="mk-lang-switcher__icon" aria-hidden="true">
        {{ \Filament\Support\generate_icon_html(Heroicon::OutlinedLanguage) }}
    </span>
    @foreach ($languages as $locale => $language)
        {{-- Full page load (no wire:navigate) so every view is rendered in the new language. --}}
        <a
            href="{{ route('locale.switch', $locale) }}"
            title="{{ $language['label'] }}"
            aria-label="{{ $language['label'] }}"
            @class(['mk-lang-switcher__btn', 'mk-lang-switcher__btn--active' => $current === $locale])
            @if ($current === $locale) aria-current="true" @endif
        >{{ $language['code'] }}</a>
    @endforeach
</div>
