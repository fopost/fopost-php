<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Workspace;

/** $client->workspaces(): the workspaces the key can reach. */
final class WorkspacesResource extends Resource
{
    /** @return array<int, Workspace> */
    public function list(): array
    {
        return Workspace::listFrom(self::unwrap($this->http->get('/workspaces')));
    }

    public function get(string $workspaceId): Workspace
    {
        return Workspace::fromArray(self::unwrap($this->http->get("/workspaces/{$workspaceId}")));
    }
}
