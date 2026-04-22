<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username'              => ['required', 'string', 'alpha', 'max:32', 'unique:users,username'],
            'email'                 => ['required', 'email', 'max:127', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'    => 'Benutzername ist erforderlich.',
            'username.alpha'       => 'Benutzername darf nur Buchstaben enthalten.',
            'username.unique'      => 'Dieser Benutzername ist bereits vergeben.',
            'email.required'       => 'E-Mail-Adresse ist erforderlich.',
            'email.email'          => 'Ungültige E-Mail-Adresse.',
            'email.unique'         => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.required'    => 'Passwort ist erforderlich.',
            'password.min'         => 'Das Passwort muss mindestens :min Zeichen lang sein.',
            'password.confirmed'   => 'Die Passwörter stimmen nicht überein.',
        ];
    }
}
