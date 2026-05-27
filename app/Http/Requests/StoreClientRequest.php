<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'naam'                          => ['required', 'string', 'max:255'],
            'contactpersoon'                => ['nullable', 'string', 'max:255'],
            'email'                         => ['required', 'email', 'max:255'],
            'telefoon'                      => ['nullable', 'string', 'max:50'],
            'website'                       => ['nullable', 'string', 'max:255'],
            'sector'                        => ['nullable', Rule::in(Client::SECTOREN)],
            'relatie_status'                => ['nullable', Rule::in(Client::RELATIE_STATUSSEN)],
            'adres'                         => ['nullable', 'string', 'max:255'],
            'postcode'                      => ['nullable', 'string', 'max:20'],
            'stad'                          => ['nullable', 'string', 'max:255'],
            'beschrijving'                  => ['nullable', 'string', 'max:2000'],
            'logo'                          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'contactpersonen'               => ['nullable', 'array'],
            'contactpersonen.*.naam'        => ['required_with:contactpersonen.*', 'string', 'max:255'],
            'contactpersonen.*.email'       => ['nullable', 'email', 'max:255'],
            'contactpersonen.*.telefoon'    => ['nullable', 'string', 'max:50'],
            // Bank
            'bank'                          => ['nullable', 'string', 'max:255'],
            'bic'                           => ['nullable', 'string', 'max:20'],
            'iban'                          => ['nullable', 'string', 'max:34'],
            'rekeninghouder'                => ['nullable', 'string', 'max:255'],
            'vestigingsplaats'              => ['nullable', 'string', 'max:255'],
            // Administratie
            'gebruik_afwijkende_factuurgegevens' => ['nullable', 'boolean'],
            // Extra
            'kvk_nummer'                    => ['nullable', 'string', 'max:50'],
            'rechtsvorm'                    => ['nullable', Rule::in(Client::RECHTSVORMEN)],
            'btw_nummer'                    => ['nullable', 'string', 'max:50'],
            'extern_id'                     => ['nullable', 'string', 'max:255'],
            // Instellingen
            'relatiebeheerder_id'           => ['nullable', 'integer', 'exists:users,id'],
            'voertaal'                      => ['nullable', 'string', Rule::in(array_keys(Client::TALEN))],
            'taal_berichten'                => ['nullable', 'string', Rule::in(array_keys(Client::TALEN))],
            'labels'                        => ['nullable', 'array'],
            'labels.*'                      => ['string', 'max:100'],
        ];
    }
}
