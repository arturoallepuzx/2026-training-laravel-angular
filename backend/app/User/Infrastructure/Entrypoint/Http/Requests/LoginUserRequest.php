<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }
}
