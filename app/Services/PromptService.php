<?php

namespace App\Services;

class PromptService
{
    private const META_FILE     = 'prompts/meta.json';
    private const SECTOREN_FILE = 'prompts/sectoren.json';

    private const SECTOREN_DEFAULT = [
        'bouw'                => 'Bouw',
        'installatietechniek' => 'Installatietechniek',
        'transport'           => 'Transport',
        'industrie'           => 'Industrie',
        'overig'              => 'Overig',
    ];

    // Keep for backward compatibility
    public const SECTOREN = self::SECTOREN_DEFAULT;

    public function getSectorenLijst(): array
    {
        $path = storage_path('app/' . self::SECTOREN_FILE);

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?? self::SECTOREN_DEFAULT;
        }

        return self::SECTOREN_DEFAULT;
    }

    public function addSector(string $slug, string $label): void
    {
        $sectoren         = $this->getSectorenLijst();
        $sectoren[$slug]  = $label;

        $path = storage_path('app/' . self::SECTOREN_FILE);
        $dir  = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($sectoren, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Create an empty prompt file so the editor has something to open
        $storageMd   = storage_path("app/prompts/sectoren/{$slug}.md");
        $resourcesMd = base_path("resources/prompts/sectoren/{$slug}.md");
        if (! file_exists($storageMd) && ! file_exists($resourcesMd)) {
            $this->writePrompt("sectoren/{$slug}.md", "# Sectorcontext: {$label}\n\n");
        }

        $this->incrementVersion();
    }

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
        $input    = strtolower(trim($sector));
        $sectoren = $this->getSectorenLijst();

        if (array_key_exists($input, $sectoren)) {
            return $input;
        }

        foreach ($sectoren as $slug => $label) {
            if (strtolower($label) === $input) {
                return $slug;
            }
        }

        return array_key_exists('overig', $sectoren) ? 'overig' : array_key_first($sectoren);
    }
}
