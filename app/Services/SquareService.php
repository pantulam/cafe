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
        'description',
        'is_variation',
        'parent_product_id'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_variation' => 'boolean'
    ];

    /**
     * Get the parent product
     */
    public function parent()
    {
        return $this->belongsTo(ProductCost::class, 'parent_product_id', 'product_uid');
    }

    /**
     * Get variations of this product
     */
    public function variations()
    {
        return $this->hasMany(ProductCost::class, 'parent_product_id', 'product_uid');
    }

    /**
     * Scope to get only main products (not variations)
     */
    public function scopeMainProducts($query)
    {
        return $query->where('is_variation', false);
    }

    /**
     * Scope to get only variations
     */
    public function scopeVariations($query)
    {
        return $query->where('is_variation', true);
    }

    /**
     * Scope to get products with their variations
     */
    public function scopeWithVariations($query)
    {
        return $query->with('variations');
    }

    /**
     * Get display name with indication if it's a variation
     */
    public function getDisplayNameAttribute()
    {
        if ($this->is_variation) {
            return "{$this->product_name} (Variation)";
        }
        return $this->product_name;
    }
}