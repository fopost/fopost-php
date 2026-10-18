<?php

declare(strict_types=1);

namespace Fopost\Sdk\Resource;

use Fopost\Sdk\Model\Automation;
use Fopost\Sdk\Model\AutomationRestore;
use Fopost\Sdk\Model\AutomationRun;
use Fopost\Sdk\Model\AutomationStats;
use Fopost\Sdk\Model\AutomationToggle;
use Fopost\Sdk\Model\AutomationVersion;
use Fopost\Sdk\Model\Page;
use Fopost\Sdk\Undefined;

/**
 * $client->automations(): triggers and the step chains they run.
 *
 * Request bodies are camelCase. Needs the `automations` scope. The webhook
 * trigger endpoint is not here: it is called with the automation's own
 * secret, not an API key.
 */
final class AutomationsResource extends Resource
{
    /** @return array<int, Automation> */
    public function list(): array
    {
        return Automation::listFrom(self::unwrap($this->http->get('/automations')));
    }

    public function stats(): AutomationStats
    {
        return AutomationStats::fromArray(self::unwrap($this->http->get('/automations/stats')));
    }

    public function get(string $automationId): Automation
    {
        return Automation::fromArray(self::unwrap($this->http->get("/automations/{$automationId}")));
    }

    /**
     * Give `$steps` as an ordered list of `{actionType, actionConfig}` or `$graph` as
     * `{nodes, edges}`; the graph wins when both are given. A webhook trigger's secret
     * comes back once on `->secret`.
     *
     * @param array<string, mixed> $triggerConfig
     * @param array<int, array<string, mixed>>|null $steps
     * @param array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}|null $graph
     * @param array<string, mixed> $extra
     */
    public function create(
        string $workspaceId,
        string $name,
        string $triggerType,
        array $triggerConfig = [],
        ?array $steps = null,
        ?array $graph = null,
        ?string $description = null,
        ?bool $active = null,
        array $extra = [],
    ): Automation {
        $body = [
            'workspaceId' => $workspaceId,
            'name' => $name,
            'triggerType' => $triggerType,
            'triggerConfig' => (object) $triggerConfig,
        ] + self::compact([
            'steps' => $steps !== null ? array_values($steps) : null,
            'graph' => $graph,
            'description' => $description,
            'active' => $active,
        ]) + $extra;

        return Automation::fromArray(self::unwrap($this->http->post('/automations', $body)));
    }

    /**
     * Partial update: only the fields you pass are sent. A new `$steps` or `$graph` saves a version.
     *
     * @param array<string, mixed>|Undefined $triggerConfig
     * @param array<int, array<string, mixed>>|Undefined $steps
     * @param array<string, mixed>|Undefined $graph
     * @param array<string, mixed> $extra
     */
    public function update(
        string $automationId,
        string|Undefined $name = Undefined::Value,
        string|null|Undefined $description = Undefined::Value,
        string|Undefined $triggerType = Undefined::Value,
        array|Undefined $triggerConfig = Undefined::Value,
        array|Undefined $steps = Undefined::Value,
        array|Undefined $graph = Undefined::Value,
        bool|Undefined $active = Undefined::Value,
        array $extra = [],
    ): Automation {
        $body = [];
        if (!Undefined::is($name)) {
            $body['name'] = $name;
        }
        if (!Undefined::is($description)) {
            $body['description'] = $description;
        }
        if (!Undefined::is($triggerType)) {
            $body['triggerType'] = $triggerType;
        }
        if (!Undefined::is($triggerConfig)) {
            /** @var array<string, mixed> $triggerConfig */
            $body['triggerConfig'] = (object) $triggerConfig;
        }
        if (!Undefined::is($steps)) {
            /** @var array<int, array<string, mixed>> $steps */
            $body['steps'] = array_values($steps);
        }
        if (!Undefined::is($graph)) {
            $body['graph'] = $graph;
        }
        if (!Undefined::is($active)) {
            $body['active'] = $active;
        }
        $body = array_merge($body, $extra);

        return Automation::fromArray(self::unwrap($this->http->put("/automations/{$automationId}", $body)));
    }

    public function delete(string $automationId): void
    {
        $this->http->delete("/automations/{$automationId}");
    }

    /** Flip the automation between active and paused. */
    public function toggle(string $automationId): AutomationToggle
    {
        return AutomationToggle::fromArray(self::unwrap($this->http->post("/automations/{$automationId}/toggle")));
    }

    /**
     * Saved versions of the graph, newest first, with what each one added and removed.
     *
     * @return Page<AutomationVersion>
     */
    public function listVersions(string $automationId, int $page = 1, int $perPage = 20): Page
    {
        $query = ['page' => $page, 'per_page' => $perPage];

        return self::page(AutomationVersion::class, $this->http->get("/automations/{$automationId}/versions", $query));
    }

    /** One version with its full graph. */
    public function getVersion(string $automationId, int $version): AutomationVersion
    {
        return AutomationVersion::fromArray(
            self::unwrap($this->http->get("/automations/{$automationId}/versions/{$version}")),
        );
    }

    /** Make that version's graph current again, as a new version. */
    public function restoreVersion(string $automationId, int $version): AutomationRestore
    {
        return AutomationRestore::fromArray(
            self::unwrap($this->http->post("/automations/{$automationId}/versions/{$version}/restore")),
        );
    }

    /**
     * Runs of the automation, newest first.
     *
     * @return Page<AutomationRun>
     */
    public function listRuns(string $automationId, int $page = 1, int $perPage = 20): Page
    {
        $query = ['page' => $page, 'per_page' => $perPage];

        return self::page(AutomationRun::class, $this->http->get("/automations/{$automationId}/runs", $query));
    }

    /** One run with its step logs. */
    public function getRun(string $automationId, int $runId): AutomationRun
    {
        return AutomationRun::fromArray(self::unwrap($this->http->get("/automations/{$automationId}/runs/{$runId}")));
    }
}
