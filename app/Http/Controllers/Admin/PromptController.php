<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptController extends Controller
{
    public function __construct(private PromptService $prompts) {}

    public function index(): Response
    {
        $sectorLabels = $this->prompts->getSectorenLijst();
        $sectoren     = [];
        foreach (array_keys($sectorLabels) as $slug) {
            $sectoren[$slug] = $this->prompts->getSectorPrompt($slug);
        }

        return Inertia::render('Admin/Prompts', [
            'stijlgids'    => $this->prompts->getStijlgids(),
            'sectoren'     => $sectoren,
            'sectorLabels' => $sectorLabels,
            'meta'         => $this->prompts->getMeta(),
        ]);
    }

    public function updateStijlgids(Request $request): JsonResponse
    {
        $request->validate(['content' => ['required', 'string']]);
        $this->prompts->saveStijlgids($request->content);

        return response()->json([
            'versie' => $this->prompts->getCurrentVersion(),
        ]);
    }

    public function storeSector(Request $request): JsonResponse
    {
        $request->validate([
            'slug'  => ['required', 'string', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:60'],
        ]);

        $sectoren = $this->prompts->getSectorenLijst();
        if (array_key_exists($request->slug, $sectoren)) {
            return response()->json(['message' => 'Deze sector bestaat al.'], 422);
        }

        $this->prompts->addSector($request->slug, $request->label);

        return response()->json([
            'sectoren' => $this->prompts->getSectorenLijst(),
            'versie'   => $this->prompts->getCurrentVersion(),
        ]);
    }

    public function updateSector(Request $request, string $sector): JsonResponse
    {
        abort_unless(array_key_exists($sector, $this->prompts->getSectorenLijst()), 404);
        $request->validate(['content' => ['required', 'string']]);
        $this->prompts->saveSectorPrompt($sector, $request->content);

        return response()->json([
            'versie' => $this->prompts->getCurrentVersion(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'sector'              => ['nullable', 'string'],
            'projectbeschrijving' => ['nullable', 'string'],
            'fireflies'           => ['nullable', 'string'],
        ]);

        $sector  = $request->sector ?? 'overig';
        $context = $this->prompts->buildSectorContext($sector);

        if ($request->fireflies) {
            $body = "Gespreksnotitie (Fireflies samenvatting):\n{$request->fireflies}";
            if ($request->projectbeschrijving) {
                $body .= "\nAanvullende notities:\n{$request->projectbeschrijving}";
            }
        } else {
            $body = "Projectbeschrijving:\n{$request->projectbeschrijving}";
        }

        return response()->json([
            'system' => $this->prompts->buildSystemPrompt(),
            'user'   => "Schrijf een offerte voor dit project:\n\nKlant: [voorbeeld]\nSector: {$sector}\n\n{$body}\n\n{$context}",
            'versie' => $this->prompts->getCurrentVersion(),
        ]);
    }
}
