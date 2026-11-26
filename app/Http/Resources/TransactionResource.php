<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        if ($this->relationLoaded('customer')) {
            $data['customer'] = $this->customer?->only(['id', 'name', 'phone']);
        }

        return $data;
    }
}
