<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number',
            'region' => 'nullable|string|max:255',
            'role' => 'required|in:user,checker,registrator,ceo,branch_agency_head,branch_chamber_head,branch_deputy,onec_developer',
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
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
            'password.required' => 'Parol kiritish majburiy.',
            'password.confirmed' => 'Parol tasdiqlanmadi.',
        ];
    }
}
