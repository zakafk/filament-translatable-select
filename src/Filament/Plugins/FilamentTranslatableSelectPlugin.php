<?php

namespace Zakafk\FilamentTranslatableSelect\Filament\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use RuntimeException;
use Zakafk\FilamentTranslatableSelect\Filament\Macros\TranslatableFieldMacro;

class FilamentTranslatableSelectPlugin implements Plugin
{
    /**
     * Prefix for the (never dehydrated) form state key that holds the active
     * locale, kept deliberately unlikely to collide with a model attribute.
     */
    public const LOCALE_STATE_PREFIX = '__translatable_locale_';

    protected array|Closure $supportedLocales = [];

    protected bool|Closure $isLocaleHidden = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        $plugin = filament(app(static::class)->getId());

        if (! $plugin instanceof static) {
            throw new RuntimeException(sprintf(
                'The [%s] plugin is not registered on the current panel.',
                static::class,
            ));
        }

        return $plugin;
    }

    public function getId(): string
    {
        return 'zakafk-filament-translatable-select';
    }

    public function setLocaleHidden(bool|Closure $isLocaleHidden): static
    {
        $this->isLocaleHidden = $isLocaleHidden;

        return $this;
    }

    public function isLocaleHidden(): bool
    {
        return (bool) ($this->isLocaleHidden instanceof Closure
            ? ($this->isLocaleHidden)()
            : $this->isLocaleHidden);
    }

    public function supportedLocales(array|Closure $supportedLocales): static
    {
        $this->supportedLocales = $supportedLocales;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getSupportedLocales(): array
    {
        $locales = $this->supportedLocales instanceof Closure
            ? ($this->supportedLocales)()
            : $this->supportedLocales;

        return static::normalizeLocales($locales);
    }

    /**
     * Accepts `['en', 'ka']`, `['en' => 'English', 'ka' => 'Georgian']` or a mix
     * of both, and always returns `[locale => label]`. Falls back to the
     * application locale when nothing is configured.
     *
     * @param  array<int|string, string>  $locales
     * @return array<string, string>
     */
    public static function normalizeLocales(array $locales): array
    {
        if (empty($locales)) {
            $locales = [config('app.locale')];
        }

        $normalized = [];

        foreach ($locales as $key => $label) {
            $locale = is_string($key) ? $key : $label;

            $normalized[$locale] = is_string($key) ? $label : strtoupper($locale);
        }

        return $normalized;
    }

    public static function getLocaleStatePath(string $fieldStatePath): string
    {
        return static::LOCALE_STATE_PREFIX . str_replace('.', '_', $fieldStatePath);
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        TranslatableFieldMacro::register();
    }
}
