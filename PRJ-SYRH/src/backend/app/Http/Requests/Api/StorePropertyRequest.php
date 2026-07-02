<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('agency');
    }

    public function rules(): array
    {
        return [
            'property_type_id' => 'required|exists:property_types,id',
            'agent_id'         => 'nullable|exists:agents,id',
            'governorate_id'   => 'required|exists:governorates,id',
            'area_id'          => 'nullable|exists:areas,id',
            'purpose'          => 'required|in:sale,rent',
            'title_ar'         => 'required|string|max:255',
            'title_en'         => 'required|string|max:255',
            'description_ar'   => 'required|string',
            'description_en'   => 'required|string',
            'price'            => 'required|numeric|min:0',
            'currency'         => 'required|in:USD,SYP',
            'area_sqm'         => 'required|integer|min:1',
            'bedrooms'         => 'nullable|integer|min:0',
            'bathrooms'        => 'nullable|integer|min:0',
            'status'           => 'sometimes|in:draft,available',
            'is_featured'      => 'sometimes|boolean',
            'is_hot_deal'      => 'sometimes|boolean',
        ];
    }
}
