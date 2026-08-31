<?php

declare(strict_types=1);

namespace Fopost\Sdk\Tests;

use Fopost\Sdk\Exception\ApiException;
use Fopost\Sdk\Exception\AuthenticationException;
use Fopost\Sdk\Exception\FopostException;
use Fopost\Sdk\Exception\NotFoundException;
use Fopost\Sdk\Exception\PaymentRequiredException;
use Fopost\Sdk\Exception\PermissionDeniedException;
use Fopost\Sdk\Exception\RateLimitException;
use Fopost\Sdk\Exception\ValidationException;

final class ErrorsTest extends TestCase
{
    public function testA401BecomesAnAuthenticationException(): void
    {
        $this->transport->push(401, ['error' => 'unauthorized', 'message' => 'Invalid API key']);

        try {
            $this->client()->workspaces()->list();
            $this->fail('expected an AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertInstanceOf(FopostException::class, $e);
            $this->assertSame(401, $e->getStatus());
            $this->assertSame('unauthorized', $e->getErrorCode());
            $this->assertSame('Invalid API key', $e->getMessage());
        }
    }

    public function testA404BecomesANotFoundException(): void
    {
        $this->transport->push(404, ['error' => 'not_found', 'message' => 'Post not found']);

        try {
            $this->client()->posts()->get('p_missing');
            $this->fail('expected a NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getStatus());
            $this->assertSame('not_found', $e->getErrorCode());
            $this->assertSame('Post not found', $e->getMessage());
        }
    }

    public function testA422BecomesAValidationException(): void
    {
        $this->transport->push(422, [
            'error' => 'validation_error',
            'message' => 'content is required',
            'errors' => ['content' => ['required']],
        ]);

        try {
            $this->client()->posts()->create('w_1', 'hello');
            $this->fail('expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getStatus());
            $this->assertSame('validation_error', $e->getErrorCode());
            $this->assertSame('content is required', $e->getMessage());
            $this->assertSame(['content' => ['required']], $e->getErrors());
        }
    }

    public function testA400AlsoBecomesAValidationException(): void
    {
        $this->transport->push(400, ['error' => 'validation_error', 'message' => 'name is required']);

        $this->expectException(ValidationException::class);
        $this->client()->labels()->create('w_1', '');
    }

    public function testA402CarriesTheUpgradeUrl(): void
    {
        $this->transport->push(402, [
            'error' => 'insufficient_credits',
            'message' => 'Out of AI credits',
            'upgrade_url' => 'https://fopost.com/dashboard/billing',
        ]);

        try {
            $this->client()->ai()->credits();
            $this->fail('expected a PaymentRequiredException');
        } catch (PaymentRequiredException $e) {
            $this->assertSame(402, $e->getStatus());
            $this->assertSame('https://fopost.com/dashboard/billing', $e->getUpgradeUrl());
        }
    }

    public function testA403BecomesAPermissionDeniedException(): void
    {
        $this->transport->push(403, ['error' => 'forbidden', 'message' => 'Missing scope']);

        $this->expectException(PermissionDeniedException::class);
        $this->client()->workspaces()->list();
    }

    public function testAn500BecomesAnApiException(): void
    {
        $this->transport->push(500, ['error' => 'server_error']);

        try {
            $this->client()->workspaces()->list();
            $this->fail('expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->getStatus());
            $this->assertSame('server_error', $e->getMessage());
        }
    }

    public function testANonJsonSuccessBodyIsAnApiException(): void
    {
        $this->transport->push(200, '<html>nope</html>', ['content-type' => 'text/html']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Expected a JSON response, got text/html');
        $this->client()->workspaces()->list();
    }

    public function testTheStringFormCarriesTheStatusAndCode(): void
    {
        $e = new FopostException('boom', 418, 'teapot');
        $this->assertSame('[418 (teapot)] boom', (string) $e);
        $this->assertSame('[418] boom', (string) new FopostException('boom', 418));
    }

    public function testRateLimitIsItsOwnException(): void
    {
        $this->transport->push(429, ['error' => 'rate_limited', 'message' => 'Slow down']);

        $this->expectException(RateLimitException::class);
        $this->client(1)->workspaces()->list();
    }
}
