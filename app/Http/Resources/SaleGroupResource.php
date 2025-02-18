<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'vendor'        => $this->vendor_info,
            'client'        => $this->client_info,
            'status'        => $this->status,
            'store_id'      => $this->store_id,
            'course_id'     => $this->course_id,
            'payment_type'  => $this->payment_type,
            'note'          => $this->note,
            'items_info'    => $this->items_info,
            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'    => $this->updated_at->toDateTimeString(),
        ];
    }
}
