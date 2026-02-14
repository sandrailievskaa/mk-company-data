<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class AddCompanyEmailCommand extends Command
{
    protected $signature = 'app:add-company-email 
                            {name : Company name (partial match allowed)}
                            {email : Email address to add}';

    protected $description = 'Manually add email address to a company';

    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');

        // Validate email
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email format: {$email}");

            return Command::FAILURE;
        }

        // Find company by name (partial match)
        $company = Company::where('name', 'like', "%{$name}%")->first();

        if (! $company) {
            $this->error("Company not found: {$name}");
            $this->info('Available companies:');
            Company::limit(10)->get()->each(function ($c) {
                $this->line("  - {$c->name}");
            });

            return Command::FAILURE;
        }

        if ($company->email && $company->email !== $email) {
            if (! $this->confirm("Company already has email: {$company->email}. Replace it?")) {
                return Command::SUCCESS;
            }
        }

        $company->update(['email' => $email]);
        $this->info("Successfully added email {$email} to {$company->name}");

        return Command::SUCCESS;
    }
}
