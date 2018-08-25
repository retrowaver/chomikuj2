<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Chomikuj\Api;
use Chomikuj\Exception\ChomikujException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;

require_once(__DIR__ . '/FakeApiFactory.php');

final class ApiLogoutTest extends TestCase
{
    public function testLogoutResetsUsernamePropertyToNull(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], 'whatever'),
            ]
        );

        $api->logout();

        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('username');
        $property->setAccessible(true);

        $this->assertEquals(null, $property->getValue($api));
    }

    public function testLogoutThrowsExceptionOnInvalidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(400, [], 'definitely not 200'),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->logout();
    }
}

