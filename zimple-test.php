<?php

/**
 * Simple unit tests tool inspired by Test::More from perl
 */

// {{{ ok, notok, is, isnt, truly, trulynot

/**
 * Test if is true or false
 * Params:
 *   mixed $ok = The test pass if $ok can be evaluated to true
 *   string $msg = The test message
 * ----
 * ok( my_test(), 'message' );
 * notok( my_test(), 'message' );
 * ----
 */
function ok( $ok, $msg = 'Should be true' )
{
	assert('is_string($msg)');
	return compare( $ok, '==', true, $msg );
}
function notok( $ok, $msg = 'Shouldn\'t be true' )
{
	assert('is_string($msg)');
	return compare( $ok, '!=', true, $msg );
}

/**
 * Test if is equal or not
 * Params:
 *   mixed $test = The tested value
 *   mixed $executed = The expected value
 *   string $msg = The test message
 * ----
 * is( $value, 123, 'message' );
 * isnt( $value, 123, 'message' );
 * ----
 */
function is( $test, $expected, $msg = 'Should be equal' )
{
	assert('is_string($msg)');
	return compare( $test, '==', $expected, $msg );
}
function isnt( $test, $expected, $msg = 'Shouldn\'t be equal' )
{
	assert('is_string($msg)');
	return compare( $test, '!=', $expected, $msg );
}

/**
 * Test if is identical or not
 * Params:
 *   mixed $test = The tested value
 *   mixed $executed = The expected value
 *   string $msg = The test message
 * ----
 * truly( clone $object, $object, 'message' );
 * ----
 */
function truly( $test, $expected, $msg = 'Should be truly equal' )
{
	assert('is_string($msg)');
	return compare( $test, '===', $expected, $msg );
}
function trulynot( $test, $expected, $msg = 'Should be truly not equal')
{
	assert('is_string($msg)');
	return compare( $test, '!==', $expected, $msg );
}

// }}}
// {{{ greater, notgreater, lesser, notlesser, contain, notcontain, has, hasnt

/**
 * Test is a value is greater, lesser or not an other value
 * Params:
 *   numeric $test = The tested value
 *   numeric $expected = The expected value
 *   string $msg = The test message
 */
function greater( $test, $expected, $msg = 'Should be greater' )
{
	assert('is_string($msg)');
	return compare( $test, '>', $expected, $msg );
}
function notgreater( $test, $expected, $msg = 'Shouldn\'t be greater' )
{
	assert('is_string($msg)');
	return compare( $test, '<=', $expected, $msg );
}
function lesser( $test, $expected, $msg = 'Should be lesser' )
{
	assert('is_string($msg)');
	return compare( $test, '<', $expected, $msg );
}
function notlesser( $test, $expected, $msg = 'Should\'t be lesser' )
{
	assert('is_string($msg)');
	return compare( $test, '>=', $expected, $msg );
}

/**
 * Test if a value is contained or not
 * Params:
 *   array,object,string = The container
 *   scalar,null = The value
 *   string $msg = The test message
 * ----
 * contain( $array, 'value', 'message' );
 * contain( $object, 'public property', 'message' );
 * contain( $string, 'substr', 'message' );
 * ----
 */
function contain( $haystack, $needle, $msg = 'Should contain an value' )
{
	if( is_string($haystack) ) return ok( false!==strpos($haystack,$needle), $msg );
	elseif( is_object($haystack) ) return ok( property_exists($haystack,$needle), $msg );
	elseif( is_array($haystack) ) return ok( array_key_exists($needle,$haystack), $msg );
	else return contain( gettype($haystack), array('array','object','string'), $msg );
}
function notcontain( $haystack, $needle, $msg = 'Shouldn\'t contain an value'  )
{
	if( is_string($haystack) ) return notok( false!==strpos($haystack,$needle), $msg );
	elseif( is_object($haystack) ) return notok( property_exists($haystack,$needle), $msg );
	elseif( is_array($haystack) ) return notok( array_key_exists($needle,$haystack), $msg );
	else return notcontain( gettype($haystack), array('array','object','string'), $msg );
}

