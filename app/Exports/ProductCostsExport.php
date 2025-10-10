<?php

namespace App\Exports;

use App\Models\ProductCost;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductCostsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return ProductCost::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product UID',
            'Product Name',
            'Cost',
            'Description',
            'Created At',
            'Updated At'
        ];
    }

    public function map($productCost): array
    {
        return [
            $productCost->id,
            $productCost->product_uid,
            $productCost->product_name,
            $productCost->cost,
            $productCost->description,
            $productCost->created_at,
            $productCost->updated_at
        ];
    }
}
