<?php

namespace Zakafk\FilamentTranslatableSelect\Filament\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
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

            $selectorStatePath = $field->getStatePath(false) . '_active_locale';

            $locales = $customLocales ?? $supportedLocales;
            $localeOptions = collect($locales)
                ->mapWithKeys(function ($label, $key) {
                    $locale = is_string($key) ? $key : $label;
                    $label = is_string($key) ? $label : strtoupper($locale);
                    return [$locale => $label];
                });

            $defaultLocale = app()->getLocale();
            if (!array_key_exists($defaultLocale, $localeOptions->all())) {
                $defaultLocale = $localeOptions->keys()->first();
            }

            $clonedSelect = $localeOptions
                ->map(function ($label, $locale) use ($field, $localeSpecificRules, $selectorStatePath, $defaultLocale) {

                    $clone = $field
                        ->getClone()
                        ->hiddenLabel(function (callable $get) use ($selectorStatePath, $locale, $defaultLocale) {
                            $activeLocale = $get($selectorStatePath) ?? $defaultLocale;

                            if ($activeLocale !== $locale) {
                                return true;
                            }
                            return false;
                        })
                        ->name("{$field->getName()}.{$locale}")
                        ->statePath("{$field->getStatePath(false)}.{$locale}")
                        ->extraAttributes(function (callable $get) use ($selectorStatePath, $locale, $defaultLocale) {
                            $activeLocale = $get($selectorStatePath) ?? $defaultLocale;

                            if ($activeLocale !== $locale) {
                                return ['style' => 'display: none;'];
                            }
                            return [];
                        });

                    if ($localeSpecificRules && isset($localeSpecificRules[$locale])) {
                        $clone->rules($localeSpecificRules[$locale]);
                    }

                    return $clone;
                })
                ->all();

            // Create the Select component to switch locales
            $localeSelector = Select::make($selectorStatePath)
                ->label('ㅤ')
                // ->hiddenLabel()
                ->options($localeOptions)
                ->live()
                ->dehydrated(false)
                ->placeholder($defaultLocale)
                ->default($defaultLocale);


            return
                Grid::make(12)
                ->dense()
                ->schema([
                    Group::make()->gap(0)->schema($clonedSelect)->columnSpan($isLocaleHidden ? 12 : 8),
                    $localeSelector->columnSpan(4)->hidden($isLocaleHidden),
                ])
                ->columnSpan($this->getColumnSpan());
        });
    }
}
