# Changelog

All notable changes to `fopost/sdk` are documented here.

## Unreleased

### Added

- `$client->media()->limits($workspaceId)` reads `GET /media/limits` into `UploadLimits`:
  `maxFileBytes`, `source`, `connectedAccounts`, `largeUploadsEnabled`, `nextTier`
  (`UploadLimitTier`), `multipartMaxFileBytes`, `maxFilesPerUpload`, `directUploadAvailable`
  and `allowedTypes`.
- `presign()` and `uploadDirect()` accept `folderId`, `origin`, `brandRole`, `brandVoiceId` and
  `personConsent`; `PresignedUpload` gains `maxFileBytes`. Files up to the account's limit
  (5 GB at most) may be presigned.
- `posts()->create()` and `posts()->update()` accept `files` (paths or
  `['filename', 'contents', 'mimeType']`). Files within the multipart ceiling travel in one
  `multipart/form-data` request; larger files are uploaded direct and attached to the first
  content block. `update()` without `content` appends to the post's existing first block.
- `HttpClient::requestMultipart()` sends multipart bodies through the same retry loop.

### Changed

- A `413 file_too_large` answer carries `max_file_bytes`, `file_bytes` and `upgrade_url` on the
  `ApiException` body.
