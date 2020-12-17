<?php
declare(strict_types=1);


namespace Snowflake\Core;

/**
 * Class Reader
 * @package Snowflake\Snowflake\Core
 */
class Reader
{


	/**
	 * @param $filepath
	 * @param int $page
	 * @param int $size
	 * @return array and int
	 */
	public static function readerServerLogPagination($filepath, $page = 1, $size = 20): array
	{
		$count = 0;
		$strings = [];
		$offset = ($page - 1) * $size;

		if (!file_exists($filepath)) {
			return [0, []];
		}

		//只读方式打开文件
		$fp = fopen($filepath, "r");

		//开始循环读取$buffer_size
		while (!feof($fp)) {
			//读文件到缓冲区
			$buffer = fgets($fp);
			$count++;
			if ($count > $offset && count($strings) < $size) {
				$strings[] = [
					'id'      => $count,
					'content' => $buffer
				];
			}
		}
		//关闭文件
		fclose($fp);
		unset($fp);

		return ['total' => $count, 'list' => $strings];
	}

	/**
	 * @param $filename
	 * @param $start
	 * @param $lines
	 * @return mixed
	 */
	public static function read_backward_line($filename, $start, $lines): mixed
	{
		$lines++;
		$offset = -1;
		$read = '';
		$fp = @fopen($filename, "r");

		$tmpStart = 0;
		while ($lines && fseek($fp, $offset, SEEK_END) >= 0) {

			$c = fgetc($fp);
			if ($c == "\n" || $c == "\r") {
				if (++$tmpStart >= $start)
					$lines--;
			}


			if ($tmpStart >= $start)
				$read .= $c;
			$offset--;
		}

		$read = trim($read);

		$contents = [];
		$read = array_reverse(explode("\n", strrev($read)));
		foreach ($read as $key => $value) {
			if (empty($value)) {
				unset($read[$key]);
			} else {
				$contents[] = ['content' => $value, 'id' => $key + $start];
			}
		}

		$response['total'] = self::read_count_by_file($filename);
		$response['list'] = $contents;

		return $response;
	}

	/**
	 * @param $filepath
	 * @return int
	 */
	private static function read_count_by_file($filepath): int
	{
		$count = 0;
		//只读方式打开文件
		$fp = fopen($filepath, "r");

		//开始循环读取$buffer_size
		while (fgets($fp)) {
			$count++;
		}
		//关闭文件
		fclose($fp);
		unset($fp);

		return $count;
	}

	/**
	 * @param $filepath
	 * @param int $page
	 * @param int $size
	 * @return array
	 */
	public static function folderPagination($filepath, $page = 1, $size = 20): array
	{
		$count = 0;
		$strings = [];
		$offset = ($page - 1) * $size;

		if (!is_dir($filepath)) {
			return [0, []];
		}

		foreach (glob($filepath . '/*') as $key => $value) {
			$count++;
			if ($key < $offset || count($strings) >= $size) {
				continue;
			}
			$explode = explode(DIRECTORY_SEPARATOR, $value);

			$addTime = fileatime($value);
			$changeTime = filectime($value);
			$modifyTime = filemtime($value);
			$strings[] = [
				'id'    => $count,
				'path'  => $value,
				'isDir' => (int)is_dir($value),
				'name'  => end($explode),
				'atime' => [
					'format'    => date('Y-m-d H:i:s', $addTime),
					'microtime' => $addTime
				],
				'ctime' => [
					'format'    => date('Y-m-d H:i:s', $changeTime),
					'microtime' => $changeTime
				],
				'mtime' => [
					'format'    => date('Y-m-d H:i:s', $modifyTime),
					'microtime' => $modifyTime
				],
			];
		}

		array_multisort($strings, array_column($strings, 'isDir'), SORT_DESC);

		return ['total' => $count, 'list' => $strings];
	}


}
