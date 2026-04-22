<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovementRequest extends FormRequest
{
    public function rules(): array
    {
        $mapSize = (int) config('game.game.map_size', 100) - 1;

        return [
            'dest_x'        => ['required', 'integer', 'min:0', "max:{$mapSize}"],
            'dest_y'        => ['required', 'integer', 'min:0', "max:{$mapSize}"],
            'action'        => ['required', 'integer', 'min:0', 'max:3'],
            'units'         => ['required', 'array'],
            'units.*'       => ['integer', 'min:0'],
            'gold'          => ['nullable', 'integer', 'min:0'],
            'wood'          => ['nullable', 'integer', 'min:0'],
            'stone'         => ['nullable', 'integer', 'min:0'],
            'iron'          => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'dest_x.required' => 'Ziel-X-Koordinate fehlt.',
            'dest_y.required' => 'Ziel-Y-Koordinate fehlt.',
            'dest_x.min'      => 'Koordinate außerhalb der Karte.',
            'dest_y.min'      => 'Koordinate außerhalb der Karte.',
            'dest_x.max'      => 'Koordinate außerhalb der Karte.',
            'dest_y.max'      => 'Koordinate außerhalb der Karte.',
            'action.required' => 'Bewegungstyp fehlt.',
            'units.required'  => 'Keine Einheiten ausgewählt.',
        ];
    }
}
