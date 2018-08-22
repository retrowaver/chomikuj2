<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Chomikuj\Api;
use Chomikuj\ChomikujException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;

require_once(__DIR__ . '/FakeApiFactory.php');

final class ApiFileTest extends TestCase
{
    public function testMoveFileSendsProperFields(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], '{"Data":{"Status": "OK"}}'),
            ],
            $container
        );

        $fileId = 123;
        $sourceFolderId = 6;
        $destinationFolderId = 9;

        $api->moveFile($fileId, $sourceFolderId, $destinationFolderId);

        $expectedFields = [
            'ChomikName' => 'username',
            'FileId' => (string)$fileId,
            'FolderId' => (string)$sourceFolderId,
            'FolderTo' => (string)$destinationFolderId,
        ];
        parse_str($container[0]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(
            $expectedFields,
            $receivedFields
        );
    }

    public function testMoveFileThrowsExceptionOnInvalidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], 'invalid data'),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->moveFile(123, 6, 9);
    }

    public function testCopyFileSendsProperFields(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], '{"Data":{"Status": "OK"}}'),
            ],
            $container
        );

        $fileId = 123;
        $sourceFolderId = 6;
        $destinationFolderId = 9;

        $api->copyFile($fileId, $sourceFolderId, $destinationFolderId);

        $expectedFields = [
            'ChomikName' => 'username',
            'FileId' => (string)$fileId,
            'FolderId' => (string)$sourceFolderId,
            'FolderTo' => (string)$destinationFolderId,
        ];
        parse_str($container[0]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(
            $expectedFields,
            $receivedFields
        );
    }

    public function testCopyFileThrowsExceptionOnInvalidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], 'invalid data'),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->copyFile(123, 6, 9);
    }

    public function testRenameFileSendsProperFields(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], '{"Data":{"Status": "OK"}}'),
            ],
            $container
        );

        $fileId = 123;
        $name = 'new filename';
        $description = 'new description';

        $api->renameFile($fileId, $name, $description);

        $expectedFields = [
            'FileId' => (string)$fileId,
            'Name' => $name,
            'Description' => $description,
        ];
        parse_str($container[0]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(
            $expectedFields,
            $receivedFields
        );
    }

    public function testRenameFileThrowsExceptionOnInvalidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], 'invalid data'),
            ]
        );

        $this->expectException(ChomikujException::class);
        $api->renameFile(123, 'some filename', 'some description');
    }
}