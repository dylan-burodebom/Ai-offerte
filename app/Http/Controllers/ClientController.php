<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $clients = Client::query()
            ->when($request->search, fn ($q, $s) => $q
                ->where('naam', 'like', "%{$s}%")
                ->orWhere('contactpersoon', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->when($request->sector, fn ($q, $s) => $q->where('sector', $s))
            ->when($request->relatie_status, fn ($q, $s) => $q->where('relatie_status', $s))
            ->orderBy('naam')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients'          => $clients,
            'filters'          => $request->only(['search', 'sector', 'relatie_status']),
            'sectoren'         => Client::SECTOREN,
            'relatie_statussen' => Client::RELATIE_STATUSSEN,
        ]);
    }

    public function show(Client $client): Response
    {
        $client->loadCount('quotes');
        $quotes = $client->quotes()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'offerte_nummer', 'titel', 'status', 'totaal', 'geldig_tot', 'created_at', 'verzonden_op']);

        $contactpersonen = $client->contactpersonen()->get();
        $opmerkingen = $client->opmerkingen()->with('user:id,name')->get();

        return Inertia::render('Clients/Show', [
            'client'          => $client,
            'quotes'          => $quotes,
            'contactpersonen' => $contactpersonen,
            'opmerkingen'     => $opmerkingen,
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except('logo')->toArray();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('client-logos', 'public');
        }

        Client::create($data);

        return redirect()->back()->with('success', 'Bedrijf aangemaakt.');
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $data = collect($request->validated())->except(['logo', 'verwijder_logo'])->toArray();

        if ($request->hasFile('logo')) {
            if ($client->logo) Storage::disk('public')->delete($client->logo);
            $data['logo'] = $request->file('logo')->store('client-logos', 'public');
        } elseif ($request->boolean('verwijder_logo')) {
            if ($client->logo) Storage::disk('public')->delete($client->logo);
            $data['logo'] = null;
        }

        $client->update($data);

        return redirect()->back()->with('success', 'Bedrijf bijgewerkt.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->back()->with('success', 'Bedrijf verwijderd.');
    }

    public function storeInline(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json($client, 201);
    }

    public function search(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->when($request->q, fn ($q, $s) => $q
                ->where('naam', 'like', "%{$s}%")
                ->orWhere('contactpersoon', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->orderBy('naam')
            ->limit($request->q ? 10 : 50)
            ->get(['id', 'naam', 'contactpersoon', 'email', 'sector']);

        return response()->json($clients);
    }
}
