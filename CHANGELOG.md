# Changelog

All notable changes to `fopost/sdk` are documented here.

## 0.4.0 - 2026-10-18

### Added

- `$client->queue()` wraps the posting queue: `listSlots`, `createSlot`, `updateSlot`,
  `deleteSlot`, `next` and `preview`, with `QueueSlot`, `QueueMove`, `QueueSlotUpdate` and
  `QueueSlotDeletion`. `posts()->create()` and `update()` take `schedule: 'queue'`.
- `$client->posts()->unpublish()` takes a published post down everywhere and answers an
  `UnpublishResult` with one `PlatformRemoval` per network. `validateBulkImport()`,
  `commitBulkImport()` and `rollbackBulkImport()` wrap the CSV import.
- `$client->connectSessions()` creates and reads hosted connect links (`ConnectSession`).
- `$client->webhooks()` manages endpoints (`list`, `create`, `update`, `delete`, `test`) and
  their delivery log (`listDeliveries`, `getDelivery`, `redeliver`), with `Webhook` and
  `WebhookDelivery`.
- `$client->automations()` wraps `/automations`: `list`, `stats`, `get`, `create`, `update`,
  `delete`, `toggle`, versions (`listVersions`, `getVersion`, `restoreVersion`) and runs
  (`listRuns`, `getRun`), with `Automation`, `AutomationStep`, `AutomationStats`,
  `AutomationVersion`, `AutomationNodeRef`, `AutomationRestore`, `AutomationRun`,
  `AutomationRunLog` and `AutomationToggle`.
- `$client->workspaces()->create()`, `update()`, `delete()` and `analytics()` (`WorkspaceAnalytics`,
  `WorkspaceAccountStats`). `Workspace` carries
  `connectRedirectDomains` and `updatedAt`.
- `$client->accounts()->validate()` (`AccountValidation`), `refreshToken()`, `togglePrimary()`
  (`AccountPrimary`) and `analyticsHistory()` (`AccountAnalyticsHistory`, `AccountSnapshot`).
- `$client->accounts()` X communities: `listCommunities`, `syncCommunities`,
  `searchCommunities`, `addCommunity` and `removeCommunity` (`XCommunity`, `XCommunityMatch`).
- `$client->logs()->list()` reads the request log into `RequestLogPage` (`RequestLog`,
  `RequestLogBucket`).
- `$client->analytics()` dashboard calls: `overview`, `timeSeries`, `topPosts`, `labels`,
  `postsTable`, `postingStreak`, `demographics` and `inbox`, with `AnalyticsOverview`,
  `AnalyticsAccountTotal`, `AnalyticsTimeSeries`, `AnalyticsSeriesPoint`, `TopPost`,
  `LabelAnalytics`, `PostsTablePage`, `PostsTableRow`, `PostingStreakDay`,
  `AudienceDemographics`, `DemographicsBucket` and `InboxAnalytics`.
- `$client->media()->upload()` sends files through the API in one multipart request;
  `list()` pages through the library and `delete()` removes a file. `MediaAsset` carries every
  library field (`mimeType`, `tags`, `brandRole`, `hasPersonConsent`, `share`, ...).

## 0.3.0 - 2026-10-18

### Removed

- **Breaking:** `BlogSettings::$autoPublish` is gone, and `updateSettings()` no longer takes
  `autoPublish`. Nothing publishes without approval: a draft goes to `in_review` through
  `submitArticle()` first.

### Added

- `$client->blog()->suggestSeeds($workspaceId, $seeds, $prompt)` returns about 10 search
  terms from the blog goal and topics (`ai` scope; an empty balance is a
  `PaymentRequiredException`).
- `$client->blog()->submitArticle()` moves a draft, failed or rejected article to `in_review`
  (`posts` scope, 409 from any other state). `publishArticle()` and `publishAt` now need an
  article in review, so a draft answers 409 until it is submitted.
- `BlogSettings` carries `goal`, `focusTopics`, `avoidTopics` and `planRun` (a `BlogPlanRun`),
  and `updateSettings()` accepts `goal`, `focusTopics` and `avoidTopics`. `BlogKeyword::$fitReason`,
  `Blog::GOALS`, and `in_review` in `Blog::ARTICLE_STATUSES`. `planKeywords()` no longer fills
  the calendar: setting a keyword to `planned` picks it, and `reviewHoldDays` is how many days
  before its date it starts being written.
- `$client->blog()` wraps `/blog`: settings (`getSettings`, `updateSettings`,
  `rotateWebsiteSecret`, `testWebsite`), keywords (`listKeywords`, `planKeywords`,
  `addKeyword`, `updateKeyword`, `deleteKeyword`) and articles (`listArticles`,
  `requestArticle`, `getArticle`, `updateArticle`, `regenerateArticle`, `publishArticle`,
  `rejectArticle`, `deleteArticle`), with `BlogSettings`, `BlogKeyword`, `BlogArticle`,
  `BlogArticleFull` and `BlogArticleImage` models and the `Blog` status constants.
  The workspace travels as `workspace_id` on the query string, and `testWebsite()` returns the
  site's answer as `BlogWebsiteTest::$httpStatus`.
- `Webhooks::verify($rawBody, $headers, $secret)` checks a delivery's signature in constant
  time, preferring `X-FoPost-Signature-256` with a 300 second timestamp tolerance and
  falling back to `X-FoPost-Signature` when no timestamp travels, and returns a
  `WebhookEvent`. `WebhookEvent::article()` types an `article.published` payload as
  `BlogArticleWebhookPayload`. A refused delivery is a `WebhookSignatureException`.
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
- Reddit on `AccountsResource`: `listRedditSubreddits()`, `listRedditSubredditRules($accountId,
  $subreddit)`, `listRedditFlairs($accountId, $subreddit)` and `setRedditDefaultSubreddit()`, with
  `RedditSubreddit`, `RedditSubredditRules`, `RedditFlairs` and `RedditDefaultSubreddit` models.
  A flair id is valid only in the subreddit it was listed for. `$client->validate()->subreddit($accountId,
  $name)` answers whether a subreddit exists and takes a post from that account (`SubredditCheck`), and
  `$client->inbox()->vote($itemId, $direction)` votes `up`, `down` or `none` where `canVote` is true
  (`publish` scope).
- `$client->blogs()` wraps `/accounts/{id}/blogs`: the blogs, articles and products a connected
  WordPress site or Shopify store already owns (`listBlogs`, `listArticles`, `getArticle`,
  `createArticle`, `updateArticle`, `deleteArticle`, `listProducts`, `updateProduct`), with
  `RemoteBlog`, `RemoteArticle` and `RemoteProduct` models. Every id is the platform's own, never a
  FoPost id; `updateArticle()` edits the live article in place and never leaves a duplicate. Reads
  need the `posts` scope, anything that changes the site `publish` as well.

### Changed

- A `413 file_too_large` answer carries `max_file_bytes`, `file_bytes` and `upgrade_url` on the
  `ApiException` body.
