<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $variantId = $this->route('variant') instanceof \App\Models\ProductVariant
            ? $this->route('variant')->id
            : $this->route('variant');

        return [
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'price_adjustment' => ['nullable', 'numeric', 'min:-99999.99', 'max:99999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'price_adjustment' => 'price adjustment',
        ];
    }
}
