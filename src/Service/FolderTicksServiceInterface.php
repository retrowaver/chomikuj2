<?php

namespace Chomikuj\Service;

interface FolderTicksServiceInterface
{
	/**
	 * Get ticks value for username
	 *
	 * @param string $username
     * @param bool $forceUpdate update cached version
	 * @return string
	 */
	public function getTicks(string $username, bool $forceUpdate = false): string;
}
