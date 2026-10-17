<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Enrollment;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PageMeta;
use Fopost\Sdk\Model\Sequence;
use Fopost\Sdk\Model\SequenceStep;
use Fopost\Sdk\Undefined;

/**
 * $client->sequences(): a series of messages, each a delay after the one
 * before, walked per enrolled contact.
 *
 * The messaging window applies to every step. A step that comes due outside
 * it is skipped rather than sent, and the enrollment carries on — so someone
 * can complete a sequence having received only some of its messages.
 */
final class SequencesResource extends Resource
{
    /** @return Page<Sequence> */
    public function list(?string $workspaceId = null, int $page = 1, int $perPage = 25): Page
    {
        $body = self::asArray($this->http->get('/sequences', self::compact([
            'workspace_id' => $workspaceId,
            'page' => $page,
            'per_page' => $perPage,
        ])));

        $items = Sequence::listFrom($body['data'] ?? []);
        $meta = isset($body['pagination']) && is_array($body['pagination'])
            ? PageMeta::fromArray($body['pagination'])
            : PageMeta::empty();

        return new Page($items, $meta);
    }

    public function get(string $sequenceId): Sequence
    {
        return Sequence::fromArray(self::unwrap($this->http->get("/sequences/{$sequenceId}")));
    }

    /**
     * Creating a sequence enrolls nobody.
     *
     * @param array<int, SequenceStep|array<string, mixed>> $steps
     */
    public function create(
        string $workspaceId,
        string $accountId,
        string $name,
        array $steps,
        ?string $status = null,
    ): Sequence {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'account_id' => $accountId,
            'name' => $name,
            'steps' => self::steps($steps),
            'status' => $status,
        ]);

        return Sequence::fromArray(self::unwrap($this->http->post('/sequences', $body)));
    }

    /**
     * Partial update. Pausing stops every enrollment from firing without
     * ending any of them; resuming picks them up where they stood.
     *
     * @param array<int, SequenceStep|array<string, mixed>>|Undefined $steps
     */
    public function update(
        string $sequenceId,
        string|Undefined $name = Undefined::Value,
        array|Undefined $steps = Undefined::Value,
        string|Undefined $status = Undefined::Value,
    ): Sequence {
        $body = [];
        if (!Undefined::is($name)) {
            $body['name'] = $name;
        }
        if (!Undefined::is($steps)) {
            /** @var array<int, SequenceStep|array<string, mixed>> $steps */
            $body['steps'] = self::steps($steps);
        }
        if (!Undefined::is($status)) {
            $body['status'] = $status;
        }

        return Sequence::fromArray(
            self::unwrap($this->http->request('PATCH', "/sequences/{$sequenceId}", $body)),
        );
    }

    /**
     * Put contacts on the sequence, by id or by audience.
     *
     * Re-enrolling someone restarts their walk from the first step rather
     * than running two in parallel. Needs the publish scope as well as inbox.
     *
     * @param array<int, string>|null $contactIds
     * @param array<string, mixed>|null $audience
     * @return array<string, mixed>
     */
    public function enroll(string $sequenceId, ?array $contactIds = null, ?array $audience = null): array
    {
        $body = self::compact(['contact_ids' => $contactIds, 'audience' => $audience]);

        return self::asArray(
            self::unwrap($this->http->post("/sequences/{$sequenceId}/enroll", $body)),
        );
    }

    /**
     * Nothing further fires for them. Needs the publish scope.
     *
     * @param array<int, string> $contactIds
     * @return array<string, mixed>
     */
    public function unenroll(string $sequenceId, array $contactIds): array
    {
        return self::asArray(self::unwrap($this->http->post(
            "/sequences/{$sequenceId}/unenroll",
            ['contact_ids' => $contactIds],
        )));
    }

    /**
     * Who is on it, what step they are at, and when the next one is due.
     *
     * @return Page<Enrollment>
     */
    public function enrollments(string $sequenceId, int $page = 1, int $perPage = 50): Page
    {
        $body = self::asArray($this->http->get("/sequences/{$sequenceId}/enrollments", self::compact([
            'page' => $page,
            'per_page' => $perPage,
        ])));

        $items = Enrollment::listFrom($body['data'] ?? []);
        $meta = isset($body['pagination']) && is_array($body['pagination'])
            ? PageMeta::fromArray($body['pagination'])
            : PageMeta::empty();

        return new Page($items, $meta);
    }

    /** Removes the sequence and every enrollment on it. */
    public function delete(string $sequenceId): void
    {
        $this->http->delete("/sequences/{$sequenceId}");
    }

    /**
     * @param array<int, SequenceStep|array<string, mixed>> $steps
     * @return array<int, array<string, mixed>>
     */
    private static function steps(array $steps): array
    {
        return array_values(array_map(
            static fn (SequenceStep|array $s): array => $s instanceof SequenceStep ? $s->toArray() : $s,
            $steps,
        ));
    }
}
