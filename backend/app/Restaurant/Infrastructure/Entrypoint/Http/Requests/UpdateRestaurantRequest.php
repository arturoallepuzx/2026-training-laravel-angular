<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['sometimes', 'string', 'max:255'],
            'tax_id' => ['sometimes', 'string', 'max:32'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
        ];
    }
}
