<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketDepartment extends Model
{
    use ConvertsTimezone;
    use ModelActivityLogger;

    protected $table = 'ticket_departments';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'description', 'email', 'hidden',
        'feedback_on_close', 'allow_client_reopen', 'auto_close_hours',
        'mail_import_enabled', 'mail_import_type', 'mail_import_host',
        'mail_import_port', 'mail_import_username', 'mail_import_password',
        'mail_import_encryption', 'mail_import_delete_after',
        'mail_import_replies_only', 'mail_import_clients_only', 'sort_order',
        'sla_response_hours',
        'allowed_file_extensions', 'max_file_size_mb', 'max_files_count',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'feedback_on_close' => 'boolean',
        'allow_client_reopen' => 'boolean',
        'mail_import_enabled' => 'boolean',
        'mail_import_delete_after' => 'boolean',
        'mail_import_replies_only' => 'boolean',
        'mail_import_clients_only' => 'boolean',
        'mail_import_password' => 'encrypted',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            Admin::class,
            'admin_x_ticket_department',
            'ticket_department_uuid',
            'admin_uuid'
        )->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'department_uuid', 'uuid');
    }
}

