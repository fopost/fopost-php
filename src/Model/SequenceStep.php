<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** One message and how long after the previous step it goes out. */
final class SequenceStep extends Model
{
    private function __construct(
        array $raw,
        /** Hours to wait after the previous step; 0 on the first means straight away. */
        public readonly float $delayHours,
        public readonly string $text,
        public readonly ?string $mediaId,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $delay = self::field($data, 'delay_hours');

        return new self(
            $data,
            is_int($delay) || is_float($delay) ? (float) $delay : 0.0,
            self::str($data, 'text') ?? '',
            self::str($data, 'media_id'),
        );
    }

    /** The wire shape a write sends. */
    public static function make(float $delayHours, string $text, ?string $mediaId = null): self
    {
        return self::fromArray(array_filter(
            ['delay_hours' => $delayHours, 'text' => $text, 'media_id' => $mediaId],
            static fn (mixed $v): bool => $v !== null,
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['delay_hours' => $this->delayHours, 'text' => $this->text];
        if ($this->mediaId !== null) {
            $out['media_id'] = $this->mediaId;
        }

        return $out;
    }
}
