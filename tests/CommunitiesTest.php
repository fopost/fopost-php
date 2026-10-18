<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class CommunitiesTest extends TestCase
{
    public function testCommunitiesAreListedSyncedSearchedAddedAndRemoved(): void
    {
        $community = [
            'id' => 12,
            'account_id' => 'a_1',
            'community_id' => '1700000000000000000',
            'name' => 'PHP Builders',
            'member_count' => 420,
            'description' => null,
            'image_url' => null,
            'last_synced_at' => '2026-03-01T09:00:00Z',
            'created_at' => '2026-03-01T09:00:00Z',
        ];
        $this->transport->push(200, ['data' => [$community]]);
        $this->transport->push(200, ['data' => [$community], 'note' => 'synced']);
        $this->transport->push(200, ['data' => [['id' => '17', 'name' => 'PHP', 'member_count' => 9]]]);
        $this->transport->push(201, $community);
        $this->transport->push(204);

        $accounts = $this->client()->accounts();
        $list = $accounts->listCommunities('a_1');
        $synced = $accounts->syncCommunities('a_1');
        $matches = $accounts->searchCommunities('a_1', 'PHP');
        $added = $accounts->addCommunity('a_1', '1700000000000000000', 'PHP Builders');
        $accounts->removeCommunity('a_1', 12);

        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/communities', $this->transport->requests[0]['url']);
        $this->assertSame(12, $list[0]->id);
        $this->assertSame('POST', $this->transport->requests[1]['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/communities/sync',
            $this->transport->requests[1]['url'],
        );
        $this->assertSame(420, $synced[0]->memberCount);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/communities/search?q=PHP',
            $this->transport->requests[2]['url'],
        );
        $this->assertSame('17', $matches[0]->id);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a_1/communities/manual',
            $this->transport->requests[3]['url'],
        );
        $this->assertSame(
            ['communityId' => '1700000000000000000', 'name' => 'PHP Builders'],
            json_decode((string) $this->transport->requests[3]['body'], true),
        );
        $this->assertSame('1700000000000000000', $added->communityId);
        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/accounts/a_1/communities/12', $this->transport->last()['url']);
    }
}
