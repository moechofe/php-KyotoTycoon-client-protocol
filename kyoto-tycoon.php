<?php

declare(encoding='UTF-8');

namespace
{

	function kt( $uri = 'http://localhost:1978' )
	{
		assert('is_array(parse_url($uri))');
		return new KyotoTycoon\API( $uri );
	}

}

namespace KyotoTycoon
{

	class Exception extends \RuntimeException	{}
	class ConnectionException extends Exception
	{
		function __construct( $uri, $code, $msg )
		{
			parent::__construct( "Could'nt connect to KyotoTycoon server {$uri}. {$msg}", $code );
		}
	}

	final class API
	{
		private $keepalive = 30;
		private $timeout = 3;

		private $uri = null;
		private $host = null;
		private $port = null;
		private $base = null;

		function __construct( $uri = 'http://localhost:1978' )
		{
			assert('is_array(parse_url($uri))');
			$this->uri = $uri;
			$this->host = parse_url( $uri, PHP_URL_HOST );
			$this->port = parse_url( $uri, PHP_URL_PORT );
			$this->base = trim( parse_url( $uri, PHP_URL_PATH ), '/' );
		}

		function __get( $property )
		{
			assert('preg_match("/^[\w_]+$/",$property)');
			switch( $property )
			{
			case 'clear': return $this->rpc( 'clear', null, null );
			}
		}

		private function socket()
		{
			static $handle = null;

			if( is_null($handle) )
			{
				if( ! $handle = @fsockopen( $this->host, $this->port, $errno, $errstr ) )
					throw new ConnectionException( $this->uri, $errno, $errstr );
				if( $this->timeout )
					stream_set_timeout( $handle, $this->timeout );
			}

			return $handle;
		}

		private function rpc( $cmd, $data = null, $when_ok = null )
		{
			assert('in_array($cmd,array("add","append","cas","clear","cur_delete","cur_get","cur_get_key","cur_get_value","cur_jump","cur_jump_back","cur_set_value","cur_step","cur_remove","echo","get","get_bulk","increment","increment_double","match_prefix","match_regex","play_script","remove","remove_bulk","replace","report","set","set_bulk","status","synchronize","tune_replication","vacuum"))');
			assert('is_null($data) or count($data)==count(array_filter(array_keys($data),"is_string"))');
			assert('is_null($data) or count($data)==count(array_filter($data,"is_string"))');
			assert('is_callable($when_ok) or is_null($when_ok)');

			$data = is_array($data) ? http_build_query($data) : '';
			$buffer = sprintf( "POST /rpc/%s HTTP/1.1\r\nHost: %s:%s\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: %s\r\n\r\n%s",
				$cmd, $this->host, $this->port, strlen($data), $data );
			$size = strlen($buffer);
			unset($data);

			var_dump($buffer);

			for( $offset = $written = 0; $offset < $size; $offset += $written )
			{
				var_dump( __LINE__ );
				$written = fputs( $this->socket(), substr($buffer,$offset) );
				var_dump( $written );
				if( ! $written )
					break;
			}

			unset($size,$total,$written);

			$data = array();
			while( $line = fgets( $this->socket() ) )
				$data[ substr($line,0,strpos($line,"\t")) ] = substr($line,strpos($line,"\t"));
var_dump( $data );
			if( $when_ok ) call_user_func($when_ok,$data);

			fclose( $this->socket() );
		}
	}

}
