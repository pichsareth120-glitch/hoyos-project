<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if (!$this->filled('slug') && $this->filled('name')) {
            $merge['slug'] = Str::slug($this->input('name'));
        }
        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }
        if ($this->has('is_featured')) {
            $merge['is_featured'] = $this->boolean('is_featured');
        }
        if ($this->has('is_new_arrival')) {
            $merge['is_new_arrival'] = $this->boolean('is_new_arrival');
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'size_guide' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'gt:price', 'max:99999.99'],
            'collection_name' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new_arrival' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'compare_at_price' => 'compare-at price',
            'collection_name' => 'collection name',
            'size_guide' => 'size guide',
            'sku_prefix' => 'SKU prefix',
            'initial_sizes' => 'initial sizes',
        ];
    }
}
