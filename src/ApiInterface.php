<?php

namespace Chomikuj;

use Chomikuj\Entity\Folder;

interface ApiInterface
{
    /**
     * Logs in using provided credentials
     *
     * @param string $username
     * @param string $password
     * @throws ChomikujException if request failed
     * @return self
     */
    public function login(string $username, string $password): ApiInterface;

    /**
     * Logs out
     *
     * @return self
     */
    public function logout(): ApiInterface;

    /**
     * Returns first level subfolders of specified folder of specified user
     *
     * @param string $username use null for currently logged in user
     * @param int $folderId use 0 for root folder
     * @throws ChomikujException if request failed
     * @return Folder[]
     */
    public function getFolders(?string $username, int $folderId);

    /**
     * Creates folder of provided name
     *
     * @param string $folderName
     * @param int $parentFolderId use 0 for root folder
     * @param bool $adult true for nsfw content
     * @param string|null $password if set, folder will be password-protected
     * @throws ChomikujException if request failed
     * @return self
     */
    public function createFolder(string $folderName, int $parentFolderId, bool $adult, ?string $password): ApiInterface;

    /**
     * Removes folder of provided id
     *
     * @param int $folderId
     * @throws ChomikujException if request failed
     * @return self
     */
    public function removeFolder(int $folderId): ApiInterface;

    /**
     * Uploads a file
     *
     * @param int $folderId
     * @param string $filePath
     * @throws ChomikujException if request failed
     * @return self
     */
    public function uploadFile(int $folderId, string $filePath): ApiInterface;

    /**
     * Moves a file between folders
     *
     * @param int $fileId
     * @param int $sourceFolderId
     * @param int $destinationFolderId
     * @throws ChomikujException if request failed
     * @return self
     */
    public function moveFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface;

    /**
     * Copies a file from source folder to destination folder
     *
     * @param int $fileId
     * @param int $sourceFolderId
     * @param int $destinationFolderId
     * @throws ChomikujException if request failed
     * @return self
     */
    public function copyFile(int $fileId, int $sourceFolderId, int $destinationFolderId): ApiInterface;

    /**
     * Changes name and description of a file
     *
     * @param int $fileId
     * @param string $newFilename
     * @param string $newDescription
     * @throws ChomikujException if request failed
     * @return self
     */
    public function renameFile(int $fileId, string $newFilename, string $newDescription): ApiInterface;
}
