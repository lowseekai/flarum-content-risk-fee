<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Api;

use Flarum\Api\Schema;

final class DiscussionFields
{
    public function __invoke(): array
    {
        return [
            Schema\Str::make('contentRiskToken')
                ->writableOnCreate()
                ->maxLength(128)
                ->visible(false)
                ->set(static function (): void {
                    // The initial post is saved by Flarum in a nested request.
                }),
            Schema\Str::make('contentRiskTitle')
                ->writableOnCreate()
                ->maxLength(80)
                ->visible(false)
                ->set(static function (): void {
                    // The initial post is saved by Flarum in a nested request.
                }),
        ];
    }
}
