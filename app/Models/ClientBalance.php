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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientBalance extends Model
{
    use ConvertsTimezone;

    protected $table = 'client_balances';

    protected $primaryKey = 'client_uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    public static $allowSave = false;

    protected $fillable = [
        'client_uuid',
        'balance',
    ];

    protected static function booted()
    {
        static::saving(function () {
            return self::$allowSave;
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public static function createSafe(array $data): self
    {
        self::$allowSave = true;
        $model = self::create($data);
        self::$allowSave = false;

        return $model;
    }
}
