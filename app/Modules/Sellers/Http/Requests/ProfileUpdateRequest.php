<?php

namespace App\Modules\Sellers\Http\Requests;

use App\Modules\Sellers\Models\Seller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(Seller::class)->ignore($this->user('seller')->id)],
        ];
    }
}
