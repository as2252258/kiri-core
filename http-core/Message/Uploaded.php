<?php

namespace Http\Message;

use Exception;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;


class Uploaded implements UploadedFileInterface
{

	const ERROR = [
		0 => "There is no error, the file uploaded with success",
		1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
		2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
		3 => "The uploaded file was only partially uploaded",
		4 => "No file was uploaded",
		6 => "Missing a temporary folder"
	];


	/**
	 * @var resource
	 */
	private mixed $stream;


	/**
	 * @param string $tmp_name
	 * @param string $name
	 * @param string $type
	 * @param int $size
	 * @param int $error
	 */
	public function __construct(
		public string $tmp_name,
		public string $name,
		public string $type,
		public int    $size,
		public int    $error
	)
	{
	}


	/**
	 * @return StreamInterface
	 * @throws Exception
	 */
	public function getStream(): StreamInterface
	{
		if ($this->stream instanceof Stream) {
			return $this->stream;
		}

		$this->stream = new Stream(fopen($this->tmp_name, 'r+'));

		return $this->stream;
	}


	/**
	 * @param string $targetPath
	 * @return StreamInterface
	 * @throws Exception
	 */
	public function moveTo($targetPath): StreamInterface
	{
		@move_uploaded_file($this->tmp_name, $targetPath);
		if (!file_exists($targetPath)) {
			throw new Exception('File save fail.');
		}

		if ($this->stream instanceof Stream) {
			$this->stream->close();
			$this->stream = null;
		}

		$this->tmp_name = $targetPath;
		return $this->getStream();
	}


	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return $this->size;
	}

	/**
	 * @return int
	 */
	public function getError(): int
	{
		return $this->error;
	}


	/**
	 * @return string
	 */
	public function getClientFilename(): string
	{
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function getClientMediaType(): string
	{
		return $this->type;
	}
}
