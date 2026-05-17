<?php

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfService
{
    /**
     * Generate the full PDF (quote + optional bijlagen) and return binary string.
     */
    public function generate(Quote $quote): string
    {
        $quote->load(['client', 'sections' => fn ($q) => $q->orderBy('volgorde'), 'investments' => fn ($q) => $q->orderBy('volgorde')]);

        $mainPdf = $this->renderQuote($quote);

        $bijlagen = $this->collectBijlagen();

        if (empty($bijlagen)) {
            return $mainPdf;
        }

        return $this->mergePdfs($mainPdf, $bijlagen);
    }

    private function renderQuote(Quote $quote): string
    {
        $assets = $this->loadAssets();

        return Pdf::loadView('pdf.quote', array_merge(compact('quote'), $assets))
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function loadAssets(): array
    {
        $fonts = [
            'fira_regular'  => $this->fontUrl('FiraSans-Regular.otf'),
            'fira_italic'   => $this->fontUrl('FiraSans-Italic.ttf'),
            'fira_semibold' => $this->fontUrl('FiraSans-SemiBold.ttf'),
            'fira_bold'     => $this->fontUrl('FiraSans-Bold.otf'),
            'oxide_regular' => $this->fontUrl('OxideSolidPro.otf'),
            'oxide_bold'    => $this->fontUrl('OxideSolidPro-Bold.otf'),
        ];
        $voorblad_bg   = $this->b64(public_path('images/voorblad_bg.png'), 'image/png');
        $achterblad_bg = $this->b64(public_path('images/achterblad_bg.png'), 'image/png');
        $watermark     = $this->b64(public_path('images/b_.png'), 'image/png');

        return compact('fonts', 'voorblad_bg', 'achterblad_bg', 'watermark');
    }

    private function fontUrl(string $filename): string
    {
        $path = public_path('fonts/' . $filename);

        return 'file:///' . str_replace('\\', '/', $path);
    }

    private function b64(string $path, string $mime): string
    {
        if (!file_exists($path)) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /**
     * Collect bijlagen that exist in storage/app/pdf-assets/.
     * Returns array of absolute file paths.
     */
    private function collectBijlagen(): array
    {
        $candidates = ['achterblad.pdf', 'av.pdf', 'sla.pdf'];
        $paths = [];

        foreach ($candidates as $file) {
            $path = Storage::disk('local')->path("pdf-assets/{$file}");
            if (file_exists($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Merge main PDF binary with additional PDF files using FPDI.
     */
    private function mergePdfs(string $mainPdfBinary, array $bijlagenPaths): string
    {
        // Write main PDF to temp file
        $tempMain = tempnam(sys_get_temp_dir(), 'quote_') . '.pdf';
        file_put_contents($tempMain, $mainPdfBinary);

        try {
            $fpdi = new Fpdi();
            $fpdi->SetAutoPageBreak(false);

            // Import all pages from main PDF
            $this->importPdf($fpdi, $tempMain);

            // Import all pages from each bijlage
            foreach ($bijlagenPaths as $bijlagePath) {
                try {
                    $this->importPdf($fpdi, $bijlagePath);
                } catch (\Throwable) {
                    // Skip unreadable bijlage — do not break the whole PDF
                }
            }

            return $fpdi->Output('S');
        } finally {
            @unlink($tempMain);
        }
    }

    /**
     * Import all pages of a PDF file into an FPDI instance.
     */
    private function importPdf(Fpdi $fpdi, string $path): void
    {
        $pageCount = $fpdi->setSourceFile($path);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $fpdi->importPage($i);
            $size = $fpdi->getTemplateSize($tpl);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $fpdi->AddPage($orientation, [$size['width'], $size['height']]);
            $fpdi->useTemplate($tpl);
        }
    }

    /**
     * Save the generated PDF to storage and return the storage path.
     */
    public function save(Quote $quote): string
    {
        $pdf     = $this->generate($quote);
        $filename = "quotes/{$quote->offerte_nummer}_v{$quote->versie}.pdf";
        Storage::disk('local')->put($filename, $pdf);

        return $filename;
    }
}
