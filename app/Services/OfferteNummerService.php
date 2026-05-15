<?php

namespace App\Services;

use App\Models\Quote;

class OfferteNummerService
{
    // Genereert een uniek offerte-nummer in het formaat OFFyy00001-V01
    public function genereer(int $versie = 1): string
    {
        $jaar = date('y');
        $versieLabel = 'V' . str_pad($versie, 2, '0', STR_PAD_LEFT);

        $laatste = Quote::where('offerte_nummer', 'like', "OFF{$jaar}%")
            ->orderByDesc('id')
            ->value('offerte_nummer');

        $volgend = 1;
        if ($laatste) {
            $nummer = (int) substr($laatste, 5, 5);
            $volgend = $nummer + 1;
        }

        $volgnummer = str_pad($volgend, 5, '0', STR_PAD_LEFT);

        return "OFF{$jaar}{$volgnummer}-{$versieLabel}";
    }

    public function nieuwVersie(string $offerteNummer): string
    {
        preg_match('/-V(\d+)$/', $offerteNummer, $matches);
        $huidigeVersie = isset($matches[1]) ? (int) $matches[1] : 1;
        $basis = preg_replace('/-V\d+$/', '', $offerteNummer);

        return $basis . '-V' . str_pad($huidigeVersie + 1, 2, '0', STR_PAD_LEFT);
    }
}
