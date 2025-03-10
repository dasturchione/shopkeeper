<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->getIdName('brand'),
            'category' => $this->getIdName('category'),
            'supplier' => $this->getIdName('supplier'),
            'receiver' => $this->getIdName('user'),
            'condition' => $this->condition,
            'barcode'   => $this->barcode,
            'name' => $this->name,
            'in_price' => $this->getPrice('in_price'),
            'sale_price' => $this->getPrice('sale_price'),
            'quantity' => $this->quantity,
            'warranty' => $this->warranty,
            'warranty_type' => $this->warranty_type,
            'note' => $this->note,
            'store_id' => $this->store_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('d-m-Y'),
            'updated_at' => $this->updated_at->format('d-m-Y'),
        ];
    }
}
