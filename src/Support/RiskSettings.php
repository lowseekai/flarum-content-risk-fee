<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Support;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class RiskSettings
{
    public function __construct(protected SettingsRepositoryInterface $settings) {}

    public function enabled(): bool
    {
        return $this->bool('content-risk-fee.enabled', true);
    }

    public function detectLinks(): bool
    {
        return $this->bool('content-risk-fee.detect_links', true);
    }

    public function detectSensitiveWords(): bool
    {
        return $this->bool('content-risk-fee.detect_sensitive_words', true);
    }

    public function linkFee(): int
    {
        return max(0, $this->int('content-risk-fee.link_fee', 10));
    }

    public function sensitiveFee(): int
    {
        return max(0, $this->int('content-risk-fee.sensitive_fee', 5));
    }

    public function maxFee(): int
    {
        return max(0, $this->int('content-risk-fee.max_fee', 15));
    }

    public function duplicateEnabled(): bool
    {
        return $this->bool('content-risk-fee.duplicate_enabled', true);
    }

    public function duplicateWindowHours(): int
    {
        return max(1, min(720, $this->int('content-risk-fee.duplicate_window_hours', 24)));
    }

    public function duplicateBlockAfter(): int
    {
        return max(2, min(20, $this->int('content-risk-fee.duplicate_block_after', 2)));
    }

    public function sensitiveWords(): array
    {
        return $this->list('content-risk-fee.sensitive_words');
    }

    public function customRegexes(): array
    {
        return $this->list('content-risk-fee.custom_regex');
    }

    public function whitelistDomains(): array
    {
        return array_values(array_filter(array_map(function (string $value): string {
            $value = strtolower(trim($value));
            $value = preg_replace('~^(?:https?|ftp)://~i', '', $value) ?? $value;
            $value = preg_replace('~^www\.~i', '', $value) ?? $value;
            $value = preg_replace('~/.*$~', '', $value) ?? $value;
            return trim($value, " .\t\n\r\0\x0B");
        }, $this->list('content-risk-fee.whitelist_domains'))));
    }

    public function bypasses(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('lowseekai-content-risk-fee.bypass');
    }

    private function bool(string $key, bool $default): bool
    {
        $value = $this->settings->get($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function int(string $key, int $default): int
    {
        return (int) $this->settings->get($key, $default);
    }

    private function list(string $key): array
    {
        $value = (string) $this->settings->get($key, '');
        $items = preg_split('/[\r\n,]+/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $items), static fn (string $item): bool => $item !== ''));
    }
}