/**
 * Test if a value is one of the expected values
 * Params:
 *   mixed $test = The tested value
 *   array $expected = The array of expected values
 * ----
 * isoneof( $value, array('array','object') );
 * isnotoneof( $value, array('array','object') );
 * ----
 */
function isoneof( $test, $expected, $msg = 'Should be one of' )
{
	assert('is_array($expected)');
	return compare( $test, 'in_array', $expected, $msg );
}
function isnotoneof( $test, $expected, $msg )
{
	assert('is_array($expected)');
	return compare( $test, '!in_array', $expected, $msg = 'Shouldn\'t be one of' );
}

/**
 * Test if a container has a specified size or not
 * Params:
 *   array,object,string = The tested container
 *   integer = The specified size
 * ----
 * has( $array, 1, 'message' );
 * has( $object, 2, 'message' );
 * has( $string, 3, 'message' );
 * hasnt( $array, 1, 'message' );
 * hasnt( $object, 2, 'message' );
 * hasnt( $string, 3, 'message' );
 */
function has( $haystack, $size, $msg = 'Should have a specific size' )
{
	assert('is_integer($size)');
	if( is_string($haystack) ) return is( strlen($haystack), $size, $msg );
	elseif( is_object($haystack) ) return is( count(get_object_vars($haystack)), $size, $msg );
	elseif( is_array($haystack) ) return is( count($haystack), $size, $msg );
	else return isoneof( gettype($haystack), array('array','object','string'), $msg );
}
function hasnt( $haystack, $size, $msg = 'Shoulnd have a specific size' )
{
	assert('is_integer($size)');
	if( is_string($haystack) ) return isnt( strlen($haystack), $size, $msg );
	elseif( is_object($haystack) ) return isnt( count(get_object_vars($haystack)), $size, $msg );
	elseif( is_array($haystack) ) return isnt( count($haystack), $size, $msg );
	else return isoneof( gettype($haystack), array('array','object','string'), $msg );
}

// }}}
// {{{ isa, isnota, like, notlike, except, notexcept

/**
 * Test if an object is from a class or not
 * Params:
 *   object $object = The tested object
 *   object,string $class = The reference object or the class name
 *   string $msg = The test message
 * ----
 * isa( clone $object, $object );
 * isnota( clone $object, $object );
 * ----
 */
function isa( $object, $class, $msg = 'Should be an instance of' )
{
	assert('is_object($object)');
	assert('is_string($class) or is_object($class)');
	return compare( $object, 'is_a', $class, $msg );
}
function isnota( $object, $class, $msg = 'Shouldn\'t be an instance of' )
{
	assert('is_object($object)');
	assert('is_string($class) or is_object($class)');
	return notok( $object, '!is_a', $class, $msg );
}

/**
 * Test is a value match or not an expression
 * Params:
 *   string $test = The tested value
 *   string $expression = The expression
 *   string $msg = The test message
 * ----
 * like( $value, '/\w+/', 'message' );
 * notlike( $value, '/\w+/', 'message' );
 * ----
 */
function like( $test, $expression, $msg = 'Should match a regular expression' )
{
	assert('is_string($test)');
	assert('is_string($expression)');
	return ok( preg_match($expression,$test), $msg );
}
function notlike( $test, $expression, $msg = 'Shouldn\'t match a regular expression' )
{
	assert('is_string($test)');
	assert('is_string($expression)');
	return notok( preg_match($expression,$test), $msg );
}

/**
 * Test if a function trow an exception or not
 * Params:
 *   string,array,Closure $callback = A valid callback
 *   string $exception = The exception class name
 * ----
 * except( 'my_func', 'Exception', 'message' );
 * except( array($my_class,'my_func'), 'Exception', 'message' );
 * except( function(){throw Exception();}, 'Exception', 'message' );
 * ----
 */
function except( $callback, $exception, $msg = 'Should throw an exception' )
{
	assert('is_callable($callback)');
	try { call_user_func($callback); }
	catch( Exception $e )
	{ return isa( $e, $exception, $msg ); }
	return fail( $msg );
}
function notexcept( $callback, $exception, $msg = 'Shouldn\'t throw an exception' )
{
	assert('is_callable($callback)');
	try { call_user_func($callback); }
	catch( Exception $e )
	{ return isnota( $e, $exception, $msg ); }
	return pass( $msg );
}

