<?php

namespace App\Services;

class PromptService
{
    private const META_FILE = 'prompts/meta.json';

    // Sectors that have dedicated prompt files
    public const SECTOREN = [
        'bouw'             => 'Bouw',
        'installatietechniek' => 'Installatietechniek',
        'transport'        => 'Transport',
        'industrie'        => 'Industrie',
        'overig'           => 'Overig',
    ];

    public function getStijlgids(): string
    {
        return $this->readPrompt('stijlgids.md');
    }

    public function getSectorPrompt(string $sector): string
    {
        $slug = $this->sectorSlug($sector);
        return $this->readPrompt("sectoren/{$slug}.md");
    }

    public function getCurrentVersion(): string
    {
        return $this->readMeta()['versie'] ?? '1.0';
    }

    public function getMeta(): array
    {
        return $this->readMeta();
    }

    public function saveStijlgids(string $content): void
    {
        $this->writePrompt('stijlgids.md', $content);
        $this->incrementVersion();
    }

    public function saveSectorPrompt(string $sector, string $content): void
    {
        $slug = $this->sectorSlug($sector);
        $this->writePrompt("sectoren/{$slug}.md", $content);
        $this->incrementVersion();
    }

    // Returns the system prompt text (stijlgids)
    public function buildSystemPrompt(): string
    {
        return $this->getStijlgids();
    }

    // Returns sector context to inject in the user prompt
    public function buildSectorContext(string $sector): string
    {
        return $this->getSectorPrompt($sector);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function readPrompt(string $relativePath): string
    {
        // Storage overrides take precedence (admin edits)
        $storagePath = storage_path("app/prompts/{$relativePath}");
        if (file_exists($storagePath)) {
            return file_get_contents($storagePath);
        }

        // Fall back to git-tracked defaults
        $resourcePath = base_path("resources/prompts/{$relativePath}");
        if (file_exists($resourcePath)) {
            return file_get_contents($resourcePath);
        }

        return '';
    }

    private function writePrompt(string $relativePath, string $content): void
    {
        $path = storage_path("app/prompts/{$relativePath}");
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function readMeta(): array
    {
        $path = storage_path('app/' . self::META_FILE);

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?? [];
        }

        return ['versie' => '1.0', 'bijgewerkt_op' => now()->toISOString()];
    }

    private function incrementVersion(): void
    {
        $meta  = $this->readMeta();
        $parts = explode('.', $meta['versie'] ?? '1.0');
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        $meta['versie']       = "{$major}." . ($minor + 1);
        $meta['bijgewerkt_op'] = now()->toISOString();

        $path = storage_path('app/' . self::META_FILE);
        $dir  = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($meta, JSON_PRETTY_PRINT));
    }

    private function sectorSlug(string $sector): string
    {
        return match (strtolower(trim($sector))) {
            'bouw'                              => 'bouw',
            'transport', 'logistiek'            => 'transport',
            'industrie', 'maakindustrie'        => 'industrie',
            'installatie', 'installatietechniek' => 'installatietechniek',
            default                             => 'overig',
        };
    }
}
