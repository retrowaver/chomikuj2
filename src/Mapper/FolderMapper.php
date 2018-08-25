<?php

namespace Chomikuj\Mapper;

use Chomikuj\Api;
use Chomikuj\Entity\Folder;
use Chomikuj\Exception\ChomikujException;
use Psr\Http\Message\ResponseInterface;

class FolderMapper implements FolderMapperInterface
{
	public function mapHtmlResponseToFolders(ResponseInterface $response): Folder
	{
		// Extract data from HTML
        // [1] -> path
        // [2] -> id
        // [3] -> name
        preg_match_all(
            '/href=\"(.*?)\"\ rel=\"([0-9]+)\"\ title=\"(.*?)\"/',
            $response->getBody()->getContents(),
            $matches
        );

        // Validate data (just so the script won't easily crash when Chomikuj.pl makes some changes)
        if (empty($matches[1])) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        if (substr_count($matches[1][0], '/') !== 1) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }
        $root = $matches[1][0];

        for ($i = 1, $c = count($matches[0]); $i < $c; $i++) {
            $depth = substr_count($matches[1][$i], '/');

            if ($depth < 2) {
                throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
            }

            if ($i < $c - 1 && substr_count($matches[1][$i + 1], '/') - $depth > 1) {
                throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
            }

            if (strpos($matches[1][$i], $root) !== 0) {
                throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
            }
        }

        // Create objects
        $folders = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $folder = new Folder((int)$matches[2][$i], $matches[3][$i], $matches[1][$i], []);
            $folders[] = $folder;
        }

        // Build tree
        $stack = [array_shift($folders)];
        $depth = 1;
        foreach ($folders as $folder) {
            $folderDepth = substr_count($folder->getPath(), '/');
            if ($folderDepth === $depth) {
                // Replace the last element on stack and also add it to a folder one level above
                array_pop($stack);
                end($stack)->addFolder($folder);
                array_push($stack, $folder);
            } elseif ($folderDepth > $depth) {
                // Add to the last element on the stack, and push it on the stack
                end($stack)->addFolder($folder);
                array_push($stack, $folder);
                $depth++;
            } else {
                // Remove all unnecessary elements from the stack, alter depth accordingly, and...
                $diff = $depth - $folderDepth;
                $stack = array_splice($stack, 0, -$diff);
                $depth -= $diff;

                // ...replace the last element on the stack and also add it to a folder one level above
                array_pop($stack);
                end($stack)->addFolder($folder);
                array_push($stack, $folder);
            }
        }

        return array_shift($stack);
	}
}
