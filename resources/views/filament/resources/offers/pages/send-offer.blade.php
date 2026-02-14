<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Понуда: {{ $this->getRecord()->title }}</h3>
            @if($this->getRecord()->subject)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2"><strong>Предмет:</strong> {{ $this->getRecord()->subject }}</p>
            @endif
            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $this->getRecord()->content }}</p>
            </div>
        </div>

        <form wire:submit="send">
            {{ $this->form }}

            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button type="button" color="gray" tag="a" href="{{ \App\Filament\Resources\Offers\OfferResource::getUrl('index') }}">
                    Откажи
                </x-filament::button>
                <x-filament::button type="submit" color="success">
                    Испрати понуди
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
