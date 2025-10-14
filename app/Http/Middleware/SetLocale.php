<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get available locales from config
        $availableLocales = config('app.available_locales', []);

        // Get locale from session, or default to app locale
        $locale = Session::get('locale', config('app.locale', 'uz-latn'));

        // Validate locale is available
        if (! array_key_exists($locale, $availableLocales)) {
            $locale = config('app.locale', 'uz-latn');
        }

        // Force default locale to uz-latn if not set in session
        if (!Session::has('locale')) {
            $locale = 'uz-latn';
            Session::put('locale', $locale);
        }

        // Set the application locale
        App::setLocale($locale);

        return $next($request);
    }
}
