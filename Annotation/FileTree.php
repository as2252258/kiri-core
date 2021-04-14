<?php


namespace Annotation;


class FileTree
{

	private array $files = [];


	private array $childes = [];


	private string $_filePath = '';


	/**
	 * @param $path
	 * @return $this|null
	 */
	public function getChild($path): ?static
	{
		if (!isset($this->childes[$path])) {
			$this->addChild($path, new FileTree());
		}
		return $this->childes[$path];
	}


	/**
	 * @param string $path
	 * @param FileTree $fileTree
	 */
	public function addChild(string $path, FileTree $fileTree)
	{
		$this->childes[$path] = $fileTree;
	}


	/**
	 * @param string $className
	 * @param string $path
	 */
	public function addFile(string $className, string $path)
	{
		$this->files[] = $className;
		$this->_filePath = $path;
	}


	/**
	 * @return array
	 */
	public function getFiles(): array
	{
		return $this->files;
	}


	/**
	 * @return string
	 */
	public function getDirPath(): string
	{
		var_dump($this->_filePath);
		return $this->_filePath;
	}


	/**
	 * @return array
	 */
	public function getChildes(): array
	{
		return $this->childes;
	}


}
