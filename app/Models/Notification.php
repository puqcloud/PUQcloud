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

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 * @property string $locale
 * @property string $name
 * @property string $description
 * @property string $layout
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationLayout query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationLayout find(string)
 */
class Notification extends Model
{
    use ConvertsTimezone;
    use HasFactory;

    protected $table = 'notifications';

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
            $model->setModelData();
        });
        static::saving(function ($model) {
            unset($model->model_data);
        });
    }

    protected $fillable = [
        'subject',
        'text',
        'text_mini',
        'layout',
        'model_type',
        'model_uuid',
    ];

    protected $attributes = [
        'model_data' => [],
    ];

    protected function setModelData(): void
    {
        if (empty($this->model_uuid)) {
            return;
        }
        try {
            $modelClass = $this->model_type;

            if (! class_exists($modelClass)) {
                throw new \Exception("Model class {$modelClass} not found.");
            }

            $data = [
                'type' => class_basename($modelClass),
                'uuid' => null,
                'email' => null,
                'phone_number' => null,
                'lastname' => null,
                'firstname' => null,
                'web_url' => null,
                'gravatar' => null,
            ];

            if ($modelClass === \App\Models\Admin::class) {
                $model = $modelClass::findOrFail($this->model_uuid);
                $data['email'] = $model->email;
                $data['phone_number'] = $model->phone_number;
                $data['uuid'] = $model->uuid;
                $data['lastname'] = $model->lastname;
                $data['firstname'] = $model->firstname;
                $data['web_url'] = route('admin.web.admin', $model->uuid);
                $data['gravatar'] = get_gravatar($model->email);
            } elseif ($modelClass === \App\Models\User::class) {
                $model = $modelClass::findOrFail($this->model_uuid);
                $data['email'] = $model->email;
                $data['phone_number'] = $model->phone_number;
                $data['uuid'] = $model->uuid;
                $data['lastname'] = $model->lastname;
                $data['firstname'] = $model->firstname;
                $data['gravatar'] = get_gravatar($model->email);
                // $data['web_url'] = route('admin.web.admin',$model->uuid);
            }

            $this->model_data = $data;

        } catch (\Exception $e) {
            $this->model_data = [];
            Log::error('Error finding model: '.$e->getMessage());
        }
    }

    public function notificationStatus(): HasMany
    {
        return $this->hasMany(NotificationStatus::class, 'notification_uuid', 'uuid');
    }
}
