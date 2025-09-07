# Translation Guide

This application supports multiple languages including English, Uzbek Latin, and Uzbek Cyrillic.

## Available Languages

- **English** (`en`) - Default language
- **Uzbek Latin** (`uz-latn`) - O'zbekcha (Lotin)
- **Uzbek Cyrillic** (`uz-cyrl`) - Ўзбекча (Кирилл)

## Backend Usage (Laravel)

### Using Translation Functions

```php
// Basic translation
echo __('messages.welcome'); // Returns "Welcome" in English

// With parameters
echo __('messages.password_min', ['min' => 8]); // Returns "Password must be at least 8 characters"

// Using trans() helper
echo trans('auth.failed'); // Returns "These credentials do not match our records."
```

### Setting Locale

```php
// Set locale for current request
App::setLocale('uz-latn');

// Set locale in session
Session::put('locale', 'uz-cyrl');
```

## Frontend Usage (Vue.js)

### Using the Translation Composable

```vue
<template>
    <div>
        <h1>{{ t('messages.welcome') }}</h1>
        <p>{{ t('messages.dashboard') }}</p>
    </div>
</template>

<script setup>
import { useTranslations } from '@/composables/useTranslations'

const { t, currentLocale, availableLocales } = useTranslations()
</script>
```

### Language Switcher Component

The application includes a `LanguageSwitcher` component that can be used anywhere:

```vue
<template>
    <LanguageSwitcher 
        :current-locale="locale" 
        :available-locales="availableLocales" 
    />
</template>

<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const locale = computed(() => page.props.locale)
const availableLocales = computed(() => page.props.available_locales)
</script>
```

## Adding New Translations

### 1. Add Translation Keys

Add your translation keys to the appropriate language files:

**English** (`lang/en/messages.php`):
```php
'new_key' => 'New Value',
```

**Uzbek Latin** (`lang/uz-latn/messages.php`):
```php
'new_key' => 'Yangi qiymat',
```

**Uzbek Cyrillic** (`lang/uz-cyrl/messages.php`):
```php
'new_key' => 'Янги қиймат',
```

### 2. Use in Backend

```php
echo __('messages.new_key');
```

### 3. Use in Frontend

```vue
<template>
    <p>{{ t('messages.new_key') }}</p>
</template>
```

## Translation File Structure

```
lang/
├── en/
│   ├── auth.php
│   └── messages.php
├── uz-latn/
│   ├── auth.php
│   └── messages.php
└── uz-cyrl/
    ├── auth.php
    └── messages.php
```

## Language Switching

Users can switch languages using the language switcher in the header. The selected language is stored in the session and persists across requests.

## Testing Translations

Run the translation tests to ensure everything works correctly:

```bash
php artisan test --filter=TranslationTest
```

## Best Practices

1. **Use descriptive keys**: `user_created_successfully` instead of `success`
2. **Group related translations**: Keep related translations in the same file
3. **Use parameters**: For dynamic content, use parameters instead of string concatenation
4. **Test all languages**: Ensure all translations work in all supported languages
5. **Keep translations consistent**: Use the same terminology across the application
