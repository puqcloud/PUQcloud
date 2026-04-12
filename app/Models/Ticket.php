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
use App\Traits\ModelActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use ConvertsTimezone;
    use ModelActivityLogger;

    protected $table = 'tickets';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'subject', 'client_uuid', 'user_uuid', 'email', 'name',
        'department_uuid', 'status_uuid', 'priority', 'assigned_admin_uuid',
        'service_uuid', 'cc_emails', 'tags', 'admin_notes',
        'time_spent_minutes', 'last_reply_at', 'closed_at', 'sla_reminder_sent',
    ];

    protected $casts = [
        'cc_emails' => 'array',
        'tags' => 'array',
        'last_reply_at' => 'datetime:Y-m-d H:i',
        'closed_at' => 'datetime:Y-m-d H:i',
        'sla_reminder_sent' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->number = self::generateNumber();
        });
    }

    public static function generateNumber(): int
    {
        $digits = 6;

        while (true) {
            $min = (int) pow(10, $digits - 1);
            $max = (int) pow(10, $digits) - 1;

            for ($i = 0; $i < 10; $i++) {
                $number = random_int($min, $max);
                if (! DB::table('tickets')->where('number', $number)->exists()) {
                    return $number;
                }
            }

            $digits++;
        }
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketDepartment::class, 'department_uuid', 'uuid');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_uuid', 'uuid');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_uuid', 'uuid');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_uuid', 'uuid');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(TicketFeedback::class, 'ticket_uuid', 'uuid');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_uuid', 'uuid');
    }

    public function getTotalTimeSpent(): int
    {
        return $this->time_spent_minutes + $this->replies()->sum('time_spent_minutes');
    }

    public function isClosed(): bool
    {
        return $this->status && strtolower($this->status->title) === 'closed';
    }

    public function isDraft(): bool
    {
        return $this->status && strtolower($this->status->title) === 'draft';
    }

    public function getAttachments()
    {
        return File::where('model_type', self::class)
            ->where('model_uuid', $this->uuid)
            ->where('model_field', 'attachment')
            ->get();
    }
}

