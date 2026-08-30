<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class LabelsTest extends TestCase
{
    public function testListSendsTheSnakeCaseWorkspaceParam(): void
    {
        $this->transport->push(200, ['data' => [['id' => 'l_1', 'name' => 'Launch', 'color' => '#0070f3']]]);

        $labels = $this->client()->labels()->list('w_1');

        $this->assertSame('https://api.fopost.com/v1/labels?workspace_id=w_1', $this->transport->last()['url']);
        $this->assertSame('Launch', $labels[0]->name);
        $this->assertSame('#0070f3', $labels[0]->color);
    }

    public function testCreatePostsTheLabel(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'l_1', 'name' => 'Launch']]);

        $label = $this->client()->labels()->create('w_1', 'Launch', '#0070f3');

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/labels', $this->transport->last()['url']);
        $this->assertSame(
            ['workspace_id' => 'w_1', 'name' => 'Launch', 'color' => '#0070f3'],
            $this->transport->lastJson(),
        );
        $this->assertSame('l_1', $label->id);
    }

    public function testUpdateSendsOnlyTheFieldsGiven(): void
    {
        $this->transport->push(200, ['data' => ['id' => 'l_1', 'name' => 'Renamed']]);

        $this->client()->labels()->update('l_1', 'Renamed');

        $this->assertSame('PUT', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/labels/l_1', $this->transport->last()['url']);
        $this->assertSame(['name' => 'Renamed'], $this->transport->lastJson());
    }

    public function testDeleteRemovesTheLabel(): void
    {
        $this->transport->push(204);

        $this->client()->labels()->delete('l_1');

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/labels/l_1', $this->transport->last()['url']);
    }
}
