<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public JSON shape of a company. The e-mail always carries its origin, and a
 * pattern-generated address is flagged so clients never treat it as a found contact.
 *
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sector' => $this->sector?->value,
            'sector_label' => $this->sector?->getLabel(),
            'city' => $this->city ? trim($this->city) : null,
            'address' => $this->address ? trim($this->address) : null,
            'phone' => $this->phone,
            'website' => $this->website,
            'email' => $this->email,
            'email_source' => $this->email_source?->value,
            'email_is_inferred' => $this->email_source?->isInferred() ?? false,
            'activity_index' => $this->activity_index,
            'sources_count' => $this->sources_count,
            'edb' => $this->edb,
            'embs' => $this->embs,
            'founded_on' => $this->founded_on?->toDateString(),
            'company_size' => $this->company_size,
            'employees' => $this->employees,
            'activities' => $this->activities,
            'data_quality_flag' => $this->data_quality_flag,
        ];
    }
}