// }}}
// {{{ isaboolean, isaboolean, isaninteger, isnotaninteger, isastring, isnotastring, isanobject, isnotanobject, isanarray, isnotanarray, isaresource, isnotaresource

/**
 * Test if a value is a boolean or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isaboolean( mysql_connect() );
 * isnotaboolean( 'boolean' );
 * ----
 */
function isaboolean( $test, $msg = 'Should be a boolean' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '==', 'boolean', $msg );
}
function isnotaboolean( $test, $msg = 'Shouldn\'t be a boolean' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '!=', 'boolean', $msg );
}

/**
 * Test if a value is a integer or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isaninteger( 123 );
 * isnotaninteger( '123' );
 * ----
 */
function isaninteger( $test, $msg = 'Should be an integer' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '==', 'integer', $msg );
}
function isnotaninteger( $test, $msg = 'Shouldn\'t be an integer' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '!=', 'integer', $msg );
}

/**
 * Test if a value is a number or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isanumber( '123' );
 * isnotanumber( 'abc' );
 * ----
 */
function isanumber( $test, $msg = 'Should be a number' )
{
	assert('is_string($msg)');
	return compare( $test, 'is_numeric', 'numeric', $msg );
}
function isnotanumber( $test, $msg = 'Shouldn\'t be a number' )
{
	assert('is_string($msg)');
	return compare( $test, '!is_numeric', 'numeric', $msg );
}

/**
 * Test if a value is a string or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isastring( '123' );
 * isnotastring( 123 );
 * ----
 */
function isastring( $test, $msg = 'Should be a string' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '==', 'string', $msg );
}
function isnotastring( $test, $msg = 'Shouldn\'t be a string' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '!=', 'string', $msg );
}

/**
 * Test if a value is an object or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isanobject( (object)array() );
 * isnotanobject( 'object' );
 * ----
 */
function isanobject( $test, $msg = 'Should be an object' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '==', 'object', $msg );
}
function isnotanobject( $test, $msg = 'Shouldn\'t be an object' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '!=', 'object', $msg );
}

/**
 * Test if a value is an array or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isanarray( array() );
 * isnotanarray( 'array' );
 * ----
 */
function isanarray( $test, $msg = 'Should be an array' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '==', 'array', $msg );
}
function isnotanarray( $test, $msg = 'Shouldn\'t be an array' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '!=', 'array', $msg );
}

/**
 * Test if a value is an resource or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isaresource( mysql_connect() );
 * isnotaresource( 'resource' );
 * ----
 */
function isaresource( $test, $msg = 'Should be a resource' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '==', 'resource', $msg );
}
function isnotaresource( $test, $msg = 'Shouldn\'t be a resource' )
{
	assert('is_string($msg)');
	return compare( gettype($test), '!=', 'resource', $msg );
}

/**
 * Test if a value is null or not
 * Params:
 *   mixed $test = The tested value
 *   string $msg = The test message
 * ----
 * isnull( null );
 * isnotnull( '' );
 * ----
 */
function isnull( $test, $msg = 'Should be null' )
{
	assert('is_string($msg)');
	return compare( $test, 'is_null', null, $msg );
}
function isnotnull( $test, $msg = 'Shouldn\'t be null' )
{
	assert('is_string($msg)');
	return compare( $test, '!is_null', null, $msg );
}

// }}}
// {{{ compare, diag, fail, pass, fault, result, report, plan, test

/**
 * Test a value with a particular operator
 * Params:
 *   mixed $test = The tested value
 *   string $operator = The operator
 *   mixed $expected = The expected value
 *   string $msg = The test message
 * ----
 * compare( '123', '===', 123', 'message' );
 * ----
 */
