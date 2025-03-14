<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'rate' => $this->rate,
            'main_id' => $this->main_id,
            'convert_id' => $this->convert_id,
            'store_id' => $this->store_id,
            'created_at' => $this->created_at->format("d-m-Y H:i:s"),
            'active' => $this->isActive(),
        ];
    }
}
