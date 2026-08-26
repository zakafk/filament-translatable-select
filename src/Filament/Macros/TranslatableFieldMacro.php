<?php

namespace Zakafk\FilamentTranslatableSelect\Filament\Macros;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Zakafk\FilamentTranslatableSelect\Filament\Plugins\FilamentTranslatableSelectPlugin;

/**
 * Holds the body of the `Field::translatable()` macro.
 *
 * `Field::macro()` rebinds the closure to the field it is called on, so `$this`
 * inside `handler()`'s closure is that field. Declaring this class as a `Field`
 * is what makes that visible to IDEs and static analysis; no instance of it is
 * ever rendered.
 */
final class TranslatableFieldMacro extends Field
{
    public static function register(): void
    {
        if (Field::hasMacro('translatable')) {
            return;
        }

        Field::macro('translatable', (new self('translatable'))->handler());
    }

    public function handler(): Closure
    {
        return function (bool $translatable = true, ?array $customLocales = null, ?array $localeSpecificRules = null) {
            if (! $translatable) {
                return $this;
            }

            $field = $this->getClone();

            // Resolved here rather than at panel boot, so that closures passed to
            // the plugin may depend on the request, the tenant or the record.
            $plugin = FilamentTranslatableSelectPlugin::get();

            $fieldStatePath = $field->getStatePath(false);
            $selectorStatePath = FilamentTranslatableSelectPlugin::getLocaleStatePath($fieldStatePath);

            $localeOptions = $customLocales !== null
                ? FilamentTranslatableSelectPlugin::normalizeLocales($customLocales)
                : $plugin->getSupportedLocales();

            $defaultLocale = app()->getLocale();

            if (! array_key_exists($defaultLocale, $localeOptions)) {
                $defaultLocale = (string) array_key_first($localeOptions);
            }

            $activeLocaleJs = '($get(' . Js::from($selectorStatePath) . ') ?? ' . Js::from($defaultLocale) . ')';

            $fieldLabel = $field->getLabel();
            $fieldLabel = is_string($fieldLabel) ? $fieldLabel : (string) $field->getName();

            // Validation messages belonging to a locale that is not currently on
            // screen would otherwise be invisible, so they are looked up here and
            // surfaced by `$localeErrorSummary` below.
            $getLocaleErrors = function (Component $component, string $locale) use ($fieldStatePath): array {
                $livewire = $component->getLivewire();

                if (! method_exists($livewire, 'getErrorBag')) {
                    return [];
                }

                $containerStatePath = $component->getContainer()->getStatePath();

                $path = filled($containerStatePath)
                    ? "{$containerStatePath}.{$fieldStatePath}.{$locale}"
                    : "{$fieldStatePath}.{$locale}";

                return $livewire->getErrorBag()->get($path);
            };

            $clones = [];
            $localeErrorSummary = [];

            foreach ($localeOptions as $locale => $localeLabel) {
                $locale = (string) $locale;

                $clone = $field
                    ->getClone()
                    ->name("{$field->getName()}.{$locale}")
                    ->statePath("{$fieldStatePath}.{$locale}")
                    // Client-side only: every locale is always rendered and
                    // dehydrated, so switching never loses state and never hits
                    // the server. `fi-hidden` is applied to the whole schema
                    // wrapper, so the label, helper text, hint and error message
                    // are hidden along with the input.
                    ->visibleJs($activeLocaleJs . ' === ' . Js::from($locale))
                    ->validationAttribute(trim("{$fieldLabel} ({$localeLabel})"));

                if ($localeSpecificRules[$locale] ?? null) {
                    $clone->rules($localeSpecificRules[$locale]);
                }

                $clones[] = $clone;

                $localeErrorSummary[] = Text::make(function (Component $component) use ($getLocaleErrors, $locale, $localeLabel): ?HtmlString {
                    $messages = $getLocaleErrors($component, $locale);

                    return empty($messages)
                        ? null
                        : new HtmlString(e($localeLabel) . ': ' . e(implode(' ', $messages)));
                })
                    ->color('danger')
                    ->visible(fn (Component $component): bool => ! empty($getLocaleErrors($component, $locale)))
                    ->visibleJs($activeLocaleJs . ' !== ' . Js::from($locale));
            }

            $localeSelector = Select::make($selectorStatePath)
                ->options(function (Select $component) use ($localeOptions, $getLocaleErrors): array {
                    $options = [];

                    foreach ($localeOptions as $locale => $localeLabel) {
                        $options[$locale] = empty($getLocaleErrors($component, (string) $locale))
                            ? $localeLabel
                            : "{$localeLabel} ⚠";
                    }

                    return $options;
                })
                ->selectablePlaceholder(false)
                ->native()
                ->dehydrated(false)
                ->default($defaultLocale)
                ->afterStateHydrated(fn (Select $component, $state) => $component->state(
                    (is_string($state) && array_key_exists($state, $localeOptions)) ? $state : $defaultLocale
                ))
                ->grow(false);

            // The field's label occupies a row above the input, so the selector
            // needs an equally tall (but empty) label row to line up with it.
            // Screen readers get a real name through `aria-label` instead.
            if (filled($field->getLabel()) && ! $field->isLabelHidden()) {
                $localeSelector
                    ->label(new HtmlString('&nbsp;'))
                    ->extraInputAttributes(['aria-label' => __('filament-translatable-select::translatable-select.locale_selector_label')]);
            } else {
                $localeSelector
                    ->label(__('filament-translatable-select::translatable-select.locale_selector_label'))
                    ->hiddenLabel();
            }

            return Flex::make([
                Group::make()->gap(false)->schema([...$clones, ...$localeErrorSummary])->grow(),
                ...($plugin->isLocaleHidden() ? [] : [$localeSelector]),
            ])
                ->columnSpan($field->getColumnSpan())
                ->columnStart($field->getColumnStart());
        };
    }
}
