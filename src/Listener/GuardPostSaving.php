<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Listener;

use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;
use Lowseekai\ContentRiskFee\Support\ContentRiskService;

class GuardPostSaving
{
    public function __construct(protected ContentRiskService $service) {}

    public function handle(Saving $event): void
    {
        if (! $event->post instanceof CommentPost || $event->post->exists || ! $event->actor->exists) {
            return;
        }

        $this->service->guardPost($event->actor, $event->post, $event->data);
    }
}
