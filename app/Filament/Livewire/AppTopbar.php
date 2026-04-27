<?php

namespace App\Filament\Livewire;

use App\Enums\SectorEnum;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Livewire\Topbar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class AppTopbar extends Topbar
{
    public string $search = '';

    public bool $searchOpen = false;

    #[On('refresh-topbar')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('filament.livewire.app-topbar');
    }

    public function updatedSearch(string $value): void
    {
        $this->searchOpen = $value !== '' && $this->getCompanySearchResultsProperty()->isNotEmpty();
    }

    public function openSearchIfNeeded(): void
    {
        if (trim($this->search) === '') {
            $this->searchOpen = false;

            return;
        }
        $this->searchOpen = $this->getCompanySearchResultsProperty()->isNotEmpty();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->searchOpen = false;
    }

    public function companyEditUrl(Company $company): string
    {
        return CompanyResource::getUrl('edit', ['record' => $company]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Company>
     */
    public function getCompanySearchResultsProperty(): Collection
    {
        $term = trim($this->search);
        if (mb_strlen($term) < 1) {
            return collect();
        }
        $like = '%'.addcslashes($term, '%_\\').'%';
        $lower = mb_strtolower($term);

        return Company::query()
            ->where(function ($q) use ($like, $lower) {
                $q->where('name', 'like', $like)
                    ->orWhere('city', 'like', $like);

                foreach (SectorEnum::cases() as $e) {
                    if (str_contains(mb_strtolower($e->getLabel() ?? ''), $lower)) {
                        $q->orWhere('sector', $e);
                    }
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();
    }
}
