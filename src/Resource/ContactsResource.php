<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Contact;
use Fopost\Sdk\Model\ContactChannel;
use Fopost\Sdk\Model\ContactConversation;
use Fopost\Sdk\Model\ContactField;
use Fopost\Sdk\Model\ContactImportResult;
use Fopost\Sdk\Model\ConversationAnalytics;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Model\PageMeta;
use Fopost\Sdk\Undefined;

/**
 * $client->contacts(): the people behind the inbox.
 *
 * A contact is one human however many handles they write from. An inbound
 * inbox item files its author, a reply files whoever you answered, and both
 * fold into whatever is already on file.
 */
final class ContactsResource extends Resource
{
    /**
     * One page of contacts, most recently active first.
     *
     * Omit $workspaceId to span every workspace the key can reach; each
     * contact then carries workspaceId.
     *
     * @return Page<Contact>
     */
    public function list(
        ?string $workspaceId = null,
        ?string $search = null,
        ?string $platform = null,
        ?string $source = null,
        int $page = 1,
        int $perPage = 25,
    ): Page {
        $body = self::asArray($this->http->get('/contacts', self::compact([
            'workspace_id' => $workspaceId,
            'search' => $search,
            'platform' => $platform,
            'source' => $source,
            'page' => $page,
            'per_page' => $perPage,
        ])));

        $items = Contact::listFrom($body['data'] ?? []);
        $meta = isset($body['pagination']) && is_array($body['pagination'])
            ? PageMeta::fromArray($body['pagination'])
            : PageMeta::empty();

        return new Page($items, $meta);
    }

    public function get(string $contactId): Contact
    {
        return Contact::fromArray(self::unwrap($this->http->get("/contacts/{$contactId}")));
    }

    /**
     * Create a contact.
     *
     * Folds into the contact that already holds the first channel, so this
     * cannot duplicate someone the inbox has already met.
     *
     * @param array<int, ContactChannel|array<string, mixed>> $channels
     * @param array<string, string>|null $fields
     */
    public function create(
        string $workspaceId,
        array $channels,
        ?string $displayName = null,
        ?string $note = null,
        ?array $fields = null,
    ): Contact {
        $body = self::compact([
            'workspace_id' => $workspaceId,
            'channels' => self::channels($channels),
            'display_name' => $displayName,
            'note' => $note,
            'fields' => $fields,
        ]);

        return Contact::fromArray(self::unwrap($this->http->post('/contacts', $body)));
    }

    /**
     * Partial update: only the fields you pass are sent. A custom field set to
     * null is cleared.
     *
     * @param array<int, ContactChannel|array<string, mixed>>|Undefined $channels
     * @param array<string, string|null>|Undefined $fields
     */
    public function update(
        string $contactId,
        string|null|Undefined $displayName = Undefined::Value,
        array|Undefined $channels = Undefined::Value,
        string|null|Undefined $note = Undefined::Value,
        array|Undefined $fields = Undefined::Value,
    ): Contact {
        $body = [];
        if (!Undefined::is($displayName)) {
            $body['display_name'] = $displayName;
        }
        if (!Undefined::is($channels)) {
            /** @var array<int, ContactChannel|array<string, mixed>> $channels */
            $body['channels'] = self::channels($channels);
        }
        if (!Undefined::is($note)) {
            $body['note'] = $note;
        }
        if (!Undefined::is($fields)) {
            $body['fields'] = $fields;
        }

        return Contact::fromArray(
            self::unwrap($this->http->request('PATCH', "/contacts/{$contactId}", $body)),
        );
    }

    /** Removes a contact. Their messages stay in the inbox and file them again. */
    public function delete(string $contactId): void
    {
        $this->http->delete("/contacts/{$contactId}");
    }

    /**
     * The threads this person appears in, newest first.
     *
     * @return array<int, ContactConversation>
     */
    public function conversations(string $contactId, ?int $limit = null): array
    {
        return ContactConversation::listFrom(self::unwrap(
            $this->http->get("/contacts/{$contactId}/conversations", self::compact(['limit' => $limit])),
        ));
    }

    /**
     * Import from CSV text.
     *
     * `platform` and `handle` are required columns. Any other column is read
     * as a custom field key, and one matching no field is reported back in
     * unknownColumns rather than stored.
     */
    public function import(string $workspaceId, string $csv): ContactImportResult
    {
        return ContactImportResult::fromArray(self::unwrap(
            $this->http->post('/contacts/import', ['workspace_id' => $workspaceId, 'csv' => $csv]),
        ));
    }

    /**
     * The columns this workspace keeps about its contacts, in display order.
     *
     * @return array<int, ContactField>
     */
    public function listFields(string $workspaceId): array
    {
        return ContactField::listFrom(self::unwrap(
            $this->http->get('/contacts/fields', ['workspace_id' => $workspaceId]),
        ));
    }

    /** @param array<int, string> $options */
    public function createField(
        string $workspaceId,
        string $key,
        string $name,
        string $type = 'text',
        array $options = [],
    ): ContactField {
        $query = http_build_query(['workspace_id' => $workspaceId]);

        return ContactField::fromArray(self::unwrap($this->http->post(
            "/contacts/fields?{$query}",
            ['key' => $key, 'name' => $name, 'type' => $type, 'options' => $options],
        )));
    }

    /**
     * The key and the type are fixed once created; the name and options are not.
     *
     * @param array<int, string>|Undefined $options
     */
    public function updateField(
        string $fieldId,
        string|Undefined $name = Undefined::Value,
        array|Undefined $options = Undefined::Value,
        int|Undefined $position = Undefined::Value,
    ): ContactField {
        $body = [];
        if (!Undefined::is($name)) {
            $body['name'] = $name;
        }
        if (!Undefined::is($options)) {
            $body['options'] = $options;
        }
        if (!Undefined::is($position)) {
            $body['position'] = $position;
        }

        return ContactField::fromArray(
            self::unwrap($this->http->request('PATCH', "/contacts/fields/{$fieldId}", $body)),
        );
    }

    /** Removes the field and every answer to it. */
    public function deleteField(string $fieldId): void
    {
        $this->http->delete("/contacts/fields/{$fieldId}");
    }

    /**
     * Volume and median reply time per thread.
     *
     * Needs the analytics scope rather than inbox.
     */
    public function conversationAnalytics(
        ?string $workspaceId = null,
        ?string $accountId = null,
        ?int $days = null,
        ?string $sort = null,
        int $page = 1,
        int $perPage = 25,
    ): ConversationAnalytics {
        return ConversationAnalytics::fromArray(self::unwrap(
            $this->http->get('/analytics/inbox/conversations', self::compact([
                'workspace_id' => $workspaceId,
                'accountId' => $accountId,
                'days' => $days,
                'sort' => $sort,
                'page' => $page,
                'per_page' => $perPage,
            ])),
        ));
    }

    /**
     * @param array<int, ContactChannel|array<string, mixed>> $channels
     * @return array<int, array<string, mixed>>
     */
    private static function channels(array $channels): array
    {
        return array_values(array_map(
            static fn (ContactChannel|array $c): array => $c instanceof ContactChannel ? $c->toArray() : $c,
            $channels,
        ));
    }
}
