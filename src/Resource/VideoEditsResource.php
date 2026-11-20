<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\VideoEdit;
use Fopost\Sdk\Model\VideoEditOp;

/**
 * $client->videoEdits(): trim, cut, subtitle, clip and otherwise edit a library video as a
 * background job. Needs the `posts` scope; operations that use AI also need `ai`.
 */
final class VideoEditsResource extends Resource
{
    /** @return array<int, VideoEditOp> */
    public function ops(): array
    {
        return VideoEditOp::listFrom(self::unwrap($this->http->get('/video-edits/ops')));
    }

    /**
     * Recent jobs, newest first. Needs `$workspaceId` or `$mediaId`.
     *
     * @return array<int, VideoEdit>
     */
    public function list(?string $workspaceId = null, ?string $mediaId = null, ?int $limit = null): array
    {
        $query = ['workspace_id' => $workspaceId, 'media_id' => $mediaId, 'limit' => $limit];

        return VideoEdit::listFrom(self::unwrap($this->http->get('/video-edits', $query)));
    }

    /**
     * Queue up to ten operations on a video, run in order. Each operation is
     * `['op' => 'trim', 'params' => ['startSec' => 0, 'endSec' => 10]]`; poll get() for the result.
     *
     * @param array<int, array{op: string, params?: array<string, mixed>|object}> $operations
     */
    public function create(string $mediaId, array $operations, ?string $workspaceId = null): VideoEdit
    {
        $ops = [];
        foreach ($operations as $operation) {
            $params = $operation['params'] ?? [];
            $ops[] = $params === [] ? ['op' => $operation['op']] : ['op' => $operation['op'], 'params' => $params];
        }
        $body = self::compact(['workspace_id' => $workspaceId, 'media_id' => $mediaId, 'operations' => $ops]);

        return VideoEdit::fromArray(self::unwrap($this->http->post('/video-edits', $body)));
    }

    public function get(string $jobId, ?string $workspaceId = null): VideoEdit
    {
        return VideoEdit::fromArray(
            self::unwrap($this->http->get("/video-edits/{$jobId}", ['workspace_id' => $workspaceId])),
        );
    }
}
