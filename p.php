<?php
$A_DEFAULT = [
	'Source'       => '',
	'Package'      => '',
	'Path'         => '',
	'Content-Type' => '',
	'Method'       => ''
];
$A_DEFAULT_1 = [
	'REQUEST tcp/other.protocol v1.0',
	'Source'       => '127.0.0.1, 134.43.54.64',
	'Package'      => 'qat',
	'Path'         => 'getUserDetail',
	'Content-Type' => 'application/json',
	'Method'       => 'rpcRequest',
	'Meth21131od'       => 'rpcRequest',
	'Met231hod'       => 'rpcRequest',
	'Meth1231od'       => 'rpcRequest',
	'Meth12312od'       => 'rpcRequest',
];

var_dump(array_diff_key($A_DEFAULT, $A_DEFAULT_1));
