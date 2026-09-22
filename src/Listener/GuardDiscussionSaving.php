<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Listener;

use Flarum\Discussion\Event\Saving;
use Lowseekai\ContentRiskFee\Support\ContentRiskService;

final class GuardDiscussionSaving
{
    public function __construct(protected ContentRiskService $service) {}

    public function handle(Saving $event): void
    {
        if ($event->discussion->exists || ! $event->actor->exists) {
            return;
        }

        $this->service->prepareDiscussion($event->actor, $event->data);
    }
}
