<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

final class AdsNetworksTest extends TestCase
{
    public function testAuthorizeReachesWhicheverNetworkTheRegistryNamed(): void
    {
        $this->transport->push(200, ['data' => ['url' => 'https://www.linkedin.com/oauth?state=abc']]);

        $url = $this->client()->ads()->authorize('linkedin', 'w_1', returnTo: '/ads');

        $this->assertSame(
            'https://api.fopost.com/v1/ads/connections/linkedin/authorize',
            $this->transport->last()['url'],
        );
        $this->assertSame(['workspaceId' => 'w_1', 'returnTo' => '/ads'], $this->transport->lastJson());
        $this->assertSame('https://www.linkedin.com/oauth?state=abc', $url);
    }

    public function testProvidersCarryWhatEachNetworkSupports(): void
    {
        $this->transport->push(200, ['data' => [[
            'id' => 'linkedin',
            'name' => 'LinkedIn Ads',
            'logo' => 'linkedin',
            'configured' => false,
            'connectMethods' => [],
            'capabilities' => ['campaigns' => true, 'conversions' => true],
            'targetingFacets' => ['country', 'job_title'],
            'trackingMacros' => [['token' => '{{LINKEDIN_CAMPAIGN_ID}}', 'description' => 'The campaign']],
        ]]]);

        $providers = $this->client()->ads()->providers();

        $this->assertCount(1, $providers);
        $this->assertFalse($providers[0]->configured);
        $this->assertTrue($providers[0]->capabilities['conversions']);
        $this->assertSame(['country', 'job_title'], $providers[0]->targetingFacets);
    }

    public function testCompanyRowsTravelWithTheRequest(): void
    {
        $this->transport->push(200, ['data' => ['added' => 2]]);

        $added = $this->client()->ads()->addAudienceCompanies(
            'urn:li:adSegment:44',
            'w_1',
            'conn_1',
            [['domain' => 'northwind.example'], ['name' => 'Contoso']],
        );

        $this->assertSame(2, $added);
        $this->assertSame(
            ['companies' => [['domain' => 'northwind.example'], ['name' => 'Contoso']]],
            $this->transport->lastJson(),
        );
    }

    public function testConversionEventsSendTheIdentityTheApiHashes(): void
    {
        $this->transport->push(200, ['data' => ['accepted' => 1]]);

        $accepted = $this->client()->ads()->sendConversionEvents(
            'urn:li:conversion:9',
            'w_1',
            'conn_1',
            [['happenedAt' => 1758326400000, 'email' => 'buyer@example.test']],
        );

        $this->assertSame(1, $accepted);
        $this->assertStringContainsString(
            '/ads/linkedin/conversion-rules/urn:li:conversion:9/events',
            $this->transport->last()['url'],
        );
    }
}
