<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionResource extends JsonResource
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
            'product_id' => $this->actionable_id,
            'action_type' => $this->action_type,
            'data' => json_decode($this->data, true), // JSON ma'lumotlarni array ko'rinishida qaytarish
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
