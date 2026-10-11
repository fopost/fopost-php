# Changelog

All notable changes to `fopost/sdk` are documented here.

## Unreleased

### Added

- `$client->blog()` wraps `/blog`: settings (`getSettings`, `updateSettings`,
  `rotateWebsiteSecret`, `testWebsite`), keywords (`listKeywords`, `planKeywords`,
  `addKeyword`, `updateKeyword`, `deleteKeyword`) and articles (`listArticles`,
  `requestArticle`, `getArticle`, `updateArticle`, `regenerateArticle`, `publishArticle`,
  `rejectArticle`, `deleteArticle`), with `BlogSettings`, `BlogKeyword`, `BlogArticle`,
  `BlogArticleFull` and `BlogArticleImage` models and the `Blog` status constants.
- `Webhooks::verify($rawBody, $headers, $secret)` checks a delivery's signature in constant
  time, preferring `X-FoPost-Signature-256` with a 300 second timestamp tolerance and
  falling back to `X-FoPost-Signature` when no timestamp travels, and returns a
  `WebhookEvent`. `WebhookEvent::article()` types an `article.published` payload as
  `BlogArticleWebhookPayload`. A refused delivery is a `WebhookSignatureException`.
