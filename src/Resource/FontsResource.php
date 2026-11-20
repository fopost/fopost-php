<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\FontCatalog;
use Fopost\Sdk\Model\UploadedFont;

/** $client->fonts(): the font library and a workspace's own fonts. Needs the `posts` scope. */
final class FontsResource extends Resource
{
    /** The library, plus the workspace's uploaded fonts when `$workspaceId` is given. */
    public function list(?string $workspaceId = null): FontCatalog
    {
        return FontCatalog::fromArray(self::unwrap($this->http->get('/fonts', ['workspace_id' => $workspaceId])));
    }

    /**
     * Upload one font file (TTF, OTF, WOFF or WOFF2). `$family` defaults to the file name,
     * `$weight` to 400 and `$style` (normal or italic) to normal.
     *
     * @param string|array{filename: string, contents: string, mimeType?: string} $file
     *   A path or an in-memory file.
     */
    public function upload(
        string $workspaceId,
        string|array $file,
        ?string $family = null,
        ?int $weight = null,
        ?string $style = null,
    ): UploadedFont {
        $fields = array_map('strval', self::compact([
            'workspace_id' => $workspaceId,
            'family' => $family,
            'weight' => $weight,
            'style' => $style,
        ]));
        $body = $this->http->requestMultipart('POST', '/fonts', $fields, self::normalizeFiles([$file]), 'file');

        return UploadedFont::fromArray(self::unwrap($body));
    }

    public function delete(string $fontId): void
    {
        $this->http->delete("/fonts/{$fontId}");
    }
}
