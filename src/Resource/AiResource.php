<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\AiCreditBalance;
use Fopost\Sdk\Model\CaptionResult;
use Fopost\Sdk\Model\RepurposeResult;
use Fopost\Sdk\Model\RewriteResult;

/**
 * $client->ai(): caption assist, per platform rewriting, and blog fan out.
 *
 * Every call spends AI credits. Check the balance with credits(); a
 * PaymentRequiredException means the plan has none left.
 */
final class AiResource extends Resource
{
    /** Credits remaining, used, and total for the current billing period. */
    public function credits(): AiCreditBalance
    {
        return AiCreditBalance::fromArray(self::unwrap($this->http->get('/ai/credits')));
    }

    /**
     * @param array<int, string>|null $imageUrls
     * @param array<int, string>|null $platforms
     */
    public function generateCaption(
        ?string $currentCaption = null,
        ?array $imageUrls = null,
        ?array $platforms = null,
        ?int $charLimit = null,
        ?string $workspaceId = null,
        ?string $brandVoiceId = null,
    ): CaptionResult {
        $body = self::compact([
            'current_caption' => $currentCaption,
            'image_urls' => $imageUrls !== null ? array_values($imageUrls) : null,
            'platforms' => $platforms !== null ? array_values($platforms) : null,
            'char_limit' => $charLimit,
            'workspace_id' => $workspaceId,
            'brand_voice_id' => $brandVoiceId,
        ]);

        return CaptionResult::fromArray(self::unwrap($this->http->post('/ai/generate-caption', $body)));
    }

    /**
     * Rewrite one draft for each target platform. Costs 1 credit per platform.
     *
     * @param array<int, string> $platforms
     */
    public function rewrite(
        string $content,
        array $platforms,
        ?string $tone = null,
        ?string $workspaceId = null,
        ?string $brandVoiceId = null,
    ): RewriteResult {
        $body = self::compact([
            'content' => $content,
            'platforms' => array_values($platforms),
            'tone' => $tone,
            'workspace_id' => $workspaceId,
            'brand_voice_id' => $brandVoiceId,
        ]);

        return RewriteResult::fromArray(self::unwrap($this->http->post('/ai/rewrite', $body)));
    }

    /**
     * Turn an article URL into a post for each platform, in one call.
     *
     * @param array<int, string> $platforms
     */
    public function repurposeUrl(
        string $url,
        array $platforms,
        ?string $workspaceId = null,
        ?string $brandVoiceId = null,
    ): RepurposeResult {
        $body = self::compact([
            'url' => $url,
            'platforms' => array_values($platforms),
            'workspace_id' => $workspaceId,
            'brand_voice_id' => $brandVoiceId,
        ]);

        return RepurposeResult::fromArray(self::unwrap($this->http->post('/ai/repurpose-url', $body)));
    }
}
