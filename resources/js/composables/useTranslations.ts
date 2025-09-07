import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useTranslations() {
    const page = usePage()
    
    const t = (key: string, replace: Record<string, any> = {}): string => {
        // Get the current locale
        const locale = page.props.locale as string || 'en'
        
        // Split the key to get the file and the key
        const [file, ...keyParts] = key.split('.')
        const translationKey = keyParts.join('.')
        
        // Get translations from the page props
        const translations = (page.props as any)[`translations_${locale}`] || {}
        const fileTranslations = translations[file] || {}
        
        // Get the translation value
        let translation = fileTranslations[translationKey] || key
        
        // Replace placeholders
        Object.keys(replace).forEach(key => {
            translation = translation.replace(`:${key}`, replace[key])
        })
        
        return translation
    }
    
    const currentLocale = computed(() => page.props.locale as string || 'en')
    const availableLocales = computed(() => page.props.available_locales as Record<string, string> || {})
    
    return {
        t,
        currentLocale,
        availableLocales,
    }
}
