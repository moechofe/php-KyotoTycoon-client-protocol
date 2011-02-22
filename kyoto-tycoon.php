<?php
namespace KyotoTycoon;

use \RuntimeException, \LogicException;

define('timeout',3);

// {{{ rpc_add()

/**
 * Send a RPC ADD command to a KyotoTycoon server.
 * Add a value to a record if it not already exists.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   string $key = The key of the record.
 *   string $value = The value of the record.
 *   integer,null $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time. If it is omitted, no expiration time is specified.
 *   string,null $DB = The database identifier.
 *   integer $timeout = The timeout before the function will fail.
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish with the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
 */
function rpc_add( $uri, $key, $value, $xt = null, $DB = null, $timeout = timeout )
{
	assert('is_string($uri)');
	assert('is_string($key)');
	assert('is_string($value)');
	assert('is_integer($xt) or is_null($xt)');
	assert('is_string($DB) or is_null($DB)');
	assert('is_integer($timeout)');

	return rpc( $uri, 'add', compact('key','value','xt','DB'), null, $timeout );
}

// }}}
// {{{ rpc_append()

/**
 * Send a RPC APPEND command to a KyotoTycoon server.
 * Append a value to an existing record.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   string $key = The key of the record.
 *   string $value = The value of the record.
 *   integer,null $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time. If it is omitted, no expiration time is specified.
 *   string,null $DB = The database identifier.
 *   integer $timeout = The timeout before the function will fail.
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish with the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
 */
function rpc_append( $uri, $key, $value, $xt = null, $DB = null, $timeout = timeout )
{
	assert('is_string($uri)');
	assert('is_string($key)');
	assert('is_string($value)');
	assert('is_integer($xt) or is_null($xt)');
	assert('is_string($DB) or is_null($DB)');
	assert('is_integer($timeout)');

	return rpc( $uri, 'append', compact('key','value','xt','DB'), null, $timeout );
}

// }}}
// {{{ rpc_cas

/**
 * Send a RPC CAS command to a KyotoTycoon server.
 * Perform compare-and-swap the value of a record.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   string $key = The key of the record.
 *   string $oval = The old value of the record. If it is omittted, no record is meant.
 *   string $nval = The new value. If it is omittted, the record is removed.
 *   integer,null $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time. If it is omitted, no expiration time is specified.
 *   string,null $DB = The database identifier.
 *   integer $timeout = The timeout before the function will fail.
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish with the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
 */
function rpc_cas( $uri, $key, $oval = null, $nval = null, $xt = null, $DB = null, $timeout = timeout )
{
	assert('is_string($uri)');
	assert('is_string($key)');
	assert('is_string($oval) or is_null($oval)');
	assert('is_string($nval) or is_null($nval)');
	assert('is_integer($xt) or is_null($xt)');
	assert('is_string($DB) or is_null($DB)');
	assert('is_integer($timeout)');

	return rpc( $uri, 'cas', compact('key','oval','nval','xt','DB'), null, $timeout );
}

// }}}
// {{{ rpc_clear

/**
 * Send a RPC CLEAR command to a KyotoTycoon server.
 * Remove all records in a database.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   integer $timeout = The timeout before the function will fail.
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish with the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
 */
function rpc_clear( $uri, $DB = null, $timeout = timeout )
{
	assert('is_string($uri)');
	assert('is_string($DB) or is_null($DB)');
	assert('is_integer($timeout)');

	return rpc( $uri, 'clear', compact('DB'), null, $timeout );
}

// }}}
// {{{ rpc_cur_delete

/**
 * Send a RPC CUR DELETE command to a KyotoTycoon server.
 * Delete a cursor implicitly.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   numeric $CUR = The cursor identifier.
 *   integer $timeout = The timeout before the function will fail.
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish with the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
 */
function rpc_cur_delete( $uri, $CUR, $timeout = timeout )
{
	assert('is_string($uri)');
	assert('is_numeric($CUR)');
	assert('is_integer($timeout)');

	return rpc( $uri, 'cur_delete', compact('CUR'), null, $timeout );
}

// }}}
// {{{ rpc_cur_get()

/**
 * Send a RPC CUR GET command to a KyotoTycoon server.
 * Get a pair of the key and the value of the current record.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   numeric $CUR = The cursor identifier.
 *   boolean,null $step = To move the cursor to the next record. If it is omitted, the cursor stays at the current record.
 *   (out) integer,null $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time. If it is omitted, no expiration time is specified.
 *   string,null $DB = The database identifier.
 *   integer $timeout = The timeout before the function will fail.
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish with the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
 */
function rpc_cur_get( $uri, $CUR, $step, &$xt = null, $DB = null, $timeout = timeout )
{
	assert('is_string($uri)');
	assert('is_numeric($CUR)');
	assert('is_boolean($step) or is_null($step)');
	assert('is_string($DB) or is_null($DB)');
	assert('is_integer($timeout)');

	return rpc( $uri, 'cur_get', compact('CUR','step'), function($result) use(&$xt) {
		if( isset($result['xt']) ) $xt = $result['xt'];
	}, $timeout );
}

// }}}
// {{{ rpc()

/**
 * Send a RPC command and pass the result to a callback function.
 * Params:
 *   string $uri = The scheme + host + port, like "http://localhost:1979".
 *   string $command = Can be [add,append,cas,clear,cur_delete,cur_get,cur_get_key,cur_get_value,cur_jump,cur_jump_back,cur_set_value,cur_step,cur_remove,echo,get,get_bulk,increment,increment_double,match_prefix,match_regex,play_script,remove,remove_bulk,replace,report,set,set_bulk,status,synchronize,tune_replication,vacuum]
 *   array(string => string) $data = The pair (key, value) of each arguments to pass.
 *   callable(array) $when_ok = A callback function that been executed when the server succesfully respond.
 *   integer $timeout
 * Return:
 *   true = If success.
 * Throws:
 *   KyotoTycoonConnectionException = If the connection cannot be establish whis the server.
 *   KyotoTycoonProtocolException = If a developpement error occurs.
 *   KyotoTycoonLogicalException = If a inconsistency error occurs.
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
		return true;
	case 400:
		throw new KyotoTycoonProtocolException( "{$uri}/rpc/{$cmd}" );
	case 450:
		throw new KyotoTycoonLogicalException( "{$uri}/rpc/{$cmd}" );
	default:
		throw new LogicException( "Cannot determine server response: $http_response_header[0]" );
	}
}

// }}}
// {{{ KyotoTycoonException, KyotoTycoonConnectionException, KyotoTycoonProtocolException, KyotoTycoonLogicalException

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

class KyotoTycoonLogicalException extends KyotoTycoonException
{
	function __construct( $uri )
	{
		parent::__construct( sprintf('(Un)existing or incompatible record was detected on server <%s>', $uri), self::record_error );
	}
}

// }}}