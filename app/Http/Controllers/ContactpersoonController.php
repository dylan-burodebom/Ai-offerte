<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contactpersoon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactpersoonController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'naam'          => 'required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'telefoon'      => 'nullable|string|max:50',
            'geboortedatum' => 'nullable|date',
        ]);

        $client->contactpersonen()->create($data);

        return back()->with('success', 'Contactpersoon toegevoegd.');
    }

    public function update(Request $request, Client $client, Contactpersoon $contactpersoon): RedirectResponse
    {
        $data = $request->validate([
            'naam'          => 'required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'telefoon'      => 'nullable|string|max:50',
            'geboortedatum' => 'nullable|date',
        ]);

        $contactpersoon->update($data);

        return back()->with('success', 'Contactpersoon bijgewerkt.');
    }

    public function destroy(Client $client, Contactpersoon $contactpersoon): RedirectResponse
    {
        $contactpersoon->delete();

        return back()->with('success', 'Contactpersoon verwijderd.');
    }
}
