<?php

namespace Chomikuj\Mapper;

use Chomikuj\Api;
use Chomikuj\Entity\Folder;
use Chomikuj\Exception\ChomikujException;
use Psr\Http\Message\ResponseInterface;

class FolderMapper implements FolderMapperInterface
{
	public function mapHtmlResponseToFolders(ResponseInterface $response): array
	{
        // Decode JSON
        $json = json_decode($response->getBody()->getContents());
        if ($json === null) {
            // This shouldn't ever be thrown
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        // Extract data from HTML
        // [1] -> path
        // [2] -> id
        // [3] -> name
        preg_match_all(
            '/href=\"(.*?)\"\ rel=\"([0-9]+)\"\ title=\"(.*?)\"/',
            $json->Html,
            $matches
        );

        // Validate data (just so the script won't unexpectedly crash when Chomikuj.pl makes some changes)
        if (empty($matches[1])) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        // Measure depth of first folder, which is always first-level child of folder being mapped
        $firstFolderPath = $matches[1][0];
        $depthIndex = substr_count($firstFolderPath, '/');

        // Create objects
        $folders = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $folderId = (int)$matches[2][$i];
            $folderName = $matches[3][$i];
            $folderPath = $matches[1][$i];

            if (substr_count($folderPath, '/') !== $depthIndex) {
                // Omit deeper folders (they could be used for creating a whole tree with single request,
                // but Chomikuj won't return the full tree when there are too many folders - and it's
                // seemingly hard / impossible to reliably predict which are omitted and which are not)
                continue;
            }

            $folder = new Folder($folderId, $folderName, $folderPath, []);
            $folders[] = $folder;
        }

        return $folders;
	}
}
