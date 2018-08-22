<?php

namespace Chomikuj;

interface ApiInterface
{
	public function login(string $username, string $password): ApiInterface;
	//public function logout(): ApiInterface;

	public function createFolder(
		string $folderName,
		int $parentFolderId,
		bool $adult,
		?string $password
	): ApiInterface;

	public function removeFolder(int $folderId): ApiInterface;

	public function uploadFile(int $folderId, string $filePath): ApiInterface;

	public function getFoldersByUsername(?string $username): array;

	public function moveFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface;

	public function copyFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface;

	public function renameFile(int $fileId, string $newFilename, string $newDescription): ApiInterface;
}
