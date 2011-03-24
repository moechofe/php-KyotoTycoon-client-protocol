<?php

/**
 * API to communicate with a Kyoto Tycoon server.
 * Author: martin mauchauffee
 * Link: http://github.com/moechofe/phpkyototycoon
 * Date: March 2011
 * Requirement: PHP 5.3+
 */

declare(encoding='UTF-8');

namespace
{

	/**
	 * Return an UI object ready to send command to a KyotoTycoon server.
	 * Params:
	 *	 string $uri = The URI of the KyotoTycoon server.
	 * Return:
	 *	 KyotoTycoon\UI = The User Interface object.
	 * Set the connection parameters:
	 * ----
	 * $kt = kt(); // Default parameters is: localhost:1978 and the first loaded by the server.
	 * $kt = kt('http://kt.local:1979/user.kch');
	 * ----
	 * Set and get value of the records:
	 * ----
	 * $kt['japan'] = 'tokyo';
	 * $kt['france'] = 'paris';
	 * var_dump( $kt['japan'], $kt['france'] );
	 * ----
	 * Set and get the expiration time of a record.
	 * ----
	 * $kt->set('a','ananas',2);
	 * var_dump( $kt->gxt('a') );
	 * ----
	 */
	function kt( $uri = 'http://localhost:1978' )
	{
		assert('is_array(parse_url($uri))');
		return new KyotoTycoon\UI( $uri );
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
	 * The processing is done but the result is not fulfill the application logic.
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

	/**
	 * Fluent and quick user interface (UI) for the KyotoTycoon API.
	 *
	 */
	final class UI implements \Iterator, \ArrayAccess
	{
		// {{{ ---properties

		// The API object used to send command.
		private $api = null;

		// Indicate if OutOfBoundsException should be throw instead of returning null.
		private $outofbound = false;

		// Indicate if RuntimeException should be throw instead of returning false.
		private $runtime = true;

		// Used to store the prefixe before initiate the process of browsing the keys.
		private $prefix = null;

		// Used to store the regex before intitiate the process of browsing the keys.
		private $regex = null;

		private $just_key = false;

		// Indicate the maximum number of keys returned by match_prefix and match_regex operations.
		private $max = null;

		// Used to store the retreived number of records founds with match_prefix and match_regex operations.
		private $num = null;

		// Used to store all the keys returned by match_prefix and match_regex operations.
		private $keys = null;

		// Used to store temporally the key and the value of a retrieved records during any browse operations.
		private $record = null;

		// Indicate the direction of the browsing operation.
		private $backward = null;

		// Set to store the current used cursor (CUR).
		private $cursor = null;

		// Indiquate the first key of a browsing operation.
		private $startkey = null;

 		// Maintain a list of all used Kyoto Tycoon cursor (CUR).
		static $cursors = array();

		// }}}
		// {{{ __construct(), __clone()

		function __construct( $uri = 'http://localhost:1978' )
		{
			assert('is_array(parse_url($uri))');
			$this->api = new API( $uri );
		}

		function __destruct()
		{
			if( ! is_null($this->cursor) )
			{
				assert('is_integer($this->cursor)');
				unset(self::$cursors[$this->cursor]);
			}
		}

		function __clone()
		{
			$this->prefix = null;
			$this->regex = null;
			$this->just_key = false;
			$this->max = null;
			$this->num = null;
			$this->cursor = null;
			$this->keys = null;
			$this->record = null;
			$this->backward = null;
			$this->startkey = null;
		}

		// }}}
		// {{{ __get(), __isset(), __unset(), __call()

		function __get( $property )
		{
			assert('is_string($property)');
			switch( $property )
			{
			case 'clear':
				$this->api->clear;
				return $this;
			case 'outofbound_throw_exception':
				$this->outofbound = true;
				return $this;
			case 'outofbound_return_null':
				$this->outofbound = false;
				return $this;
			case 'runtime_throw_exception':
				$this->runtime = true;
				return $this;
			case 'runtime_return_false':
				$this->runtime = false;
				return $this;
			default:
				try { return $this->api->get($property,$xt); }
				catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
				catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
			}
		}

		function __isset( $key )
		{
			assert('is_string($key)');
			try { return is_string($this->api->get($key,$xt)); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return false; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		function __unset( $key )
		{
			assert('is_string($key)');
			$this->del($key);
		}

		function __call( $method, $args )
		{
			assert('is_string($method)');
			assert('is_scalar($args[0])');
			return $this->set($method, (string)$args[0]);
		}

		// }}}
		// {{{ get(), gxt(), set(), inc(), cat(), add(), rep(), del(), cas()

		/**
		 * Retrieve the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 (out) integer $xt = The absolute expiration time.
		 *	 (out) null $xt = There is no expiration time.
		 * Return:
		 *	 string = The value of the record.
		 *	 null = If the record do not exists.
		 *	 false = If an error ocurred.
		 */
		function get( $key, &$xt = null )
		{
			assert('is_string($key)');
			try { return $this->api->get($key,$xt); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		/**
		 * Retrieve the expiration time of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 (out) integer $xt = The absolute expiration time.
		 *	 (out) null $xt = There is no expiration time.
		 * Return:
		 *	 string = The value of the expiration time.
		 *	 null = If the record do not exists.
		 *	 false = If an error ocurred.
		 */
		function gxt( $key )
		{
			assert('is_string($key)');
			$xt = null;
			try { $this->api->get($key,$xt); return $xt; }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		/**
		 * Set the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *   true = If success.
		 *   false = If an error ocurred.
		 */
		function set( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			try { $this->api->set($key,$value,$xt); return true; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		function inc( $key, $num = 1, $xt = null )
		{
			assert('is_string($key)');
			assert('is_numeric($num)');
			assert('is_null($xt) or is_numeric($xt)');
			try
			{
				if( is_integer($num) or (int)$num==$num )
					return $this->api->increment( $key, $num, $xt );
				else
					return $this->api->increment_double( $key, $num, $xt );
			}
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
		}

		/**
		 * Append the value to a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *   true = If success.
		 *   false = If an error ocurred.
		 */
		function cat( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			try { $this->api->append($key,$value,$xt); return true; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		/**
		 * Add a record if it not exits.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success.
		 *	 false = If an error ocurred.
		 *	 null = If the record already exists.
		 */
		function add( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			try { return $this->api->add($key,$value,$xt); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		/**
		 * Replace the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success.
		 *	 false = If an error ocurred.
		 *	 null = If the record don't exists.
		 */
		function rep( $key, $value, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($value)');
			assert('is_null($xt) or is_numeric($xt)');
			try { return $this->api->replace($key,$value,$xt); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		/**
		 * Replace the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 * Return:
		 *	 true = If succes.
		 *	 false = If an error ocurred.
		 *	 null = If the record don't exists.
		 */
		function del( $key )
		{
			assert('is_string($key)');
			try { $this->api->remove($key); return true; }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		/**
		 * Perform compare-and-swap.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $oval = The old value.
		 *	 null $oval = If it is omittted, no record is meant.
		 *	 string $nval = The new value.
		 *	 null $nval = If it is omittted, the record is removed.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success.
		 *	 false = If an error ocurred.
		 *	 null = If the old value assumption was failed.
		 */
		function cas( $key, $oval, $nval, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($oval) or is_null($oval)');
			assert('is_string($nval) or is_null($nval)');
			assert('is_null($xt) or is_numeric($xt)');
			try { return $this->api->cas($key,$oval,$nval,$xt); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		// }}}
		// {{{ begin(), search(), forward(), backward(), prefix(), regex()

		function begin( $prefix, $max = 0, &$num = null )
		{
			assert('is_string($prefix)');
			assert('is_numeric($max) and $max>=0 and (int)$max==$max');
			$stm = clone $this;
			$stm->prefix = $prefix;
			$stm->max = $max;
			$stm->num = &$num;
			return $stm;
		}

		function search( $regex, $max = 0, &$num = null )
		{
			assert('is_string($regex)');
			assert('is_numeric($max) and $max>=0 and (int)$max==$max');
			$stm = clone $this;
			$stm->regex = $regex;
			$stm->max = $max;
			$stm->num = &$num;
			return $stm;
		}

		function forward( $key = null )
		{
			assert('is_string($key) or is_null($key)');
			$stm = clone $this;
			$stm->startkey = $key;
			$stm->backward = false;
			return $stm;
		}

		function backward( $key = null )
		{
			assert('is_string($key) or is_null($key)');
			$stm = clone $this;
			$stm->startkey = $key;
			$stm->backward = true;
			return $stm;
		}

		function prefix( $prefix, $max = 0, &$num = null )
		{
			assert('is_string($prefix)');
			assert('is_numeric($max) and $max>=0 and (int)$max==$max');
			$stm = clone $this;
			$stm->prefix = $prefix;
			$stm->just_key = true;
			$stm->max = $max;
			$stm->num = &$num;
			return $stm;
		}

		function regex( $regex, $max = 0, &$num = null )
		{
			assert('is_string($regex)');
			assert('is_numeric($max) and $max>=0 and (int)$max==$max');
			$stm = clone $this;
			$stm->regex = $regex;
			$stm->just_key = true;
			$stm->max = $max;
			$stm->num = &$num;
			return $stm;
		}

		// }}}
		// {{{ rewind(), current(), key(), next(), valid()

		/**
		 * TODO check if integer limit is reach with cursor.
		 */
		function rewind()
		{
			// If prefix is set, then retrieve the list of keys begin with this prefix.
			if( ! is_null($this->prefix) )
				$this->keys = $this->api->match_prefix( $this->prefix, $this->limit, $this->num );
			// Else, if regex is set, then retrieve the list of keys that match this regex.
			elseif( ! is_null($this->regex) )
				$this->keys = $this->api->match_regex( $this->regex, $this->limit, $this->num );
			// Else, the cursor will be use
			else
			{
				// If no cursor was set, the create a new one. It need to be uniq for each cURL session.
				if( is_null($this->cursor) )
				{
					if( ! $cursor = end(self::$cursors) ) $this->cursor = 1;
					else $this->cursor = $cursor+1;
					self::$cursors[$this->cursor] = $this->cursor;
				}
				// Now set the position of the cursor.
				try
				{
					assert('is_bool($this->backward)');
					if( $this->backward )
						$this->api->cur_jump_back( $this->cursor, $this->startkey );
					else
						$this->api->cur_jump( $this->cursor, $this->startkey );
				}
				catch( \OutOfBoundsException $e ) {}
			}
		}

		function current()
		{
			assert('is_array($this->record)');
			if( ! is_null($this->prefix) or ! is_null($this->regex) or ! is_null($this->cursor) )
				return current($this->record);
			else
				return null;
		}

		function key()
		{
			assert('is_array($this->record)');
			if( ! is_null($this->prefix) or ! is_null($this->regex) or ! is_null($this->cursor) )
				return key($this->record);
			else
				return null;
		}

		function next()
		{
			if( ! is_null($this->prefix) or ! is_null($this->regex) )
			{
				assert('is_array($this->keys)');
				next($this->keys);
			}
			elseif( ! is_null($this->cursor) )
			{
				try
				{
					if( $this->backward )
						$this->api->cur_step_back($this->cursor);
					else
						$this->api->cur_step($this->cursor);
				}
				catch( \OutOfBoundsException $e ) {}
			}
		}

		function valid()
		{
			if( ! is_null($this->prefix) or ! is_null($this->regex) )
			{
				assert('is_array($this->keys)');
				if( current($this->keys) )
					try
					{
						if( $this->just_key )
							return $this->record = array( key($this->keys) => current($this->keys) );
						else
							return $this->record = array( current($this->keys) => $this->get(current($this->keys)) );
					}
					catch( \OutOfBoundsException $e ) { return false; }
				else
					return false;
			}
			elseif( ! is_null($this->cursor) )
			{
				try { $this->record = $this->api->cur_get($this->cursor,false); return $this->record; }
				catch( \OutOfBoundsException $e ) { return false; }
			}
			else
				return false;
		}

		// }}}
		// {{{ to(), from()

		function to( $key, &$value )
		{
			assert('is_string($key)');
			$value = $this->get($key);
			return $this;
		}

		function from( $key, &$value = null )
		{
			assert('is_string($key)');
			$this->set($key,$value);
			return $this;
		}

		// }}}
		// {{{ offsetExists(), offsetGet(), offsetSet(), offsetUnset()

		function offsetExists( $offset )
		{
			assert('is_string($offset)');
			try { return is_string($this->api->get($offset)); }
			catch( \OutOfBoundsException $e ) { return false; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		function offsetGet( $offset )
		{
			assert('is_string($offset)');
			try { return $this->api->get($offset); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e; else return null; }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; else return false; }
		}

		function offsetSet( $offset, $value )
		{
			assert('is_string($offset)');
			assert('is_string($value)');
			try { $this->api->set($offset,$value); }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; }
		}

		function offsetUnset( $offset )
		{
			assert('is_string($offset)');
			try { $this->api->remove($offset); }
			catch( \OutOfBoundsException $e ) { if( $this->outofbound ) throw $e;  }
			catch( \RuntimeException $e ) { if( $this->runtime ) throw $e; }
		}

		// }}}
	}

	/**
	 * The application programming interface (API) for KyotoTycoon.
	 * Send RPC command with a keepalive connection.
	 */
	final class API
	{
		// {{{ $keepalive, $timeout, $uri, $host, $post, $base, $encode, __construct()

		private $keepalive = 30;
		private $timeout = 3;

		// Contain all connection parameters in one URI.
		private $uri = null;

		// The hostname or the IP of the server.
		private $host = null;

		// The port of the server.
		private $port = null;

		// The name or the ID of the database.
		private $base = null;

		private $encode = null;

		function __construct( $uri = 'http://localhost:1978' )
		{
			assert('is_array(parse_url($uri))');
			$this->uri = $uri;
			$this->host = parse_url( $uri, PHP_URL_HOST );
			$this->port = parse_url( $uri, PHP_URL_PORT );
			$this->base = trim( parse_url( $uri, PHP_URL_PATH ), '/' );
			$this->use_form_url();
		}

		// }}}
		// {{{ use_tab_base64(), use_tab_quoted(), use_tab_url(), use_tab(), use_form_url()

		function use_tab_base64()
		{
			$this->encode = function( $data )
			{
				assert('is_array($data)');
				return implode("\r\n", array_map( function($k,$v) {
					return sprintf("%s\t%s", base64_encode($k), base64_encode($v));
				}, array_keys($data), $data ));
			};
			curl_setopt($this->curl(), CURLOPT_HTTPHEADER, array('Content-type: text/tab-separated-values; colenc=B'));
		}

		function use_tab_quoted()
		{
			$this->encode = function( $data )
			{
				assert('is_array($data)');
				return implode("\r\n", array_map( function($k,$v) {
					return sprintf("%s\t%s", quoted_printable_encode($k), quoted_printable_encode($v));
				}, array_keys($data), $data ));
			};
			curl_setopt($this->curl(), CURLOPT_HTTPHEADER, array('Content-type: text/tab-separated-values; colenc=Q'));
		}

		function use_tab_url()
		{
			$this->encode = function( $data )
			{
				assert('is_array($data)');
				return implode("\r\n", array_map( function($k,$v) {
					return sprintf("%s\t%s", urlencode($k), urlencode($v));
				}, array_keys($data), $data ));
			};
			curl_setopt($this->curl(), CURLOPT_HTTPHEADER, array('Content-type: text/tab-separated-values; colenc=U'));
		}

		function use_tab()
		{
			$this->encode = function( $data )
			{
				assert('is_array($data)');
				return implode("\r\n", array_map( function($k,$v) {
					return sprintf("%s\t%s", str_replace($k,"\r\n\t",''), str_replace($v,"\r\n\t",''));
				}, array_keys($data), $data ));
			};
			curl_setopt($this->curl(), CURLOPT_HTTPHEADER, array('Content-type: text/tab-separated-values'));
		}

		function use_form_url()
		{
			$this->encode = function( $data )
			{
				assert('is_array($data)');
				return http_build_query($data);
			};
			curl_setopt($this->curl(), CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
		}

		// }}}
		// {{{ add()

		/**
		 * Add a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the record already exists.
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
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success
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
		// {{{ cas()

		/**
		 * Perform compare-and-swap.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $oval = The old value.
		 *	 null $oval = If it is omittted, no record is meant.
		 *	 string $nval = The new value.
		 *	 null $nval = If it is omittted, the record is removed.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success
		 */
		function cas( $key, $oval, $nval, $xt = null )
		{
			assert('is_string($key)');
			assert('is_string($oval) or is_null($oval)');
			assert('is_string($nval) or is_null($nval)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			if( ! $oval ) unset($oval);
			if( ! $nval ) unset($nval);
			return $this->rpc( 'cas', compact('DB','key','oval','nval','xt'), null );
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
				return $this->rpc( 'clear', compact('DB'), null );
			}
		}

		// }}}
		// {{{ get()

		/**
		 * Retrieve the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 (out) integer $xt = The absolute expiration time.
		 *	 (out) null $xt = There is no expiration time.
		 * Return:
		 *	 string = The value of the record.
		 * Throws:
		 *	 InconsistencyException = If the record do not exists.
		 */
		function get( $key, &$xt = null )
		{
			assert('is_string($key)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			return $this->rpc( 'get', compact('DB','key'), function($result) use(&$xt) {
				if( isset($result['xt']) ) $xt = $result['xt'];
				if( isset($result['value']) )
					return $result['value'];
				elseif( isset($result['dmFsdWU=']) )
					return base64_decode($result['dmFsdWU=']);
				else
					throw ProtocolException( $this->url );
			} );
		}

		// }}}
		// {{{ cur_get()

		/**
		 * Get a pair of the key and the value of the current record.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 *	 true $step = To move the cursor to the next record.
		 *	 null,false $step = If it is omitted, the cursor stays at the current record.
		 * Return:
		 *	 array(string=>string) = The key and the value of the record.
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_get( $CUR, $step = true )
		{
			assert('is_integer($CUR)');
			assert('is_bool($step) or is_null($step)');
			if( ! $step ) unset($step); else $step = (string)$step;
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_get', compact('CUR','step'), function($result) {
				return array($result['key']=>$result['value']);
			} );
		}

		// }}}
		// {{{ cur_get_key()

		/**
		 * Get the key of the current record.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 *	 true $step = To move the cursor to the next record.
		 *	 null,false $step = If it is omitted, the cursor stays at the current record.
		 * Return:
		 *	 string = The key of the record.
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_get_key( $CUR, $step = true )
		{
			assert('is_integer($CUR)');
			assert('is_bool($step) or is_null($step)');
			if( ! $step ) unset($step); else $step = (string)$step;
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_get_key', compact('CUR','step'), function($result) {
				return $result['key'];
			} );
		}

		// }}}
		// {{{ cur_get_value()

		/**
		 * Get a pair of the key and the value of the current record.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 *	 true $step = To move the cursor to the next record.
		 *	 null,false $step = If it is omitted, the cursor stays at the current record.
		 * Return:
		 *	 string = The value of the record.
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_get_value( $CUR, $step = true )
		{
			assert('is_integer($CUR)');
			assert('is_bool($step) or is_null($step)');
			if( ! $step ) unset($step); else $step = (string)$step;
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_get_value', compact('CUR','step'), function($result) {
				return $result['value'];
			} );
		}

		// }}}
		// {{{ cur_jump()

		/**
		 * Jump the cursor to the first record for forward scan.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 *	 string $key = The key of the destination record.
		 *	 null $key = If it is omitted, the first record is specified.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_jump( $CUR, $key = null )
		{
			assert('is_integer($CUR)');
			assert('is_string($key) or is_null($key)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $key ) unset($key);
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_jump', compact('DB','CUR','key'), null );
		}

		// }}}
		// {{{ cur_jump_back()

		/**
		 * Jump the cursor to a record for forward scan.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 *	 string $key = The key of the destination record.
		 *	 null $key = If it is omitted, the first record is specified.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_jump_back( $CUR, $key = null )
		{
			assert('is_integer($CUR)');
			assert('is_string($key) or is_null($key)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $key ) unset($key);
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_jump_back', compact('DB','CUR','key'), null );
		}

		// }}}
		// {{{ cur_step()

		/**
		 * Retrieve the value of a record.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_step( $CUR )
		{
			assert('is_integer($CUR)');
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_step', compact('CUR'), null );
		}

		// }}}
		// {{{ cur_step_back()

		/**
		 * Retrieve the value of a record.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_step_back( $CUR )
		{
			assert('is_integer($CUR)');
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_step_back', compact('CUR'), null );
		}

		// }}}
		// {{{ cur_remove()

		/**
		 * Remove the current record.
		 * Params:
		 * 	 integer $CUR = The cursor identifier.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the cursor is invalidated.
		 */
		function cur_remove( $CUR )
		{
			assert('is_integer($CUR)');
			$CUR = (string)$CUR;
			return $this->rpc( 'cur_remove', compact('CUR'), null );
		}

		// }}}
		// {{{ increment()

		/**
		 * Add a number to the numeric integer value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 numeric $num = The additional number.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 string = The result value.
		 * Throws:
		 *	 InconsistencyException = If the record was not compatible.
		 */
		function increment( $key, $num = 1, $xt = null )
		{
			assert('is_string($key)');
			assert('is_numeric($num)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			$num = (string)$num;
			return $this->rpc( 'increment', compact('DB','key','num','xt'), function($result) use(&$xt) {
				return $result['num'];
			} );
		}

		// }}}
		// {{{ increment_double()

		/**
		 * Add a number to the numeric integer value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 numeric $num = The additional number.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 string = The result value.
		 * Throws:
		 *	 InconsistencyException = If the record was not compatible.
		 */
		function increment_double( $key, $num = 1, $xt = null )
		{
			assert('is_string($key)');
			assert('is_numeric($num)');
			assert('is_null($xt) or is_numeric($xt)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $xt ) unset($xt);
			$num = (string)$num;
			return $this->rpc( 'increment_double', compact('DB','key','num','xt'), function($result) use(&$xt) {
				return $result['num'];
			} );
		}

		// }}}
		// {{{ match_prefix()

		/**
		 * Get keys matching a prefix string.
		 * Params:
		 *	 string $prefix = The prefix string.
		 *	 integer $max = The maximum number to retrieve.
		 *	 null $max = If it is omitted or negative, no limit is specified.
		 *	 (out) $num = The number of retrieved keys.
		 * Return:
		 *	 array(string) = List of arbitrary keys.
		 * Throws:
		 *	 InconsistencyException = If the record do not exists.
		 */
		function match_prefix( $prefix, $max = null, $num = null )
		{
			assert('is_string($prefix)');
			assert('is_numeric($max) or is_null($max)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $max ) unset($max); else $max = (string)$max;
			return $this->rpc( 'match_prefix', compact('DB','prefix','max'), function($result) use(&$num) {
				$num = $result['num'];
				return array_reduce(array_keys($result),function($a,$b)use(&$result){return $b[0]=='_'?array_merge($a,array(substr($b,1))):$a;},array());
			}	);
		}

		// }}}
		// {{{ match_regex()

		/**
		 * Get keys matching a ragular expression string.
		 * Params:
		 *	 string $regex = The regular expression string.
		 *	 integer $max = The maximum number to retrieve.
		 *	 null $max = If it is omitted or negative, no limit is specified.
		 *	 (out) string $num = The number of retrieved keys.
		 * Return:
		 *	 array(string) = List of arbitrary keys.
		 * Throws:
		 *	 InconsistencyException = If the record do not exists.
		 */
		function match_regex( $regex, $max = null, $num = null )
		{
			assert('is_string($regex)');
			assert('is_numeric($max) or is_null($max)');
			if( $this->DB ) $DB = $this->DB;
			if( ! $max ) unset($max); else $max = (string)$max;
			return $this->rpc( 'match_regex', compact('DB','regex','max'), function($result) use(&$num) {
				$num = $result['num'];
				return array_reduce(array_keys($result),function($a,$b)use(&$result){return $b[0]=='_'?array_merge($a,array(substr($b,1))):$a;},array());
			}	);
		}

		// }}}
		// {{{ remove()

		/**
		 * Replace the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the record do not exists.
		 */
		function remove( $key )
		{
			assert('is_string($key)');
			if( $this->DB ) $DB = $this->DB;
			return $this->rpc( 'remove', compact('DB','key'), null );
		}

		// }}}
		// {{{ replace()

		/**
		 * Replace the value of a record.
		 * Params:
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success
		 * Throws:
		 *	 InconsistencyException = If the record do not exists.
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
		 *	 string $key = The key of the record.
		 *	 string $value = The value of the record.
		 *	 numeric $xt = The expiration time from now in seconds. If it is negative, the absolute value is treated as the epoch time.
		 *	 null $xt = No expiration time is specified.
		 * Return:
		 *	 true = If success
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
		 *	 string $cmd = The command.
		 *	 array,null $data = Lexical indexed array containing the input parameters.
		 *	 $return callable($result) = $when_ok = A callback function called if success.
		 *	 array $result = Lexical indexed array containing the output parameters.
		 *	 string,false $return = The returned value of the command or true if success.
		 * Return:
		 *
		 */
		private function rpc( $cmd, $data = null, $when_ok = null )
		{
			static $encode = null; if( is_null($encode) ) $encode = &$this->encode;
			assert('in_array($cmd,array("add","append","cas","clear","cur_delete","cur_get","cur_get_key","cur_get_value","cur_jump","cur_jump_back","cur_set_value","cur_step","cur_step_back","cur_remove","echo","get","get_bulk","increment","increment_double","match_prefix","match_regex","play_script","remove","remove_bulk","replace","report","set","set_bulk","status","synchronize","tune_replication","vacuum"))');
			assert('is_null($data) or count($data)==count(array_filter(array_keys($data),"is_string"))');
			assert('is_null($data) or count($data)==count(array_filter($data,"is_string"))');
			assert('is_callable($when_ok) or is_null($when_ok)');

			if( is_array($data) )
				$post = $encode($data);
			else
				$post = '';
			unset($data);
			assert('is_string($post)');

			curl_setopt($this->curl(), CURLOPT_URL, "{$this->uri}/rpc/{$cmd}" );
			curl_setopt($this->curl(), CURLOPT_POSTFIELDS, $post);
			if( is_string($data = curl_exec($this->curl())) and $data and $data = explode("\n",substr($data,0,-1)) )
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
			 		assert('is_string($data) or is_array($data) or $data===true');
					return $data;
				}
				else
					return true;
			case 450: throw new InconsistencyException($this->uri,$data['ERROR']);
			case 501: throw new ImplementationException($this->uri);
			case 400: throw new ProtocolException($this->uri);
			}
		}

		// }}}
	}

}
