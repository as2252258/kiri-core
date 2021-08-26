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


	public int $loopTime = 2;


	private int|string $month = '*';

	private int|string $day = '*';

	private int|string $hour = '*';

	private int|string $minute = '*/2';

	private int|string $second = '1-30';

	private int|string $week = '*';


	public function __construct()
	{
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
		$time = time();
		return sprintf('%s-%s-%s %s:%s:%s %s',
			date('Y'),
			$this->format($time, $this->month, 'm', 'month'),
			$this->format($time, $this->day, 'd', 'day'),
			$this->format($time, $this->hour, 'H', 'hour'),
			$this->format($time, $this->minute, 'i', 'minute'),
			$this->format($time, $this->second, 's', 'second'),
			$this->format($time, $this->week, 'N'),
		);
	}


	/**
	 * @param int $startTime
	 * @param string $text
	 * @param string $match
	 * @param string|null $format
	 * @return string
	 */
	private function format(int &$startTime, string $text, string $match, ?string $format = null): string
	{
		$time = date($match);
		if ($text == '*' || $text == '*/1') {
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
				return intval($time);
			}
			return '^';
		}
		if (str_contains($text, '/')) {
			$explode = explode('/', $text);
			if ($time % $explode[1] !== 0) {
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

//$date = date('Y-m-d H:i:s');
//var_dump(date('Y-m-d H:i:s', strtotime('+10 month', strtotime($date))));
//var_dump(date('Y-m-d H:i:s', strtotime('+10 day', strtotime($date))));
//var_dump(date('Y-m-d H:i:s', strtotime('+10 hour', strtotime($date))));
//var_dump(date('Y-m-d H:i:s', strtotime('+10 minute', strtotime($date))));
//var_dump(date('Y-m-d H:i:s', strtotime('+10 second', strtotime($date))));
//var_dump(date('Y-m-d H:i:s', strtotime('+10 week', strtotime($date))));

$c = new Crontab();

while (true) {
	var_dump($c->next());

	sleep(1);
}
