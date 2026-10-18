<?php

namespace App\Http\Controllers\Api;

use App\Enums\SectorEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * GET /api/companies
     *
     * Filters: sector, city, has_email, has_website, min_index, q (name). Sorted by
     * activity index (descending) unless sort=name. 25 per page by default (max 100).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'sector' => ['nullable', Rule::enum(SectorEnum::class)],
            'city' => ['nullable', 'string', 'max:100'],
            'has_email' => ['nullable', 'boolean'],
            'has_website' => ['nullable', 'boolean'],
            'min_index' => ['nullable', 'numeric', 'between:0,1'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:index,name'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = Company::query()
            ->when($filters['sector'] ?? null, fn ($q, $v) => $q->where('sector', $v))
            ->when($filters['city'] ?? null, fn ($q, $v) => $q->where('city', 'like', '%'.trim($v).'%'))
            ->when($request->has('has_email'), fn ($q) => $request->boolean('has_email') ? $q->whereNotNull('email') : $q->whereNull('email'))
            ->when($request->has('has_website'), fn ($q) => $request->boolean('has_website') ? $q->whereNotNull('website') : $q->whereNull('website'))
            ->when(isset($filters['min_index']), fn ($q) => $q->where('activity_index', '>=', (float) $filters['min_index']))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.addcslashes($v, '%_\\').'%'));

        ($filters['sort'] ?? 'index') === 'name'
            ? $query->orderBy('name')
            : $query->orderByDesc('activity_index')->orderBy('name');

        return CompanyResource::collection($query->paginate((int) ($filters['per_page'] ?? 25))->withQueryString());
    }

    /**
     * GET /api/companies/{company}
     */
    public function show(Company $company): CompanyResource
    {
        return new CompanyResource($company);
    }
}
