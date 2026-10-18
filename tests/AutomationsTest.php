<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AutomationsTest extends TestCase
{
    public function testAutomationsAreListedCreatedUpdatedToggledAndDeleted(): void
    {
        $automation = [
            'id' => 'au_1',
            'workspace_id' => 'w_1',
            'name' => 'Cross-post',
            'description' => null,
            'trigger_type' => 'content_created',
            'trigger_config' => ['accountIds' => ['a_1']],
            'active' => true,
            'schedule_window' => null,
            'max_per_day' => ['posts' => 5],
            'credit_ceiling' => 50,
            'approval_mode' => false,
            'steps' => [['position' => 0, 'node_id' => 'n1', 'action_type' => 'publish', 'action_config' => []]],
            'run_count' => 3,
            'created_at' => '2026-03-01T09:00:00Z',
            'updated_at' => '2026-03-01T09:00:00Z',
        ];
        $this->transport->push(200, ['data' => [$automation]]);
        $this->transport->push(200, [
            'total_automations' => 1,
            'active_automations' => 1,
            'total_runs_24h' => 2,
            'runs_by_status' => ['completed' => 2],
            'recent_runs' => [['id' => 7, 'automation_id' => 'au_1', 'status' => 'completed']],
        ]);
        $this->transport->push(201, $automation + ['secret' => 'as_once']);
        $this->transport->push(200, $automation);
        $this->transport->push(200, ['id' => 'au_1', 'active' => false]);
        $this->transport->push(204);

        $automations = $this->client()->automations();
        $list = $automations->list();
        $stats = $automations->stats();
        $created = $automations->create(
            'w_1',
            'Cross-post',
            'content_created',
            ['accountIds' => ['a_1']],
            steps: [['actionType' => 'publish', 'actionConfig' => ['accountIds' => ['a_2']]]],
        );
        $updated = $automations->update('au_1', name: 'Cross-post v2', active: false);
        $toggled = $automations->toggle('au_1');
        $automations->delete('au_1');

        $this->assertSame('https://api.fopost.com/v1/automations', $this->transport->requests[0]['url']);
        $this->assertSame('publish', $list[0]->steps[0]->actionType);
        $this->assertSame(['posts' => 5], $list[0]->maxPerDay);
        $this->assertSame(50, $list[0]->creditCeiling);

        $this->assertSame('https://api.fopost.com/v1/automations/stats', $this->transport->requests[1]['url']);
        $this->assertSame(['completed' => 2], $stats->runsByStatus);
        $this->assertSame(7, $stats->recentRuns[0]->id);

        $this->assertSame('POST', $this->transport->requests[2]['method']);
        $this->assertSame([
            'workspaceId' => 'w_1',
            'name' => 'Cross-post',
            'triggerType' => 'content_created',
            'triggerConfig' => ['accountIds' => ['a_1']],
            'steps' => [['actionType' => 'publish', 'actionConfig' => ['accountIds' => ['a_2']]]],
        ], json_decode((string) $this->transport->requests[2]['body'], true));
        $this->assertSame('as_once', $created->secret);

        $this->assertSame('PUT', $this->transport->requests[3]['method']);
        $this->assertSame('https://api.fopost.com/v1/automations/au_1', $this->transport->requests[3]['url']);
        $this->assertSame(
            ['name' => 'Cross-post v2', 'active' => false],
            json_decode((string) $this->transport->requests[3]['body'], true),
        );
        $this->assertSame('au_1', $updated->id);

        $this->assertSame('https://api.fopost.com/v1/automations/au_1/toggle', $this->transport->requests[4]['url']);
        $this->assertFalse($toggled->active);

        $this->assertSame('DELETE', $this->transport->last()['method']);
        $this->assertSame('https://api.fopost.com/v1/automations/au_1', $this->transport->last()['url']);
    }

    public function testAnEmptyTriggerConfigIsSentAsAnObject(): void
    {
        $this->transport->push(201, ['id' => 'au_1', 'name' => 'Daily', 'trigger_type' => 'schedule']);

        $this->client()->automations()->create('w_1', 'Daily', 'schedule');

        $this->assertStringContainsString('"triggerConfig":{}', (string) $this->transport->last()['body']);
    }

    public function testVersionsAreListedReadAndRestored(): void
    {
        $this->transport->push(200, [
            'data' => [[
                'version' => 3,
                'note' => 'Added a delay',
                'created_by' => ['name' => 'Ops'],
                'created_at' => '2026-03-01T09:00:00Z',
                'node_count' => 4,
                'nodes_added' => [['id' => 'n4', 'kind' => 'delay', 'label' => null]],
                'nodes_removed' => [],
            ]],
            'meta' => ['page' => 1, 'per_page' => 20, 'total' => 3, 'total_pages' => 1],
        ]);
        $this->transport->push(200, [
            'version' => 2,
            'note' => null,
            'created_by' => null,
            'created_at' => '2026-02-01T09:00:00Z',
            'graph' => ['nodes' => [['id' => 'n1', 'kind' => 'trigger']], 'edges' => []],
        ]);
        $this->transport->push(200, [
            'version' => 4,
            'graph' => ['nodes' => [['id' => 'n1', 'kind' => 'trigger']], 'edges' => []],
            'steps' => [],
        ]);

        $automations = $this->client()->automations();
        $page = $automations->listVersions('au_1', 1, 20);
        $version = $automations->getVersion('au_1', 2);
        $restored = $automations->restoreVersion('au_1', 2);

        $this->assertSame(
            'https://api.fopost.com/v1/automations/au_1/versions?page=1&per_page=20',
            $this->transport->requests[0]['url'],
        );
        $this->assertSame(3, $page->meta->total);
        $this->assertSame('Ops', $page->items[0]->createdByName);
        $this->assertSame('delay', $page->items[0]->nodesAdded[0]->kind);
        $this->assertNull($page->items[0]->graph);

        $this->assertSame(
            'https://api.fopost.com/v1/automations/au_1/versions/2',
            $this->transport->requests[1]['url'],
        );
        $this->assertSame('n1', $version->graph['nodes'][0]['id']);
        $this->assertNull($version->createdByName);

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/automations/au_1/versions/2/restore',
            $this->transport->last()['url'],
        );
        $this->assertSame(4, $restored->version);
    }

    public function testRunsAreListedAndRead(): void
    {
        $this->transport->push(200, [
            'data' => [['id' => 9, 'status' => 'deferred', 'defer_reason' => 'daily_cap', 'defer_count' => 1]],
            'meta' => ['page' => 1, 'per_page' => 20, 'total' => 1, 'total_pages' => 1],
        ]);
        $this->transport->push(200, [
            'id' => 9,
            'automation_id' => 'au_1',
            'status' => 'completed',
            'started_at' => '2026-03-01T09:00:00Z',
            'logs' => [['id' => 1, 'step_position' => 0, 'status' => 'completed', 'duration_ms' => 12]],
        ]);

        $runs = $this->client()->automations()->listRuns('au_1');
        $run = $this->client()->automations()->getRun('au_1', 9);

        $this->assertSame(
            'https://api.fopost.com/v1/automations/au_1/runs?page=1&per_page=20',
            $this->transport->requests[0]['url'],
        );
        $this->assertSame('daily_cap', $runs->items[0]->deferReason);
        $this->assertSame('https://api.fopost.com/v1/automations/au_1/runs/9', $this->transport->last()['url']);
        $this->assertSame(12, $run->logs[0]->durationMs);
    }
}
