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

namespace Modules\Plugin\puqSamplePlugin\Models;

use Illuminate\Database\Eloquent\Model;

class PuqSamplePlugin extends Model
{
    protected $table = 'puq_sample_plugins';

    protected $fillable = [
        'name',
        'test',
        'test2',
    ];
}
