<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date->format('Y-m-d'),
            'description' => $this->description,
            'receipt_url' => $this->receipt_path ? url("/api/v1/expenses/{$this->id}/receipt") : null,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
