<?php

declare(strict_types=1);

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'restaurant_id' => $this->route('restaurantId'),
        ]);
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'uuid'],
            'name' => ['sometimes', 'string', 'max:255'],
            'percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
