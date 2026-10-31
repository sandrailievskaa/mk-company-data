@php
    $scenes = [
        ['title' => __('Од јавни податоци до подготвена понуда'), 'text' => __('Системот собира јавни податоци, ги чисти, ги рангира и пишува понуди. За околу една минута го гледаш целиот тек.')],
        ['title' => __('Најди ги вистинските компании'), 'text' => __('Пребарај и филтрирај по сектор, град и е-пошта. Подреди по индекс на активност.')],
        ['title' => __('Види од каде е секој податок'), 'text' => __('Индексот има три дела, а секоја е-пошта го покажува својот извор. Нема претпоставки.')],
        ['title' => __('AI пишува понуда и препорачува'), 'text' => __('Внеси контекст, а AI ја генерира понудата и предлага најсоодветни компании со образложение.')],
        ['title' => __('Испрати еднаш и следи'), 'text' => __('Секоја компанија ја добива понудата најмногу еднаш, а статусот се следи: чека, испратена, отворена.')],
        ['title' => __('Подготвен си'), 'text' => __('Продолжи со водичот „Започни“ на контролната табла. Видеото можеш да го пуштиш пак кога сакаш.')],
    ];
    $first = $showcase[0] ?? ['name' => '—', 'city' => '', 'pct' => 0, 'parts' => ['email' => 0, 'presence' => 0, 'frequency' => 0]];
@endphp

<div
    class="mk-intro"
    x-data="{
        open: {{ $introSeen ? 'false' : 'true' }},
        scene: 0,
        total: {{ count($scenes) }},
        dur: 8500,
        elapsed: 0,
        playing: true,
        timer: null,
        init() {
            this.$watch('open', (v) => {
                if (v) { this.scene = 0; this.elapsed = 0; this.playing = true; this.start(); } else { this.stop(); }
                document.documentElement.classList.toggle('mk-intro-lock', v);
            });
            if (this.open) { this.start(); document.documentElement.classList.add('mk-intro-lock'); }
        },
        start() {
            this.stop();
            this.timer = setInterval(() => {
                if (! this.playing) return;
                this.elapsed += 100;
                if (this.elapsed >= this.dur) {
                    this.elapsed = 0;
                    if (this.scene < this.total - 1) { this.scene++; } else { this.playing = false; }
                }
            }, 100);
        },
        stop() { clearInterval(this.timer); },
        go(i) { this.scene = i; this.elapsed = 0; this.playing = true; },
        next() { if (this.scene < this.total - 1) this.go(this.scene + 1); },
        prev() { if (this.scene > 0) this.go(this.scene - 1); },
        close() { this.open = false; $wire.markIntroSeen(); },
    }"
    @open-mk-intro.window="open = true"
    @keydown.escape.window="if (open) close()"
    @keydown.arrow-right.window="if (open) next()"
    @keydown.arrow-left.window="if (open) prev()"