function compare( $test, $operator, $expected, $msg )
{
	switch( $operator )
	{
	case '==': $ok = ($test==$expected); break;
	case '<>':case '!=': $ok = ($test!=$expected); break;
	case '===': $ok = ($test===$expected); break;
	case '!==': $ok = ($test!==$expected); break;
	case '>': $ok = ($test>$expected); break;
	case '<': $ok = ($test<$expected); break;
	case '>=': $ok = ($test>=$expected); break;
	case '<=': $ok = ($test<=$expected); break;
	case 'in_array': $ok = in_array($test,$expected); break;
	case '!in_array': $ok = !in_array($test,$expected); break;
	case 'is_numeric': $ok = is_numeric($test); break;
	case '!is_numeric': $ok = !is_numeric($test); break;
	case 'is_null': $ok = is_null($test); break;
	case '!is_null': $ok = !is_null($test); break;
	case 'is_a': $ok = is_a( $test, is_object($expected)?get_class($expected):$expected ); break;
	case '!is_a': $ok = is_a( $test, is_object($expected)?get_class($expected):$expected ); break;
	default: assert('false and "Invalide compare operator"');
	}
	result( $ok ? pass($msg) : fail($msg) );
	if( ! $ok )
	{
		ob_start(); var_dump($test); $test = preg_replace('/\s+/',' ',ob_get_clean());
		ob_start(); var_dump($expected); $expected = preg_replace('/\s+/',' ',ob_get_clean());
		diag( sprintf('#  obtained: %s %s', str_repeat(' ',strlen($operator)), $test) );
		diag( sprintf('#  expected: %s %s', $operator, $expected) );
	}
	return $ok;
}

/**
 * Display a message
 * Params:
 *   string $msg = The message
 */
function diag( $msg )
{
	static $first;
	if( PHP_SAPI == 'cli' )
		fwrite(STDERR, $msg.PHP_EOL);
	else
	{
		if( ! headers_sent() ) header('Content-Type: text/html' );
		if( ! $first ) { $first = true; echo '<pre>'; }
		echo $msg,PHP_EOL;
	}
}

/**
 * Indiquate that a test fails
 * Params:
 *   string,null $msg = The test message
 * Returns:
 *   integer = Return the number af fails if $msg is null
 * ----
 * fail( 'message' ); // add one failed test
 * echo fail(); // display "1"
 * ----
 */
function fail( $msg = null )
{
	static $count;
	if( is_null($msg) ) return $count;
	$count++;
	result( false, $msg );
	return false;
}

/**
 * Indiquate that a test pass
 * Params:
 *   string,null $msg = The test message
 * Return:
 *   integer = The number of pass if $msg is null
 * ----
 * pass( 'message' ); // add one passed test
 * echo pass(); // display "1"
 * ----
 */
function pass( $msg = null )
{
	static $count;
	if( is_null($msg) ) return $count;
	$count++;
	result( true, $msg );
	return true;
}

/**
 * Indiquate that a test produce a fault
 * Params:
 *   exception,null $exception = The throwed exception
 * Return:
 *   integer = The number of fault if $exception is null
 * ----
 * fault( throw Exception() ) // add one faulted test
 * echo fault(); // display "1"
 * ----
 */
function fault( $exception = null )
{
	static $count;
	if( is_null($exception) ) return $count;
	assert('$exception instanceof Exception');
	$count++;
	if( $exception instanceof ErrorException )
		diag( sprintf('# %s - %s: %s', get_class($exception), strtr($exception->getSeverity(),array(E_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_ERROR=>'User error',E_USER_WARNING=>'User warning',E_USER_NOTICE=>'User notice',E_STRICT=>'Strict',E_RECOVERABLE_ERROR=>'Recoverable error',E_DEPRECATED=>'Deprecated','User deprecated')), $exception->getMessage()) );
	else
		diag( sprintf('# %s - %s: %s', get_class($exception), $exception->getCode(), $exception->getMessage()) );
	preg_replace(array('/^#(\d+) /m','/\s# \{main\}/','/'.preg_quote(getcwd(),'/').'/'),array('#  ','','.'),$exception->getTraceAsString());
	diag( preg_replace(array('/^#(\d+) /m','/\s# \{main\}/','/'.preg_quote(getcwd(),'/').'/'),array('#  ','','.'),$exception->getTraceAsString()) );
}

