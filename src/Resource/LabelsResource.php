<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Label;
use Fopost\Sdk\Undefined;

/** $client->labels(): workspace labels you can attach to posts. */
final class LabelsResource extends Resource
{
    /** @return array<int, Label> */
    public function list(?string $workspaceId = null): array
    {
        return Label::listFrom(
            self::unwrap($this->http->get('/labels', ['workspace_id' => $workspaceId])),
        );
    }

    public function get(string $labelId): Label
    {
        return Label::fromArray(self::unwrap($this->http->get("/labels/{$labelId}")));
    }

    public function create(string $workspaceId, string $name, ?string $color = null): Label
    {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'color' => $color,
        ]);

        return Label::fromArray(self::unwrap($this->http->post('/labels', $body)));
    }

    /** Partial update: only the fields you pass are sent. */
    public function update(
        string $labelId,
        string|Undefined $name = Undefined::Value,
        string|Undefined $color = Undefined::Value,
    ): Label {
        $body = [];
        if (!Undefined::is($name)) {
            $body['name'] = $name;
        }
        if (!Undefined::is($color)) {
            $body['color'] = $color;
        }

        return Label::fromArray(self::unwrap($this->http->put("/labels/{$labelId}", $body)));
    }

    public function delete(string $labelId): void
    {
        $this->http->delete("/labels/{$labelId}");
    }
}
