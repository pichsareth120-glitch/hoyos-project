<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'size' => ['required', 'string', Rule::in(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One size'])],
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'price_adjustment' => ['nullable', 'numeric', 'min:-99999.99', 'max:99999.99'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'price_adjustment' => 'price adjustment',
            'initial_stock' => 'initial stock',
        ];
    }
}
