<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KhaltiSettingUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'khalti_status' => ['required', 'in:active,inactive'],
            'khalti_account_mode' => ['required', 'in:live,sandbox'],
            'khalti_currency_name' => ['required'],
            'khalti_currency_rate' => ['required', 'numeric'],
            'khalti_secret_key' => ['required'],
            'khalti_public_key' => ['required']
        ];
    }
}
