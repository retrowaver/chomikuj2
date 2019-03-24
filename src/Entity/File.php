<?php

namespace Chomikuj\Entity;

use Chomikuj\Api;

class File
{
	protected $name;
	protected $path;
	protected $size;
	protected $timeUploaded;

	public function __construct($name, $path, $size, $timeUploaded)
	{
		$this
			->setName($name)
			->setPath($path)
			->setSize($size)
			->setTimeUploaded($timeUploaded)
		;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function getTimeUploaded(): \DateTime
	{
		return $this->timeUploaded;
	}

	private function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	private function setPath(string $path): self
	{
		$this->path = $path;

		return $this;
	}

	private function setSize(int $size): self
	{
		$this->size = $size;

		return $this;
	}

	private function setTimeUploaded(\DateTime $timeUploaded): self
	{
		$this->timeUploaded = $timeUploaded;

		return $this;
	}
}