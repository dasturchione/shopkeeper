<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
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
            'name' => $this->name,
            'created_at' => $this->created_at->format("d-m-Y H:i:s"),
            'last_used_at' => $this->last_used_at ? $this->last_used_at->format("d-m-Y H:i:s") : null,
            'user' => [
                'id' => $this->tokenable->id,
                'name' => $this->tokenable->name,
                'role' => $this->tokenable->role->name,
            ],
        ];
    }
}
