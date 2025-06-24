<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => 'sometimes|exists:rooms,id',
            'property_id' => 'sometimes|exists:properties,id',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',
            'guests' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string',
        ];
    }
}
