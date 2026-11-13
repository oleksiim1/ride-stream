<?php

namespace Modules\LocationIngest\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDriverLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'string', 'max:64'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'heading' => ['required', 'integer', 'between:0,359'],
            'speed_kmh' => ['required', 'numeric', 'min:0'],
            'accuracy_m' => ['required', 'numeric', 'min:0'],
            'recorded_at' => ['required', 'date'],
        ];
    }
}
