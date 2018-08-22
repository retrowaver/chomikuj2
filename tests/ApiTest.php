<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Chomikuj\Api;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

final class ApiTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler
            )
        ]);

        $this->assertInstanceOf(
            Api::class,
            new Api($client)
        );
    }

    public function testCannotBeCreatedWithWrongTypeArgument(): void
    {
        $this->expectException(TypeError::class);
        new Api('certainly not GuzzleHttp\Client');
    }
}
