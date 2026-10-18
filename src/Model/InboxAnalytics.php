<?php

declare(strict_types=1);

namespace Fopost\Sdk\Model;

/** Inbox volume and response times for the period. */
final class InboxAnalytics extends Model
{
    /**
     * @param array<string, float|null> $deltas Percentage change vs the previous period: received, sent, answered.
     * @param array<int, array{date: string, received: int, sent: int, answered: int}> $series
     * @param array<int, array{platform: string, received: int, sent: int}> $byPlatform
     * @param array<int, array{type: string, count: int}> $byType
     * @param array<int, array<string, mixed>> $byAccount account_id, platform, username, name, avatar, received, sent
     * @param array<int, array{bucket: string, count: int}> $responseTime
     * @param array<int, array{weekday: int, hour: int, count: int}> $heatmap
     */
    private function __construct(
        array $raw,
        public readonly int $received,
        public readonly int $sent,
        /** Inbound items that got a reply. */
        public readonly int $answered,
        /** Inbound items still unread or read, not resolved. */
        public readonly int $open,
        public readonly int $conversations,
        /** Median minutes from an inbound item to its first reply; null without replies. */
        public readonly ?float $medianResponseMinutes,
        public readonly array $deltas,
        public readonly array $series,
        public readonly array $byPlatform,
        public readonly array $byType,
        public readonly array $byAccount,
        public readonly array $responseTime,
        public readonly array $heatmap,
        public readonly ?string $timeZone,
    ) {
        parent::__construct($raw);
    }

    public static function fromArray(mixed $data): static
    {
        $data = is_array($data) ? $data : [];
        $totals = self::map($data, 'totals');
        $deltas = [];
        foreach (self::map($data, 'deltas') as $metric => $delta) {
            $deltas[(string) $metric] = is_int($delta) || is_float($delta) ? (float) $delta : null;
        }
        $rows = static fn (string $name): array => array_values(array_filter(self::seq($data, $name), is_array(...)));

        return new self(
            $data,
            self::int($totals, 'received') ?? 0,
            self::int($totals, 'sent') ?? 0,
            self::int($totals, 'answered') ?? 0,
            self::int($totals, 'open') ?? 0,
            self::int($totals, 'conversations') ?? 0,
            self::num($totals, 'median_response_minutes'),
            $deltas,
            $rows('series'),
            $rows('by_platform'),
            $rows('by_type'),
            $rows('by_account'),
            $rows('response_time'),
            $rows('heatmap'),
            self::str($data, 'time_zone'),
        );
    }
}
