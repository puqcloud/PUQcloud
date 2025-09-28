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

namespace App\Models;

use App\Traits\AutoTranslatable;
use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 * @property string $locale
 * @property string $name
 * @property string $category
 * @property bool $custom
 * @property string subject
 * @property string $text
 * @property string $text_mini
 * @property array $category_data
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationTemplate find(string)
 */
class NotificationTemplate extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFactory;

    protected $table = 'notification_templates';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
        static::retrieved(function ($model) {
            $model->setCategoryData();
        });
        static::saving(function ($model) {
            unset($model->category_data);
        });
    }

    protected $attributes = [
        'category_data' => [],
    ];

    protected $fillable = [
        'name',
        'category',
        'custom',
    ];

    protected $translatable = ['subject', 'text', 'text_mini'];

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($key === 'category_data' && empty($this->category_data)) {
            $this->setCategoryData();

            return $this->category_data;
        }

        return $value;
    }

    protected function setCategoryData(): void
    {
        $categories = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));
        foreach ($categories as $category) {
            if ($category['key'] === $this->category) {
                unset($category['notifications']);
                $this->category_data = $category;
                break;
            }
        }
    }
}
