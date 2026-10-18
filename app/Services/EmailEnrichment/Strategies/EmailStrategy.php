<?php

namespace App\Services\EmailEnrichment\Strategies;

use App\Enums\EmailSource;
use App\Models\Company;

interface EmailStrategy
{
    /** Provenance recorded for addresses this strategy returns. */
    public function source(): EmailSource;

    public function find(Company $company): ?string;
}
