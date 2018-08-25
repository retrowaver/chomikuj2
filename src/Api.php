<?php

namespace Chomikuj;

use Chomikuj\Exception\ChomikujException;
use Chomikuj\Entity\Folder;
use Chomikuj\Mapper\FolderMapperInterface;
use Chomikuj\Mapper\FolderMapper;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;

class Api implements ApiInterface
{
    const BASE_URL = 'https://chomikuj.pl';
    const URIS = [
        'login' => '/action/Login/TopBarLogin',
        'logout' => '/action/Login/LogOut',
        'create_folder' => '/action/FolderOptions/NewFolderAction',
        'remove_folder' => '/action/FolderOptions/DeleteFolderAction',
        'upload_file' => '/action/Upload/GetUrl',
        'move_file' => '/action/FileDetails/MoveFileAction',
        'copy_file' => '/action/FileDetails/CopyFileAction',
        'rename_file' => '/action/FileDetails/EditNameAndDescAction',
        'get_folders' => '/action/tree/loadtree',
    ];
    const ERR_REQUEST_FAILED = 'Request failed.';
    const ERR_WEIRD_RESPONSE = 'Response looks valid, but could not be read.';
    const ERR_TOKEN_NOT_FOUND = 'Token could not be found.';

    private $client;
    private $username;

    public function __construct(
        ?ClientInterface $client = null,
        FolderMapperInterface $folderMapper = null
    ) {
        if ($client === null) {
            $client = new Client([
                'cookies' => new CookieJar,
                'allow_redirects' => false,
                'http_errors' => false,
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
            ]);
        }

        if ($folderMapper === null) {
            $folderMapper = new FolderMapper;
        }

        $this->client = $client;
        $this->folderMapper = $folderMapper;
    }

    public function login(string $username, string $password): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('login'),
            [
                'form_params' => [
                    'Login' => $username,
                    'Password' => $password,
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_issuccess_one')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        $this->setUsername($username);

        return $this;
    }

    public function logout(): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('logout')
        );

        if (!$this->wasRequestSuccessful($response, 'status_200')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        $this->setUsername(null);

        return $this;
    }

    public function createFolder(
        string $folderName,
        int $parentFolderId = 0,
        bool $adult = false,
        ?string $password = null
    ): ApiInterface {
        $response = $this->client->request(
            'POST',
            $this->getUrl('create_folder'),
            [
                'form_params' => [
                    '__RequestVerificationToken' => $this->getToken(),
                    'ChomikName' => $this->getUsername(),
                    'FolderName' => $folderName,
                    'FolderId' => $parentFolderId,
                    'AdultContent' => $adult ? 'true' : 'false', // it has to be like this
                    'Password' => $password,
                    'NewFolderSetPassword' => $password !== null ? 'true' : 'false',
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_data_status_zero')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this;
    }

    public function removeFolder(int $folderId): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('remove_folder'),
            [
                'form_params' => [
                    '__RequestVerificationToken' => $this->getToken(),
                    'ChomikName' => $this->getUsername(),
                    'FolderId' => $folderId,
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_data_status_zero')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this;
    }

    public function uploadFile(int $folderId, string $filePath): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('upload_file'),
            [
                'form_params' => [
                    'accountname' => $this->getUsername(),
                    'folderid' => $folderId,
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_url')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        $json = json_decode($response->getBody()->getContents());

        $response = $this->client->request(
            'POST',
            $json->Url,
            [
                'multipart' => [
                    [
                        'name' => 'files',
                        'contents' => fopen($filePath, 'r')
                    ]
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'status_200')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this;
    }

    public function getFoldersByUsername(?string $username = null): Folder
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('get_folders'),
            [
                'form_params' => [
                    'ChomikName' => $username ?? $this->getUsername()
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'status_200')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this->folderMapper->mapHtmlResponseToFolders($response);
    }

    public function moveFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('move_file'),
            [
                'form_params' => [
                    'ChomikName' => $this->getUsername(),
                    'FileId' => $fileId,
                    'FolderId' => $sourceFolderId, // this has to be set
                    'FolderTo' => $destinationFolderId,
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_data_status_ok')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this;
    }

    public function copyFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('copy_file'),
            [
                'form_params' => [
                    'ChomikName' => $this->getUsername(),
                    'FileId' => $fileId,
                    'FolderId' => $sourceFolderId, // this has to be set
                    'FolderTo' => $destinationFolderId,
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_data_status_ok')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this;
    }

    public function renameFile(int $fileId, string $newFilename, string $newDescription): ApiInterface
    {
        $response = $this->client->request(
            'POST',
            $this->getUrl('rename_file'),
            [
                'form_params' => [
                    'FileId' => $fileId,
                    'Name' => $newFilename,
                    'Description' => $newDescription,
                ],
            ]
        );

        if (!$this->wasRequestSuccessful($response, 'json_data_status_ok')) {
            throw new ChomikujException(self::ERR_REQUEST_FAILED);
        }

        return $this;
    }

    private function getUsername(): ?string
    {
        return $this->username;
    }

    private function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * Validates response
     *
     * Chomikuj.pl is extremely inconsistent when it comes to responses. Sometimes they return JSON, sometimes plain HTML, sometimes no body at all. Even for JSON responses there are at least several ways to mark it as successful.
     *
     * @param ResponseInterface $response
     * @param string $type
     * @return bool
     */
    private function wasRequestSuccessful(ResponseInterface $response, string $type): bool
    {
        $json = json_decode($response->getBody()->getContents());
        $response->getBody()->rewind();

        switch ($type) {
            case 'json_data_status_ok':
                return (isset($json->Data->Status) && $json->Data->Status === 'OK');
            case 'json_data_status_zero':
                return (isset($json->Data->Status) && $json->Data->Status === 0);
            case 'json_url':
                return isset($json->Url);
            case 'json_issuccess_one':
                return (isset($json->IsSuccess) && $json->IsSuccess === true);
            case 'status_200':
                return ($response->getStatusCode() === 200);
        }
    }

    /**
     * Gets URL that can be used to make a HTTP request
     *
     * @param string|null $identifier
     * @return string
     */
    private function getUrl(?string $identifier): string
    {
        switch ($identifier) {
            case '':
                return self::BASE_URL;
            case 'user_profile':
                return self::BASE_URL . '/' . $this->getUsername();
            default:
                return self::BASE_URL . self::URIS[$identifier];
        }
    }

    private function getToken(): string
    {
        $response = $this->client->request(
            'GET',
            $this->getUrl('user_profile'),
            [
                'headers' => [
                    'X-Requested-With' => null
                ],
            ]
        );

        preg_match(
            '/__RequestVerificationToken(?:.*?)value=\"(.*?)\"/',
            $response->getBody()->getContents(),
            $matches
        );

        if (empty($matches[1])) {
            throw new ChomikujException(self::ERR_TOKEN_NOT_FOUND);
        }

        return $matches[1];
    }
}
