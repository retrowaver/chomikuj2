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

final class ApiFolderTest extends TestCase
{
    public function testCreateFolderSendsProperFields(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            'username',
            [
                FakeApiFactory::getTokenResponse(),
                new Response(200, [], '{"Data":{"Status": 0}}'),
            ],
            $container
        );

        $folderName = 'folder name';
        $api->createFolder($folderName);

        $expectedFields = [
            '__RequestVerificationToken' => 'SOME_TOKEN_VALUE',
            'ChomikName' => 'username',
            'FolderName' => $folderName,
            'FolderId' => '0',
            'AdultContent' => 'false',
            'NewFolderSetPassword' => 'false',
        ];
        parse_str($container[1]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(
            $expectedFields,
            $receivedFields
        );
    }

    public function testCreateFolderThrowsExceptionOnInvalidResponse(): void
    {
        $client = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(200, [], 'invalid, not even JSON'),
                ])
            )
        ]);
        $api = new Api($client);

        $this->expectException(ChomikujException::class);
        $api->createFolder('some folder name');
    }

    public function testRemoveFolderSendsProperFields(): void
    {
        $container = [];
        $api = FakeApiFactory::getApi(
            'username',
            [
                FakeApiFactory::getTokenResponse(),
                new Response(200, [], '{"Data":{"Status": 0}}'),
            ],
            $container
        );

        $folderId = 123;
        $api->removeFolder($folderId);

        $expectedFields = [
            '__RequestVerificationToken' => 'SOME_TOKEN_VALUE',
            'ChomikName' => 'username',
            'FolderId' => (string)$folderId,
        ];
        parse_str($container[1]['request']->getBody()->getContents(), $receivedFields);

        $this->assertEquals(
            $expectedFields,
            $receivedFields
        );
    }

    public function testRemoveFolderThrowsExceptionOnInvalidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], 'invalid, not even JSON'),
            ],
            $container
        );

        $this->expectException(ChomikujException::class);
        $api->removeFolder(123);
    }

    public function testGetFoldersCorrectlyReadsValidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], '{"Status":0,"Html":"<tr><td class=\"T_bg\"><div class=\"T T_pn\"><\/div><\/td><td><a href=\"\/username\/folder1\" rel=\"1\" title=\"Folder 1\" id=\"Ta_1\">Folder 1<\/a><\/td><\/tr><tr><td class=\"T_bg\"><div class=\"T_pn\"><div id=\"Ti_2\" class=\"T_exp\"><\/div><\/div><\/td><td><a href=\"\/username\/folder2\" rel=\"2\" title=\"Folder 2\" id=\"Ta_2\">Folder 2<\/a><\/td><\/tr>"}'),
            ]
        );

        $folders = $api->getFolders('username');

        $this->assertEquals(2, count($folders));
        $this->assertEquals(2, $folders[1]->getId());
        $this->assertEquals('Folder 1', $folders[0]->getName());
        $this->assertEquals('/username/folder1', $folders[0]->getPath());
    }
}