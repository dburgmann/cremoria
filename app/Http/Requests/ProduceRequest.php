<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a build / recruit / research request.
 */
class ProduceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Kein Element ausgewählt.',
            'item_id.integer'  => 'Ungültige Element-ID.',
        ];
    }
}
