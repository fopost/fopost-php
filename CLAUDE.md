# CLAUDE.md

Guidance for Claude Code (claude.ai/code) when working in this repository.

## What This Is

`fopost/sdk` — the official PHP SDK for the FoPost REST API, distributed on **Packagist**.
It wraps the HTTP API in a `Fopost\Sdk\Client` with typed resources, response models, and an
exception per error status. PSR-4 autoload root `Fopost\Sdk\` → `src/`.

- **PHP >= 8.1.** Extensions `ext-curl` and `ext-json` are the only runtime requirements —
  there are no Composer runtime dependencies, and none may be added.
- Version `0.3.0`, declared in `Fopost\Sdk\Client::VERSION`. `composer.json` carries no
  `version` key (Packagist reads the git tag).

## Downstream Packages

These repos wrap this SDK and must be updated in lockstep:

- `fopost-laravel` — Laravel package (`fopost/laravel`): service provider, facade, config, events
- `fopost-symfony` — Symfony bundle: DI wiring and config over this SDK
- `fopost-woocommerce` — WordPress/WooCommerce plugin; bundles this SDK in its release zip.
  It is NOT a child of `fopost-wp` (that plugin is inbound-only and exposes no client), so a
  breaking change here reaches WooCommerce users through this package.

**Whenever you change this SDK's public surface — a renamed method, a changed parameter,
a new or removed resource, a new error type, a bumped minimum language version — you must
open a matching PR in every repo listed above in the same session.** They are separate
git repos, checked out as siblings at `../fopost-<child>`. A parent release that silently
breaks a child is only discovered by the user who upgrades first.

Also bump the child's dependency constraint on this package and note the change in its
CHANGELOG when this package is released.

## Brand Rules

- The product is **FoPost** (`fopost.com`). Never write "OwlStack" — retired Aug 2026.
- Never write an email address anywhere: not in code, docblocks, README, or package metadata.
  Support is https://fopost.com/contact and the GitHub issues page.
- Never name AI providers or models, infrastructure vendors, hosting, or any person.
  The author is the brand / Porter Bridge, LLC.

## Architecture

```
src/
  Client.php            entry point; constructs HttpClient, owns one instance per resource
  Platforms.php         platform and status string constants
  Undefined.php         enum sentinel for "the caller did not pass this"
  Http/
    Transport.php       interface: send(method, url, headers, body): Response
    CurlTransport.php   default implementation, ext-curl, one handle per request
    HttpClient.php      headers, URL building, JSON coding, retry loop, decode, unwrap
    Response.php        status + lowercased headers + raw body, pre-decode
  Resource/
    Resource.php        base: unwrap/compact/asArray/iso helpers
    PostsResource.php AccountsResource.php AccountGroupsResource.php WorkspacesResource.php
    LabelsResource.php AiResource.php InboxResource.php AdsResource.php MediaResource.php
    ValidateResource.php
  Model/
    Model.php           base: reads both wire casings, keeps the untouched payload on ->raw
    Page.php PageMeta.php Post.php SocialAccount.php Workspace.php Label.php ...
    AccountGroup.php AccountRename.php AccountMove.php
    Inbox*.php (item, thread, conversation, account, platform, approval, reply, refresh and start-conversation results)
    Ad.php AdInsights.php ExternalAd.php AdConnection.php AdSource.php BoostablePost.php
    Audience.php AudiencesResult.php CreatedAudience.php TargetingOption.php
    LeadForm.php LeadFormSource.php Lead.php LeadsPage.php
    PostValidation.php LengthValidation.php MediaValidation.php (+ per-platform rows, ValidationSignal)
  Exception/
    FopostException.php ErrorFactory.php + one subclass per status
