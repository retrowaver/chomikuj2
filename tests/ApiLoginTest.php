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

final class ApiLoginTest extends TestCase
{
    public function testLoginSendsProperFields(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            null,
            [
                new Response(200, [], '{"IsSuccess":true}'),
            ],
            $container
        );

        // Do the test
        $username = 'username';
        $password = 'password';

        $api->login($username, $password);

        $expectedFields = [
            'Login' => $username,
            'Password' => $password,
        ];
        parse_str($container[0]['request']->getBody()->getContents(), $receivedFields);

        //
        $this->assertEquals(
            $expectedFields,
            $receivedFields
        );
    }

    public function testLoginReturnsSelfOnValidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            null,
            [
                new Response(200, [], '{"IsSuccess":true}'),
            ],
            $container
        );
        
        $this->assertInstanceOf(
            Api::class,
            $api->login('username', 'password')
        );
    }

    public function testLoginThrowsExceptionOnInvalidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            null,
            [
                new Response(200, [], 'invalid, not even JSON'),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->login('username', 'password');
    }
}

