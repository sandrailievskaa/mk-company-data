@php
    use Filament\Support\Icons\Heroicon;
    use Filament\View\PanelsIconAlias;

    $modes = [
        'light' => Heroicon::Sun,
        'dark' => Heroicon::Moon,
        'system' => Heroicon::ComputerDesktop,
    ];
@endphp

@if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
    <div
        x-data="{ theme: null }"
        x-init="
            $watch('theme', () => {
                $dispatch('theme-changed', theme)
            })

            theme = localStorage.getItem('theme') || @js(filament()->getDefaultThemeMode()->value)
        "
        class="fi-theme-switcher mk-topbar-theme-switcher"
        role="group"
        aria-label="Тема (светла / темна / систем)"
    >
        @foreach ($modes as $mode => $icon)
            @php
                $label = __("filament-panels::layout.actions.theme_switcher.{$mode}.label");
            @endphp
            <button
                type="button"
                aria-label="{{ $label }}"
                x-on:click="theme = @js($mode)"
                x-tooltip="{
                    content: @js($label),
                    theme: $store.theme,
                }"
                x-bind:class="{ 'fi-active': theme === @js($mode) }"
                class="fi-theme-switcher-btn"
            >
                {{
                    \Filament\Support\generate_icon_html($icon, alias: match ($mode) {
                        'light' => PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON,
                        'dark' => PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON,
                        'system' => PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON,
                    })
                }}
            </button>
        @endforeach
    </div>
@endif
