<?php

namespace Chomikuj\Mapper;

use Chomikuj\Entity\Folder;
use Psr\Http\Message\ResponseInterface;

interface FolderMapperInterface
{
	/**
	 * Maps HTML response of folder tree to Folders
	 *
	 * @param ResponseInterface $response
	 * @return Folder
	 */
	public function mapHtmlResponseToFolders(ResponseInterface $response): Folder;
}
