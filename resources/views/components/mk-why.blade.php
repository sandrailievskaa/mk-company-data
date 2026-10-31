@props(['title', 'formula' => null])

{{-- „Зошто?“: мал објаснувачки балонче покрај број или ознака. Работи со тастатура (Esc го затвора). --}}
<span
    class="mk-why"
    x-data="{ open: false, flip: false }"
    x-init="$watch('open', (v) => { if (v) { flip = false; $nextTick(() => { flip = $refs.pop.getBoundingClientRect().right > window.innerWidth - 12; }); } })"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        class="mk-why__btn"
        @click.stop.prevent="open = ! open"
        :aria-expanded="open.toString()"
        aria-label="{{ __('Зошто?') }} {{ $title }}"
    >
        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" /></svg>
    </button>
    <span
        x-ref="pop"
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        class="mk-why__pop"
        :class="{ 'mk-why__pop--end': flip }"
        role="dialog"
    >
        <span class="mk-why__eyebrow">{{ __('Зошто?') }}</span>
        <strong class="mk-why__title">{{ $title }}</strong>
        <span class="mk-why__body">{{ $slot }}</span>
        @if ($formula)
            <code class="mk-why__formula">{{ $formula }}</code>
        @endif
    </span>
</span>
