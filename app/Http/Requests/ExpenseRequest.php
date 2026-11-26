<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('type', 'expense')],
            'amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
