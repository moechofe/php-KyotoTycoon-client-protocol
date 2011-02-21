<?php
namespace KyotoTycoon;

use \RuntimeException, LogicException;

class KyotoTycoonException extends RuntimeException
{
	const connection_error = 0x01;
	const protocol_error = 0x02;
	const record_error = 0x03;
}

class KyotoTycoonConnectionException extends KyotoTycoonException
{
	function __construct( $uri )
	{
		parent::__construct( sprintf('Couldn\'t connect to database server <%s>', $uri), self::connection_error );
	}
}

class KyotoTycoonProtocolException extends KyotoTycoonException
{
	function __construct( $uri )
	{
		parent::__construct( sprintf('Bad request sent to server <%s>', $uri), self::protocol_error );
	}
}

class KyotoTycoonRecordException extends KyotoTycoonException
{
	function __construct( $uri )
	{
		parent::__construct( sprintf('(Un)existing or incompatible record was detected on server <%s>', $uri), self::record_error );
	}
}

/**
 * Send a RPC command and pass the result to a callback function.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   string $command = Can be [add,append,cas,clear,cur_delete,cur_get,cur_get_key,cur_get_value,cur_jump,cur_jump_back,cur_set_value,cur_step,cur_remove,echo,get,get_bulk,increment,increment_double,match_prefix,match_regex,play_script,remove,remove_bulk,replace,report,set,set_bulk,status,synchronize,tune_replication,vacuum]
 *   array(string => string) $data = The pair (key, value) of each arguments to pass.
 *   callable(array) $when_ok = A callback function that been executed when the server succesfully respond.
 *   integer $timeout
 */
function rpc( $uri, $cmd, $data, $when_ok, $timeout )
{
	assert('is_string($uri)');
	assert('in_array($cmd,array("add","append","cas","clear","cur_delete","cur_get","cur_get_key","cur_get_value","cur_jump","cur_jump_back","cur_set_value","cur_step","cur_remove","echo","get","get_bulk","increment","increment_double","match_prefix","match_regex","play_script","remove","remove_bulk","replace","report","set","set_bulk","status","synchronize","tune_replication","vacuum")');
	assert('count($data)==count(array_filter(array_keys($data),"is_string"))');
	assert('count($data)==count(array_filter($data,"is_string"))');
	assert('is_callable($when_ok) or is_null($when_ok)');

	if( false === ($result = explode("\n",trim(@file_get_contents( "{$uri}/rpc/{$cmd}", false, stream_context_create(array('http'=>array(
		'method' => 'POST',
		'content' => $data = http_build_query($data),
		'header' => sprintf("Content-Type: application/x-www-form-urlencoded\r\nContent-Length: %s",strlen($data)),
		'timeout' => $timeout,
 		'ignore_errors' => true ))) )))) )
		throw new KyotoTycoonConnectionException( "{$uri}/rpc/{$cmd}" );


	switch( substr($http_response_header[0],9,3) )
	{
	case 200:
		if( $when_ok ) $when_ok(array_combine(
			array_map(function($v){return substr($v,0,strpos($v,"\t"));},$result),
			array_map(function($v){return substr($v,strpos($v,"\t")+1);},$result) ));
		return $this;
	case 400:
		throw new KyotoTycoonProtocolException( "{$uri}/rpc/{$cmd}" );
	case 450:
		throw new KyotoTycoonRecordException( "{$uri}/rpc/{$cmd}" );
	default:
		throw new LogicException( "Cannot determine server response: $http_response_header[0]" );
	}
}