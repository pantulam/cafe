<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_uid',
        'product_name',
        'cost',
        'description'
    ];

    protected $casts = [
        'cost' => 'decimal:2'
    ];
}
