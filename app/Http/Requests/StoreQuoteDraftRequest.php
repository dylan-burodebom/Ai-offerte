<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'           => ['required', 'exists:clients,id'],
            'titel'               => ['required', 'string', 'min:3', 'max:255'],
            'projectbeschrijving' => ['nullable', 'string'],
            'diensten'            => ['required', 'array', 'min:1'],
            'diensten.*'          => ['string'],
            'geldig_tot'               => ['required', 'date', 'after:today'],
            'fireflies_samenvatting'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'           => 'Selecteer een klant.',
            'client_id.exists'             => 'De geselecteerde klant bestaat niet.',
            'titel.required'               => 'Vul een titel in.',
            'titel.min'                    => 'De titel moet minimaal 3 tekens bevatten.',
            'projectbeschrijving.required' => 'Vul een projectbeschrijving in.',
            'diensten.required'            => 'Selecteer minimaal één dienst.',
            'diensten.min'                 => 'Selecteer minimaal één dienst.',
            'geldig_tot.required'          => 'Kies een geldigheidsdatum.',
            'geldig_tot.after'             => 'De geldigheidsdatum moet in de toekomst liggen.',
        ];
    }
}
