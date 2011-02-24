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
		function __construct( $uri, $msg )
		{
			parent::__construct( "Could'nt connect to KyotoTycoon server {$uri}. {$msg}", 1 );
		}
	}
	class InconsistencyException extends Exception
	{
		function __construct( $uri, $msg )
		{
			parent::__construct( "(Un)existing record was detected on server {$uri}. {$msg}", 2 );
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
			case 'clear':
				if( $this->DB ) $DB = $this->DB;
				return $this->rpc( 'clear', null, null );
			}
		}

		function replace( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'replace', compact('DB','key','value','xt'), null );
		}

		private function curl()
		{
			static $curl = null;
			if( is_null($curl) )
			{
				$curl = curl_init();
				curl_setopt_array($curl, array(
					CURLOPT_HTTPHEADER => array('Content-Type: text/tab-separated-values; colenc=B'),
					CURLOPT_POST => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CONNECTTIMEOUT => $this->timeout,
					CURLOPT_TIMEOUT => $this->keepalive ));
			}
			return $curl;
		}

		private function rpc( $cmd, $data = null, $when_ok = null )
		{
			assert('in_array($cmd,array("add","append","cas","clear","cur_delete","cur_get","cur_get_key","cur_get_value","cur_jump","cur_jump_back","cur_set_value","cur_step","cur_remove","echo","get","get_bulk","increment","increment_double","match_prefix","match_regex","play_script","remove","remove_bulk","replace","report","set","set_bulk","status","synchronize","tune_replication","vacuum"))');
			assert('is_null($data) or count($data)==count(array_filter(array_keys($data),"is_string"))');
			assert('is_null($data) or count($data)==count(array_filter($data,"is_string"))');
			assert('is_callable($when_ok) or is_null($when_ok)');

			if( is_array($data) )
				$post = implode("\r\n", array_map( function($k,$v) {
					return sprintf("%s\t%s", base64_encode($k), base64_encode($v));
				}, array_keys($data), $data ));
			else
				$post = '';
			unset($data);

			curl_setopt($this->curl(), CURLOPT_URL, "{$this->uri}/rpc/{$cmd}" );
			curl_setopt($this->curl(), CURLOPT_POSTFIELDS, $post);
			if( is_string($data = curl_exec($this->curl())) and $data = explode("\r\n",trim($data)) )
				$data = array_combine(
					array_map( function($k) { return substr($k,0,strpos($k,"\t")); }, $data ),
					array_map( function($v) { return substr($v,strpos($v,"\t")); }, $data ) );

			switch( curl_getinfo($this->curl(),CURLINFO_HTTP_CODE) )
			{
			case 200: if( $when_ok ) call_user_func( $when_ok, $data ); return true;
			case 450: throw new InconsistencyException($this->uri,$data['ERROR']);
			default: throw new ProtocolException($this->uri);
			}

		}
	}

}
