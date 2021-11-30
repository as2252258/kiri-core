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


	private function createInterface($name): string
	{
		$html = '<?php

namespace Rpc;


interface ' . ucfirst($name) . 'RpcInterface
{


}';


		$name = ucfirst($name) . 'RpcInterface.php';
		if (!is_dir(APP_PATH . '/rpc/')) {
			mkdir(APP_PATH . '/rpc/');
		}
		file_put_contents(APP_PATH . '/rpc/' . $name, $html);

		return $name;

	}


	private function createProducers($name): string
	{
		$html = '<?php

namespace Rpc\Producers;


use Note\Target;
use Note\Mapping;
use Rpc\\' . ucfirst($name) . 'RpcInterface;
use Exception;
use Kiri\Rpc\JsonRpcConsumers;


#[Target]
#[Mapping(' . ucfirst($name) . 'RpcInterface::class)]
class ' . ucfirst($name) . 'RpcService extends JsonRpcConsumers implements ' . ucfirst($name) . 'RpcInterface
{

	protected string $name = \'' . $name . '\';



}';

		$name = ucfirst($name) . 'RpcService.php';
		if (!is_dir(APP_PATH . '/rpc/Producers/')) {
			mkdir(APP_PATH . '/rpc/Producers/');
		}
		file_put_contents(APP_PATH . '/rpc/Producers/' . $name, $html);

		return $name;
	}


	private function createConsumer($name): string
	{
		$html = '<?php

namespace Rpc\Consumers;


use Note\Target;
use Kiri\Rpc\Note\JsonRpc;
use Http\Handler\Controller;
use Rpc\\' . ucfirst($name) . 'RpcInterface;


#[Target]
#[JsonRpc(service: \'' . $name . '\', version: \'2.0\')]
class ' . ucfirst($name) . 'RpcConsumer extends Controller implements ' . ucfirst($name) . 'RpcInterface
{



}';

		$name = ucfirst($name) . 'RpcConsumer.php';
		if (!is_dir(APP_PATH . '/rpc/Consumers/')) {
			mkdir(APP_PATH . '/rpc/Consumers/');
		}
		file_put_contents(APP_PATH . '/rpc/Consumers/' . $name, $html);

		return $name;
	}

}
