<x-filament-panels::page>
    <div wire:poll.15s class="fi-section-content-ctn">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Целиот тек од јавни податоци до подготвена понуда. Секоја фаза покажува колку компании се обработени.
            Задачите работат во позадина (queue: <strong>{{ $queue }}</strong>).
        </p>

        <div style="display: grid; gap: 1rem; margin-top: 1rem;">
            @foreach ($stages as $stage)
                @php
                    $pct = $stage['total'] > 0 ? (int) round(100 * $stage['done'] / $stage['total']) : 0;
                @endphp
                <x-filament::section :heading="$stage['title']" :description="$stage['description']">
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div style="flex: 1 1 260px; min-width: 200px;">
                            <div style="height: 10px; border-radius: 999px; background: rgba(128,128,128,.2); overflow: hidden;">
                                <div style="height: 100%; width: {{ $pct }}%; background: #6366f1;"></div>
                            </div>
                        </div>
                        <strong>{{ number_format($stage['done']) }} / {{ number_format($stage['total']) }} ({{ $pct }}%)</strong>
                        @if ($stage['pending'] !== null)
                            <span class="text-sm text-gray-500 dark:text-gray-400">на чекање: {{ number_format($stage['pending']) }}</span>
                        @endif
                    </div>
                    @if (count($stage['details']) > 0)
                        <ul class="text-sm text-gray-600 dark:text-gray-300" style="margin-top: .75rem; display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            @foreach ($stage['details'] as $detail)
                                <li>{{ $detail }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