```

**Request flow.** `$client->posts()->create(...)` → `PostsResource` builds a params array and
calls `HttpClient::post()` → `HttpClient::request()` builds the URL (`url()`), JSON-encodes the
body, and enters the retry loop → `Transport::send()` returns a `Response` → `decode()` either
throws through `ErrorFactory` or returns the decoded body → the resource calls
`HttpClient::unwrap()` and hands the array to `Post::fromArray()`.

- **The `Transport` interface is the seam.** Pass an implementation as the 5th constructor
  argument to `Client` (or `HttpClient`) to swap the HTTP stack. `HttpClient` takes a 6th
  `?callable $sleeper` argument used only by the retry tests; it is not exposed on `Client`.
- `Model` reads every field under both `snake_case` and `camelCase` because the API is
  inconsistent (posts snake, accounts camel). Unknown fields survive on `->raw` / `->get()`.
- `Undefined::Value` distinguishes "not passed" from `null` on partial updates, so a `PUT`
  sends only the named fields.
- `PostsResource::iterate()` / `iteratePages()` are generators that page through the list
  endpoint; `Page` is `IteratorAggregate + Countable + ArrayAccess` and read-only.

**Resources wired today:** `posts`, `accounts`, `accountGroups`, `workspaces`, `labels`, `ai`,
`inbox`, `ads`, `media`, `validate`. There is no `communities`, `webhooks`, `analytics`, or
`automations` resource here — reach those through the escape hatch `Client::request()` until one
is added.

- `inbox` (scope `inbox`) covers `/inbox`, `/inbox/posts`, `/inbox/conversations`, `unread-count`,
  `accounts`, `platforms`, `read`, `refresh`, `approvals` (+ `approve`/`reject`, integer ids), and
  per-item `PATCH`, `reply`, `hide`, `unhide`, `DELETE`. Not wrapped: `/inbox/chat/*` (browser
  encrypted X Chat) and `/inbox/{id}/attachments/{index}` (a binary stream; the SDK has no binary
  download path). The read and refresh bodies are snake_case, the item `PATCH` is camelCase
  (`snoozedUntil`), and list `meta` is `{page, perPage, total}` — `PageMeta` reads `page` as
  `currentPage` for it.
- `ads` (scope `ads`) covers the full `/ads` family: ads, external ads, boostable posts,
  connections (+ Meta authorize), sources, boost, create, refresh, status, delete, audiences,
  targeting search, lead forms and leads. `boost()`, `create()`, `setStatus()` and `delete()` also
  need the `publish` scope, and a boost or ad starts paused unless `paused` is `false`. Request
  bodies are camelCase; query params stay snake_case. `Resource::page()` is the shared
  `{data, meta}` → `Page` helper.
- `media` (scope `posts`) is direct upload only: `presign()` → `PresignedUpload`, `complete()` →
  `MediaAsset`, and `uploadDirect()` which does both around a raw `PUT` of the bytes to the
  presigned URL through `HttpClient::sendRaw()` (no API key, no JSON, no retry; a non-2xx throws
  and `complete` is never called). Bodies are camelCase.

## API Contract

- **Base URL:** `HttpClient::DEFAULT_BASE_URL` = `https://api.fopost.com/v1`, the path the
  API actually serves and the docs publish. `/api/v1` is **not** served and returns 404 —
  never reintroduce it. `HttpClient::normalizeBaseUrl()` appends `HttpClient::API_PATH_SUFFIX`
  (`/v1`) when the given URL has no path, so both `https://api.fopost.com` and the full URL
  work. There is **no `FOPOST_BASE_URL` env read** — pass `baseUrl` to the constructor.
- **Auth:** header `X-API-Key: <key>`, never Bearer. The key falls back to the `FOPOST_API_KEY`
  environment variable (`getenv()` then `$_ENV`); a missing key is an `InvalidArgumentException`
  before any request goes out.
- **Headers on every request:** `Accept: application/json`, `Content-Type: application/json`,
  `X-API-Key`, `User-Agent: fopost-php`. Note the User-Agent carries **no version suffix**.
- **Timeout:** 30.0s default, applied by `CurlTransport` as both connect and total timeout.
- **Retries — as implemented here:** `$maxRetries` (default 3) is the **total attempt count**,
  and only **HTTP 429** is retried. 5xx and transport failures are **not** retried; a curl
  failure raises `ApiException` with status `0` and code `transport_error` on the first attempt.
  There is **no exponential backoff**: the wait is `Retry-After` (delta-seconds or an HTTP date)
  when present, otherwise a flat `1.0`s, capped at `MAX_RETRY_WAIT` = 60s.
- **Success envelope:** `HttpClient::unwrap()` peels `{"data": ...}` only when the key is
  present, because some endpoints answer bare. Paginated lists carry a sibling `meta`
  (`current_page`, `per_page`, `total`, `last_page`, `from`, `to`) parsed by `PageMeta`.
- **Error envelope:** `{"error": "<code>", "message": "<text>"}` maps onto
  `FopostException::$errorCode` and the exception message; the decoded body stays on `->body`
  so callers can read extra fields. `PaymentRequiredException::getUpgradeUrl()` reads
  `upgrade_url` off that body.
- **Exception map** (`Exception/ErrorFactory.php`): 400/422 `ValidationException` ·
  401 `AuthenticationException` · 402 `PaymentRequiredException` · 403 `PermissionDeniedException` ·
  404 `NotFoundException` · 429 `RateLimitException` (has `getRetryAfter(): ?float`) ·
  **everything else, 5xx included, falls through to `ApiException`** — there is no dedicated
  server-error class. All extend `FopostException extends RuntimeException`.
- **Rate-limit headers** (`X-RateLimit-Limit`/`-Remaining`/`-Reset`) are not surfaced on models
  or exceptions. They are readable via `Response::header()` from a custom `Transport`.

## Commands

```bash
composer install                # dev deps: phpunit ^10.5, php_codesniffer ^3.9
composer test                   # → phpunit
composer lint                   # → phpcs
./vendor/bin/phpunit                       # whole suite
./vendor/bin/phpunit tests/PostsTest.php   # one file
./vendor/bin/phpunit --filter testName
./vendor/bin/phpcs                         # PSR-12 over src/ and tests/
./vendor/bin/phpcbf                        # autofix what phpcs can
```

There is no build step — the package ships source. CI (`.github/workflows/ci.yml`) runs
`phpunit` then `phpcs` on PHP **8.1, 8.2 and 8.3**, on push to `main` and on PRs.

## Conventions

- **PSR-12** enforced by `phpcs.xml` over `src/` and `tests/`, plus a line limit of **120**
  (hard stop 140). Run `phpcs` after every change.
- `declare(strict_types=1);` at the top of every file.
- Classes are `final` unless a subclass is the point (the exception hierarchy). Constructor
  property promotion with `readonly` for anything immutable.
- Full PHPDoc generics on array shapes (`@param array<string, mixed>`, `@return Page<Post>`) —
  the typed-array types the language cannot express are carried in docblocks.
- Comments stay short and explain a "why". No narrated docblocks on obvious code; do keep the
  doc comments on public API symbols.
- `phpunit.xml` sets `failOnWarning` and `failOnRisky` — a warning is a failure.

## Testing

- PHPUnit 10, tests in `tests/`, namespace `Fopost\Sdk\Tests\`.
- **`tests/FakeTransport.php` implements `Http\Transport`.** It queues responses
  (`push($status, $body, $headers)`), records every outbound request, and never touches the
  network. `tests/TestCase.php` builds a `Client` around it via `client(int $maxRetries = 3)`.
- Retry tests build an `HttpClient` directly so they can pass a recording `$sleeper` closure and
  assert the exact waits — see `tests/RetryTest.php`.
- **Tests never hit the live API.** No network call in the suite, ever, in CI or locally. If a
  change cannot be tested through `FakeTransport`, the change is in the wrong layer.
- Cover at minimum: the auth header is sent, 429 is retried, error mapping per status, and one
  happy path per resource.

## Releasing

**`fopost/sdk` is NOT yet on Packagist, and this repo has no `.github/workflows/release.yml`.**
Composer publishing is pull-based, so no publish workflow or registry token is strictly needed —
Packagist reads tags off the repo.

First publish requires, in order:

1. A Packagist account with rights to the `fopost` vendor namespace.
2. Submitting `https://github.com/fopost/fopost-php` on Packagist, which claims `fopost/sdk`.
3. Connecting the GitHub integration (or the repo webhook) so Packagist refreshes on push —
   without it, every new tag has to be updated by hand.
4. Tagging `v0.1.0` on `main`. Packagist derives the version from the tag; `composer.json` must
   stay version-less.

Before tagging, bump `Fopost\Sdk\Client::VERSION` in the same commit as the tag's target — it is
the only version constant in the source and nothing verifies it automatically.

This repo also has **no `CHANGELOG.md` and no `examples/` directory**, which the sibling SDKs
carry. Add both before the first release rather than after.

## Git

- **Conventional Commits** `<type>(<scope>): <description>` — one logical change per commit.
- Branch `feature/<description>` off a fresh `main`; merge to `main` via PR.
- **Never run `gh pr create`.** Push the branch and hand over the compare link:
  `https://github.com/fopost/fopost-php/compare/main...<branch>`
- Never `git stash` — use a worktree for parallel work.
