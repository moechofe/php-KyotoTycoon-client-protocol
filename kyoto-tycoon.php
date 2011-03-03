<?php
declare(encoding='UTF-8');

namespace
{

	/**
	 * Return an API object redy to send command to a KyotoTycoon server.
	 * Params:
	 *   string $uri = The URI of the KyotoTycoon server
	 * Return:
	 *   KyotoTycoon\API = The API object.
	 */
	function kt( $uri = 'http://localhost:1978' )
	{
		assert('is_array(parse_url($uri))');
		return new KyotoTycoon\API( $uri );
	}

}

namespace KyotoTycoon
{
	// {{{ ConnectionException, InconsistencyException, ProtocolException

	/**
	 * Thrown when the connection to the KyotoTycoon cannot be established.
	 */
	class ConnectionException extends \RuntimeException
	{
		function __construct( $uri, $msg )
		{
			parent::__construct( "Could'nt connect to KyotoTycoon server {$uri}. {$msg}", 1 );
		}
	}

	/**
	 * Thrown when an operation is asked about a record that didn't respect all the needs.
	 */
	class InconsistencyException extends \OutOfBoundsException
	{
		function __construct( $uri, $msg )
		{
			parent::__construct( "(Un)existing record was detected on server {$uri}. {$msg}", 2 );
		}
	}

	/**
	 * Throw if the protocol isn't well implemented for an operation.
	 */
	class ProtocolException extends \DomainException
	{
		function __construct( $uri )
		{
			parent::__construct( "Bad protocol communication with the KyotoTycoon server {$uri}.", 3 );
		}
	}


	// }}}

	final class API
	{
		// {{{ $keepalive, $timeout, $uri, $host, $post, $base, __construct()

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

		// }}}
		// {{{ add()

		/**
		 * Add a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   string $value = The value of the record.
		 *   numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *   null $xt = No expiration time is specified.
		 * Return:
		 *   true = If success
		 * Throws:
		 *   InconsistencyException = If the record already exists.
		 */
		function add( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'add', compact('DB','key','value','xt'), null );
		}

		// }}}
		// {{{ append()

		/**
		 * Append the value to a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   string $value = The value of the record.
		 *   numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *   null $xt = No expiration time is specified.
		 * Return:
		 *   true = If success
		 */
		function append( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'append', compact('DB','key','value','xt'), null );
		}

		// }}}
		// {{{ clear

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

		// }}}
		// {{{ get()

		/**
		 * Retrieve the value of a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   (out) integer $xt = The absolute expiration time.
		 *   (out) null $xt = There is no expiration time.
		 * Return:
		 *   string = The value of the record.
		 * Throws:
		 *   InconsistencyException = If the record do not exists.
		 */
		function get( $key, &$xt = null )
		{
			assert('is_string($key)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'get', compact('DB','key'), function($result) use(&$xt) {
				if( isset($result['xt']) ) $xt = $result['xt'];
				return $result['value'];
			} );
		}

		// }}}
		// {{{ increment()

		/**
		 * Add a number to the numeric integer value of a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   numeric $num = The additional number.
		 *   numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *   null $xt = No expiration time is specified.
		 * Return:
		 *   string = The result value.
		 * Throws:
		 *   InconsistencyException = If the record was not compatible.
		 */
		function increment( $key, $num = 1, $xt = null )
		{
			assert('is_string($key)');
			assert('is_numeric($num)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'increment', compact('DB','key','num','xt'), function($result) use(&$xt) {
				return $result['num'];
			} );
		}

		// }}}
		// {{{ increment_double()

		/**
		 * Add a number to the numeric integer value of a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   numeric $num = The additional number.
		 *   numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *   null $xt = No expiration time is specified.
		 * Return:
		 *   string = The result value.
		 * Throws:
		 *   InconsistencyException = If the record was not compatible.
		 */
		function increment_double( $key, $num = 1, $xt = null )
		{
			assert('is_string($key)');
			assert('is_numeric($num)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'increment_double', compact('DB','key','num','xt'), function($result) use(&$xt) {
				return $result['num'];
			} );
		}

		// }}}
		// {{{ replace()

		/**
		 * Replace the value of a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   string $value = The value of the record.
		 *   numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *   null $xt = No expiration time is specified.
		 * Return:
		 *   true = If success
		 * Throws:
		 *   InconsistencyException = If the record do not exists.
		 */
		function replace( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'replace', compact('DB','key','value','xt'), null );
		}

		// }}}
		// {{{ set()

		/**
		 * Set the value of a record.
		 * Params:
		 *   string $key = The key of the record.
		 *   string $value = The value of the record.
		 *   numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *   null $xt = No expiration time is specified.
		 * Return:
		 *   true = If success
		 */
		function set( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'set', compact('DB','key','value','xt'), null );
		}

		// }}}
		// {{{ curl(), rpc()

		/**
		 * Return a curl resource identifier
		 * KyotoTycoon use a keep-alive connection by default.
		 */
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

		/**
		 * Send an RPC command to a KyotoTycoon server.
		 * Params:
		 *   string $cmd = The command.
		 *   array,null $data = Lexical indexed array containing the input parameters.
		 *   $return callable($result) = $when_ok = A callback function called if success.
		 *   array $result = Lexical indexed array containing the output parameters.
		 *   string,false $return = The returned value of the command or true if success.
		 * Return:
		 *
		 */
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
			if( is_string($data = curl_exec($this->curl())) and $data and $data = explode("\r\n",trim($data)) )
				$data = array_combine(
					array_map( function($k) { return substr($k,0,strpos($k,"\t")); }, $data ),
					array_map( function($v) { return substr($v,strpos($v,"\t")+1); }, $data ) );
			elseif( $data === false )
				throw new ConnectionException($this->uri, curl_error($this->curl()));
			else
				$data = array();

			switch( curl_getinfo($this->curl(),CURLINFO_HTTP_CODE) )
			{
			case 200:
				if( $when_ok )
				{
					$data = call_user_func( $when_ok, $data );
			 		assert('is_string($data) or $data===true');
					return $data;
				}
				else
					return true;
			case 450: throw new InconsistencyException($this->uri,$data['ERROR']);
			default: throw new ProtocolException($this->uri);
			}
		}

		// }}}
	}

}
