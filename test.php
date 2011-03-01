<?php

require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

test(

	'Test simple operation: get,set,clear,replace,add,append', function()
	{
		$kt = kt('http://martibox:1978');
		ok( $kt->clear );
		except( function()use($kt){$kt->replace('a','academy');}, 'KyotoTycoon\InconsistencyException' );
		except( function()use($kt){$kt->get('a');}, 'KyotoTycoon\InconsistencyException' );
		ok( $kt->add('a', 'alien') );
		is( $kt->get('a'), 'alien' );
		ok( $kt->set('a', 'ananas') );
		is( $kt->get('a'), 'ananas' );
		ok( $kt->replace('a', 'akira') );
		is( $kt->get('a'), 'akira');
		except( function()use($kt){$kt->add('a', 'aligator');}, 'KyotoTycoon\InconsistencyException' );
		is( $kt->get('a'), 'akira' );
		ok( $kt->append('a', ' kurozawa') );
		is( $kt->get('a'), 'akira kurozawa' );
	}

);


