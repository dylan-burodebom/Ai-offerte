<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'initialStats' => $this->buildStats(auth()->id(), 'jaar'),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'periode' => ['required', 'in:maand,kwartaal,jaar,alles'],
        ]);

        $userId  = auth()->id();
        $periode = $request->periode;
        $cacheKey = "dashboard_stats_{$userId}_{$periode}";

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $periode) {
            return $this->buildStats($userId, $periode);
        });

        return response()->json($stats);
    }

    private function buildStats(int $userId, string $periode): array
    {
        $from = match ($periode) {
            'maand'    => now()->startOfMonth(),
            'kwartaal' => now()->startOfQuarter(),
            'jaar'     => now()->startOfYear(),
            default    => null,
        };

        $base = Quote::where('user_id', $userId)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from));

        $totaal       = (clone $base)->count();
        $openstaand   = (clone $base)->whereIn('status', ['concept', 'verzonden'])->count();
        $gewonnen     = (clone $base)->where('status', 'gewonnen')->count();
        $verloren     = (clone $base)->where('status', 'verloren')->count();
        $verzonden    = (clone $base)->where('status', 'verzonden')->count();
        $omzetGewonnen = (clone $base)->where('status', 'gewonnen')->sum('totaal');

        $afgesloten   = $gewonnen + $verloren;
        $conversie    = $afgesloten > 0 ? round($gewonnen / $afgesloten * 100, 1) : null;

        // Offertes per maand (last 12 months regardless of period filter)
        $perMaand = Quote::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as maand"),
                DB::raw('COUNT(*) as aantal'),
                DB::raw("SUM(CASE WHEN status = 'gewonnen' THEN 1 ELSE 0 END) as gewonnen")
            )
            ->groupBy('maand')
            ->orderBy('maand')
            ->get()
            ->keyBy('maand');

        // Fill in missing months
        $maanden = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $maanden[] = [
                'maand'    => now()->subMonths($i)->locale('nl')->isoFormat('MMM YY'),
                'aantal'   => $perMaand[$key]->aantal ?? 0,
                'gewonnen' => $perMaand[$key]->gewonnen ?? 0,
            ];
        }

        // Recente offertes
        $recente = Quote::where('user_id', $userId)
            ->with('client:id,naam')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'client_id', 'offerte_nummer', 'titel', 'status', 'totaal', 'geldig_tot', 'created_at']);

        return [
            'totaal'        => $totaal,
            'openstaand'    => $openstaand,
            'gewonnen'      => $gewonnen,
            'verloren'      => $verloren,
            'verzonden'     => $verzonden,
            'omzetGewonnen' => (float) $omzetGewonnen,
            'conversie'     => $conversie,
            'perMaand'      => $maanden,
            'recente'       => $recente,
        ];
    }
}
