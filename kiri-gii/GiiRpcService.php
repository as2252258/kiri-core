<?php


namespace Gii;


use Exception;
use Kiri\Kiri;

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

		$port = $this->input->get('port', 443);

		$html = '<?php
		
		
namespace App\Http\Rpc;

use Annotation\Route\RpcProducer;
use Annotation\Target;
use Exception;
use Http\Controller;
use Http\Exception\RequestException;
use Kiri\Core\Json;

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
	 * @param array $params
	 * @throws Exception
	 */
	#[RpcProducer(cmd: \'default\', port: ' . $port . ')]
	public function actionIndex(array $params)
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

		Kiri::writeFile($file, $html);
		return [$managerName . 'Producer.php'];
	}
}