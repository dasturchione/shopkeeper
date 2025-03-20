<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventorySnapshotItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'inventory_group' => $this->inventory_snapshot_group_id,
            'product'         => [
                'id'        => $this->product->id,
                'brand'     => optional($this->product->brand)->name,
                'category'  => $this->product->category->name,
                'supplier'  => $this->product->supplier->surname.' '.$this->product->supplier->name,
                'name'      => $this->product->name,
            ],
            'base_quantity'   => $this->base_quantity,
            'stock_quantity'  => $this->stock_quantity,
            'not_selected'    => $this->not_selected,
            'created_at'      => $this->created_at->format('d-m-Y H:i:s'),
        ];
    }
}
