<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\MediaAsset;
use Fopost\Sdk\Model\PresignedUpload;

/** $client->media(): direct uploads to the media library. Needs the `posts` scope. */
final class MediaResource extends Resource
{
    /** Reserve an upload and get the URL to PUT the bytes to. */
    public function presign(string $workspaceId, string $filename, string $mimeType, int $size): PresignedUpload
    {
        $body = [
            'workspaceId' => $workspaceId,
            'filename' => $filename,
            'mimeType' => $mimeType,
            'size' => $size,
        ];

        return PresignedUpload::fromArray(self::unwrap($this->http->post('/media/presign', $body)));
    }

    /** Tell the API the bytes are in place; returns the library row. */
    public function complete(string $uploadId): MediaAsset
    {
        return MediaAsset::fromArray(self::unwrap($this->http->post("/media/presign/{$uploadId}/complete")));
    }

    /** Presign, PUT the bytes, and complete in one call. */
    public function uploadDirect(string $workspaceId, string $filename, string $mimeType, string $bytes): MediaAsset
    {
        $upload = $this->presign($workspaceId, $filename, $mimeType, strlen($bytes));

        // Storage, not the API: no API key, and the presigned headers as given.
        $headers = $upload->headers + ['Content-Length' => (string) strlen($bytes)];
        $this->http->sendRaw($upload->method, $upload->uploadUrl, $headers, $bytes);

        return $this->complete($upload->uploadId);
    }
}
