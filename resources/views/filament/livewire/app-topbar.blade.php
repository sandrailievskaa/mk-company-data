@php
    use Filament\Enums\UserMenuPosition;
    use Filament\Support\Icons\Heroicon;
    use Filament\View\PanelsIconAlias;
    use Illuminate\View\ComponentAttributeBag;
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
    $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
    $hasTopNavigation = filament()->hasTopNavigation();
    $hasNavigation = filament()->hasNavigation();
    $hasTenancy = filament()->hasTenancy();
@endphp

<div
    class="fi-topbar-ctn mk-app-topbar-ctn"
    x-data
    x-on:keydown.window="if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); $refs.mkTopbarSearch?.focus(); }"
>
    <nav class="fi-topbar mk-app-topbar">
        @if ($hasNavigation)
            <x-filament::icon-button
                color="gray"
                :icon="Heroicon::OutlinedBars3"
                :icon-alias="PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.open()"
                x-show="! $store.sidebar.isOpen"
                class="fi-topbar-open-sidebar-btn"
            />

            <x-filament::icon-button
                color="gray"
                :icon="Heroicon::OutlinedXMark"
                :icon-alias="PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                class="fi-topbar-close-sidebar-btn"
            />
        @endif

        <div class="fi-topbar-start">
            @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                <div
                    x-show="$store.sidebar.isOpen || @js($isSidebarCollapsibleOnDesktop)"
                    class="fi-topbar-collapse-sidebar-btn-ctn"
                >
                    @if ($isSidebarCollapsibleOnDesktop)
                        <x-filament::icon-button
                            color="gray"
                            :icon="$isRtl ? Heroicon::OutlinedChevronLeft : Heroicon::OutlinedChevronRight"
                            :icon-alias="
                                $isRtl
                                    ? [
                                        PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
                                        PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
                                    ]
                                    : PanelsIconAlias::SIDEBAR_EXPAND_BUTTON
                            "
                            icon-size="lg"
                            :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                            x-cloak
                            x-data="{}"
                            x-on:click="$store.sidebar.open()"
                            x-show="! $store.sidebar.isOpen"
                            class="fi-topbar-open-collapse-sidebar-btn"
                        />
                    @endif

                    @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                        <x-filament::icon-button
                            color="gray"
                            :icon="$isRtl ? Heroicon::OutlinedChevronRight : Heroicon::OutlinedChevronLeft"
                            :icon-alias="
                                $isRtl
                                    ? [
                                        PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
                                        PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
                                    ]
                                    : PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON
                            "
                            icon-size="lg"
                            :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                            x-cloak
                            x-data="{}"
                            x-on:click="$store.sidebar.close()"
                            x-show="$store.sidebar.isOpen"
                            class="fi-topbar-close-collapse-sidebar-btn"
                        />
                    @endif
                </div>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_LOGO_BEFORE) }}

            @if ($homeUrl = filament()->getHomeUrl())
                <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                    <x-filament-panels::logo />
                </a>
            @else
                <x-filament-panels::logo />
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_LOGO_AFTER) }}
        </div>

        @if ($hasTopNavigation || (! $hasNavigation))
            @if ($hasTenancy && filament()->hasTenantMenu())
                <x-filament-panels::tenant-menu teleport />
            @endif
        @endif

        <div
            @if ($hasTenancy)
                x-persist="topbar.end.panel-{{ filament()->getId() }}.tenant-{{ filament()->getTenant()?->getKey() }}"
            @else
                x-persist="topbar.end.panel-{{ filament()->getId() }}"
            @endif
            class="fi-topbar-end mk-app-topbar__end"
        >
            <div
                class="mk-app-topbar__search-ctn"
                x-on:click.outside="$wire.set('searchOpen', false)"
            >
                <div class="mk-app-topbar__search">
                    <span class="mk-app-topbar__search-icon" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html(
                            Heroicon::OutlinedMagnifyingGlass,
                            null,
                            (new ComponentAttributeBag)->class(['h-4 w-4']),
                        ) }}
                    </span>
                    <input
                        x-ref="mkTopbarSearch"
                        type="search"
                        class="mk-app-topbar__search-input"
                        placeholder="Search companies, offers, signals…"
                        autocomplete="off"
                        wire:model.live.debounce.250ms="search"
                        wire:input="openSearchIfNeeded"
                    />
                    <span class="mk-app-topbar__kbd" title="Пребарај (Ctrl/⌘ + K)">
                        <span
                            x-cloak
                            x-text="(navigator && navigator.platform && (navigator.platform.includes('Mac') || (navigator.userAgentData && navigator.userAgentData.platform === 'macOS'))) ? '⌘K' : 'Ctrl+K'"
                        ></span>
                    </span>
                </div>

                <div
                    @class(['mk-app-topbar__results', 'mk-app-topbar__results--open' => $this->searchOpen && $search !== ''])
                >
                    @if (trim($search) !== '' && $this->companySearchResults->isNotEmpty())
                        <ul class="mk-app-topbar__results-list" role="listbox">
                            @foreach ($this->companySearchResults as $c)
                                <li role="option">
                                    <a
                                        class="mk-app-topbar__result"
                                        href="{{ $this->companyEditUrl($c) }}"
                                        wire:navigate
                                    >
                                        <span class="mk-app-topbar__result-name">{{ $c->name }}</span>
                                        <span class="mk-app-topbar__result-meta">
                                            {{ $c->sector?->getLabel() ?? '—' }}
                                            @if (filled($c->city))
                                                <span class="mk-app-topbar__result-sep" aria-hidden="true">·</span>
                                                {{ $c->city }}
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @elseif (trim($search) !== '' && $this->companySearchResults->isEmpty())
                        <p class="mk-app-topbar__results-empty">Нема совпаѓања за компании.</p>
                    @endif
                </div>
            </div>

            @if (filament()->auth()->check())
                @include('filament.components.mk-theme-switcher')

                @if (filament()->hasUserMenu() && filament()->getUserMenuPosition() === UserMenuPosition::Topbar)
                    <x-filament-panels::user-menu />
                @endif
            @endif
        </div>
    </nav>

    <x-filament-actions::modals />
</div>
