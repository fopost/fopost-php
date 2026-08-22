<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Client;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected FakeTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = new FakeTransport();
    }

    protected function client(int $maxRetries = 3): Client
    {
        return new Client('fop_test_key', Client::DEFAULT_BASE_URL, 30.0, $maxRetries, $this->transport);
    }
}
