<?php
/*
if( ! $s = fsockopen( 'martibox', '1978', $code, $msg ) ) var_dump( $code, $msg );
stream_set_timeout($s,3);
stream_set_blocking($s, true);
/*
echo "=======";

fputs($s, "POST /rpc/set HTTP/1.1
Host: martibox:1978
Accept: * / *
Content-Type: text/tab-separated-values; colenc=B
Content-Length: 32

");
fputs($s, "a2V5\tamFwYW4=\t\ndmFsdWU=\tdG9reW8=");
fputs($s, "\r\n");

echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
/*
while( $l = fgets($s) )
	echo strtr($l,array("\r"=>'\r',"\n"=>'\n'));
 * /
echo "=======";
 * /
fputs($s, "POST /rpc/get HTTP/1.1
Host: martibox:1978
Content-Type: application/x-www-form-urlencoded
Content-Length: 9

");
fputs($s, "key=japan");
fputs($s, "\r\n");
/*
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
 * /
fputs($s, "POST /rpc/get HTTP/1.1
Host: martibox:1978
Content-Type: application/x-www-form-urlencoded
Content-Length: 9

");
fputs($s, "key=japan");
fputs($s, "\r\n");

echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;

echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;
echo strtr(fgets($s),array("\r"=>'\r',"\n"=>'\n')),PHP_EOL;

exit;
 */
require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

test(

	'Test simple operation: get,set,clear,replace,add,append', function()
	{
		$kt = kt('http://martibox:1978');
		ok( $kt->clear );
		isnull( $kt->replace('a','academy') );
		isnull( $kt->get('a') );
		ok( $kt->add('a', 'alien') );
		is( $kt->get('a'), 'alien' );
		ok( $kt->set('a', 'ananas') );
		is( $kt->get('a'), 'ananas' );
		ok( $kt->replace('a', 'akira') );
		is( $kt->get('a'), 'akira');
		isnull( $kt->add('a', 'aligator') );
		is( $kt->get('a'), 'akira' );
		ok( $kt->append('a'), ' kurozawa' );
		is( $kt->get('a'), 'akira kurozawa' );
	}

);


