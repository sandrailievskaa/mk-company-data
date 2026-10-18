<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Public profile on zk.mk (path as listed) and what it exposes.
            $table->string('profile_url')->nullable()->after('normalized_name');
            $table->string('website')->nullable()->after('phone');
            $table->string('facebook_url')->nullable()->after('website');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->text('activities')->nullable()->after('instagram_url');
            $table->timestamp('profile_scraped_at')->nullable()->after('activities');

            // Business registry data from CompanyWall (public company profile).
            $table->string('edb')->nullable()->index()->after('profile_scraped_at');
            $table->string('embs')->nullable()->after('edb');
            $table->date('founded_on')->nullable()->after('embs');
            $table->string('company_size')->nullable()->after('founded_on');
            $table->unsignedInteger('employees')->nullable()->after('company_size');
            $table->string('companywall_url')->nullable()->after('employees');
            $table->timestamp('companywall_scraped_at')->nullable()->after('companywall_url');

            // MX lookup of the e-mail domain (null = not checked).
            $table->boolean('email_domain_has_mx')->nullable()->after('email_found_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['edb']);
            $table->dropColumn([
                'profile_url', 'website', 'facebook_url', 'instagram_url', 'activities', 'profile_scraped_at',
                'edb', 'embs', 'founded_on', 'company_size', 'employees', 'companywall_url', 'companywall_scraped_at',
                'email_domain_has_mx',
            ]);
        });
    }
};
