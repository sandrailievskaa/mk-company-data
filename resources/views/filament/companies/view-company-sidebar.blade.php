@php
    use App\Models\Company;

    /** @var \Filament\Infolists\Components\Entry $entry */
    $record = $entry->getRecord();
@endphp
@if ($record instanceof Company)
    <div class="mk-cv-side">
        <div class="mk-cv-card mk-cv-side__block">
            <div class="mk-cv-card__kicker">{{ __('примарни') }}</div>
            <h2 class="mk-cv-card__title">{{ __('Контакт') }}</h2>
            <dl class="mk-cv-kv">
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Е-пошта') }}</dt>
                    <dd>
                        @if (filled($record->email))
                            <a class="mk-cv-kv__link" href="mailto:{{ e($record->email) }}">{{ $record->email }}</a>
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> {{ __('Нема') }}</span>
                        @endif
                    </dd>
                </div>
                @if (filled($record->email) && $record->email_source)
                    <div class="mk-cv-kv__row">
                        <dt>{{ __('Извор на е-пошта') }}</dt>
                        <dd>
                            <span class="mk-cv-pill @if($record->email_source->isInferred()) mk-cv-pill--warn @else mk-cv-pill--ok @endif">{{ $record->email_source->getLabel() }}</span>
                            @if ($record->email_source->isInferred())
                                <span class="mk-cv-muted">{{ __('непроверена') }}</span>
                            @endif
                            @if ($record->email_domain_has_mx !== null)
                                <span class="mk-cv-muted">{{ $record->email_domain_has_mx ? __('доменот прима пошта (MX)') : __('доменот нема MX') }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Телефон') }}</dt>
                    <dd>
                        @if (filled($record->phone))
                            <a class="mk-cv-kv__link" href="tel:{{ e(preg_replace('/\s+/', '', (string) $record->phone)) }}">{{ $record->phone }}</a>
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> {{ __('Нема') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Веб-сајт') }}</dt>
                    <dd>
                        @if (filled($record->website))
                            <a class="mk-cv-kv__link" href="{{ e($record->website) }}" target="_blank" rel="noopener noreferrer">{{ preg_replace('#^https?://(www\.)?#i', '', rtrim($record->website, '/')) }}</a>
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> {{ __('Нема') }}</span>
                        @endif
                    </dd>
                </div>
                @if (filled($record->facebook_url) || filled($record->instagram_url))
                    <div class="mk-cv-kv__row">
                        <dt>{{ __('Социјални мрежи') }}</dt>
                        <dd>
                            @if (filled($record->facebook_url))
                                <a class="mk-cv-kv__link" href="{{ e($record->facebook_url) }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                            @endif
                            @if (filled($record->instagram_url))
                                <a class="mk-cv-kv__link" href="{{ e($record->instagram_url) }}" target="_blank" rel="noopener noreferrer">Instagram</a>
                            @endif
                        </dd>
                    </div>
                @endif
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Локација') }}</dt>
                    <dd>
                        @php $loc = collect([$record->city, $record->address])->filter()->implode(' · '); @endphp
                        @if (filled($loc))
                            {{ $loc }}
                        @else
                            <span class="mk-cv-missing"><span class="mk-cv-missing__i">—</span> {{ __('Нема') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="mk-cv-card mk-cv-side__block">
            <div class="mk-cv-card__kicker">{{ __('регистар') }}</div>
            <h2 class="mk-cv-card__title">{{ __('Јавни податоци') }}</h2>
            <dl class="mk-cv-kv">
                <div class="mk-cv-kv__row"><dt>{{ __('ЕДБ') }}</dt><dd>{{ $record->edb ?: '—' }}</dd></div>
                <div class="mk-cv-kv__row"><dt>{{ __('ЕМБС') }}</dt><dd>{{ $record->embs ?: '—' }}</dd></div>
                <div class="mk-cv-kv__row"><dt>{{ __('Основана') }}</dt><dd>{{ $record->founded_on?->format('d.m.Y') ?? '—' }}</dd></div>
                <div class="mk-cv-kv__row"><dt>{{ __('Големина') }}</dt><dd>{{ $record->company_size ?: '—' }}</dd></div>
                <div class="mk-cv-kv__row"><dt>{{ __('Вработени') }}</dt><dd>{{ $record->employees ?? '—' }}</dd></div>
                <div class="mk-cv-kv__row"><dt>{{ __('Дејности') }}</dt><dd>{{ $record->activities ?: '—' }}</dd></div>
                <div class="mk-cv-kv__row"><dt>{{ __('Извори') }}</dt><dd>zk.mk @if (filled($record->companywall_url)) · <a class="mk-cv-kv__link" href="{{ e(rtrim(config('scraping.companywall_url'), '/').$record->companywall_url) }}" target="_blank" rel="noopener noreferrer">CompanyWall</a>@endif</dd></div>
            </dl>
        </div>

        <div class="mk-cv-card mk-cv-side__block">
            <div class="mk-cv-card__kicker">{{ __('квалитет') }}</div>
            <h2 class="mk-cv-card__title">{{ __('Податоци & освежување') }}</h2>
            <dl class="mk-cv-kv">
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Забелешка (AI)') }}</dt>
                    <dd>
                        @if (filled($record->data_quality_note))
                            <span class="mk-cv-kv__note">{{ $record->data_quality_note }}</span>
                        @else
                            <span class="mk-cv-muted">—</span>
                        @endif
                    </dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Последно ажурирање') }}</dt>
                    <dd>{{ $record->updated_at?->translatedFormat('d.m.Y H:i') ?? '—' }}</dd>
                </div>
                <div class="mk-cv-kv__row">
                    <dt>{{ __('Креирано') }}</dt>
                    <dd>{{ $record->created_at?->translatedFormat('d.m.Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endif
