<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Change the application language
     */
    public function changeLanguage(Request $request)
    {
        try {
            $request->validate([
                'locale' => 'required|string|in:uz-latn,uz-cyrl',
            ]);

            $locale = $request->locale;

            // Set the application locale
            App::setLocale($locale);

            // Store the locale in session for persistence
            Session::put('locale', $locale);

            return redirect()->back();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to change language');
        }
    }
}
