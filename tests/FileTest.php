<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Chomikuj\Entity\File;

final class FileTest extends TestCase
{
	public function testCanBeCreatedWithValidData(): void
	{
		$this->assertInstanceOf(
            File::class,
            new File('filename', '/path/filename', 123456, new \DateTime('1999-09-11'))
        );
	}

	public function testCreatedWithValidDataReturnsValidValues(): void
	{
		$filename = 'filename';
		$path = '/path/filename';
		$size = 123456;
		$timeUploaded = new \DateTime('1999-09-11');

		$file = new File($filename, $path, $size, $timeUploaded);

		$this->assertEquals($filename, $file->getName());
		$this->assertEquals($path, $file->getPath());
		$this->assertEquals($size, $file->getSize());
		$this->assertEquals($timeUploaded, $file->getTimeUploaded());
	}
}