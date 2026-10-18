<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Services\EmailEnrichment\SiteEmailFinder;
use App\Services\EmailEnrichment\Strategies\SearchStrategy;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when([SiteEmailFinder::class, SearchStrategy::class])
            ->needs(ClientInterface::class)
            ->give(fn () => new Client([
                'timeout' => (int) config('enrichment.timeouts.default'),
                'verify' => config('enrichment.ca_bundle') ?: (bool) config('enrichment.verify_ssl'),
                'allow_redirects' => true,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => config('scraping.user_agent'),
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'mk-MK,mk;q=0.9,en-US;q=0.8,en;q=0.7',
                ],
            ]));
    }

    public function boot(): void
    {
        Model::unguard();

        // Livewire updates are separate requests: keep the chosen language there too.
        Livewire::addPersistentMiddleware([SetLocale::class]);
    }
}
