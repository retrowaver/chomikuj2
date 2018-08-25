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

    public function testGetFoldersByUsernameCorrectlyReadsValidResponse(): void
    {
        $api = FakeApiFactory::getApi(
            'username',
            [
                new Response(200, [], '<tr><td><div class="T_c"><div id="Ti_0" class="T_col"></div></div></td><td><a href="/username" rel="0" title="username" id="Ta_0">username</a></td></tr><tr id="Tc_0"><td></td><td><table cellspacing="0" cellpadding="0"><tbody><tr><td class="T_bg"><div class="T_pn"><div id="Ti_1" class="T_exp"></div></div></td><td><a href="/username/Dokumenty" rel="1" title="Dokumenty" id="Ta_1">Dokumenty</a></td></tr>'),
            ]
        );

        $rootFolder = $api->getFoldersByUsername();

        $this->assertEquals(1, count($rootFolder->getFolders()));
        $this->assertEquals(1, $rootFolder->getFolders()[0]->getId());
        $this->assertEquals('Dokumenty', $rootFolder->getFolders()[0]->getName());
        $this->assertEquals('/username/Dokumenty', $rootFolder->getFolders()[0]->getPath());
    }
}