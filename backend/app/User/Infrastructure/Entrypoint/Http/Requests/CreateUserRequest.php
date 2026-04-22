<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Domain\ValueObject\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
            'role' => ['required', 'string', Rule::in(UserRole::allowed())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'pin' => ['nullable', 'string', 'digits:4'],
            'image_src' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
