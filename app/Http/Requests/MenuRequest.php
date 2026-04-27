<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menus', 'id')->where(function ($query) {
                    $query->where('id', '!=', $this->route('menu')?->id);
                }),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        if ($this->isMethod('POST')) {
            $rules['sort_order'] = 'required|integer|min:0';
            $rules['is_active'] = 'required|boolean';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The menu name is required.',
            'name.max' => 'The menu name may not be greater than 255 characters.',
            'route.max' => 'The route may not be greater than 255 characters.',
            'icon.max' => 'The icon may not be greater than 255 characters.',
            'parent_id.exists' => 'The selected parent menu is invalid.',
            'parent_id.integer' => 'The parent ID must be an integer.',
            'sort_order.required' => 'The sort order is required.',
            'sort_order.integer' => 'The sort order must be an integer.',
            'sort_order.min' => 'The sort order must be at least 0.',
            'is_active.required' => 'The active status is required.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => $this->integer('sort_order', 0),
        ]);
    }
}
