<?php

namespace Chomikuj\Mapper;

use Chomikuj\Entity\File;
use Psr\Http\Message\ResponseInterface;

interface FileMapperInterface
{
	/**
	 * Maps HTML response of folder tree to array of Files
	 *
	 * @param ResponseInterface $response
	 * @return File[]
	 */
	public function mapSearchResponseToFiles(ResponseInterface $response): array;
}
