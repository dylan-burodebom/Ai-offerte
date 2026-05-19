<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientOpmerking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientOpmerkingController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $request->validate(['tekst' => 'required|string|max:5000']);

        $client->opmerkingen()->create(['tekst' => $request->tekst]);

        return back()->with('success', 'Opmerking toegevoegd.');
    }

    public function destroy(Client $client, ClientOpmerking $opmerking): RedirectResponse
    {
        $opmerking->delete();

        return back()->with('success', 'Opmerking verwijderd.');
    }
}
