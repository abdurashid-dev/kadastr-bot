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
        $locale = Session::get('locale', config('app.locale', 'en'));

        // Validate locale is available
        if (! array_key_exists($locale, $availableLocales)) {
            $locale = config('app.locale', 'en');
        }

        // Set the application locale
        App::setLocale($locale);

        // Share locale info with Inertia
        if ($request->header('X-Inertia')) {
            // Load translations for the current locale
            $translations = [];
            $translationFiles = ['auth', 'messages'];

            foreach ($translationFiles as $file) {
                $translations[$file] = trans($file, [], $locale);
            }

            $request->merge([
                'locale' => $locale,
                'available_locales' => $availableLocales,
                "translations_{$locale}" => $translations,
            ]);
        }

        return $next($request);
    }
}
