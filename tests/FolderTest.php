<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Chomikuj\Entity\Folder;

final class FolderTest extends TestCase
{
	public function testCanBeCreatedWithValidData(): void
	{
		$this->assertInstanceOf(
            Folder::class,
            new Folder(123, 'foldername', '/path', [])
        );
	}
}