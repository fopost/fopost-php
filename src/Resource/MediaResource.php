<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\MediaAsset;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PresignedUpload;
use Fopost\Sdk\Model\UploadLimits;

/** $client->media(): uploads to the media library. Needs the `posts` scope. */
final class MediaResource extends Resource
{
    /**
     * The library, newest first. `$type` is image, video, gif or document; `$shared` of true
     * keeps only assets with a live share link and fills `->share`.
     *
     * @return Page<MediaAsset>
     */
    public function list(
        ?string $workspaceId = null,
        int $page = 1,
        int $perPage = 20,
        ?string $folderId = null,
        ?string $search = null,
        ?string $tag = null,
        ?string $type = null,
        ?string $url = null,
        ?string $brandRole = null,
        ?string $brandVoiceId = null,
        ?string $generationRunId = null,
        ?string $origin = null,
        ?bool $shared = null,
    ): Page {
        $query = self::compact([
            'workspace_id' => $workspaceId,
            'page' => $page,
            'per_page' => $perPage,
            'folder_id' => $folderId,
            'search' => $search,
            'tag' => $tag,
            'type' => $type,
            'url' => $url,
            'brand_role' => $brandRole,
            'brand_voice_id' => $brandVoiceId,
            'generation_run_id' => $generationRunId,
            'origin' => $origin,
            'shared' => $shared === null ? null : ($shared ? 'true' : 'false'),
        ]);

        return self::page(MediaAsset::class, $this->http->get('/media', $query));
    }

    /** Remove a file from the library. */
    public function delete(string $mediaId): void
    {
        $this->http->delete("/media/{$mediaId}");
    }

    /** The largest file the account may upload, and which route carries it. */
    public function limits(?string $workspaceId = null): UploadLimits
    {
        $body = $this->http->get('/media/limits', ['workspaceId' => $workspaceId]);

        return UploadLimits::fromArray(self::unwrap($body));
    }

    /**
     * Reserve an upload and get the URL to PUT the bytes to.
     *
     * @param array{subject: string, statement: string}|null $personConsent Required with brandRole `person`.
     */
    public function presign(
        string $workspaceId,
        string $filename,
        string $mimeType,
        int $size,
        ?string $folderId = null,
        ?string $origin = null,
        ?string $brandRole = null,
        ?string $brandVoiceId = null,
        ?array $personConsent = null,
    ): PresignedUpload {
        $body = [
            'workspaceId' => $workspaceId,
            'filename' => $filename,
            'mimeType' => $mimeType,
            'size' => $size,
        ] + self::compact([
            'folderId' => $folderId,
            'origin' => $origin,
            'brandRole' => $brandRole,
            'brandVoiceId' => $brandVoiceId,
            'personConsent' => $personConsent,
        ]);

        return PresignedUpload::fromArray(self::unwrap($this->http->post('/media/presign', $body)));
    }

    /** Tell the API the bytes are in place; returns the library row. */
    public function complete(string $uploadId): MediaAsset
    {
        return MediaAsset::fromArray(self::unwrap($this->http->post("/media/presign/{$uploadId}/complete")));
    }

    /**
     * Presign, PUT the bytes, and complete in one call.
     *
     * @param array{subject: string, statement: string}|null $personConsent
     */
    public function uploadDirect(
        string $workspaceId,
        string $filename,
        string $mimeType,
        string $bytes,
        ?string $folderId = null,
        ?string $origin = null,
        ?string $brandRole = null,
        ?string $brandVoiceId = null,
        ?array $personConsent = null,
    ): MediaAsset {
        $upload = $this->presign(
            $workspaceId,
            $filename,
            $mimeType,
            strlen($bytes),
            $folderId,
            $origin,
            $brandRole,
            $brandVoiceId,
            $personConsent,
        );

        // Storage, not the API: no API key, and the presigned headers as given.
        $headers = $upload->headers + ['Content-Length' => (string) strlen($bytes)];
        $this->http->sendRaw($upload->method, $upload->uploadUrl, $headers, $bytes);

        return $this->complete($upload->uploadId);
    }

    /**
     * Upload files in one multipart request. With `$workspaceId` each file lands in the
     * library and comes back with an `id`; without it the files are stored for a post only.
     *
     * @param array<int, string|array{filename: string, contents: string, mimeType?: string}> $files
     *   Paths or in-memory files.
     * @return array<int, MediaAsset>
     */
    public function upload(array $files, ?string $workspaceId = null): array
    {
        $fields = $workspaceId !== null ? ['workspaceId' => $workspaceId] : [];
        $body = $this->http->requestMultipart('POST', '/media/upload', $fields, self::normalizeFiles($files), 'files');

        return MediaAsset::listFrom(self::unwrap($body));
    }
}
