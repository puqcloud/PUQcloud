<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait AutoTranslatable
 *
 * This trait allows models to dynamically translate their attributes
 * based on the current or set locale. Translated data is stored
 * in a separate translations table linked to the model via
 * morphable relationships.
 *
 * @property string|null $currentLocale The locale used for retrieving or saving translations
 * @property array $translatable Array of translations fetched from the database
 * @property array $locale
 */
trait AutoTranslatable
{
    protected $locale;

    protected $defaultLocale;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (empty($this->locale)) {
            if (app()->bound('user') && ($user = app('user')) && ! empty($user->language)) {
                $this->locale = $user->language;
            } else {
                $this->locale = config('locale.client.default');
            }
        }
        $this->defaultLocale = config('locale.admin.default');
    }

    public function save(array $options = []): bool
    {
        $saved = parent::save($options);
        $this->loadTranslations();

        return $saved;
    }

    public function refresh(): static
    {
        parent::refresh();
        $this->loadTranslations();

        return $this;
    }

    public function delete(): ?bool
    {
        $this->translations()->delete();

        return parent::delete();
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;
        $this->loadTranslations();

        return $this;
    }

    protected function loadTranslations(): void
    {
        foreach ($this->translatable as $field) {
            $this->attributes[$field] = $this->getTranslatedAttribute($field);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    protected function isTranslatable(string $key): bool
    {
        return in_array($key, $this->translatable ?? []);
    }

    protected function getTranslatedAttribute(string $key): mixed
    {
        $locale = $this->locale;

        $translation = $this->translations()
            ->where('locale', $locale)
            ->where('field', $key)
            ->first();

        if (empty($translation->value)) {
            $defaultTranslation = $this->translations()
                ->where('locale', $this->defaultLocale)
                ->where('field', $key)
                ->first();

            if ($defaultTranslation) {
                return $defaultTranslation->value;
            }

            $this->setTranslatedAttribute($key, '');

            return '';
        }

        return $translation->value;
    }

    protected function setTranslatedAttribute(string $key, mixed $value): static
    {
        $locale = $this->locale;

        $this->translations()->updateOrCreate(
            ['locale' => $locale, 'field' => $key],
            ['value' => $value]
        );

        return $this;
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable', 'model_type', 'model_uuid', 'uuid');
    }

    public function __get($key)
    {
        if ($this->isTranslatable($key)) {
            return $this->getTranslatedAttribute($key);
        }

        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        if ($this->isTranslatable($key)) {
            if (empty($value)) {
                $value = '';
            }
            $this->setTranslatedAttribute($key, $value);

            return;
        }
        foreach ($this->translatable as $field) {
            unset($this->attributes[$field]);
            unset($this->$field);
        }
        parent::__set($key, $value);
    }
}
