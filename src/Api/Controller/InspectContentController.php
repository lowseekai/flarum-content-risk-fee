<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Lowseekai\ContentRiskFee\Support\ContentRiskService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InspectContentController implements RequestHandlerInterface
{
    public function __construct(protected ContentRiskService $service) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        if ($actor->isGuest()) {
            throw new PermissionDeniedException();
        }

        $attributes = (array) Arr::get($request->getParsedBody(), 'data.attributes', []);
        $result = $this->service->inspect(
            $actor,
            trim((string) ($attributes['title'] ?? '')),
            (string) ($attributes['content'] ?? ''),
        );

        return new JsonResponse([
            'data' => [
                'type' => 'content-risk-checks',
                'id' => 'current',
                'attributes' => $result,
            ],
        ]);
    }
}
