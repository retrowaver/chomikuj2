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

final class ApiSearchTest extends TestCase
{
    public function testSearchSendsProperFieldsInMinimumCase(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            null,
            [
                new Response(200, [], 'no results'),
            ],
            $container
        );

        $fileName = 'some filename';

        $api->findFiles($fileName);

        parse_str($container[0]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(0, $receivedFields['IsGallery']);
        $this->assertEquals(1, $receivedFields['Page']);
        $this->assertEquals($fileName, $receivedFields['FileName']);
    }

    public function testSearchSendsProperFieldsInMaximumCase(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            null,
            [
                new Response(200, [], 'no results'),
            ],
            $container
        );

        $page = 23;
        $fileName = 'some file name';
        $fileType = 'music';
        $sizeFrom = 12;
        $sizeTo = 34;
        $extension = 'flac';
        $adult = 0;
        $onAccount = 1;
        $username = 'some_username';

        $api->findFiles($fileName, [
            'FileType' => $fileType,
            'SizeFrom' => $sizeFrom,
            'SizeTo' => $sizeTo,
            'Extension' => $extension,
            'ShowAdultContent' => $adult,
            'SearchOnAccount' => $onAccount,
            'TargetAccountName' => $username
        ], $page);

        parse_str($container[0]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(0, $receivedFields['IsGallery']);
        $this->assertEquals($page, $receivedFields['Page']);
        $this->assertEquals($fileName, $receivedFields['FileName']);
        $this->assertEquals($fileType, $receivedFields['FileType']);
        $this->assertEquals($sizeFrom, $receivedFields['SizeFrom']);
        $this->assertEquals($sizeTo, $receivedFields['SizeTo']);
        $this->assertEquals($extension, $receivedFields['Extension']);
        $this->assertEquals($adult, $receivedFields['ShowAdultContent']);
        $this->assertEquals($onAccount, $receivedFields['SearchOnAccount']);
        $this->assertEquals($username, $receivedFields['TargetAccountName']);
    }

    public function testSearchThrowsExceptionOnBadRequestResponse(): void
    {
        $api = FakeApiFactory::getApi(
            null,
            [
                new Response(400),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->findFiles('some file', [], 1);
    }

    public function testSearchThrowsExceptionOnMalformedResponse(): void
    {
        $api = FakeApiFactory::getApi(
            null,
            [
                // a row with no file data
                new Response(200, [], '...<div id="listView"><div class="filerow alt fileItemContainer"></div></div>...'),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->findFiles('some file', [], 1);
    }
}