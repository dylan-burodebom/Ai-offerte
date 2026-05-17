<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'Fira Sans';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts["fira_regular"] }}') format('opentype');
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
    src: url('{{ $fonts["fira_bold"] }}') format('opentype');
}
@font-face {
    font-family: 'OxideSolidPro';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts["oxide_regular"] }}') format('opentype');
}
@font-face {
    font-family: 'OxideSolidPro';
    font-weight: 700;
    font-style: normal;
    src: url('{{ $fonts["oxide_bold"] }}') format('opentype');
}

* { margin: 0; padding: 0; box-sizing: border-box; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; }
@page { size: A4; margin: 0; }

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
}
.voorblad-bg {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
}
.voorblad-content {
    position: absolute;
    left: 56mm; top: 116mm;
    max-width: 130mm;
}
.voorblad-label {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; font-size: 10pt;
    color: #e0e0e0; letter-spacing: 0.05em;
    margin-bottom: 5mm;
}
.voorblad-streep {
    width: 11mm; height: 2.5px;
    background-color: #4076f0;
    margin-bottom: 14mm;
}
.voorblad-titel {
    font-family: 'OxideSolidPro', sans-serif;
    font-weight: 700; font-size: 20pt;
    color: #ffffff; letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 14mm; line-height: 1.15;
    max-width: 100%;
    word-wrap: break-word;
}
.voorblad-meta {
    font-size: 9.5pt;
    max-width: 100%;
}
.voorblad-meta table { border-collapse: collapse; }
.voorblad-meta td { padding: 1.8mm 0; vertical-align: top; line-height: 1.3; }
.voorblad-meta td.label {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 600; min-width: 40mm; color: #ffffff;
}
.voorblad-meta td.value {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; color: #aabbcc;
}

