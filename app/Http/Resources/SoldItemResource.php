<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use function PHPSTORM_META\map;

class SoldItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'vendor'        => $this->getIdName('vendor'),
            'client'        => $this->getIdName('client'),
            'status'        => $this->status,
            'course'        => [
                'id'    => $this->course->id,
                'rate'  => $this->course->rate,
            ],
            'payment_type'  => $this->payment_type,
            'note'          => $this->note,
            'items_info'    => $this->items_info,
            'payment_info'  => [
                'main'      => $this->maincurrency,
                'convert'   => $this->convertcurrency
            ],
            'created_at'    => $this->created_at->toDateTimeString(),
            'items'   => $this->items->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'product'       => [
                        'id'    => $item->product->id,
                        'name'  => $item->product->name,
                        'brand' => optional($item->product->brand)->name,
                        'category' => $item->product->category->name,
                        'supplier' => $item->product->supplier->name,
                        'receiver' => $item->product->user->name,
                        'condition' => $item->product->condition,
                    ],
                    'in_price'      => $item->in_price,
                    'sale_price'    => $item->sale_price,
                    'quantity'      => $item->quantity,
                    'discount'      => $item->discount,
                    'warranty'      => $item->warranty,
                    'warranty_type' => $item->warranty_type,
                    'created_at'    => $item->created_at->format('d-m-Y'),
                    'updated_at'    => $item->updated_at->format('d-m-Y'),
                ];
            }),

        ];
    }
}
