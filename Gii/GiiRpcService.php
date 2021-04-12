<?php


namespace Gii;


use Exception;
use Snowflake\Snowflake;

/**
 * Class GiiRpcClient
 * @package Gii
 */
class GiiRpcService extends GiiBase
{

	/**
	 * @return array
	 * @throws Exception
	 */
	public function generate(): array
	{

		$managerName = $this->input->get('name', null);
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}

		$service = $this->input->get('service', strtolower($managerName));

		$port = $this->input->get('port', 443);

		$html = '<?php
		
		
namespace App\Http\Rpc;

use Annotation\Route\RpcProducer;
use Annotation\Target;
use Exception;
use HttpServer\Controller;
use HttpServer\Exception\RequestException;
use Snowflake\Core\Json;

';

		$managerName = ucfirst($managerName);
		$html .= '
 /**
 * Class ' . $managerName . 'Consumer
 * @package App\Client\Rpc
 */
#[Target]
class ' . $managerName . 'Producer extends Controller
{


	/**
	 * @param Users $users
	 * @param string $event
	 * @param array $params
	 * @throws Exception
	 */
	#[RpcProducer(cmd: \'default\', port: ' . $port . ')]
	public function actionIndex()
	{
		
	}
}';

		if (!is_dir(APP_PATH . 'app/Http/Rpc/')) {
			mkdir(APP_PATH . 'app/Http/Rpc/', 0777, true);
		}

		$file = APP_PATH . 'app/Http/Rpc/' . $managerName . 'Producer.php';
		if (file_exists($file)) {
			throw new Exception('File exists.');
		}

		Snowflake::writeFile($file, $html);
		return [$managerName . 'Producer.php'];
	}
}
