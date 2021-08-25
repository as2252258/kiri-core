<?php

date_default_timezone_set('Asia/Shanghai');

class Crontab
{

	public bool $isLoop = false;


	const LOOP_TYPE_YEAR = 0;
	const LOOP_TYPE_MONTH = 1;
	const LOOP_TYPE_DAY = 2;
	const LOOP_TYPE_HOUR = 3;
	const LOOP_TYPE_MINUTE = 4;
	const LOOP_TYPE_SECOND = 5;


	public string $crontab = '2021 * * * */2 */5';


	public int $loopType = Crontab::LOOP_TYPE_MINUTE;


	private int $startTime = 0;


	public int $loopTime = 2;


	private int|string $year = 2021;


	private int|string $month = 8;

	private int|string $day = 25;

	private int|string $hour = 19;

	private int|string $minute = 02;

	private int|string $second = 32;


	public function __construct()
	{
		$this->startTime = time();
	}


	/**
	 * @return bool
	 */
	public function canExecute(): bool
	{
		$match = $this->next();
		if (str_contains($match, '^')) {
			return false;
		}
		return true;
	}


	public function next(): string
	{
		if ($this->loopType == Crontab::LOOP_TYPE_YEAR) $this->year = '*/' . $this->loopTime;
		if ($this->loopType == Crontab::LOOP_TYPE_MONTH) $this->month = '*/' . $this->loopTime;
		if ($this->loopType == Crontab::LOOP_TYPE_DAY) $this->day = '*/' . $this->loopTime;
		if ($this->loopType == Crontab::LOOP_TYPE_HOUR) $this->hour = '*/' . $this->loopTime;
		if ($this->loopType == Crontab::LOOP_TYPE_MINUTE) $this->minute = '*/' . $this->loopTime;
		if ($this->loopType == Crontab::LOOP_TYPE_SECOND) $this->second = '*/' . $this->loopTime;

		return sprintf('%s-%s-%s %s:%s:%s',
			$this->format($this->year, 'Y'),
			$this->format($this->month, 'm'),
			$this->format($this->day, 'd'),
			$this->format($this->hour, 'H'),
			$this->format($this->minute, 'i'),
			$this->format($this->second, 's')
		);
	}


	/**
	 * @param string $text
	 * @param string $match
	 * @return string
	 */
	private function format(string $text, string $match): string
	{
		$time = date($match);
		if ($text == '*') {
			return $time;
		}
		if (str_contains($text, ',')) {
			$explode = explode(',', $text);
			sort($explode, SORT_NUMERIC);
			if (in_array($time, $explode)) {
				return $explode[array_search($time, $explode) + 1];
			}
			return '^';
		}
		if (str_contains($text, '-')) {
			$explode = explode('-', $text);
			if ($time >= $explode[0] && $time <= $explode[1]) {
				return intval($time) + 1;
			}
			return '^';
		}
		if (str_contains($text, '/')) {
			$explode = explode('/', $text);
			if ($time % $this->loopTime !== 0) {
				return '^';
			}
			if ($explode[0] != '*') {
				return $explode[0] == $text ? $time : '^';
			}
			return $time;
		}
		return $time == $text ? $time : '^';
	}


}


$c = new Crontab();

while (true) {
	var_dump($c->next());

	sleep(1);
}
