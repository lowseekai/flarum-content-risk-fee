<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee;

use Flarum\Extend;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\PostResource;
use Illuminate\Database\ConnectionInterface;
use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Post\Event\Posted as PostPosted;
use Flarum\Post\Event\Saving as PostSaving;
use Lowseekai\ContentRiskFee\Api\DiscussionFields;
use Lowseekai\ContentRiskFee\Api\PostFields;
use Lowseekai\ContentRiskFee\Api\Controller\InspectContentController;
use Lowseekai\ContentRiskFee\Listener\GuardDiscussionSaving;
use Lowseekai\ContentRiskFee\Listener\GuardPostSaving;
use Lowseekai\ContentRiskFee\Listener\RecordPostedRiskEvent;

return [
    (new Extend\ServiceProvider())
        ->register(ContentRiskFeeServiceProvider::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Event())
        ->listen(DiscussionSaving::class, GuardDiscussionSaving::class)
        ->listen(PostSaving::class, GuardPostSaving::class)
        ->listen(PostPosted::class, RecordPostedRiskEvent::class),

    (new Extend\Routes('api'))
        ->post('/content-risk/inspect', 'lowseekai-content-risk-fee.inspect', InspectContentController::class),

    (new Extend\ApiResource(PostResource::class))
        ->fields(PostFields::class)
        ->endpoint(Endpoint\Create::class, function (Endpoint\Create $endpoint): Endpoint\Create {
            $originalEndpoint = clone $endpoint;

            return $endpoint->action(function (Context $context) use ($originalEndpoint): ?object {
                return resolve(ConnectionInterface::class)
                    ->transaction(fn () => $originalEndpoint->process($context));
            });
        }),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(DiscussionFields::class),

    (new Extend\Settings())
        ->default('content-risk-fee.enabled', true)
        ->default('content-risk-fee.detect_links', true)
        ->default('content-risk-fee.detect_sensitive_words', true)
        ->default('content-risk-fee.link_fee', 10)
        ->default('content-risk-fee.sensitive_fee', 5)
        ->default('content-risk-fee.max_fee', 15)
        ->default('content-risk-fee.sensitive_words', '')
        ->default('content-risk-fee.custom_regex', '')
        ->default('content-risk-fee.whitelist_domains', "github.com\ngithub.io\nwikipedia.org\ndocs.python.org\nopenai.com")
        ->default('content-risk-fee.duplicate_enabled', true)
        ->default('content-risk-fee.duplicate_window_hours', 24)
        ->default('content-risk-fee.duplicate_block_after', 2)
        ->serializeToForum('content-risk-fee.enabled', 'content-risk-fee.enabled', 'boolval')
        ->serializeToForum('content-risk-fee.link_fee', 'content-risk-fee.link_fee', 'intval')
        ->serializeToForum('content-risk-fee.sensitive_fee', 'content-risk-fee.sensitive_fee', 'intval'),

];
