<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style>
    /* ── Reset ─────────────────────────────────────────────────────── */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: Helvetica, Arial, sans-serif;
      font-size: 10pt;
      color: #1a1a2e;
      line-height: 1.55;
    }

    /* ── Paginering ─────────────────────────────────────────────────── */
    .page-break { page-break-before: always; }

    /* ── Running footer op alle pagina's behalve voorblad ───────────── */
    @page {
      margin: 22mm 20mm 20mm 20mm;
    }
    @page :first {
      margin: 0;
    }

    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 12mm;
      border-top: 1pt solid #e5e7eb;
      display: table;
      width: 100%;
      font-size: 8pt;
      color: #9ca3af;
    }
    .footer-left  { display: table-cell; vertical-align: middle; padding: 0 20mm; }
    .footer-right { display: table-cell; vertical-align: middle; text-align: right; padding: 0 20mm; }

    /* ── Voorblad ───────────────────────────────────────────────────── */
    .cover {
      width: 210mm;
      height: 297mm;
      position: relative;
      overflow: hidden;
    }
    .cover-top {
      background: #1a1a2e;
      width: 100%;
      height: 170mm;
      position: relative;
      padding: 18mm 18mm 0 18mm;
    }
    .cover-logo {
      font-size: 56pt;
      font-weight: bold;
      color: #ffffff;
      letter-spacing: -2pt;
      line-height: 1;
      margin-bottom: 4mm;
    }
    .cover-logo-sub {
      font-size: 9pt;
      color: #6366f1;
      letter-spacing: 3pt;
      text-transform: uppercase;
      margin-bottom: 20mm;
    }
    .cover-offerte-label {
      font-size: 8pt;
      color: #6366f1;
      letter-spacing: 2pt;
      text-transform: uppercase;
      margin-bottom: 3mm;
    }
    .cover-offerte-nummer {
      font-size: 22pt;
      font-weight: bold;
      color: #ffffff;
      font-family: 'Courier New', monospace;
    }
    .cover-bottom {
      background: #ffffff;
      width: 100%;
      padding: 14mm 18mm;
    }
    .cover-client-label {
      font-size: 8pt;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 1.5pt;
      margin-bottom: 2mm;
    }
    .cover-client-naam {
      font-size: 20pt;
      font-weight: bold;
      color: #1a1a2e;
      margin-bottom: 2mm;
    }
    .cover-titel {
      font-size: 12pt;
      color: #4f46e5;
      margin-bottom: 10mm;
    }
    .cover-meta-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12mm;
    }
    .cover-meta-table td {
      padding: 3mm 0;
      border-bottom: 0.5pt solid #f3f4f6;
      font-size: 9pt;
    }
    .cover-meta-label {
      color: #9ca3af;
      width: 40mm;
    }
    .cover-meta-value {
      color: #1a1a2e;
      font-weight: bold;
    }
    .cover-agency {
      font-size: 8pt;
      color: #9ca3af;
      border-top: 0.5pt solid #f3f4f6;
      padding-top: 4mm;
    }

    /* ── Paginaheader ───────────────────────────────────────────────── */
    .page-header {
      display: table;
      width: 100%;
      margin-bottom: 8mm;
      border-bottom: 2pt solid #1a1a2e;
      padding-bottom: 3mm;
    }
    .page-header-left  { display: table-cell; vertical-align: bottom; }
    .page-header-right { display: table-cell; vertical-align: bottom; text-align: right; }
    .page-header-logo  { font-size: 14pt; font-weight: bold; color: #1a1a2e; }
    .page-header-nummer { font-size: 8pt; color: #9ca3af; font-family: 'Courier New', monospace; }

    /* ── Sectie ─────────────────────────────────────────────────────── */
    .section-titel {
      font-size: 14pt;
      font-weight: bold;
      color: #1a1a2e;
      margin-bottom: 5mm;
      padding-bottom: 2mm;
      border-bottom: 1pt solid #e5e7eb;
    }
    .section-content { font-size: 10pt; line-height: 1.6; color: #374151; }
    .section-content p  { margin-bottom: 3mm; }
    .section-content h2 { font-size: 12pt; font-weight: bold; margin: 5mm 0 2mm; color: #1a1a2e; }
    .section-content h3 { font-size: 11pt; font-weight: bold; margin: 4mm 0 2mm; color: #1a1a2e; }
    .section-content ul, .section-content ol { padding-left: 8mm; margin: 3mm 0; }
    .section-content li { margin-bottom: 1.5mm; }
    .section-content strong { font-weight: bold; }
    .section-content em { font-style: italic; }
    .section-content a  { color: #4f46e5; }

    /* ── Investeringstabel ──────────────────────────────────────────── */
    .inv-titel {
      font-size: 14pt;
      font-weight: bold;
      color: #1a1a2e;
      margin-bottom: 6mm;
      padding-bottom: 2mm;
      border-bottom: 1pt solid #e5e7eb;
    }
    .inv-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6mm;
      font-size: 10pt;
    }
    .inv-table thead tr {
      background: #1a1a2e;
      color: #ffffff;
    }
    .inv-table thead th {
      padding: 3mm 4mm;
      text-align: left;
      font-size: 9pt;
      letter-spacing: 0.5pt;
    }
    .inv-table thead th.right { text-align: right; }
    .inv-table tbody tr { border-bottom: 0.5pt solid #f3f4f6; }
    .inv-table tbody tr:nth-child(even) { background: #f9fafb; }
    .inv-table tbody td { padding: 3mm 4mm; color: #374151; }
    .inv-table tbody td.right { text-align: right; font-family: 'Courier New', monospace; }

    .totaal-table {
      width: 100%;
      border-collapse: collapse;
      margin-left: auto;
      max-width: 90mm;
    }
    .totaal-table td { padding: 2.5mm 4mm; font-size: 10pt; }
    .totaal-table .label { color: #6b7280; }
    .totaal-table .amount { text-align: right; font-family: 'Courier New', monospace; color: #374151; }
    .totaal-table .totaal-row { border-top: 1.5pt solid #1a1a2e; }
    .totaal-table .totaal-row td { padding-top: 3mm; font-size: 12pt; font-weight: bold; color: #1a1a2e; }
    .totaal-table .totaal-row .amount { color: #4f46e5; }

    .btw-note {
      font-size: 8pt;
      color: #9ca3af;
      margin-top: 4mm;
      font-style: italic;
    }
    .geldigheid-box {
      margin-top: 10mm;
      padding: 4mm 5mm;
      background: #f0f0ff;
      border-left: 3pt solid #4f46e5;
      font-size: 9pt;
      color: #374151;
    }
  </style>
</head>
<body>

{{-- ── Footer (alle pagina's behalve voorblad) ──────────────────── --}}
<div class="footer">
  <div class="footer-left">{{ $quote->offerte_nummer }} — {{ $quote->client->naam }}</div>
  <div class="footer-right">buro_deBom</div>
</div>

{{-- ── Voorblad ─────────────────────────────────────────────────── --}}
<div class="cover">
  <div class="cover-top">
    <div class="cover-logo">B</div>
    <div class="cover-logo-sub">buro_deBom</div>
    <div class="cover-offerte-label">Offerte</div>
    <div class="cover-offerte-nummer">{{ $quote->offerte_nummer }}</div>
  </div>
  <div class="cover-bottom">
    <div class="cover-client-label">Opgesteld voor</div>
    <div class="cover-client-naam">{{ $quote->client->naam }}</div>
    <div class="cover-titel">{{ $quote->titel }}</div>

    <table class="cover-meta-table">
      @if($quote->client->contactpersoon)
      <tr>
        <td class="cover-meta-label">T.a.v.</td>
        <td class="cover-meta-value">{{ $quote->client->contactpersoon }}</td>
      </tr>
      @endif
      <tr>
        <td class="cover-meta-label">Datum</td>
        <td class="cover-meta-value">{{ $quote->created_at->locale('nl')->isoFormat('D MMMM YYYY') }}</td>
      </tr>
      @if($quote->geldig_tot)
      <tr>
        <td class="cover-meta-label">Geldig tot</td>
        <td class="cover-meta-value">{{ $quote->geldig_tot->locale('nl')->isoFormat('D MMMM YYYY') }}</td>
      </tr>
      @endif
      <tr>
        <td class="cover-meta-label">Versie</td>
        <td class="cover-meta-value">{{ $quote->versie }}</td>
      </tr>
    </table>

    <div class="cover-agency">
      buro_deBom &nbsp;·&nbsp; dylan.burodebom@gmail.com
    </div>
  </div>
</div>

{{-- ── Inhoudsecties ────────────────────────────────────────────── --}}
@foreach($quote->sections as $index => $section)
<div class="page-break">
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-header-logo">buro_deBom</div>
    </div>
    <div class="page-header-right">
      <div class="page-header-nummer">{{ $quote->offerte_nummer }}</div>
    </div>
  </div>

  <div class="section-titel">{{ $section->titel }}</div>
  <div class="section-content">
    {!! $section->content['html'] ?? '' !!}
  </div>
</div>
@endforeach

{{-- ── Investeringstabel ────────────────────────────────────────── --}}
<div class="page-break">
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-header-logo">buro_deBom</div>
    </div>
    <div class="page-header-right">
      <div class="page-header-nummer">{{ $quote->offerte_nummer }}</div>
    </div>
  </div>

  <div class="inv-titel">Investering</div>

  <table class="inv-table">
    <thead>
      <tr>
        <th>Omschrijving</th>
        <th class="right" style="width:40mm;">Bedrag</th>
      </tr>
    </thead>
    <tbody>
      @foreach($quote->investments as $inv)
      <tr>
        <td>{{ $inv->omschrijving }}</td>
        <td class="right">{{ number_format($inv->bedrag, 2, ',', '.') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @php
    $subtotaal  = $quote->investments->sum('bedrag');
    $btwTarief  = $quote->btw_tarief ?? '21%';
    $btwBedrag  = $btwTarief === '21%' ? $subtotaal * 0.21 : 0;
    $eindtotaal = $subtotaal + $btwBedrag;
    $fmt = fn($v) => '€ ' . number_format($v, 2, ',', '.');
  @endphp

  <table class="totaal-table">
    <tr>
      <td class="label">Subtotaal</td>
      <td class="amount">{{ $fmt($subtotaal) }}</td>
    </tr>
    <tr>
      <td class="label">BTW ({{ $btwTarief }})</td>
      <td class="amount">{{ $btwTarief === '21%' ? $fmt($btwBedrag) : '—' }}</td>
    </tr>
    <tr class="totaal-row">
      <td class="label">Totaal</td>
      <td class="amount">{{ $fmt($eindtotaal) }}</td>
    </tr>
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

</body>
</html>
