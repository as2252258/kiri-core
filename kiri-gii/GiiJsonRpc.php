<?php

namespace Gii;

class GiiJsonRpc extends GiiBase
{


	/**
	 * @return array
	 */
	public function create(): array
	{
		return [
			$this->createInterface($this->input->getArgument('name')),
			$this->createProducers($this->input->getArgument('name')),
			$this->createConsumer($this->input->getArgument('name')),
		];
	}


	private function createInterface($name)
	{
		$html = '<?php

namespace Rpc;

interface ' . ucfirst($name) . 'RpcInterface
{


}		
		';


		$name = ucfirst($name) . 'RpcInterface.php';
		if (!is_dir(APP_PATH . '/rpc/')) {
			mkdir(APP_PATH . '/rpc/');
		}
		file_put_contents(APP_PATH . '/rpc/' . $name, $html);

		return $name;

	}


	private function createProducers($name)
	{
		$html = '<?php

use Annotation\Target;
use Http\Handler\Controller;
use Kiri\Rpc\Annotation\JsonRpc;
use Rpc\\' . ucfirst($name) . 'RpcInterface;


#[Target]
#[JsonRpc(method: \'test\', version: \'2.0\')]
class ' . ucfirst($name) . 'RpcController extends Controller implements ' . ucfirst($name) . 'RpcInterface
{



}
		
		';

		$name = ucfirst($name) . 'RpcController.php';
		if (!is_dir(APP_PATH . '/rpc/Producers/')) {
			mkdir(APP_PATH . '/rpc/Producers/');
		}
		file_put_contents(APP_PATH . '/rpc/Producers/' . $name, $html);

		return $name;
	}


	private function createConsumer($name)
	{
		$html = '<?php

use Annotation\Target;
use Http\Handler\Controller;
use Kiri\Rpc\Annotation\JsonRpc;
use Rpc\\' . ucfirst($name) . 'RpcInterface;


#[Target]
#[JsonRpc(method: \'test\', version: \'2.0\')]
class ' . ucfirst($name) . 'RpcConsumer extends JsonRpcConsumers implements ' . ucfirst($name) . 'RpcInterface
{



}
		
		';

		$name = ucfirst($name) . 'RpcConsumer.php';
		if (!is_dir(APP_PATH . '/rpc/Consumers/')) {
			mkdir(APP_PATH . '/rpc/Consumers/');
		}
		file_put_contents(APP_PATH . '/rpc/Consumers/' . $name, $html);

		return $name;
	}

}
