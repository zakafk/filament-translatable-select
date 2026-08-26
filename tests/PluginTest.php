<?php

use Zakafk\FilamentTranslatableSelect\Filament\Plugins\FilamentTranslatableSelectPlugin;

it('normalizes a plain list of locales into locale => uppercased label', function () {
    expect(FilamentTranslatableSelectPlugin::normalizeLocales(['en', 'ka']))
        ->toBe(['en' => 'EN', 'ka' => 'KA']);
});

it('keeps explicit labels', function () {
    expect(FilamentTranslatableSelectPlugin::normalizeLocales(['en' => 'English', 'ka' => 'Georgian']))
        ->toBe(['en' => 'English', 'ka' => 'Georgian']);
});

it('normalizes a mixed list', function () {
    expect(FilamentTranslatableSelectPlugin::normalizeLocales(['en' => 'English', 'ka']))
        ->toBe(['en' => 'English', 'ka' => 'KA']);
});

it('falls back to the application locale when nothing is configured', function () {
    config()->set('app.locale', 'de');

    expect(FilamentTranslatableSelectPlugin::normalizeLocales([]))
        ->toBe(['de' => 'DE']);
});

it('falls back to the application locale on the plugin instance', function () {
    config()->set('app.locale', 'fr');

    expect(FilamentTranslatableSelectPlugin::make()->getSupportedLocales())
        ->toBe(['fr' => 'FR']);
});

it('resolves supported locales from a closure lazily', function () {
    $calls = 0;

    $plugin = FilamentTranslatableSelectPlugin::make()
        ->supportedLocales(function () use (&$calls) {
            $calls++;

            return ['en', 'ka'];
        });

    expect($calls)->toBe(0);

    expect($plugin->getSupportedLocales())->toBe(['en' => 'EN', 'ka' => 'KA']);
    expect($calls)->toBe(1);
});

it('does not treat an array of locales as a callable', function () {
    // `is_callable(['Some\Class', 'method'])` is true, which is why the plugin
    // must check for `Closure` instead.
    $plugin = FilamentTranslatableSelectPlugin::make()
        ->supportedLocales([FilamentTranslatableSelectPlugin::class, 'make']);

    expect(array_keys($plugin->getSupportedLocales()))
        ->toBe([FilamentTranslatableSelectPlugin::class, 'make']);
});

it('resolves the locale hidden flag from a closure', function () {
    expect(FilamentTranslatableSelectPlugin::make()->isLocaleHidden())->toBeFalse();

    expect(FilamentTranslatableSelectPlugin::make()->setLocaleHidden(fn () => true)->isLocaleHidden())
        ->toBeTrue();
});

it('prefixes the locale selector state path and flattens dots', function () {
    expect(FilamentTranslatableSelectPlugin::getLocaleStatePath('preheader'))
        ->toBe('__translatable_locale_preheader');

    expect(FilamentTranslatableSelectPlugin::getLocaleStatePath('meta.preheader'))
        ->toBe('__translatable_locale_meta_preheader');
});
