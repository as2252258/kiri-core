<?php

namespace Server\Message;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;


/**
 *
 */
class Uploaded implements UploadedFileInterface
{

	public string $tmp_name;


	public string $name;


	public string $type;


	public string $size;


	public int $error;

	const ERROR = [
		0 => "There is no error, the file uploaded with success",
		1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
		2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
		3 => "The uploaded file was only partially uploaded",
		4 => "No file was uploaded",
		6 => "Missing a temporary folder"
	];


	/**
	 * @param mixed $file
	 */
	public function __construct(array $file)
	{
		$this->tmp_name = $file['tmp_name'];
		$this->name = $file['name'];
		$this->type = $file['type'];
		$this->size = $file['size'];
		$this->error = $file['error'];
	}


	/**
	 * @return StreamInterface
	 */
	public function getStream(): StreamInterface
	{
		return new Stream(file_get_contents($this->tmp_name));
	}


	/**
	 * @param string $targetPath
	 */
	public function moveTo($targetPath)
	{
		@move_uploaded_file($this->tmp_name, $targetPath);
	}

	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getError(): string
	{
		return Uploaded::ERROR[$this->error] ?? 'unknown error';
	}


	/**
	 * @return string|null
	 */
	public function getClientFilename(): string|null
	{
		return $this->name;
	}


	/**
	 * @return string|null
	 */
	public function getClientMediaType(): string|null
	{
		return $this->type;
	}
}