/* ── CONTENT PAGINA'S ── */
.content-page {
    width: 210mm;
    font-family: 'Fira Sans', sans-serif;
    font-size: 10pt; color: #1a1a1a;
    padding: 0;
    margin: 0;
}
.content-header {
    margin-left: 19.1mm;
    margin-right: 19.1mm;
    padding-top: 10mm;
    padding-bottom: 3.5mm;
    margin-bottom: 8mm;
    border-bottom: 1px solid #e0e0e0;
    display: table;
    width: calc(100% - 38.2mm);
}
.content-header-left {
    display: table-cell;
    vertical-align: bottom;
    font-family: 'Fira Sans', sans-serif;
    font-weight: 700; font-size: 9pt;
    color: #0a0a0a; letter-spacing: 0.04em;
}
.content-header-left span { color: #4076f0; }
.content-header-right {
    display: table-cell;
    vertical-align: bottom;
    text-align: right;
    font-family: 'Fira Sans', sans-serif;
    font-weight: 400; font-size: 8pt;
    color: #999999; letter-spacing: 0.02em;
}
.content-inner {
    margin-left: 19.1mm;
    margin-right: 19.1mm;
    padding-bottom: 20mm;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
}
/* Voorkom slechte pagina-afbrekingen */
h2 { page-break-after: avoid; }
h3 { page-break-after: avoid; }
strong { page-break-after: avoid; }
p { page-break-inside: avoid; }
ul, ol { page-break-inside: avoid; }
li { page-break-inside: avoid; }
.subitem { page-break-inside: avoid; padding-top: 0; padding-bottom: 14mm; margin-bottom: 0; }
.subitem strong { display: block; margin-bottom: 2mm; }
.subitem-section h2 { padding-bottom: 14mm; }

/* Typography */
h2 {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 700; font-size: 22pt;
    color: #0a0a0a; margin-bottom: 0; padding-bottom: 5mm; line-height: 1.2;
    max-width: 100%;
    word-wrap: break-word;
}
h3 {
    font-family: 'Fira Sans', sans-serif;
    font-weight: 700; font-size: 10pt;
    color: #0a0a0a; margin-bottom: 1.5mm; margin-top: 5mm;
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
}
.inv-table tr td {
    padding-top: 3.5mm;
    border-top: 1px solid #cccccc;
    vertical-align: top;
}
.inv-table tr td.toelichting {
    padding-top: 0;
    padding-bottom: 3.5mm;
    color: #666;
    border-top: none;
    border-bottom: 0.5px solid #cccccc;
}
.inv-amount { text-align: right; white-space: nowrap; }
.inv-euro { text-align: right; }
.totaal-row td {
    padding-top: 4mm;
    text-align: right;
    color: #555;
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

{{-- Watermark logo op elke content-pagina --}}
<img class="watermark" src="{{ $watermark }}" alt="">

{{-- ════════════════════════════════════════════════════════
     VOORBLAD
     ════════════════════════════════════════════════════════ --}}
<div class="voorblad">
    <img class="voorblad-bg" src="{{ $voorblad_bg }}" alt="">
    <div class="voorblad-content">
        <div class="voorblad-label">Offerte &nbsp;·&nbsp; {{ $quote->offerte_nummer }}</div>
        <div class="voorblad-streep"></div>
        <div class="voorblad-titel">{{ $quote->titel }}</div>
        <div class="voorblad-meta">
            <table>
                <tr>
                    <td class="label">Offertedatum</td>
                    <td class="value">{{ $quote->created_at->locale('nl')->isoFormat('D MMMM YYYY') }}</td>
                </tr>
                @if($quote->geldig_tot)
                <tr>
                    <td class="label">Geldig tot</td>
                    <td class="value">{{ $quote->geldig_tot->locale('nl')->isoFormat('D MMMM YYYY') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Relatie</td>
                    <td class="value">{{ $quote->client->naam }}</td>
                </tr>
                @if($quote->client->contactpersoon)
                <tr>
                    <td class="label">Contactpersoon</td>
                    <td class="value">{{ $quote->client->contactpersoon }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     INHOUDSSECTIES
     ════════════════════════════════════════════════════════ --}}
@foreach($quote->sections as $section)
@php
    $inhoud = $section->content['html'] ?? '';
    $hasSubitems = str_contains($inhoud, 'class="subitem"');
@endphp
<div class="content-page" style="page-break-before: always;">
    <div class="content-header">
        <div class="content-header-left">buro<span>_</span>deBom</div>
        <div class="content-header-right">{{ $quote->offerte_nummer }}</div>
    </div>
    <div class="content-inner {{ $hasSubitems ? 'subitem-section' : '' }}">
        <h2>{{ $section->titel }}</h2>
        @if($hasSubitems)
            {!! $inhoud !!}
        @else
            <div style="page-break-inside: avoid;">{!! $inhoud !!}</div>
        @endif
    </div>
</div>
@endforeach

{{-- ════════════════════════════════════════════════════════
     INVESTERING
     ════════════════════════════════════════════════════════ --}}
@if($quote->investments->count() > 0)
@php
    $subtotaal  = $quote->investments->sum('bedrag');
    $btwTarief  = $quote->btw_tarief ?? '21%';
    $btwBedrag  = $btwTarief === '21%' ? $subtotaal * 0.21 : 0;
    $eindtotaal = $subtotaal + $btwBedrag;
    $fmt        = fn($v) => number_format($v, 2, ',', '.');
@endphp
<div class="content-page" style="page-break-before: always;">
    <div class="content-header">
        <div class="content-header-left">buro<span>_</span>deBom</div>
        <div class="content-header-right">{{ $quote->offerte_nummer }}</div>
    </div>
    <div class="content-inner">
        <h2>Investering</h2>
        <table class="inv-table">
            @foreach($quote->investments as $inv)
            <tr>
                <td>{{ $inv->omschrijving }}</td>
                <td class="inv-euro">&euro;</td>
                <td class="inv-amount">{{ $fmt($inv->bedrag) }}</td>
            </tr>
            @endforeach
            <tr class="totaal-row">
                <td>Totaal excl. btw</td>
                <td>&euro;</td>
                <td class="amount">{{ $fmt($subtotaal) }}</td>
            </tr>
            @if($btwTarief === '21%')
            <tr class="totaal-row">
                <td>BTW (21%)</td>
                <td>&euro;</td>
                <td class="amount">{{ $fmt($btwBedrag) }}</td>
            </tr>
            <tr class="totaal-row">
                <td><strong>Totaal incl. btw</strong></td>
                <td><strong>&euro;</strong></td>
                <td class="amount"><strong>{{ $fmt($eindtotaal) }}</strong></td>
            </tr>
            @endif
        </table>

        @if($btwTarief === '0%')
            <p class="btw-note">BTW-tarief 0% van toepassing.</p>
        @elseif($btwTarief === 'vrijgesteld')
            <p class="btw-note">Vrijgesteld van BTW op basis van artikel 11 Wet OB.</p>
        @endif

        @if($quote->geldig_tot)
        <div class="geldigheid-box">
            Deze offerte is geldig tot en met <strong>{{ $quote->geldig_tot->locale('nl')->isoFormat('D MMMM YYYY') }}</strong>.
            Neem contact op voor vragen of aanpassingen.
        </div>
        @endif
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════
     ACHTERBLAD
     ════════════════════════════════════════════════════════ --}}
<div class="achterblad">
    <img src="{{ $achterblad_bg }}" alt="">
</div>

</body>
</html>
