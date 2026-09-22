<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Listener;

use Flarum\Post\Event\Posted;
use Lowseekai\ContentRiskFee\Model\RiskEvent;
use Lowseekai\ContentRiskFee\Support\ContentRiskService;

final class RecordPostedRiskEvent
{
    public function __construct(protected ContentRiskService $service) {}

    public function handle(Posted $event): void
    {
        $post = $event->post;
        $userId = $event->actor?->id ?? $post->user_id;

        $riskEvent = RiskEvent::query()
            ->where('user_id', $userId)
            ->where('discussion_id', $post->discussion_id)
            ->whereNull('post_id')
            ->where('content_hash', $this->service->contentHash((string) $post->content))
            ->where('action', 'charged_and_published')
            ->latest('id')
            ->first();

        if ($riskEvent) {
            $riskEvent->post_id = $post->id;
            $riskEvent->save();
        }
    }
}
