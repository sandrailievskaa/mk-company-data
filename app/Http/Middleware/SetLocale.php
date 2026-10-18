<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language chosen in the panel (kept in the session and a cookie), so
 * every page, view and Livewire update renders in the same language.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys((array) config('locales.supported'));

        $locale = $request->hasSession() ? $request->session()->get('locale') : null;
        $locale ??= $request->cookie('locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('locales.default');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
