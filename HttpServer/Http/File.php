<?php
declare(strict_types=1);

namespace HttpServer\Http;

use Exception;
use Kiri\Kiri;

/**
 * Class File
 */
class File
{

	public string $name = '';
	public mixed $tmp_name = '';
	public mixed $error = '';
	public mixed $type = '';
	public mixed $size = '';


	private string $_content = '';

	private string $newName = '';
	private array $errorInfo = [
		0 => 'UPLOAD_ERR_OK.',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		3 => 'The uploaded file was only partially uploaded.',
		4 => 'No file was uploaded.',
		6 => 'Missing a temporary folder.',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.'
	];

	/**
	 * @param string $path
	 * @return bool
	 * @throws Exception
	 */
	public function saveTo(string $path): bool
	{
		if ($this->hasError()) {
			throw new Exception($this->getErrorInfo());
		}

		@move_uploaded_file($this->tmp_name, $path);
		if (!file_exists($path)) {
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function rename(): string
	{
		if (!empty($this->newName)) {
			return $this->newName;
		}

		if (!file_exists($this->getTmpPath())) {
			throw new Exception('(' . $this->name . ')Failed to open stream: No such file or directory');
		}

		return $this->name;
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function getContent(): string
	{
		$open = fopen($this->getTmpPath(), 'r');

//		@move_uploaded_file($this->tmp_name, storage($this->name));

		$limit = 1024000;

		$stat = fstat($open);

		$sleep = $offset = 0;
		$content = '';
		while ($file = fread($open, $limit)) {
			$content .= $file;
			fseek($open, $offset);
			if ($sleep > 0) {
				sleep($sleep);
			}
			if ($offset >= $stat['size']) {
				break;
			}
			$offset += $limit;
		}
		return $content;
	}


	/**
	 * @return string
	 */
	public function getTmpPath(): string
	{
		return $this->tmp_name;
	}

	/**
	 * @return bool
	 *
	 * check file have error
	 */
	public function hasError(): bool
	{
		return $this->error !== 0;
	}

	/**
	 * @return mixed
	 *
	 * get upload error info
	 */
	public function getErrorInfo(): mixed
	{
		if (!isset($this->errorInfo[$this->error])) {
			return 'Unknown upload error.';
		}
		return $this->errorInfo[$this->error];
	}
}
