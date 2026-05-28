<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'Fira Sans';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts["fira_regular"] }}') format('truetype');
}
@font-face {
    font-family: 'Fira Sans';
    font-weight: 400;
    font-style: italic;
    src: url('{{ $fonts["fira_italic"] }}') format('truetype');
}
@font-face {
    font-family: 'Fira Sans';
    font-weight: 600;
    font-style: normal;
    src: url('{{ $fonts["fira_semibold"] }}') format('truetype');
}
@font-face {
    font-family: 'Fira Sans';
    font-weight: 700;
    font-style: normal;
    src: url('{{ $fonts["fira_bold"] }}') format('truetype');
}
@font-face {
    font-family: 'OxideSolidPro';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts["oxide_regular"] }}') format('truetype');
}
@font-face {
    font-family: 'OxideSolidPro';
    font-weight: 700;
    font-style: normal;
    src: url('{{ $fonts["oxide_bold"] }}') format('truetype');
}

* { margin: 0; padding: 0; box-sizing: border-box; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; }

/* Geen paginamarges — de tabel-thead zorgt voor ruimte boven elke pagina */
@page { size: A4; margin: 0; }

/* DOMPDF: reset word-break voor table cells zodat DOMPDF geen extra hoogte berekent */
td, th { word-wrap: normal; overflow-wrap: normal; word-break: normal; }

body {
    margin: 0;
    padding: 0;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
}

/* ── VOORBLAD ── */
.voorblad {
    width: 210mm;
    height: 297mm;
    position: relative;
    overflow: hidden;
    background-color: #030810;
    padding: 0;
    page-break-after: always;
}
.voorblad-bg {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
}
.voorblad-content {
    position: absolute;
    left: 19mm; top: 116mm;
    max-width: 160mm;
}
.voorblad-label {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; font-size: 14pt;
    color: #e0e0e0; letter-spacing: 0.05em;
    margin-bottom: 4mm;
    padding-left: 11mm;
}
.voorblad-titel-wrapper {
    border-collapse: collapse;
    margin-bottom: 6mm;
}
.voorblad-streep-cel {
    width: 11mm; padding: 0;
    vertical-align: middle;
}
.voorblad-streep {
    width: 7mm; height: 4px;
    background-color: #4076f0;
}
.voorblad-titel-cel {
    padding: 0;
    vertical-align: middle;
}
.voorblad-titel {
    font-family: 'OxideSolidPro', sans-serif;
    font-weight: bold; font-size: 20pt;
    color: #ffffff; letter-spacing: 0.08em;
    text-transform: uppercase;
    line-height: 1.15;
    max-width: 100%;
    word-wrap: break-word;
}
.voorblad-meta {
    font-size: 9.5pt;
    max-width: 100%;
    padding-left: 11mm;
}
.voorblad-meta table { border-collapse: collapse; }
.voorblad-meta td { padding: 0.9mm 0; vertical-align: top; line-height: 1.3; }
.voorblad-meta td.label {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 600; min-width: 40mm; color: #ffffff;
}
.voorblad-meta td.value {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; color: #aabbcc;
}

