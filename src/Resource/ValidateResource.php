<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\LengthValidation;
use Fopost\Sdk\Model\MediaValidation;
use Fopost\Sdk\Model\PostValidation;
use Fopost\Sdk\Model\SubredditCheck;

/**
 * $client->validate(): check a draft, a text length, or a media URL before publishing.
 *
 * Nothing is stored server-side. Needs the `posts` scope.
 */
final class ValidateResource extends Resource
{
    /**
     * @param array<int, string> $platforms
     * @param array<int, array{url: string, mime_type: string, size?: int}> $media
     */
    public function post(array $platforms, string $content = '', array $media = []): PostValidation
    {
        $body = [
            'content' => $content,
            'media' => array_values($media),
            'platforms' => array_values($platforms),
        ];

        return PostValidation::fromArray(self::unwrap($this->http->post('/validate/post', $body)));
    }

    /** @param array<int, string> $platforms */
    public function length(string $text, array $platforms): LengthValidation
    {
        $body = ['text' => $text, 'platforms' => array_values($platforms)];

        return LengthValidation::fromArray(self::unwrap($this->http->post('/validate/length', $body)));
    }

    /** Fetches the URL server-side; answers 200 even when the file fails a check. */
    public function media(string $url): MediaValidation
    {
        return MediaValidation::fromArray(self::unwrap($this->http->post('/validate/media', ['url' => $url])));
    }

    /**
     * Whether a subreddit exists and takes a post from this Reddit account.
     *
     * The check runs with the account's own token, so $accountId is required.
     */
    public function subreddit(string $accountId, string $name): SubredditCheck
    {
        return SubredditCheck::fromArray(self::unwrap(
            $this->http->get('/validate/subreddit', ['account_id' => $accountId, 'name' => $name]),
        ));
    }
}
