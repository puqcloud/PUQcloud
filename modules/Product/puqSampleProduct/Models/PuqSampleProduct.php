<?php

namespace Modules\Product\puqSampleProduct\Models;

use Illuminate\Database\Eloquent\Model;

class PuqSampleProduct extends Model
{
    protected $table = 'puq_sample_products';

    protected $fillable = [
        'name',
        'test',
        'test2',
    ];
}
