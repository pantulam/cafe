<?php

namespace App\Models;

class ProductCost
{
    public $product_id;
    public $cost;
    public $product_name;
    public $description;

    public function __construct($product_id, $cost, $product_name = null, $description = null)
    {
        $this->product_id = $product_id;
        $this->cost = $cost;
        $this->product_name = $product_name;
        $this->description = $description;
    }
}