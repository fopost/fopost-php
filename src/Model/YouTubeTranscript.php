<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One caption track read back as text; $transcript is SRT. */
final class YouTubeTranscript extends Model
{
    private function __construct(
        array $raw,
        public readonly string $captionId,
        public readonly string $transcript,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];

        return new self(
            $data,
            self::requiredStr($data, 'caption_id'),
            self::requiredStr($data, 'transcript'),
        );
    }
}
