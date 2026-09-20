<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Platforms;

final class WhatsappTest extends TestCase
{
    public function testWhatsappIsOnThePlatformList(): void
    {
        $this->assertContains('whatsapp', Platforms::ALL);
    }

    public function testATemplateCreateReturnsTheReviewStatusThePlatformGaveIt(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'tpl-1',
            'name' => 'order_shipped',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'PENDING',
            'rejectedReason' => null,
            'components' => [],
            'qualityScore' => null,
        ]]);

        $template = $this->client()->whatsapp()->createTemplate(
            'a1',
            'order_shipped',
            'en_US',
            'UTILITY',
            [['type' => 'BODY', 'text' => 'On its way.']],
        );

        $this->assertSame('POST', $this->transport->last()['method']);
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a1/whatsapp/templates',
            $this->transport->last()['url'],
        );
        // Nothing marks a template approved but the platform.
        $this->assertSame('PENDING', $template->status);
        $this->assertSame('order_shipped', $template->name);
    }

    public function testASandboxSessionCarriesOnlyTheLastFourDigits(): void
    {
        $this->transport->push(200, ['data' => [
            'id' => 'ses-1',
            'status' => 'invited',
            'phoneNumberLast4' => '4567',
            'invitedAt' => '2026-09-20T10:00:00Z',
            'activatedAt' => null,
            'expiresAt' => '2026-09-21T10:00:00Z',
        ]]);

        $session = $this->client()->whatsapp()->createSandboxSession('ws', '+15551234567');

        $this->assertSame(
            'https://api.fopost.com/v1/whatsapp/sandbox/sessions',
            $this->transport->last()['url'],
        );
        $this->assertSame('4567', $session->phoneNumberLast4);
        $this->assertSame('invited', $session->status);
    }

    public function testDeletingATemplateNamesItInTheQuery(): void
    {
        $this->transport->push(200, ['data' => ['deleted' => true]]);

        $this->assertTrue($this->client()->whatsapp()->deleteTemplate('a1', 'tpl-1', 'order_shipped'));
        $this->assertSame(
            'https://api.fopost.com/v1/accounts/a1/whatsapp/templates/tpl-1?name=order_shipped',
            $this->transport->last()['url'],
        );
    }
}
