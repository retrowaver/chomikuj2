<?php

namespace Chomikuj\Entity;

use Chomikuj\Api;

class Folder
{
	private $id;
	private $name;
	private $path;
	private $folders;

	public function __construct($id, $name, $path, $folders)
	{
		$this
			->setId($id)
			->setName($name)
			->setPath($path)
			->setFolders($folders)
		;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getUrl(): string
	{
		return Api::BASE_URL . $this->path;
	}

	public function getFolders(): array
	{
		return $this->folders;
	}

	public function addFolder(Folder $folder): self
	{
		$this->folders[] = $folder;

		return $this;
	}

	private function setId(int $id): self
	{
		$this->id = $id;

		return $this;
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

	private function setFolders(array $folders): self
	{
		$this->folders = $folders;

		return $this;
	}
}