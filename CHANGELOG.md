# Changelog

All notable changes to `filament-translatable-select` will be documented in this file.

## Unreleased

### Fixed

- Helper text, hint, label and validation messages of inactive locales were rendered on screen, so a translatable field showed its helper text once per locale. Visibility is now handled with Filament's `visibleJs()` on the whole schema wrapper instead of `display: none` on the input container.
- `composer.json` autoloaded `src\Helpers\helpers.php` with Windows separators, which is a fatal error on case-sensitive filesystems. The (empty) helper file has been removed.
- `supportedLocales()` used `is_callable()`, which is `true` for array callables such as `[SomeClass::class, 'method']`; it now checks for `Closure`.
- `columnStart()` is now forwarded to the wrapper alongside `columnSpan()`.
- The locale selector no longer overwrites a valid persisted value on hydration, and no longer shows an empty placeholder on edit pages.

### Added

- Test suite (Pest + Testbench), static analysis (Larastan, level 5) and code style (Pint) configuration, wired up as `composer test`, `composer analyse` and `composer format`.
- Publishable translations for the locale selector's screen-reader label (`--tag="filament-translatable-select-translations"`).

### Changed

- The macro body moved out of the plugin into `Filament\Macros\TranslatableFieldMacro`, so the plugin class only carries configuration.
- Switching locales is now entirely client-side; the selector is no longer `live()`, so it costs no Livewire round trip.
- Validation errors of off-screen locales are summarised under the field, locales with errors are marked in the selector, and each locale clone gets a locale-suffixed `validationAttribute`.
- The active locale is stored under `__translatable_locale_{field}` instead of `{field}_active_locale`, to avoid colliding with model attributes.
- Plugin configuration is resolved when the field is built rather than at panel boot, so closures may depend on the request, tenant or record.
- The locale selector is now labelled for screen readers (`aria-label`) instead of relying on an invisible filler character.
- Dead `columnSpan()` calls on `Flex` children (which `Flex` ignores) replaced with `grow()`.

## 1.0.0 - 2024-02-27

- Initial release