>
    <div class="mk-intro__backdrop" x-show="open" x-cloak x-transition.opacity @click.self="close()">
        <div class="mk-intro__dialog" role="dialog" aria-modal="true" aria-label="{{ __('Видео упатство') }}">
            <button type="button" class="mk-intro__close" @click="close()" aria-label="{{ __('Затвори') }}">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
            </button>

            @if ($videoUrl)
                <video class="mk-intro__video" src="{{ $videoUrl }}" controls autoplay playsinline></video>
            @else
                <div class="mk-intro__stage">
                    {{-- 1 · тек --}}
                    <div class="mk-scene" x-show="scene === 0" x-cloak>
                        <div class="mk-scene__flow">
                            @foreach ([__('Собирање'), __('Чистење'), __('Индекс'), __('AI понуда'), __('Следење')] as $i => $label)
                                <div class="mk-scene__node" style="--d: {{ $i * 0.55 }}s">
                                    <span class="mk-scene__node-dot">{{ $i + 1 }}</span>
                                    <span class="mk-scene__node-label">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mk-scene__big" style="--d: 3s">
                            <strong>{{ \App\Support\Fmt::num($companyCount) }}</strong>
                            <span>{{ __('компании, секоја е-пошта со познато потекло') }}</span>
                        </div>
                    </div>

                    {{-- 2 · пребарување --}}
                    <div class="mk-scene" x-show="scene === 1" x-cloak>
                        <div class="mk-scene__panel">
                            <div class="mk-scene__search">
                                <span class="mk-scene__typed">{{ __('Пребарај компании…') }}</span>
                            </div>
                            <div class="mk-scene__chips">
                                <span class="mk-scene__chip" style="--d: 1s">{{ __('Е-пошта: со') }}</span>
                                <span class="mk-scene__chip" style="--d: 1.6s">{{ __('Сектор') }}</span>
                                <span class="mk-scene__chip" style="--d: 2.2s">{{ __('Град') }}</span>
                            </div>
                            @foreach ($showcase as $i => $row)
                                <div class="mk-scene__row" style="--d: {{ 2.4 + $i * 0.5 }}s">
                                    <span class="mk-scene__row-name">{{ $row['name'] }}<small>{{ $row['city'] }}</small></span>
                                    <span class="mk-scene__bar"><i style="--w: {{ $row['pct'] }}%; --d: {{ 2.8 + $i * 0.5 }}s"></i></span>
                                    <span class="mk-scene__row-val">{{ \App\Support\Fmt::num($row['pct'] / 100, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3 · потекло --}}
                    <div class="mk-scene" x-show="scene === 2" x-cloak>
                        <div class="mk-scene__panel mk-scene__panel--split">
                            <div class="mk-scene__ring" style="--p: {{ $first['pct'] }}">
                                <span>{{ $first['pct'] }}<small>/100</small></span>
                            </div>
                            <div class="mk-scene__parts">
                                @foreach ([['E', __('е-пошта'), (int) round($first['parts']['email'] * 100), \App\Support\Fmt::num($weights['email'], 1)], ['P', __('јавни извори'), (int) round($first['parts']['presence'] * 100), \App\Support\Fmt::num($weights['presence'], 1)], ['F', __('фреквенција'), (int) round($first['parts']['frequency'] * 100), \App\Support\Fmt::num($weights['frequency'], 1)]] as $i => $part)
                                    <div class="mk-scene__part" style="--d: {{ 0.8 + $i * 0.6 }}s">
                                        <span class="mk-scene__part-k">{{ $part[0] }} · {{ $part[1] }} <small>×{{ $part[3] }}</small></span>
                                        <span class="mk-scene__bar"><i style="--w: {{ $part[2] }}%; --d: {{ 1 + $i * 0.6 }}s"></i></span>
                                    </div>
                                @endforeach
                                <div class="mk-scene__source" style="--d: 3s">
                                    <span class="mk-scene__pill mk-scene__pill--ok">{{ __('Пронајдена на веб-сајт') }}</span>
                                    <span class="mk-scene__pill">{{ __('Домен прима пошта (MX)') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4 · AI понуда --}}
                    <div class="mk-scene" x-show="scene === 3" x-cloak>
                        <div class="mk-scene__panel">
                            <div class="mk-scene__label">✦ {{ __('Генерирај со AI') }}</div>
                            <div class="mk-scene__lines">
                                <span style="--d: 0.6s; --w: 78%"></span>
                                <span style="--d: 1.2s; --w: 94%"></span>
                                <span style="--d: 1.8s; --w: 86%"></span>
                                <span style="--d: 2.4s; --w: 52%"></span>
                            </div>
                            <div class="mk-scene__label" style="margin-top: 1rem">✦ {{ __('AI препораки') }}</div>
                            <div class="mk-scene__chips">
                                @foreach ($showcase as $i => $row)
                                    <span class="mk-scene__chip mk-scene__chip--accent" style="--d: {{ 3 + $i * 0.5 }}s">{{ $row['name'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 5 · испрати и следи --}}
                    <div class="mk-scene" x-show="scene === 4" x-cloak>
                        <div class="mk-scene__panel mk-scene__panel--center">
                            <div class="mk-scene__statuses">
                                <span class="mk-scene__status" style="--d: 0.6s">{{ __('Чека') }}</span>
                                <span class="mk-scene__arrow">→</span>
                                <span class="mk-scene__status" style="--d: 2s">{{ __('Испратена') }}</span>
                                <span class="mk-scene__arrow">→</span>
                                <span class="mk-scene__status mk-scene__status--ok" style="--d: 3.4s">{{ __('Отворена') }}</span>
                            </div>
                            <div class="mk-scene__once" style="--d: 4.6s">✓ {{ __('Најмногу еднаш по компанија') }}</div>
                            <div class="mk-scene__note" style="--d: 5.4s">{{ __('Во пробен режим пораките одат во лог, не надвор.') }}</div>
                        </div>
                    </div>

                    {{-- 6 · крај --}}
                    <div class="mk-scene mk-scene--end" x-show="scene === 5" x-cloak>
                        <div class="mk-scene__check">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="40" height="40" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                        </div>
                        <button type="button" class="mk-intro__cta" @click="close()">{{ __('Започни') }} →</button>
                    </div>
                </div>

                <div class="mk-intro__caption">
                    @foreach ($scenes as $i => $scene)
                        <div x-show="scene === {{ $i }}" x-cloak>
                            <h3 class="mk-intro__title">{{ $scene['title'] }}</h3>
                            <p class="mk-intro__text">{{ $scene['text'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mk-intro__controls">
                    <button type="button" class="mk-intro__btn" @click="playing = ! playing" :aria-label="playing ? '{{ __('Паузирај') }}' : '{{ __('Пушти') }}'">
                        <span x-show="playing" x-cloak>❚❚</span>
                        <span x-show="! playing" x-cloak>▶</span>
                    </button>
                    <div class="mk-intro__dots">
                        @foreach ($scenes as $i => $scene)
                            <button type="button" class="mk-intro__dot" :class="{ 'is-done': scene > {{ $i }}, 'is-active': scene === {{ $i }} }" @click="go({{ $i }})" aria-label="{{ $scene['title'] }}">
                                <i :style="scene === {{ $i }} ? 'width:' + Math.min(100, elapsed / dur * 100) + '%' : ''"></i>
                            </button>
                        @endforeach
                    </div>
                    <button type="button" class="mk-intro__skip" @click="close()">{{ __('Прескокни') }}</button>
                </div>
            @endif
        </div>
    </div>
</div>
