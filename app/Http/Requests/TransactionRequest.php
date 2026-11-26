<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\String\TruncateMode;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_uuid'    => ['required', 'uuid'],
            'table_label'    => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'in:cash,qris,transfer,debt'],
            'amount_paid'    => ['required_if:payment_method,cash', 'nullable', 'integer', 'min:0'],
            'customer_name'  => ['required_if:payment_method,debt', 'nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.menu_id'=> ['required', 'integer', 'exists:menus,id'],
            'items.*.qty'    => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.note'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
