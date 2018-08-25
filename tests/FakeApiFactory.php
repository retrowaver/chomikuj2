<?php

use Chomikuj\Api;
use Chomikuj\Exception\ChomikujException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;

class FakeApiFactory
{
    public static function getApi(?string $loggedAs = null, ?array $responses = null, ?array &$container = null): Api
    {
        if ($container === null) {
            // Without middleware
            $client = new Client([
                'handler' => HandlerStack::create(
                    new MockHandler($responses)
                ),
                'http_errors' => false,
            ]);
        } else {
            // With middleware
            $history = Middleware::history($container);
            $stack = HandlerStack::create(
                new MockHandler($responses)
            );
            $stack->push($history);
            $client = new Client([
                'handler' => $stack,
                'http_errors' => false,
            ]);
        }

        // Set up Api
        $api = new Api($client);

        // Artificially set username of logged-in user
        if ($loggedAs !== null) {
            $reflection = new \ReflectionClass($api);
            $property = $reflection->getProperty('username');
            $property->setAccessible(true);
            $property->setValue($api, $loggedAs);
        }

        return $api;
    }
    
    public static function getTokenResponse()
    {
        return new Response(200, [], '<input name="__RequestVerificationToken" type="hidden" value="SOME_TOKEN_VALUE" />');
    }
}