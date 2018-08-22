<?php

namespace Chomikuj;

use Chomikuj\ChomikujException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

class Api implements ApiInterface
{
	const BASE_URL = 'http://chomikuj.pl';
	const URIS = [
		'login' => '/action/Login/TopBarLogin',
		'create_folder' => '/action/FolderOptions/NewFolderAction',
		'remove_folder' => '/action/FolderOptions/DeleteFolderAction',
		'upload_file' => '/action/Upload/GetUrl',
		'move_file' => '/action/FileDetails/MoveFileAction',
		'copy_file' => '/action/FileDetails/CopyFileAction',
		'rename_file' => '/action/FileDetails/EditNameAndDescAction',
		'get_folders' => '/action/tree/loadtree',
	];

	const ERR_INVALID_JSON_RESPONSE = 'Invalid JSON response.';
	const ERR_TOKEN_NOT_FOUND = 'Token was not found.';
	const ERR_FOLDER_NOT_CREATED = 'Folder could not be created.';
	const ERR_FOLDER_NOT_REMOVED = 'Folder could not be removed.';
	const ERR_UPLOAD_URL_NOT_RETURNED = 'Upload url could not be obtained.';
	const ERR_UPLOAD_FAILED = 'Uploading file failed.';
	const ERR_GET_FOLDERS_TREE_FAILED = 'Could not get folders (is username correct?).';
	const ERR_FILE_NOT_MOVED = 'Could not move the file.';
	const ERR_FILE_NOT_COPIED = 'Could not copy the file.';
	const ERR_FILE_NOT_RENAMED = 'Could not rename the file.';
	const ERR_UNKNOWN = 'Unknown error.';

	private $client;
	private $username;

	public function __construct(ClientInterface $client) {
		$this->client = $client;
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

		$json = $this->decodeJson($response->getBody()->getContents());
		if (!isset($json->IsSuccess) || !$json->IsSuccess) {
			throw new ChomikujException($json->Message ?? self::ERR_UNKNOWN);
		}

		$this->username = $username;

		return $this;
	}

	public function logout(): ApiInterface
	{

		return $this;
	}

	public function createFolder(
		$folderName,
		$parentFolderId = 0,
		$adult = false,
		$password = null
	): ApiInterface {
		$response = $this->client->request(
			'POST',
			$this->getUrl('create_folder'),
			[
				'form_params' => [
					'__RequestVerificationToken' => $this->getToken(),
					'ChomikName' => $this->username,
					'FolderName' => $folderName,
					'FolderId' => $parentFolderId,
					'AdultContent' => $adult ? 'true' : 'false', // it has to be like this
					'Password' => $password,
					'NewFolderSetPassword' => $password !== null ? 'true' : 'false',
				],
			]
		);

		$json = $this->decodeJson($response->getBody()->getContents());
		if (!isset($json->Data->Status) || $json->Data->Status !== 0) {
			throw new ChomikujException(self::ERR_FOLDER_NOT_CREATED);
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
					'ChomikName' => $this->username,
					'FolderId' => $folderId,
				],
			]
		);

		$json = $this->decodeJson($response->getBody()->getContents());
		if (!isset($json->Data->Status) || $json->Data->Status !== 0) {
			throw new ChomikujException(self::ERR_FOLDER_NOT_REMOVED);
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
					'accountname' => $this->username,
					'folderid' => $folderId,
				],
			]
		);

		$json = $this->decodeJson($response->getBody()->getContents());

		if (!isset($json->Url)) {
			throw new ChomikujException(self::ERR_UPLOAD_URL_NOT_RETURNED);
		}

		//
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

		// I'm not sure at all whether this proves sufficient.
		if ($response->getStatusCode() !== 200) {
			throw new ChomikujException(self::ERR_UPLOAD_FAILED);
		}

		return $this;
	}

	public function getFoldersByUsername(?string $username = null): array
	{
		$response = $this->client->request(
			'POST',
			$this->getUrl('get_folders'),
			[
				'form_params' => [
					'ChomikName' => $username ?? $this->username
				],
			]
		);

		if ($response->getStatusCode() !== 200) {
			throw new ChomikujException(self::ERR_GET_FOLDERS_TREE_FAILED);
		}

		preg_match_all(
			'/href=\"(.*?)\"\ rel=\"([0-9]+)\"\ title=\"(.*?)\"/',
			$response->getBody()->getContents(),
			$matches
		);

		$folders = [];
		for ($i = 0; $i < count($matches[0]); $i++) {
			$folders[] = [
				'path' => $matches[1][$i],
				'id' => $matches[2][$i],
				'name' => $matches[3][$i],
			];
		}

		return $folders;
	}

	public function moveFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface
	{
		$response = $this->client->request(
			'POST',
			$this->getUrl('move_file'),
			[
				'form_params' => [
					'ChomikName' => $this->username,
					'FileId' => $fileId,
					'FolderId' => $sourceFolderId, // this has to be set
					'FolderTo' => $destinationFolderId,
				],
			]
		);

		$json = $this->decodeJson($response->getBody()->getContents());
		if (!isset($json->Data->Status) || $json->Data->Status !== 'OK') {
			throw new ChomikujException(self::ERR_FILE_NOT_MOVED);
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
					'ChomikName' => $this->username,
					'FileId' => $fileId,
					'FolderId' => $sourceFolderId, // this has to be set
					'FolderTo' => $destinationFolderId,
				],
			]
		);

		$json = $this->decodeJson($response->getBody()->getContents());
		if (!isset($json->Data->Status) || $json->Data->Status !== 'OK') {
			throw new ChomikujException(self::ERR_FILE_NOT_COPIED);
		}

		return $this;
	}

	//changes both filename and description - if newdescription is empty, then it'll be set to empty, erasing anything that was before
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

		$json = $this->decodeJson($response->getBody()->getContents());
		if (!isset($json->Data->Status) || $json->Data->Status !== 'OK') {
			throw new ChomikujException(self::ERR_FILE_NOT_RENAMED);
		}

		return $this;
	}

	private function decodeJson(string $jsonResponse)
	{
		$decoded = json_decode($jsonResponse);
		if ($decoded === null) {
			throw new ChomikujException(self::ERR_INVALID_JSON_RESPONSE);
		}

		return json_decode($jsonResponse);
	}

	private function getUrl(?string $name): string
	{
		switch ($name) {
			case '':
				return self::BASE_URL;
			case 'user_profile':
				return self::BASE_URL . '/' . $this->username;
			default:
				return self::BASE_URL . self::URIS[$name];
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
