@php
    use App\Models\Company;

    /** @var \Filament\Infolists\Components\Entry $entry */
    $record = $entry->getRecord();
@endphp
@if ($record instanceof Company)
    <div class="mk-cv-side">
        <div class="mk-cv-card mk-cv-side__block">
            <div class="mk-cv-card__kicker">примарни</div>
            <h2 class="mk-cv-card__title">Контакт</h2>
            <dl class="mk-cv-kv">
                <div class="mk-cv-kv__row">
                    <dt>Е-пошта</dt>
                    <dd>
                        @if (filled($record->email))
                            <a class="mk-cv-kv__link" href="mailto:{{ e($record->email) }}">{{ $record->email }}</a>
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> Нема</span>
                        @endif
                    </dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>Телефон</dt>
                    <dd>
                        @if (filled($record->phone))
                            <a class="mk-cv-kv__link" href="tel:{{ e(preg_replace('/\s+/', '', (string) $record->phone)) }}">{{ $record->phone }}</a>
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> Нема</span>
                        @endif
                    </dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>Локација</dt>
                    <dd>
                        @php $loc = collect([$record->city, $record->address])->filter()->implode(' · '); @endphp
                        @if (filled($loc))
                            {{ $loc }}
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> Нема</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="mk-cv-card mk-cv-side__block">
            <div class="mk-cv-card__kicker">квалитет</div>
            <h2 class="mk-cv-card__title">Податоци &amp; освежување</h2>
            <dl class="mk-cv-kv">
                <div class="mk-cv-kv__row">
                    <dt>Забелешка (AI)</dt>
                    <dd>
                        @if (filled($record->data_quality_note))
                            <span class="mk-cv-kv__note">{{ $record->data_quality_note }}</span>
                        @else
                            <span class="mk-cv-muted">—</span>
                        @endif
                    </dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>Последно ажурирање</dt>
                    <dd>{{ $record->updated_at?->translatedFormat('d.m.Y H:i') ?? '—' }}</dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>Креирано</dt>
                    <dd>{{ $record->created_at?->translatedFormat('d.m.Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endif
