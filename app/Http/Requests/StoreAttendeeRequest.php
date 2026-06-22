<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendeeRequest extends FormRequest
{
    /**
     * Registering interest in an event is open to anyone — no auth required.
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
            'name' => ['required', 'string', 'max:255'],
            // `email` (RFC) only — no `dns` check, which would add a network
            // dependency and make the test suite flaky offline.
            'email' => ['required', 'email', 'max:255'],
            // The browser sends an IANA identifier (e.g. "America/New_York");
            // the `timezone` rule rejects anything that isn't a real zone.
            'timezone' => ['nullable', 'timezone'],
        ];
    }
}
