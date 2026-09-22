<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Analyzer;

use Lowseekai\ContentRiskFee\Support\RiskSettings;

class RiskAnalyzer
{
    public function __construct(protected RiskSettings $settings) {}

    public function analyze(string $title, string $content): array
    {
        $text = $title."\n".$content;
        $domains = $this->extractDomains($text);
        $externalDomains = array_values(array_filter(
            $domains,
            fn (string $domain): bool => ! $this->isWhitelisted($domain)
        ));

        $sensitive = [];
        if ($this->settings->detectSensitiveWords()) {
            $normalized = $this->normalize($text);

            foreach ($this->settings->sensitiveWords() as $word) {
                if ($word !== '' && mb_stripos($normalized, $this->normalize($word)) !== false) {
                    $sensitive[] = 'word';
                    break;
                }
            }

            foreach ($this->settings->customRegexes() as $pattern) {
                $regex = $this->normalizeRegex($pattern);
                if ($regex !== null && @preg_match($regex, $text) === 1) {
                    $sensitive[] = 'regex';
                    break;
                }
            }
        }

        $types = [];
        if ($this->settings->detectLinks() && $externalDomains) {
            $types[] = 'external_link';
        }
        if ($sensitive) {
            $types[] = 'sensitive_content';
        }

        $fee = 0;
        if (in_array('external_link', $types, true)) {
            $fee += $this->settings->linkFee();
        }
        if (in_array('sensitive_content', $types, true)) {
            $fee += $this->settings->sensitiveFee();
        }
        if ($this->settings->maxFee() > 0) {
            $fee = min($fee, $this->settings->maxFee());
        }

        return [
            'hasRisk' => $types !== [] && $fee > 0,
            'types' => $types,
            'domains' => $externalDomains,
            'fee' => $fee,
        ];
    }

    public function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return trim($value);
    }

    private function extractDomains(string $text): array
    {
        preg_match_all(
            '~(?<![@\w])(?:(?:https?|ftp)://|www\.)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+~iu',
            $text,
            $matches
        );

        $domains = [];
        foreach ($matches[0] ?? [] as $match) {
            $candidate = strtolower(trim($match, " \t\n\r\0\x0B.,!?;:)]}>\"'"));
            $candidate = preg_replace('~^(?:https?|ftp)://~i', '', $candidate) ?? $candidate;
            $candidate = preg_replace('~^www\.~i', '', $candidate) ?? $candidate;
            $candidate = explode('/', $candidate, 2)[0];

            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                $domains[$candidate] = true;
            }
        }

        return array_keys($domains);
    }

    private function isWhitelisted(string $domain): bool
    {
        foreach ($this->settings->whitelistDomains() as $allowed) {
            if ($domain === $allowed || str_ends_with($domain, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRegex(string $pattern): ?string
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return null;
        }

        if (@preg_match($pattern, '') !== false) {
            return $pattern;
        }

        $wrapped = '~'.$pattern.'~iu';
        return @preg_match($wrapped, '') !== false ? $wrapped : null;
    }
}
