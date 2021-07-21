<?php

namespace PHPSTORM_META {

    // Reflect
	use Snowflake\Di\Container;

	override(Container::get(0), map('@'));
//    override(\Hyperf\Utils\Context::get(0), map('@'));
//    override(\make(0), map('@'));
    override(\di(0), map('@'));
    override(\duplicate(0), map('@'));

}
