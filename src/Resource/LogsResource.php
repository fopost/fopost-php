<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\RequestLogPage;

/** $client->logs(): the API requests made with your keys, newest first. Needs the `workspaces` scope. */
final class LogsResource extends Resource
{
    /**
     * `$status` is ok or error; `$before` is the page's `nextCursor`.
     */
    public function list(
        ?string $workspaceId = null,
        ?int $days = null,
        ?string $status = null,
        ?string $method = null,
        ?int $statusCode = null,
        ?string $platform = null,
        ?string $route = null,
        ?string $q = null,
        ?int $limit = null,
        ?string $before = null,
    ): RequestLogPage {
        $params = self::compact([
            'workspace_id' => $workspaceId,
            'days' => $days,
            'status' => $status,
            'method' => $method,
            'status_code' => $statusCode,
            'platform' => $platform,
            'route' => $route,
            'q' => $q,
            'limit' => $limit,
            'before' => $before,
        ]);

        return RequestLogPage::fromArray($this->http->get('/logs', $params));
    }
}
