<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteDraftRequest;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteSection;
use App\Services\OfferteNummerService;
use App\Services\OpenAiQuoteService;
use App\Services\PdfService;
use App\Services\PromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteController extends Controller
{
    public function index(Request $request): Response
    {
        $sorteerbaar = ['offerte_nummer', 'totaal', 'geldig_tot', 'created_at', 'status'];
        $sort  = in_array($request->sort, $sorteerbaar) ? $request->sort : 'created_at';
        $dir   = $request->direction === 'asc' ? 'asc' : 'desc';

        $quotes = Quote::with('client:id,naam,sector')
            ->when($request->search, fn ($q, $s) => $q
                ->where(fn ($q2) => $q2
                    ->where('offerte_nummer', 'like', "%{$s}%")
                    ->orWhere('titel', 'like', "%{$s}%")
                    ->orWhereHas('client', fn ($c) => $c->where('naam', 'like', "%{$s}%"))))
            ->when($request->status,    fn ($q, $s) => $q->where('status', $s))
            ->when($request->sector,    fn ($q, $s) => $q->whereHas('client', fn ($c) => $c->where('sector', $s)))
            ->when($request->datum_van, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->datum_tot, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Quotes/Index', [
            'quotes'   => $quotes,
            'filters'  => $request->only(['search', 'status', 'sector', 'datum_van', 'datum_tot', 'sort', 'direction']),
            'statussen' => Quote::STATUSSEN,
            'sectoren'  => Client::SECTOREN,
        ]);
    }

    public function destroy(Quote $quote): JsonResponse
    {
        $quote->delete();
        return response()->json(['success' => true]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
            'actie'  => ['required', 'in:status,verwijderen'],
            'status' => ['nullable', 'string', 'in:' . implode(',', Quote::STATUSSEN)],
        ]);

        $quotes = Quote::whereIn('id', $request->ids)->get();

        if ($request->actie === 'verwijderen') {
            $count = $quotes->count();
            $quotes->each->delete();
            return response()->json(['verwijderd' => $count]);
        }

        $bijgewerkt = 0;
        foreach ($quotes as $quote) {
            if ($quote->kanNaarStatus($request->status)) {
                $oudeStatus = $quote->status;
                $quote->update(['status' => $request->status]);
                $quote->statusHistory()->create([
                    'user_id'       => Auth::id(),
                    'oude_status'   => $oudeStatus,
                    'nieuwe_status' => $request->status,
                    'datum'         => now(),
                ]);
                $bijgewerkt++;
            }
        }
        return response()->json(['bijgewerkt' => $bijgewerkt]);
    }

    public function export(Request $request): StreamedResponse
    {
        $quotes = Quote::with('client:id,naam')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->sector, fn ($q, $s) => $q->whereHas('client', fn ($c) => $c->where('sector', $s)))
            ->orderByDesc('created_at')
            ->get();

        return response()->stream(function () use ($quotes) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM voor Excel
            fputcsv($handle, ['Nummer', 'Klant', 'Titel', 'Status', 'Bedrag', 'Geldig tot', 'Aangemaakt'], ';');
            foreach ($quotes as $q) {
                fputcsv($handle, [
                    $q->offerte_nummer,
                    $q->client?->naam ?? '',
                    $q->titel,
                    $q->status,
                    number_format((float) $q->totaal, 2, ',', '.'),
                    $q->geldig_tot?->format('d-m-Y') ?? '',
                    $q->created_at->format('d-m-Y'),
                ], ';');
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="offertes.csv"',
        ]);
    }

    public function create(Request $request): Response
    {
        $preselectedClient = null;
        if ($request->client_id) {
            $preselectedClient = Client::find($request->client_id);
        }

        return Inertia::render('Quotes/Create', [
            'sectoren'          => Client::SECTOREN,
            'dienstenLijst'     => config('diensten'),
            'preselectedClient' => $preselectedClient,
        ]);
    }

    public function storeDraft(StoreQuoteDraftRequest $request): JsonResponse
    {
        $offerteNummer = app(OfferteNummerService::class)->genereer();

        $quote = Quote::create([
            'client_id'              => $request->client_id,
            'user_id'                => Auth::id(),
            'offerte_nummer'         => $offerteNummer,
            'titel'                  => $request->titel,
            'projectbeschrijving'    => $request->projectbeschrijving,
            'fireflies_samenvatting' => $request->fireflies_samenvatting,
            'diensten'               => $request->diensten,
            'geldig_tot'             => $request->geldig_tot,
            'status'                 => 'concept',
        ]);

        return response()->json([
            'id'             => $quote->id,
            'offerte_nummer' => $quote->offerte_nummer,
        ], 201);
    }

    public function updateDraft(StoreQuoteDraftRequest $request, Quote $quote): JsonResponse
    {
        $quote->update([
            'client_id'              => $request->client_id,
            'titel'                  => $request->titel,
            'projectbeschrijving'    => $request->projectbeschrijving,
            'fireflies_samenvatting' => $request->fireflies_samenvatting,
            'diensten'               => $request->diensten,
            'geldig_tot'             => $request->geldig_tot,
        ]);

        return response()->json([
            'id'             => $quote->id,
            'offerte_nummer' => $quote->offerte_nummer,
        ]);
    }

    public function edit(Quote $quote): Response
    {
        $quote->load([
            'client',
            'sections'      => fn ($q) => $q->orderBy('volgorde'),
            'investments'   => fn ($q) => $q->orderBy('volgorde'),
            'statusHistory' => fn ($q) => $q->with('user')->orderByDesc('datum'),
        ]);

        return Inertia::render('Quotes/Edit', [
            'quote' => $quote,
        ]);
    }

    public function updateMeta(Request $request, Quote $quote): JsonResponse
    {
        $data = $request->validate([
            'titel'           => ['required', 'string', 'min:3', 'max:255'],
            'geldig_tot'      => ['nullable', 'date'],
            'pdf_blok_ruimte' => ['nullable', 'integer', 'min:0', 'max:60'],
            'toon_btw'        => ['nullable', 'boolean'],
            'inv_volgorde'    => ['nullable', 'integer', 'min:0'],
        ]);
        $quote->update($data);
        $this->warmPdfCache($quote->id);
        return response()->json(['saved' => true]);
    }

    public function reorderSections(Request $request, Quote $quote): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);
        foreach ($request->order as $volgorde => $sectionId) {
            $quote->sections()->where('id', $sectionId)->update(['volgorde' => $volgorde]);
        }
        return response()->json(['saved' => true]);
    }

    public function addSection(Request $request, Quote $quote): JsonResponse
    {
        $request->validate([
            'titel'      => ['required', 'string', 'max:255'],
            'volgorde'   => ['required', 'integer'],
            'block_type' => ['nullable', 'string', 'max:50'],
        ]);
        $section = $quote->sections()->create([
            'titel'    => $request->titel,
            'content'  => ['html' => '', 'ai_html' => null, 'block_type' => $request->block_type],
            'volgorde' => $request->volgorde,
        ]);
        return response()->json([
            'id'       => $section->id,
            'titel'    => $section->titel,
            'volgorde' => $section->volgorde,
        ], 201);
    }

    public function deleteSection(Quote $quote, QuoteSection $section): JsonResponse
    {
        abort_if($section->quote_id !== $quote->id, 403);
        $section->delete();
        return response()->json(['deleted' => true]);
    }

    public function updateSection(Request $request, Quote $quote, QuoteSection $section): JsonResponse
    {
        $data = $request->validate([
            'html'  => ['sometimes', 'string'],
            'titel' => ['sometimes', 'string', 'max:255'],
        ]);

        $updates = [];
        if (array_key_exists('html', $data)) {
            $updates['content'] = array_merge($section->content ?? [], ['html' => $data['html']]);
        }
        if (array_key_exists('titel', $data)) {
            $updates['titel'] = $data['titel'];
        }

        if ($updates) {
            $section->update($updates);
        }

        $this->warmPdfCache($quote->id);
        return response()->json(['saved' => true]);
    }

    public function restoreSection(Quote $quote, QuoteSection $section): JsonResponse
    {
        $aiHtml = ($section->content ?? [])['ai_html'] ?? null;
        abort_if(!$aiHtml, 404, 'Geen AI-versie beschikbaar.');
        $section->update(['content' => array_merge($section->content, ['html' => $aiHtml])]);
        return response()->json(['html' => $aiHtml]);
    }

    public function createVersion(Quote $quote): JsonResponse
    {
        $quote->load(['sections', 'investments']);

        $newNumber = app(OfferteNummerService::class)->nieuwVersie($quote->offerte_nummer);
        $newQuote  = $quote->replicate(['offerte_nummer', 'versie']);
        $newQuote->offerte_nummer = $newNumber;
        $newQuote->versie         = $quote->versie + 1;
        $newQuote->save();

        foreach ($quote->sections as $section) {
            $new = $section->replicate();
            $new->quote_id = $newQuote->id;
            $new->save();
        }
        foreach ($quote->investments as $investment) {
            $new = $investment->replicate();
            $new->quote_id = $newQuote->id;
            $new->save();
        }

        return response()->json([
            'id'             => $newQuote->id,
            'offerte_nummer' => $newQuote->offerte_nummer,
        ]);
    }

    public function saveInvestments(Request $request, Quote $quote): JsonResponse
    {
        $request->validate([
            'rows'                => ['required', 'array', 'min:1'],
            'rows.*.omschrijving' => ['required', 'string', 'max:255'],
            'rows.*.bedrag'       => ['required', 'numeric', 'gt:0'],
            'btw_tarief'          => ['required', 'in:21%,0%,vrijgesteld'],
        ], [
            'rows.required'            => 'Voeg minimaal één investeringsregel toe.',
            'rows.min'                 => 'Voeg minimaal één investeringsregel toe.',
            'rows.*.omschrijving.required' => 'Vul een omschrijving in.',
            'rows.*.bedrag.required'   => 'Vul een bedrag in.',
            'rows.*.bedrag.gt'         => 'Bedrag moet groter zijn dan 0.',
        ]);

        DB::transaction(function () use ($quote, $request) {
            $quote->investments()->delete();

            foreach ($request->rows as $i => $row) {
                $quote->investments()->create([
                    'omschrijving' => $row['omschrijving'],
                    'bedrag'       => $row['bedrag'],
                    'volgorde'     => $i,
                ]);
            }

            $subtotaal = collect($request->rows)->sum('bedrag');

            $quote->update([
                'btw_tarief' => $request->btw_tarief,
                'totaal'     => $subtotaal,
            ]);
        });

        return response()->json(['success' => true]);
    }

    private function warmPdfCache(int $quoteId): void
    {
        dispatch(function () use ($quoteId) {
            try {
                $quote = Quote::with([
                    'client',
                    'sections'     => fn ($q) => $q->orderBy('volgorde'),
                    'investments'  => fn ($q) => $q->orderBy('volgorde'),
                ])->find($quoteId);

                if ($quote) {
                    app(PdfService::class)->generateCached($quote);
                }
            } catch (\Throwable) {
                // cache warming is best-effort — nooit de response blokkeren
            }
        })->afterResponse();
    }

    public function generate(Quote $quote): StreamedResponse
    {

        // Save session before streaming to avoid session lock issues
        \Illuminate\Support\Facades\Session::save();

        return response()->stream(function () use ($quote) {
            if (ob_get_level()) ob_end_clean();

            $service  = app(OpenAiQuoteService::class);
            $fullText = '';
            $tokens   = 0;

            $sse = function (array $data) {
                echo 'data: ' . json_encode($data) . "\n\n";
                if (ob_get_level()) ob_flush();
                flush();
            };

            try {
                $quote->load('client');
                $messages = $service->buildMessages($quote);

                $sse(['type' => 'start']);

                $service->stream(
                    $messages,
                    onToken: function (string $token) use (&$fullText, $sse) {
                        $fullText .= $token;
                        $sse(['type' => 'token', 'content' => $token]);
                    },
                    onUsage: function (int $total) use (&$tokens) {
                        $tokens = $total;
                    }
                );

                $sections = $service->parseSections($fullText);

                $promptVersie = app(PromptService::class)->getCurrentVersion();

                DB::transaction(function () use ($quote, $sections, $tokens, $service, $promptVersie) {
                    $quote->sections()->delete();
                    foreach ($sections as $section) {
                        $html = $service->textToHtml($section['tekst']);
                        $quote->sections()->create([
                            'titel'    => $section['titel'],
                            'content'  => ['html' => $html, 'ai_html' => $html],
                            'volgorde' => $section['volgorde'],
                        ]);
                    }
                    $quote->update([
                        'tokens_gebruikt' => $tokens ?: null,
                        'prompt_versie'   => $promptVersie,
                    ]);
                });

                Log::info('AI generatie voltooid', [
                    'quote_id'        => $quote->id,
                    'offerte_nummer'  => $quote->offerte_nummer,
                    'tokens_gebruikt' => $tokens,
                ]);

                $quote->load('sections');
                $sse([
                    'type'     => 'done',
                    'sections' => $quote->sections
                        ->sortBy('volgorde')
                        ->values()
                        ->map(fn ($s) => [
                            'id'      => $s->id,
                            'titel'   => $s->titel,
                            'content' => $s->content,
                            'volgorde' => $s->volgorde,
                        ])
                        ->toArray(),
                    'tokens'   => $tokens,
                ]);
            } catch (\Throwable $e) {
                Log::error('AI generatie mislukt', [
                    'quote_id' => $quote->id,
                    'error'    => $e->getMessage(),
                ]);

                $sse([
                    'type'    => 'error',
                    'message' => $service->humanizeError($e->getMessage()),
                ]);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    public function pdf(Quote $quote): \Illuminate\Http\Response
    {

        $pdf      = app(PdfService::class)->generateCached($quote);
        $filename = "{$quote->offerte_nummer}_v{$quote->versie}.pdf";

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length'      => strlen($pdf),
        ]);
    }

    public function pdfPreview(Quote $quote): \Illuminate\Http\Response
    {

        $pdf      = app(PdfService::class)->generateCached($quote);
        $filename = "{$quote->offerte_nummer}_v{$quote->versie}.pdf";

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Content-Length'      => strlen($pdf),
        ]);
    }
}
