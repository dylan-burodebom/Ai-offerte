<?php

namespace App\Services;

use App\Models\Quote;
use RuntimeException;

class OpenAiQuoteService
{
    private string $apiKey;
    private string $model;
    private PromptService $prompts;

    public function __construct(PromptService $prompts)
    {
        $this->apiKey  = config('services.openai.key');
        $this->model   = config('services.openai.model', 'gpt-4o-mini');
        $this->prompts = $prompts;
    }

    public function buildMessages(Quote $quote): array
    {
        return [
            ['role' => 'system', 'content' => $this->prompts->buildSystemPrompt()],
            ['role' => 'user',   'content' => $this->userPrompt($quote)],
        ];
    }

    private function userPrompt(Quote $quote): string
    {
        $sector   = $quote->client?->sector ?? 'Overig';
        $diensten = implode(', ', $quote->diensten ?? []);
        $context  = $this->prompts->buildSectorContext($sector);

        if ($quote->fireflies_samenvatting) {
            $hoofdContext = "Gespreksnotitie (Fireflies samenvatting):\n{$quote->fireflies_samenvatting}";
            $extra = $quote->projectbeschrijving
                ? "\nAanvullende notities:\n{$quote->projectbeschrijving}"
                : '';
        } else {
            $hoofdContext = "Projectbeschrijving:\n{$quote->projectbeschrijving}";
            $extra = '';
        }

        return <<<PROMPT
Schrijf een offerte voor dit project:

Klant: {$quote->client?->naam}
Sector: {$sector}
Offertetitel: {$quote->titel}
Diensten: {$diensten}

{$hoofdContext}{$extra}

{$context}
PROMPT;
    }

    /**
     * Stream the OpenAI response, calling $onToken for each text chunk
     * and $onUsage with the total token count when complete.
     */
    public function stream(array $messages, callable $onToken, callable $onUsage): void
    {
        $payload = json_encode([
            'model'          => $this->model,
            'messages'       => $messages,
            'stream'         => true,
            'stream_options' => ['include_usage' => true],
            'temperature'    => 0.7,
            'max_tokens'     => 2000,
        ]);

        $buffer    = '';
        $httpCode  = 0;

        $curl = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_POST          => true,
            CURLOPT_POSTFIELDS    => $payload,
            CURLOPT_HTTPHEADER    => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: text/event-stream',
            ],
            CURLOPT_TIMEOUT       => 120,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer, $onToken, $onUsage) {
                $buffer .= $data;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line   = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if (!str_starts_with($line, 'data: ')) continue;

                    $json = substr($line, 6);
                    if ($json === '[DONE]') continue;

                    $chunk = json_decode($json, true);
                    if (!$chunk) continue;

                    $content = $chunk['choices'][0]['delta']['content'] ?? null;
                    if ($content !== null) {
                        $onToken($content);
                    }

                    $usage = $chunk['usage']['total_tokens'] ?? null;
                    if ($usage !== null) {
                        $onUsage((int) $usage);
                    }
                }

                return strlen($data);
            },
        ]);

        curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curlError) {
            throw new RuntimeException("curl_error: {$curlError}");
        }

        if ($httpCode === 429) {
            throw new RuntimeException('rate_limit');
        }

        if ($httpCode >= 500) {
            throw new RuntimeException('api_down');
        }

        if ($httpCode !== 200) {
            throw new RuntimeException("http_{$httpCode}");
        }
    }

    public function textToHtml(string $text): string
    {
        $lines   = explode("\n", trim($text));
        $html    = '';
        $inUl    = false;
        $inOl    = false;
        $olIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($inUl) { $html .= '</ul>'; $inUl = false; }
                if ($inOl) { $html .= '</ol>'; $inOl = false; $olIndex = 0; }
                continue;
            }
            if (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
                if ($inOl) { $html .= '</ol>'; $inOl = false; $olIndex = 0; }
                if (!$inUl) { $html .= '<ul>'; $inUl = true; }
                $html .= '<li>' . htmlspecialchars($m[1]) . '</li>';
            } elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
                if ($inUl) { $html .= '</ul>'; $inUl = false; }
                if (!$inOl) { $html .= '<ol>'; $inOl = true; }
                $html .= '<li>' . htmlspecialchars($m[1]) . '</li>';
            } else {
                if ($inUl) { $html .= '</ul>'; $inUl = false; }
                if ($inOl) { $html .= '</ol>'; $inOl = false; }
                $html .= '<p>' . htmlspecialchars($line) . '</p>';
            }
        }
        if ($inUl) $html .= '</ul>';
        if ($inOl) $html .= '</ol>';

        return $html;
    }

    /**
     * Parse generated text into sections based on ## headers.
     *
     * @return array<int, array{titel: string, tekst: string, volgorde: int}>
     */
    public function parseSections(string $text): array
    {
        $order    = ['Introductie' => 1, 'Aanpak' => 2, 'Scope' => 3, 'Planning' => 4, 'Voorwaarden' => 5];
        $sections = [];

        preg_match_all('/##\s+(.+?)\n([\s\S]*?)(?=\n##\s|\z)/', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $i => $match) {
            $titel = trim($match[1]);
            $tekst = trim($match[2]);

            $sections[] = [
                'titel'    => $titel,
                'tekst'    => $tekst,
                'volgorde' => $order[$titel] ?? ($i + 1),
            ];
        }

        return $sections;
    }

    public function humanizeError(string $code): string
    {
        return match ($code) {
            'rate_limit' => 'OpenAI limiet bereikt. Wacht een minuut en probeer het opnieuw.',
            'api_down'   => 'OpenAI is tijdelijk niet beschikbaar. Probeer het over een moment opnieuw.',
            default      => 'De generatie is mislukt. Controleer de verbinding en probeer het opnieuw.',
        };
    }
}
