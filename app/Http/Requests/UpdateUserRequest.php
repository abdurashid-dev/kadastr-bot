<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$userId,
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number,'.$userId,
            'region' => 'nullable|string|max:255',
            'role' => 'nullable|in:user,checker,registrator,ceo',
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove password fields if they are empty to avoid updating with empty values
        if (empty($this->password)) {
            $this->merge([
                'password' => null,
                'password_confirmation' => null,
            ]);
        }
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ism kiritish majburiy.',
            'name.max' => 'Ism 255 ta belgidan ko\'p bo\'lmasligi kerak.',
            'email.required' => 'Email kiritish majburiy.',
            'email.email' => 'To\'g\'ri email formatini kiriting.',
            'email.unique' => 'Bu email allaqachon ro\'yxatdan o\'tgan.',
            'phone_number.unique' => 'Bu telefon raqami allaqachon ro\'yxatdan o\'tgan.',
            'role.required' => 'Rol tanlash majburiy.',
            'role.in' => 'Noto\'g\'ri rol tanlandi.',
            'password.confirmed' => 'Parol tasdiqlanmadi.',
        ];
    }
}
