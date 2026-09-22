<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Support;

use Carbon\Carbon;
use Flarum\Foundation\ValidationException;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Lowseekai\ContentRiskFee\Analyzer\RiskAnalyzer;
use Lowseekai\ContentRiskFee\Model\RiskEvent;
use Lowseekai\ContentRiskFee\Model\RiskFingerprint;
use Lowseekai\ContentRiskFee\Model\RiskToken;
use Ramon\PointSystem\Model\UserPoints;
use Ramon\PointSystem\Repository\PointsRepository;

class ContentRiskService
{
    /**
     * Flarum creates a discussion's first post through a nested PostResource
     * request. Keep the outer discussion confirmation available for that post.
     *
     * @var array<int, array{token: string, title: string, content: string}>
     */
    private array $pendingDiscussionRisk = [];

    public function __construct(
        protected RiskAnalyzer $analyzer,
        protected RiskSettings $settings,
        protected PointsRepository $points,
        protected SettingsRepositoryInterface $forumSettings,
    ) {
    }

    public function inspect(User $actor, string $title, string $content): array
    {
        if (! $this->settings->enabled() || $this->settings->bypasses($actor)) {
            return $this->allowedResponse($actor);
        }

        $report = $this->analyzer->analyze($title, $content);
        $duplicate = $this->duplicateCount($actor, $title, $content);
        $balance = $this->balance($actor);

        if ($duplicate >= $this->settings->duplicateBlockAfter()) {
            return [
                'allowed' => false,
                'blocked' => true,
                'requiresPayment' => false,
                'reason' => 'duplicate',
                'fee' => 0,
                'balance' => $balance,
                'remainingBalance' => $balance,
                'riskTypes' => [],
                'currency' => $this->currencyName(),
            ];
        }

        if (! $report['hasRisk']) {
            return $this->allowedResponse($actor, $balance);
        }

        $plainToken = bin2hex(random_bytes(32));

        RiskToken::create([
            'user_id' => $actor->id,
            'token_hash' => hash('sha256', $plainToken),
            'content_hash' => $this->contentHash($content),
            'title_hash' => $this->contentHash($title),
            'risk_types' => $report['types'],
            'fee' => $report['fee'],
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        return [
            'allowed' => false,
            'blocked' => false,
            'requiresPayment' => true,
            'reason' => null,
            'fee' => $report['fee'],
            'balance' => $balance,
            'remainingBalance' => max(0, $balance - $report['fee']),
            'riskTypes' => $report['types'],
            'token' => $plainToken,
            'currency' => $this->currencyName(),
        ];
    }

    /**
     * Store the outer discussion confirmation until Flarum creates its first post.
     */
    public function prepareDiscussion(User $actor, array $data): void
    {
        if (! $this->settings->enabled() || $this->settings->bypasses($actor)) {
            unset($this->pendingDiscussionRisk[$actor->id]);

            return;
        }

        $attributes = (array) Arr::get($data, 'attributes', []);

        $this->pendingDiscussionRisk[$actor->id] = [
            'token' => trim((string) ($attributes['contentRiskToken'] ?? '')),
            'title' => (string) ($attributes['title'] ?? ''),
            'content' => (string) ($attributes['content'] ?? ''),
        ];
    }

    public function guardPost(User $actor, CommentPost $post, array $data): void
    {
        if (! $this->settings->enabled() || $this->settings->bypasses($actor)) {
            unset($this->pendingDiscussionRisk[$actor->id]);

            return;
        }

        $attributes = (array) Arr::get($data, 'attributes', []);
        $content = (string) ($attributes['content'] ?? $post->getRawOriginal('content', ''));
        $title = (string) ($attributes['contentRiskTitle'] ?? $post->discussion?->title ?? '');
        $plainToken = trim((string) ($attributes['contentRiskToken'] ?? ''));

        $pending = $this->pendingDiscussionRisk[$actor->id] ?? null;
        unset($this->pendingDiscussionRisk[$actor->id]);

        if (
            $plainToken === ''
            && $pending
            && $this->contentHash($pending['content']) === $this->contentHash($content)
        ) {
            $plainToken = $pending['token'];
            $title = $title !== '' ? $title : $pending['title'];
        }

        $report = $this->analyzer->analyze($title, $content);
        $contentHash = $this->contentHash($content);
        $duplicate = $this->duplicateCount($actor, $title, $content);

        if ($this->settings->duplicateEnabled() && $duplicate >= $this->settings->duplicateBlockAfter()) {
            $balance = $this->balance($actor);

            RiskEvent::create([
                'user_id' => $actor->id,
                'discussion_id' => $post->discussion_id,
                'content_hash' => $contentHash,
                'risk_types' => ['duplicate'],
                'domains' => [],
                'fee' => 0,
                'balance_before' => $balance,
                'balance_after' => $balance,
                'blocked' => true,
                'action' => 'blocked_duplicate',
            ]);

            throw new ValidationException([
                'content' => '检测到你在短时间内重复发布相同内容，请修改内容后再发布。',
            ]);
        }

        if ($report['hasRisk']) {
            $token = $this->findValidToken($actor, $plainToken, $title, $content, $report);

            if (! $token) {
                throw new ValidationException([
                    'content' => '请先完成发布前确认，确认内容后再提交。',
                ]);
            }

            $before = $this->balance($actor);

            try {
                $this->points->deduct(
                    $actor,
                    (int) $token->fee,
                    'content-risk.fee',
                    'content-risk',
                    null,
                );
            } catch (\DomainException) {
                throw new ValidationException([
                    'content' => sprintf('积分不足，本次发布需要扣除 %d 积分。', $token->fee),
                ]);
            }

            $token->used_at = Carbon::now();
            $token->save();

            RiskEvent::create([
                'user_id' => $actor->id,
                'discussion_id' => $post->discussion_id,
                'content_hash' => $contentHash,
                'risk_types' => $report['types'],
                'domains' => $report['domains'],
                'fee' => (int) $token->fee,
                'balance_before' => $before,
                'balance_after' => max(0, $before - (int) $token->fee),
                'blocked' => false,
                'action' => 'charged_and_published',
            ]);
        }

        if ($this->settings->duplicateEnabled()) {
            $this->rememberFingerprint($actor, $title, $content);
        }
    }

    public function currencyName(): string
    {
        return (string) $this->forumSettings->get('point-system.currency_name', '积分');
    }

    public function contentHash(string $value): string
    {
        return hash('sha256', $this->analyzer->normalize($value));
    }

    private function findValidToken(
        User $actor,
        string $plainToken,
        string $title,
        string $content,
        array $report,
    ): ?RiskToken {
        if ($plainToken === '') {
            return null;
        }

        $token = RiskToken::query()
            ->where('user_id', $actor->id)
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->lockForUpdate()
            ->first();

        if (! $token) {
            return null;
        }

        $tokenTypes = array_values(array_unique(array_map('strval', $token->risk_types ?? [])));
        $reportTypes = array_values(array_unique(array_map('strval', $report['types'] ?? [])));
        sort($tokenTypes);
        sort($reportTypes);

        if (
            ! hash_equals((string) $token->content_hash, $this->contentHash($content))
            || ! hash_equals((string) $token->title_hash, $this->contentHash($title))
            || (int) $token->fee !== (int) ($report['fee'] ?? 0)
            || $tokenTypes !== $reportTypes
        ) {
            return null;
        }

        return $token;
    }

    private function duplicateCount(User $actor, string $title, string $content): int
    {
        if (! $this->settings->duplicateEnabled()) {
            return 0;
        }

        return (int) (RiskFingerprint::query()
            ->where('user_id', $actor->id)
            ->where('content_hash', $this->contentHash($content))
            ->where('title_hash', $this->contentHash($title))
            ->where('last_seen_at', '>=', Carbon::now()->subHours($this->settings->duplicateWindowHours()))
            ->value('occurrence_count') ?? 0);
    }

    private function rememberFingerprint(User $actor, string $title, string $content): void
    {
        $fingerprint = RiskFingerprint::query()
            ->where('user_id', $actor->id)
            ->where('content_hash', $this->contentHash($content))
            ->where('title_hash', $this->contentHash($title))
            ->first();

        if ($fingerprint) {
            $fingerprint->increment('occurrence_count');
            $fingerprint->last_seen_at = Carbon::now();
            $fingerprint->save();

            return;
        }

        RiskFingerprint::create([
            'user_id' => $actor->id,
            'content_hash' => $this->contentHash($content),
            'title_hash' => $this->contentHash($title),
            'occurrence_count' => 1,
            'last_seen_at' => Carbon::now(),
        ]);
    }

    private function balance(User $actor): int
    {
        return (int) (UserPoints::query()->where('user_id', $actor->id)->value('balance') ?? 0);
    }

    private function allowedResponse(User $actor, ?int $balance = null): array
    {
        $balance ??= $this->balance($actor);

        return [
            'allowed' => true,
            'blocked' => false,
            'requiresPayment' => false,
            'reason' => null,
            'fee' => 0,
            'balance' => $balance,
            'remainingBalance' => $balance,
            'riskTypes' => [],
            'currency' => $this->currencyName(),
        ];
    }
}
