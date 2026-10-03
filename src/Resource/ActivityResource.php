<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\ActivityPage;

/** $client->activity(): what happened in a workspace, and the audit log. */
final class ActivityResource extends Resource
{
    /**
     * Newest first. Omit $workspaceId to read every workspace the key can reach.
     *
     * $kind of 'security' is the audit log: members joining, leaving or changing
     * role and access, and changes to two-step verification, passkeys, single
     * sign-on and signed-in devices. Those rows are append-only and never expire.
     */
    public function list(
        ?string $workspaceId = null,
        ?string $kind = null,
        ?string $from = null,
        ?string $to = null,
        ?string $cursor = null,
        ?int $limit = null,
    ): ActivityPage {
        $params = self::compact([
            'workspace_id' => $workspaceId,
            'kind' => $kind,
            'from' => $from,
            'to' => $to,
            'cursor' => $cursor,
            'limit' => $limit,
        ]);

        return ActivityPage::fromArray($this->http->get('/activity', $params));
    }
}
