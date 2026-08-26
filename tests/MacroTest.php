<?php

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Zakafk\FilamentTranslatableSelect\Filament\Macros\TranslatableFieldMacro;

beforeEach(function () {
    TranslatableFieldMacro::register();
});

it('registers the translatable macro on every field', function () {
    expect(Field::hasMacro('translatable'))->toBeTrue();
});

it('returns the untouched field when translation is disabled', function () {
    $field = TextInput::make('name')->helperText('Hello');

    expect($field->translatable(false))->toBe($field);
});
