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
            'brand' => $this->brands,
            'category' => $this->categorys,
            'supplier' => $this->suppliers,
            'receiver' => $this->receivers,
            'condition' => $this->condition,
            'name' => $this->name,
            'in_price' => $this->in_prices,
            'sale_price' => $this->sale_prices,
            'quantity' => $this->quantity,
            'warranty' => $this->warranty,
            'warranty_type' => $this->warranty_type,
            'note' => $this->note,
            'store_id' => $this->store_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
