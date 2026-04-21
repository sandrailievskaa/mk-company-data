<div class="space-y-4">
    @if (! empty($this->aiRecommendationError))
        <div class="rounded-md bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-950/40 dark:text-danger-200">
            {{ $this->aiRecommendationError }}
        </div>
    @endif

    @if (empty($this->aiRecommendations))
        <div class="text-sm text-gray-600 dark:text-gray-300">
            Нема генерирани препораки. Кликни „Generate“ за AI да предложи компании.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Избор</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Компанија</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Град</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">AI индекс</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Причина</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($this->aiRecommendations as $rec)
                        <tr>
                            <td class="px-4 py-2 align-top">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700"
                                    wire:model.live="aiRecommendationSelections.{{ $rec['company_id'] }}"
                                />
                            </td>
                            <td class="px-4 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $rec['name'] ?? ('#' . $rec['company_id']) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $rec['sector_label'] ?? ($rec['sector'] ?? '-') }}
                                </div>
                            </td>
                            <td class="px-4 py-2 align-top text-sm text-gray-700 dark:text-gray-200">
                                {{ $rec['city'] ?? '-' }}
                            </td>
                            <td class="px-4 py-2 align-top text-sm text-gray-700 dark:text-gray-200">
                                {{ isset($rec['activity_index']) ? number_format((float) $rec['activity_index'], 4) : '-' }}
                            </td>
                            <td class="px-4 py-2 align-top text-sm text-gray-700 dark:text-gray-200">
                                {{ ! empty($rec['has_email']) ? 'Да' : 'Не' }}
                            </td>
                            <td class="px-4 py-2 align-top text-sm text-gray-700 dark:text-gray-200">
                                {{ $rec['reason'] ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400">
            Забелешка: Избраните компании ќе се додадат во “target list” откако ќе ја креираш понудата.
        </div>
    @endif
</div>

