<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(Role::class)],
            'locale' => ['sometimes', 'required', Rule::in(array_keys(config('app.panel_locales')))],
            // Düzenlemede boş bırakılırsa şifre değişmez
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => __('ad'),
            'last_name' => __('soyad'),
            'email' => __('e-posta'),
            'role' => __('rol'),
            'locale' => __('panel dili'),
            'password' => __('şifre'),
            'avatar' => __('profil resmi'),
        ];
    }
}
