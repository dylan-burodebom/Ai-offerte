<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class KlantController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user   = Auth::user();
        $client = $user->client;

        abort_if(! $client, 403, 'Geen bedrijf gekoppeld aan dit account.');

        $quotes = $client->quotes()
            ->orderByDesc('created_at')
            ->get(['id', 'offerte_nummer', 'titel', 'status', 'totaal', 'geldig_tot', 'created_at', 'versie']);

        return Inertia::render('Klant/Dashboard', [
            'client' => $client->only(['id', 'naam', 'logo_url']),
            'quotes' => $quotes,
        ]);
    }

    public function pdf(Quote $quote): HttpResponse
    {
        $user = Auth::user();

        // Ensure this quote belongs to the klant's client
        abort_if($quote->client_id !== $user->client_id, 403);

        $pdf      = app(PdfService::class)->generateCached($quote);
        $filename = "{$quote->offerte_nummer}_v{$quote->versie}.pdf";

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Content-Length'      => strlen($pdf),
        ]);
    }
}
