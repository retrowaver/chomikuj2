<?php

namespace Chomikuj\Mapper;

use Chomikuj\Entity\Folder;
use Psr\Http\Message\ResponseInterface;

interface FolderMapperInterface
{
    /**
     * Maps HTML response of folder tree to array of Folders
     *
     * @param ResponseInterface $response
     * @return array
     */
    public function mapHtmlResponseToFolders(ResponseInterface $response): array;
}
