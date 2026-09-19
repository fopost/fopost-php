# FoPost PHP SDK

[![Packagist Version](https://img.shields.io/packagist/v/fopost/sdk.svg)](https://packagist.org/packages/fopost/sdk)
[![Packagist Downloads](https://img.shields.io/packagist/dt/fopost/sdk.svg)](https://packagist.org/packages/fopost/sdk)
[![PHP Version](https://img.shields.io/packagist/dependency-v/fopost/sdk/php.svg)](https://packagist.org/packages/fopost/sdk)
[![CI](https://img.shields.io/github/actions/workflow/status/fopost/fopost-php/ci.yml?branch=main&label=ci)](https://github.com/fopost/fopost-php/actions)
[![License](https://img.shields.io/packagist/l/fopost/sdk.svg)](https://github.com/fopost/fopost-php/blob/main/LICENSE)

The official PHP SDK for the [FoPost](https://fopost.com) API. Connect social accounts once, then compose, schedule, and publish from your own application.

Requires PHP 8.1 or newer, plus `ext-curl` and `ext-json`. Nothing else: no framework, no HTTP library.

## Install

```bash
composer require fopost/sdk
```

## Get an API key

Create a key at [fopost.com/dashboard/api-keys](https://fopost.com/dashboard/api-keys). The full API reference lives at [fopost.com/docs](https://fopost.com/docs).

## Quickstart

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Fopost\Sdk\Client;

$client = new Client('fp_...');           // or set FOPOST_API_KEY

$workspace = $client->workspaces()->list()[0];
$accounts = $client->accounts()->list($workspace->id);

$post = $client->posts()->create(
    workspaceId: $workspace->id,
    content: 'Hello from PHP',
    accounts: array_map(fn ($a) => $a->id, $accounts),
);

$client->posts()->publish($post->id);
```

The key falls back to the `FOPOST_API_KEY` environment variable, so `new Client()` works when it is set.

```php
$client = new Client(
    apiKey: 'fp_...',
    baseUrl: 'https://api.fopost.com',  // a bare host gets /v1 appended
    timeout: 30.0,                      // seconds
    maxRetries: 3,                      // attempts, on 429 only
);
```

## Posts

```php
use DateTimeImmutable;

// List one page. The result iterates over its items directly.
$page = $client->posts()->list(workspaceId: $workspaceId, status: 'scheduled');
foreach ($page as $post) {
    echo $post->id, ' ', $post->status, PHP_EOL;
}
echo $page->meta->total;

// Walk every matching post, one page at a time.
foreach ($client->posts()->iterate(workspaceId: $workspaceId) as $post) {
    echo $post->text(), PHP_EOL;
}

$post = $client->posts()->get('p_123');

// Create a draft, a thread, or a scheduled post.
$draft = $client->posts()->create(
    workspaceId: $workspaceId,
    content: ['First post', 'The reply'],
    accounts: ['acc_1', 'acc_2'],
);

$scheduled = $client->posts()->create(
    workspaceId: $workspaceId,
    content: 'Going out on Monday',
    accounts: ['acc_1'],
    status: 'scheduled',
    scheduleAt: new DateTimeImmutable('2026-03-01T09:00:00Z'),
);

// Partial update: only the fields you name are sent.
$client->posts()->update($draft->id, title: 'A better title');

$client->posts()->schedule($draft->id, new DateTimeImmutable('+1 day'));
$client->posts()->unschedule($draft->id);
$client->posts()->publish($draft->id);
$client->posts()->preflight($draft->id);
$client->posts()->retry($draft->id);
$client->posts()->cancel($draft->id);
$client->posts()->delete($draft->id);

foreach ($client->posts()->deliveries($draft->id) as $delivery) {
    echo $delivery->platform, ' ', $delivery->status, ' ', $delivery->externalUrl, PHP_EOL;
}
```

## Accounts

```php
$accounts = $client->accounts()->list($workspaceId);
$account = $client->accounts()->get('acc_1');

$health = $client->accounts()->health('acc_1');
$client->accounts()->disconnect('acc_1');
```

## Workspaces

```php
$workspaces = $client->workspaces()->list();
$workspace = $client->workspaces()->get($workspaceId);

echo $workspace->name, ' ', $workspace->timezone, PHP_EOL;
```

## Labels

```php
$labels = $client->labels()->list($workspaceId);

$label = $client->labels()->create($workspaceId, 'Product launch', '#0070f3');
$client->labels()->update($label->id, name: 'Launch week');
$client->labels()->delete($label->id);
```

## AI

Every AI call spends AI credits.

```php
$balance = $client->ai()->credits();
echo $balance->creditsRemaining, ' of ', $balance->creditsTotal, PHP_EOL;

$caption = $client->ai()->generateCaption(
    currentCaption: 'new feature is live',
    platforms: ['linkedin', 'bluesky'],
    charLimit: 280,
);

$rewrite = $client->ai()->rewrite('One draft, many networks', ['linkedin', 'bluesky'], tone: 'friendly');
foreach ($rewrite->results as $variant) {
    echo $variant->platform, ': ', $variant->content, PHP_EOL;
}

$repurposed = $client->ai()->repurposeUrl('https://example.com/blog/launch', ['linkedin', 'threads']);
```

## Inbox

Comments, mentions and direct messages on connected accounts. Needs the `inbox` scope.

```php
// One page of items, newest first. Filters are optional.
$page = $client->inbox()->list(workspaceId: $workspaceId, type: 'comment', state: 'unread');
foreach ($page as $item) {
    echo $item->platform, ' ', $item->authorHandle, ': ', $item->text, PHP_EOL;
}
echo $page->meta->total;

$threads = $client->inbox()->threads(workspaceId: $workspaceId);              // one row per post
$mentions = $client->inbox()->threads(workspaceId: $workspaceId, kind: 'mentions');
$conversations = $client->inbox()->conversations(workspaceId: $workspaceId); // one row per DM thread

$client->inbox()->unreadCount($workspaceId);
$client->inbox()->accounts($workspaceId);   // inboxSupported / dmSupported per account
$client->inbox()->platforms();

$client->inbox()->refresh($workspaceId);
$client->inbox()->markThreadRead($workspaceId, $accountId, postExternalId: 'ext_9');

$client->inbox()->update($item->id, 'snoozed', new DateTimeImmutable('+1 day'));
$reply = $client->inbox()->reply($item->id, 'Thanks for the kind words');
echo $reply->externalUrl;
$client->inbox()->hide($item->id);
$client->inbox()->unhide($item->id);
$client->inbox()->delete($item->id);

// Replies an automation or the agent drafted, waiting for a person.
foreach ($client->inbox()->listApprovals($workspaceId) as $approval) {
    $client->inbox()->approveReply($approval->id);          // or approveReply($id, 'edited text')
}
$client->inbox()->rejectReply($approval->id);
```

## Ads

Meta ads, audiences and lead forms. Needs the `ads` scope; `boost()`, `create()`, `setStatus()` and `delete()` spend money and also need `publish`. A boost or ad starts paused unless `paused: false` is passed.

```php
$ads = $client->ads()->list($workspaceId);
$client->ads()->external($workspaceId);      // ads made outside FoPost, read live
$client->ads()->boostable($workspaceId);
$client->ads()->connections($workspaceId);
$client->ads()->sources($workspaceId);       // ad accounts and Pages per connection

$url = $client->ads()->authorizeMeta($workspaceId);   // finish the login in a browser
$client->ads()->deleteConnection($connectionId, $workspaceId);

$boost = $client->ads()->boost(
    workspaceId: $workspaceId,
    connectionId: $connectionId,
    adAccountId: 'act_123',
    postId: $post->id,
    accountId: $accountId,
    name: 'Launch week boost',
    goal: 'engagement',
    budget: ['minor' => 2500, 'type' => 'daily'],
    targeting: ['countries' => ['US'], 'ageMin' => 18],
);

$ad = $client->ads()->create(
    workspaceId: $workspaceId,
    connectionId: $connectionId,
    adAccountId: 'act_123',
    pageId: '555',
    name: 'Spring plans',
    goal: 'traffic',
    budget: ['minor' => 10000, 'type' => 'lifetime', 'endAt' => '2026-10-01T00:00:00Z'],
    targeting: ['countries' => ['US']],
    text: 'Meet the new plan',
    destinationUrl: 'https://yourbrand.com/plans',
);

$client->ads()->refresh($ad->id, $workspaceId);
$client->ads()->setStatus($ad->id, $workspaceId, 'active');
$client->ads()->delete($ad->id, $workspaceId);

$audiences = $client->ads()->audiences($connectionId, 'act_123');
$client->ads()->createAudience($workspaceId, $connectionId, 'act_123', 'Lookalike', [
    'subtype' => 'LOOKALIKE',
    'originAudienceId' => $audiences->audiences[0]->id,
    'country' => 'US',
]);
$client->ads()->searchTargeting($connectionId, 'interest', 'coffee');

$client->ads()->leadForms($workspaceId);
$formId = $client->ads()->createLeadForm(
    workspaceId: $workspaceId,
    connectionId: $connectionId,
    pageId: '555',
    name: 'Newsletter',
    questions: ['EMAIL', 'FULL_NAME'],
    privacyPolicyUrl: 'https://yourbrand.com/privacy',
    thankYouMessage: 'Thanks, talk soon',
);
$leads = $client->ads()->leads($formId, $connectionId, '555');
$more = $client->ads()->leads($formId, $connectionId, '555', after: $leads->nextCursor);
```

## Errors

Every non-2xx response raises an exception under `Fopost\Sdk\Exception`.

| Status | Exception |
| --- | --- |
| 400, 422 | `ValidationException` |
| 401 | `AuthenticationException` |
| 402 | `PaymentRequiredException` |
| 403 | `PermissionDeniedException` |
| 404 | `NotFoundException` |
| 429 | `RateLimitException` |
| anything else | `ApiException` |

All of them extend `FopostException`, which carries `getStatus()`, `getErrorCode()`, `getMessage()`, and `getBody()`.

```php
use Fopost\Sdk\Exception\FopostException;
use Fopost\Sdk\Exception\RateLimitException;
use Fopost\Sdk\Exception\ValidationException;

try {
    $client->posts()->publish('p_123');
} catch (ValidationException $e) {
    print_r($e->getErrors());
} catch (RateLimitException $e) {
    echo 'retry in ', $e->getRetryAfter(), 's', PHP_EOL;
} catch (FopostException $e) {
    echo $e->getStatus(), ' ', $e->getMessage(), PHP_EOL;
}
```

A 429 is retried automatically, up to `maxRetries` attempts, waiting for the interval the API asks for in `Retry-After` (capped at 60 seconds). The exception is raised only when the last attempt still comes back rate limited.

## Anything the SDK does not wrap yet

```php
$body = $client->request('GET', '/some/new/endpoint', params: ['workspace_id' => $workspaceId]);
```

## Testing your integration

The transport is an interface, so nothing has to reach the network in your test suite.

```php
use Fopost\Sdk\Client;
use Fopost\Sdk\Http\Response;
use Fopost\Sdk\Http\Transport;

$fake = new class implements Transport {
    public function send(string $method, string $url, array $headers, ?string $body): Response
    {
        return new Response(200, [], json_encode(['data' => []]));
    }
};

$client = new Client('fop_test_key', Client::DEFAULT_BASE_URL, 30.0, 3, $fake);
```

## Support

Questions and issues: [fopost.com/contact](https://fopost.com/contact) or the [issue tracker](https://github.com/fopost/fopost-php/issues).

## License

MIT. Copyright Porter Bridge, LLC.
