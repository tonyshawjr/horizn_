# horizn_ WordPress Plugin - Languages Folder

## Purpose
Translation files for internationalization (i18n)

## Rules
- **Translation Ready**: All user-facing strings must use WordPress i18n functions
- **Text Domain**: Use 'horizn-analytics' consistently
- **Context**: Provide translator comments for context
- **Plurals**: Handle plural forms properly with `_n()`

## File Structure
- `horizn-analytics.pot` - Translation template file
- `horizn-analytics-{locale}.po` - Translation files for each language
- `horizn-analytics-{locale}.mo` - Compiled translation files

## WordPress i18n Functions
```php
// Basic translation
__('Text to translate', 'horizn-analytics')

// Translation with echo
_e('Text to translate', 'horizn-analytics')

// Plurals
_n('1 item', '%d items', $count, 'horizn-analytics')

// Context
_x('Post', 'noun', 'horizn-analytics')

// Translator comments
/* translators: %s is the plugin name */
sprintf(__('Welcome to %s', 'horizn-analytics'), 'horizn_')
```

## Generation
Use WP-CLI to generate/update POT file:
```bash
wp i18n make-pot . languages/horizn-analytics.pot
```

## Primary Agents
- content-creator
- localization-expert