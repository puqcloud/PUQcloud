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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 * @property string $group_uuid
 * @property string $notification_template_uuid
 * @property string $notification_layout_uuid
 * @property string $category
 * @property string $notification
 * @property array $category_data
 * @property NotificationSender $notificationsenders
 * @property NotificationTemplate $notificationtemplate
 * @property NotificationLayout $notificationlayout
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationRule find(string)
 */
class NotificationRule extends Model
{
    use ConvertsTimezone;
    use HasFactory;

    protected $table = 'notification_rules';

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
            $model->setNotificationData();
            $model->setNotificationLayoutData();
            $model->setNotificationTemplateData();
            $model->setNotificationSendersData();
        });
        static::saving(function ($model) {
            unset($model->category_data);
            unset($model->notification_data);
            unset($model->notification_layout_data);
            unset($model->notification_template_data);
            unset($model->notification_senders_data);
        });
    }

    protected $attributes = [
        'category_data' => [],
        'notification_data' => [],
        'notification_layout_data' => [],
        'notification_template_data' => [],
        'notification_senders_data' => [],
    ];

    protected $fillable = [
        'group_uuid',
        'category',
        'notification',
        'notification_layout_uuid',
        'notification_template_uuid',
        'notification_senders_uuid',
    ];

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
                $this->category_data = ['id' => $category['key'], 'text' => $category['name']];
                break;
            }
        }
    }

    protected function setNotificationData(): void
    {
        $categories = array_merge(config('adminNotifications.categories'), config('clientNotifications.categories'));
        foreach ($categories as $category) {
            if ($category['key'] === $this->category) {
                foreach ($category['notifications'] as $notification) {
                    if ($notification['name'] === $this->notification) {
                        $this->notification_data = ['id' => $notification['name'], 'text' => $notification['name']];
                    }
                    break;
                }
                break;
            }
        }
    }

    protected function setNotificationLayoutData(): void
    {
        $notification_layout_data = $this->notificationlayout;
        $this->notification_layout_data = ['id' => $notification_layout_data->uuid, 'text' => $notification_layout_data->name];
    }

    protected function setNotificationTemplateData(): void
    {
        $notification_template_data = $this->notificationtemplate;
        $this->notification_template_data = ['id' => $notification_template_data->uuid, 'text' => $notification_template_data->name];
    }

    protected function setNotificationSendersData(): void
    {
        $notification_senders_data = [];
        $notificationsenders = $this->notificationsenders;
        foreach ($notificationsenders as $notificationsender) {
            $notification_senders_data[] = ['id' => $notificationsender->uuid, 'text' => $notificationsender->name];
        }
        $this->notification_senders_data = $notification_senders_data;

    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_uuid', 'uuid');
    }

    public function notificationLayout(): BelongsTo
    {
        return $this->belongsTo(NotificationLayout::class, 'notification_layout_uuid', 'uuid');
    }

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_uuid', 'uuid');
    }

    public function notificationSenders(): BelongsToMany
    {
        return $this->belongsToMany(NotificationSender::class, 'notification_rule_x_notification_sender', 'notification_rule_uuid', 'notification_sender_uuid');
    }
}
