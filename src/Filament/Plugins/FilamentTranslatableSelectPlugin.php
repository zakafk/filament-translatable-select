<?php

namespace Zakafk\FilamentTranslatableSelect\Filament\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Tabs; // Kept for reference, but not used by the macro
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Panel;

class FilamentTranslatableSelectPlugin implements Plugin
{
    protected array|Closure $supportedLocales = [];
    protected bool|Closure $isLocaleHidden = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
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

    public function supportedLocales(array|Closure $supportedLocales): static
    {
        $this->supportedLocales = $supportedLocales;

        return $this;
    }

    public function getSupportedLocales(): array
    {
        $locales = is_callable($this->supportedLocales) ? call_user_func($this->supportedLocales) : $this->supportedLocales;

        if (empty($locales)) {
            $locales[] = config('app.locale');
        }

        return $locales;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        $supportedLocales = $this->getSupportedLocales();
        $isLocaleHidden = is_callable($this->isLocaleHidden)
            ? call_user_func($this->isLocaleHidden)
            : $this->isLocaleHidden;

        Field::macro('translatable', function (bool $translatable = true, ?array $customLocales = null, ?array $localeSpecificRules = null) use ($supportedLocales, $isLocaleHidden) {
            if (! $translatable) {
                return $this;
            }

            /**
             * @var Field $field
             * @var Field $this
             */
            $field = $this->getClone();

            // --- Start of Replaced Logic ---

            // Use the field's state path to create a unique name for its locale selector
            $selectorStatePath = $field->getStatePath(false) . '_active_locale';

            $locales = $customLocales ?? $supportedLocales;
            $localeOptions = collect($locales)
                ->mapWithKeys(function ($label, $key) {
                    $locale = is_string($key) ? $key : $label;
                    $label = is_string($key) ? $label : strtoupper($locale);
                    return [$locale => $label];
                });

            // Determine a sensible default locale
            $defaultLocale = app()->getLocale();
            if (!array_key_exists($defaultLocale, $localeOptions->all())) {
                $defaultLocale = $localeOptions->keys()->first();
            }

            // Create the cloned select, one for each locale, with visibility rules
            $clonedSelect = $localeOptions
                ->map(function ($label, $locale) use ($field, $localeSpecificRules, $selectorStatePath, $defaultLocale) {

                    $clone = $field
                        ->getClone()
                        ->name("{$field->getName()}.{$locale}")
                        ->statePath("{$field->getStatePath(false)}.{$locale}")
                        ->hiddenLabel()
                        ->label(false) // The label will be on the container Selectet
                        ->hidden(function (callable $get) use ($selectorStatePath, $locale, $defaultLocale) {
                            // Show this field only if its locale matches the selector's value
                            $activeLocale = $get($selectorStatePath) ?? $defaultLocale;
                            return $activeLocale !== $locale;
                        });

                    if ($localeSpecificRules && isset($localeSpecificRules[$locale])) {
                        $clone->rules($localeSpecificRules[$locale]);
                    }

                    return $clone;
                })
                ->all();

            // Create the Select component to switch locales
            $localeSelector = Select::make($selectorStatePath)
                ->label(false) // No label on the select itself
                ->hiddenLabel()
                ->options($localeOptions)
                ->live() // IMPORTANT: This makes the visibility rules reactive
                ->dehydrated(false) // Don't save the selector's state to the model
                ->placeholder($defaultLocale)
                // ->native(false)
                ->default($defaultLocale);


            return
                Grid::make(12)
                ->dense()
                ->schema([
                    Group::make()->schema($clonedSelect)->columnSpan($isLocaleHidden ? 12 : 8),
                    $localeSelector->columnSpan(4)->hidden($isLocaleHidden),
                ])
                ->columnSpan($this->getColumnSpan());
        });
    }
}
