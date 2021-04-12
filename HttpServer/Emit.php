<?php


namespace HttpServer;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;


/**
 * Class Emit
 * @package HttpServer
 */
class Emit extends Component
{

	private array $_array = [];


	/**
	 * @param int[] $users
	 * @param string $message
	 * @throws Exception
	 */
	public function emit(array $users, string $message)
	{
		$table = Snowflake::app()->getTable('SYSTEM:ONLINE:PEOPLES');

		foreach ($users as $user) {
			$fd  = $table->get((string)$user, ['clientId']);

			Snowflake::push($fd, $message);
		}

	}


}
