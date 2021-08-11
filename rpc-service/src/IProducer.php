<?php


namespace Rpc;


interface IProducer
{


	/**
	 * @return null|Client
	 * 初始化一个客户端
	 */
	public function initClient(): ?Client;


}