/**
 * Display the result of an executed test
 * Indiquate to hide or show passed test when $msg is a boolean
 * Params:
 *   bool,null $ok = The test result
 *   string,bool,null $msg = The test message
 * Returns:
 *   integer = The number of executeed tests if $msg is null
 * ----
 * result( $ok, 'message' ); // add one execute test
 * echo result(); // display "1"
 * ----
 */
function result( $ok = null, $msg = null )
{
	static $count;
	static $skip;
	if( is_bool($msg) ) { $skip = $msg; return; }
	if( is_null($msg) ) return $count;
	$count++;
	if( ! $skip or ! $ok ) diag( sprintf('%d %s - %s', $count, $ok?'ok    ':'not ok', $msg) );
	return $ok;
}

/**
 * Indiquate to hide or show passed test
 * ----
 * skip_ok();
 * dont_skip_ok();
 * ----
 */
function skip_ok()
{
	result(null,true);
}
function dont_skip_ok()
{
	result(null,false);
}

/**
 * Display report of all executed or planed test
 * This function is executed at the end of the script
 */
function report()
{
	if( result()!=plan() and plan() )
		diag( sprintf('# Looks like you plan %d but ran %d.', plan(), result()) );
	if( fault() )
		diag( sprintf('# Looks like %d faults is still alive.', fault()) );
	if( result() and fail() )
		diag( sprintf('# Looks like you fails %d tests of %d.', fail(), result()) );
	elseif( result() and result()==plan() and !fail() and !fault() )
		diag( '# Perfect!' );
}
register_shutdown_function('report');

/**
 * Indiquate an additional number of planned tests
 * Params:
 *   integer $add = Number of added tests
 *   true $add = Reset the number of planned tests
 * Returns:
 *   integer = Number og planned tests if $add is null
 * ----
 * plan(1); // add one planned test
 * plan(2); // add tow planned tests
 * echo plan(); // display "3"
 * ----
 */
function plan( $add = null )
{
	static $count;
	if( is_null($add) ) return $count;
	elseif( $add === true ) return $count=0;
	$count += $add;
}

/**
 * Error handler
 * DON'T CALL THIS FUNCTION DIRECTLY
 * Params:
 *   integer = Error code
 *   string = Error message
 *   string = Error file
 *   integer = Error line
 * ----
 * set_error_handler('catch_error');
 * restore_error_handler();
 * ----
 */
function catch_error( $no, $msg, $file, $line )
{
	throw new ErrorException($msg, 0, $no, $file, $line);
}

/**
 * Exception handler
 * DON'T CALL THIS FUNCTION DIRECTLY
 * Params:
 *   Exception $e = Exception object
 */
function catch_exception( $e )
{
	fault( $e );
}

/**
 * Indiquate to catch error and exception or not
 * ----
 * catch_fault();
 * uncatch_fault();
 * ----
 */
function catch_fault()
{
	set_error_handler('catch_error');
	set_exception_handler('catch_exception');
}
function uncatch_fault()
{
	restore_exception_handler();
	restore_error_handler();
}

/**
 * Execute a bunch of tests
 * Syntax:
 *   test( $name|$callback... )
 * Params:
 *   string $name = The name of the test
 *   string,array,Closure $callback = A valid callback
 * ---
 * test( 'my_test', 'my_func' );
 * test( 'my_test', array($my_class,'my_func') );
 * test( 'my_test', function(){
 *   ok( true, 'msg' );
 *   notok( false, 'msg' );
 * });
 * ----
 */
function test()
{
	set_error_handler('catch_error');
	$tests = func_get_args();
	foreach( $tests as $test )
		if( is_callable($test) ) try { call_user_func($test); }
			catch( Exception $e ) { fault( $e ); }
		elseif( is_string($test) ) diag( sprintf('= %s', $test) );
	restore_error_handler();
}

// }}}
// {{{ --compatibility

if( ! defined('E_DEPRECATED') ) define('E_DEPRECATED',8192);

// }}}
