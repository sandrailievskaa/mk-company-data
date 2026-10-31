<div @class(['mk-guide', 'mk-guide--collapsed' => $dismissed])>
    @if ($dismissed)
        <div class="mk-guide__bar">
            <span class="mk-guide__bar-text">{{ __('Водич „Започни“: :done од :total чекори', ['done' => $done, 'total' => $total]) }}</span>
            <button type="button" class="mk-guide__link" wire:click="restore">{{ __('Покажи го водичот') }}</button>
        </div>
    @else
        <header class="mk-guide__head">
            <div>
                <h2 class="mk-guide__title">
                    {{ $done >= $total ? __('Водичот е завршен') : __('Започни за :total чекори', ['total' => $total]) }}
                </h2>
                <p class="mk-guide__sub">
                    {{ $done >= $total
                        ? __('Го помина целиот тек: од јавни податоци до следење на понудата.')
                        : __('Од јавни податоци до подготвена понуда. Чекорите се означуваат сами кога ќе ги направиш.') }}
                </p>
            </div>
            <div class="mk-guide__head-side">
                <span class="mk-guide__count">{{ $done }} / {{ $total }}</span>
                <button type="button" class="mk-guide__link" wire:click="dismiss">{{ __('Сокриј') }}</button>
            </div>
        </header>

        <div class="mk-guide__progress" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}">
            <span style="width: {{ $total > 0 ? round(100 * $done / $total) : 0 }}%"></span>
        </div>

        <ol class="mk-guide__steps">
            @foreach ($steps as $key => $step)
                <li @class(['mk-guide__step', 'mk-guide__step--done' => $step['done'], 'mk-guide__step--next' => $key === $next])>
                    <span class="mk-guide__badge" aria-hidden="true">
                        @if ($step['done'])
                            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                        @else
                            {{ $loop->iteration }}
                        @endif
                    </span>
                    <div class="mk-guide__step-body">
                        <strong class="mk-guide__step-title">{{ $step['title'] }}</strong>
                        <span class="mk-guide__step-text">{{ $step['text'] }}</span>
                        <a class="mk-guide__step-link" href="{{ $step['url'] }}">
                            {{ $step['done'] ? __('Отвори пак') : __('Покажи ми') }} <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