/* ── CONTENT TABEL ── */
/* De <thead> herhaalt op elke pagina — zo krijgt elke pagina de header */
/* Elke sectie krijgt een eigen <tr> zodat DOMPDF rij-voor-rij kan pagineren */
.content-table {
    width: 210mm;
    border-collapse: collapse;
}
.content-table thead tr.header-row td {
    padding: 10mm 19.1mm 4mm;
    border-bottom: 1px solid #e0e0e0;
    background: #ffffff;
}
.header-inner {
    display: table;
    width: 100%;
}
.header-inner-left {
    display: table-cell;
    vertical-align: bottom;
    font-family: 'Fira Sans', sans-serif;
    font-weight: 700; font-size: 9pt;
    color: #0a0a0a;
}
.header-inner-left span { color: #4076f0; }
.header-inner-right {
    display: table-cell;
    vertical-align: bottom;
    text-align: right;
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; font-size: 8pt;
    color: #999999;
}
/* Adempauze na de headerregel — herhaalt ook op elke pagina */
.content-table thead tr.header-spacer td {
    height: 9mm;
    background: #ffffff;
    padding: 0;
}
/* Elke blok-rij: padding via inline style (per rij verschilt de top-marge) */
.content-table tbody tr td {
    padding-left: 19.1mm;
    padding-right: 19.1mm;
    vertical-align: top;
}

/* ── CONTENT ── */
.content-blok {
    font-family: 'Fira Sans', sans-serif;
    font-size: 10pt; color: #1a1a1a;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    vertical-align: top;
}

/* Voorkom slechte pagina-afbrekingen */
.section-title { page-break-after: avoid; }
h2 { page-break-after: avoid; }
h3 { page-break-after: avoid; }
.subitem { page-break-inside: avoid; padding-top: 0; padding-bottom: 14mm; margin-bottom: 0; }
.subitem strong { display: block; margin-bottom: 2mm; }
.subitem-section h2 { padding-bottom: 14mm; }

/* Sectietitel (grote paginakop) */
.section-title {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 700; font-size: 22pt;
    color: #0a0a0a; margin-bottom: 0; padding-bottom: 5mm; line-height: 1.2;
    max-width: 100%;
    word-wrap: break-word;
}

/* Typography */
h2 {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 700; font-size: 12pt;
    color: #0a0a0a;
    margin-top: 6mm; margin-bottom: 0; padding-bottom: 2mm;
    line-height: 1.3;
    border-bottom: 0.5px solid #e0e0e0;
    max-width: 100%;
    word-wrap: break-word;
}
h3 {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 600; font-size: 10.5pt;
    color: #111111; margin-bottom: 1mm; margin-top: 4mm;
    line-height: 1.3;
}
p {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; font-size: 10pt;
    line-height: 1.5; margin-bottom: 3.5mm; color: #333333;
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
ul { margin: 2mm 0 6mm 0; padding-left: 5mm; }
ol { margin: 2mm 0 6mm 0; padding-left: 5mm; }
ul li {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; font-size: 10pt;
    line-height: 1.5; margin-bottom: 0;
    color: #333333; list-style-type: disc; padding-left: 1mm;
    word-wrap: break-word;
}
strong { font-family: 'Fira Sans', sans-serif; font-weight: 600; color: #111111; }

/* ── INVESTERING ── */
.inv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
    font-family: 'Fira Sans', sans-serif;
    line-height: 1.3;
}
.inv-table tr td {
    padding: 2.5mm 0;
    border-top: 1px solid #cccccc;
    vertical-align: middle;
    line-height: 1.3;
    font-family: 'Fira Sans', sans-serif;
    font-size: 10pt;
}
.inv-table tr td.toelichting {
    padding-top: 0;
    padding-bottom: 2.5mm;
    color: #666;
    border-top: none;
    border-bottom: 0.5px solid #cccccc;
    line-height: 1.3;
}
.inv-amount { text-align: right; white-space: nowrap; }
.inv-euro { text-align: right; }
.totaal-row td {
    padding: 2.5mm 0;
    text-align: right;
    color: #555;
    line-height: 1.3;
    font-family: 'Fira Sans', sans-serif;
    font-size: 10pt;
}
.totaal-row td.amount { font-weight: 600; }

.btw-note {
    font-family: 'Fira Sans', sans-serif;
    font-size: 8pt;
    color: #9ca3af;
    margin-top: 4mm;
    font-style: italic;
}
.geldigheid-box {
    margin-top: 10mm;
    padding: 4mm 5mm;
    background: #f0f4ff;
    border-left: 3pt solid #4076f0;
    font-family: 'Fira Sans', sans-serif;
    font-size: 9pt;
    color: #374151;
}

/* ── WATERMARK ── */
.watermark {
    position: fixed;
    bottom: 7mm;
    left: 19.1mm;
    height: 7mm;
    width: auto;
    z-index: 9999;
}

/* ── ACHTERBLAD ── */
.achterblad {
    width: 210mm;
    height: 297mm;
    position: relative;
    overflow: hidden;
    page-break-before: always;
    background-color: #030810;
}
.achterblad img {
    position: absolute; top: 0; left: 0;
    width: 210mm; height: 297mm;
}
</style>
</head>
<body>

{{-- Watermark logo — position: fixed verschijnt op elke pagina --}}
<img class="watermark" src="{{ $watermark }}" alt="">

{{-- ════════════════════════════════════════════════════════
     VOORBLAD
     ════════════════════════════════════════════════════════ --}}
<div class="voorblad">
    <img class="voorblad-bg" src="{{ $voorblad_bg }}" alt="">
    <div class="voorblad-content">
        <div class="voorblad-label">Offerte</div>
        @php
            // Max 2 opeenvolgende woorden met 6+ letters op één regel
            $words   = explode(' ', $quote->titel);
            $lines   = [];
            $current = [];
            $streak  = 0;
            foreach ($words as $w) {
                if (mb_strlen($w) >= 6) {
                    $streak++;
                    if ($streak > 2) {
                        $lines[]  = implode(' ', $current);
                        $current  = [$w];
                        $streak   = 1;
                    } else {
                        $current[] = $w;
                    }
                } else {
                    $streak    = 0;
                    $current[] = $w;
                }
            }
            if (!empty($current)) $lines[] = implode(' ', $current);
            $titelHtml   = implode('<br>', array_filter($lines));
            $isMultiLine = str_contains($titelHtml, '<br>');
            $streepStyle = $isMultiLine
                ? 'vertical-align: middle;'
                : 'vertical-align: bottom; padding-bottom: 1.5mm;';
            $titelStyle  = $isMultiLine ? 'vertical-align: middle;' : 'vertical-align: bottom;';
        @endphp
        <table class="voorblad-titel-wrapper">
            <tr>
                <td class="voorblad-streep-cel" style="{{ $streepStyle }}">
                    <div class="voorblad-streep"></div>
                </td>
                <td class="voorblad-titel-cel" style="{{ $titelStyle }}">
                    <div class="voorblad-titel">{!! $titelHtml !!}</div>
                </td>
            </tr>
        </table>
        @php
            $contactpersoon = $quote->client->contactpersonen->first()?->naam
                ?? ($quote->client->contactpersoon ?: null);
        @endphp
        <div class="voorblad-meta">
            <table>
                <tr>
                    <td class="label">Offertedatum</td>
                    <td class="value">{{ $quote->created_at->format('d-m-Y') }}</td>
                </tr>
                @if($quote->geldig_tot)
                <tr>
                    <td class="label">Geldig tot</td>
                    <td class="value">{{ $quote->geldig_tot->format('d-m-Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Relatie</td>
                    <td class="value">{{ $quote->client->naam }}</td>
                </tr>
                @if($contactpersoon)
                <tr>
                    <td class="label">Contactpersoon</td>
                    <td class="value">{{ $contactpersoon }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     CONTENT TABEL — thead herhaalt de paginakop op elke pagina
     ════════════════════════════════════════════════════════ --}}
@php
    $blokRuimte  = $quote->pdf_blok_ruimte ?? 10;
    $invVolgorde = $quote->inv_volgorde ?? 9999;
    $hasInv      = $quote->investments->count() > 0;
    $sections    = $quote->sections->sortBy('volgorde')->values();

    $allBlocks = collect();
    foreach ($sections as $i => $s) {
        if ($i === $invVolgorde && $hasInv) {
            $allBlocks->push(['type' => 'investering']);
        }
        $allBlocks->push(['type' => 'section', 'section' => $s]);
    }
    if ($invVolgorde >= $sections->count() && $hasInv) {
        $allBlocks->push(['type' => 'investering']);
    }

    $isFirst       = true;
    $nextPageBreak = false;
@endphp

<table class="content-table">
    <thead>
        {{-- Paginakop --}}
        <tr class="header-row">
            <td>
                <div class="header-inner">
                    <div class="header-inner-left">buro<span>.</span>deBom</div>
                    <div class="header-inner-right">{{ $quote->offerte_nummer }}</div>
                </div>
            </td>
        </tr>
        {{-- Adempauze na de kop — herhaalt op elke pagina --}}
        <tr class="header-spacer"><td></td></tr>
    </thead>
    <tbody>

@foreach($allBlocks as $block)

@if($block['type'] === 'section')
@php $section = $block['section']; $blockType = $section->content['block_type'] ?? null; @endphp
@if($blockType === 'einde_pagina')
@php $nextPageBreak = true; @endphp
@else
@php
    $inhoud      = $section->content['html'] ?? '';
    $hasSubitems = str_contains($inhoud, 'class="subitem"');
    $showTitle   = !$isFirst;
    $trStyle     = $nextPageBreak ? 'page-break-before: always;' : '';
    $topPad      = $isFirst ? '0mm' : ($blokRuimte . 'mm');
    $nextPageBreak = false;
    $isFirst       = false;
@endphp
        <tr style="{{ $trStyle }}">
            <td style="padding-top:{{ $topPad }};padding-bottom:0;" class="content-blok">
                <div class="{{ $hasSubitems ? 'subitem-section' : '' }}">
                    @if($showTitle)<div class="section-title">{{ $section->titel }}</div>@endif
                    {!! $inhoud !!}
                </div>
            </td>
        </tr>
@endif

@elseif($block['type'] === 'investering')
@php
    $subtotaal  = $quote->investments->sum('bedrag');
    $btwTarief  = $quote->btw_tarief ?? '21%';
    $btwBedrag  = $btwTarief === '21%' ? $subtotaal * 0.21 : 0;
    $eindtotaal = $subtotaal + $btwBedrag;
    $fmt        = fn($v) => number_format($v, 2, ',', '.');
    $trStyle    = $nextPageBreak ? 'page-break-before: always;' : '';
    $topPad     = $isFirst ? '0mm' : ($blokRuimte . 'mm');
    $nextPageBreak = false;
    $isFirst       = false;
@endphp
        <tr style="{{ $trStyle }}">
            <td style="padding-top:{{ $topPad }};padding-bottom:0;" class="content-blok">
                <div class="section-title">Investering</div>
                @php $tdBase = 'padding:2.5mm 0;border-top:1px solid #cccccc;vertical-align:top;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;'; @endphp
                <table style="width:100%;border-collapse:collapse;font-size:10pt;font-family:Fira Sans,sans-serif;line-height:1.3;" cellpadding="0" cellspacing="0">
                    @foreach($quote->investments as $inv)
                    <tr>
                        <td style="{{ $tdBase }}">{{ $inv->omschrijving }}</td>
                        <td style="{{ $tdBase }}text-align:right;white-space:nowrap;">&euro;&nbsp;{{ $fmt($inv->bedrag) }}</td>
                    </tr>
                    @if($inv->toelichting ?? null)
                    <tr>
                        <td colspan="2" style="padding:0 0 2.5mm;border-top:none;font-size:9pt;color:#666;line-height:1.3;font-family:Fira Sans,sans-serif;">{{ $inv->toelichting }}</td>
                    </tr>
                    @endif
                    @endforeach
                    @if($quote->toon_btw)
                    <tr>
                        <td style="{{ $tdBase }}color:#555;">Totaal excl. btw</td>
                        <td style="{{ $tdBase }}text-align:right;white-space:nowrap;color:#555;">&euro;&nbsp;{{ $fmt($subtotaal) }}</td>
                    </tr>
                    @if($btwTarief === '21%')
                    <tr>
                        <td style="padding:2.5mm 0;border-top:none;vertical-align:top;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;color:#555;">BTW (21%)</td>
                        <td style="padding:2.5mm 0;border-top:none;vertical-align:top;text-align:right;white-space:nowrap;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;color:#555;">&euro;&nbsp;{{ $fmt($btwBedrag) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2.5mm 0;border-top:1px solid #cccccc;vertical-align:top;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;font-weight:600;">Totaal incl. btw</td>
                        <td style="padding:2.5mm 0;border-top:1px solid #cccccc;vertical-align:top;text-align:right;white-space:nowrap;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;font-weight:600;">&euro;&nbsp;{{ $fmt($eindtotaal) }}</td>
                    </tr>
                    @endif
                    @else
                    <tr>
                        <td style="padding:2.5mm 0;border-top:1px solid #cccccc;vertical-align:top;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;font-weight:600;">Totaal</td>
                        <td style="padding:2.5mm 0;border-top:1px solid #cccccc;vertical-align:top;text-align:right;white-space:nowrap;line-height:1.3;font-size:10pt;font-family:Fira Sans,sans-serif;font-weight:600;">&euro;&nbsp;{{ $fmt($subtotaal) }}</td>
                    </tr>
                    @endif
                </table>

                @if($quote->toon_btw)
                    @if($btwTarief === '0%')
                        <p class="btw-note">BTW-tarief 0% van toepassing.</p>
                    @elseif($btwTarief === 'vrijgesteld')
                        <p class="btw-note">Vrijgesteld van BTW op basis van artikel 11 Wet OB.</p>
                    @endif
                @endif

                @if($quote->geldig_tot)
                <div class="geldigheid-box">
                    Deze offerte is geldig tot en met <strong>{{ $quote->geldig_tot->format('d-m-Y') }}</strong>.
                    Neem contact op voor vragen of aanpassingen.
                </div>
                @endif
            </td>
        </tr>
@endif

@endforeach

        {{-- Ondermarge --}}
        <tr><td style="height:15mm;"></td></tr>
    </tbody>
</table>

{{-- ════════════════════════════════════════════════════════
     ACHTERBLAD
     ════════════════════════════════════════════════════════ --}}
<div class="achterblad">
    <img src="{{ $achterblad_bg }}" alt="">
</div>

</body>
</html>
