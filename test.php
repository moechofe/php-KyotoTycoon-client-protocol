<?php

require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

test(

	'Test simple operation: get,set,clear,replace,add,append', function()
	{
		$kt = kt('http://localhost:1978');
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


