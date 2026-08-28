<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

/**
 * Handles both customer and vendor sign-up from a single form
 * (section 3.2: "Vendor registration and admin approval workflow").
 * When `role` is `vendor`, the shop fields become required; the
 * resulting Vendor record is created with status `pending` and needs
 * admin approval before the user can publish products.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['customer', 'vendor'])],

            'shop_name' => ['required_if:role,vendor', 'nullable', 'string', 'max:255'],
            'shop_description' => ['nullable', 'string', 'max:2000'],
            'shop_address' => ['required_if:role,vendor', 'nullable', 'string', 'max:255'],
        ];
    }
}
