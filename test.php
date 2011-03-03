<?php

require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

define('server_uri','http://martibox:1978');

test(

	'Test simple operations: get,set,clear,replace,add,append,remove', function()
	{
		plan(15);
		$kt = kt(server_uri);
		ok( $kt->clear );
		except( function()use($kt){$kt->replace('a','academy');}, 'OutOfBoundsException' );
		except( function()use($kt){$kt->get('a');}, 'OutOfBoundsException' );
		except( function()use($kt){$kt->remove('a');}, 'OutOfBoundsException' );
		ok( $kt->add('a', 'alien') );
		is( $kt->get('a'), 'alien' );
		ok( $kt->set('a', 'ananas') );
		is( $kt->get('a'), 'ananas' );
		ok( $kt->replace('a', 'akira') );
		is( $kt->get('a'), 'akira');
		except( function()use($kt){$kt->add('a', 'aligator');}, 'OutOfBoundsException' );
		is( $kt->get('a'), 'akira' );
		ok( $kt->append('a', ' kurozawa') );
		is( $kt->get('a'), 'akira kurozawa' );
		ok( $kt->remove('a') );
	},

	'Test sequence operations: increment, increment_double', function()
	{
		plan(8);
		$kt = kt(server_uri);
		ok( $kt->clear );
		is( $kt->increment('i'), 1 );
		is( $kt->increment('i',1), 2 );
		is( $kt->increment('i',-1), 1 );
		is( $kt->increment('i','-1'), 0 );
		is( $kt->increment('i','-2'), -2 );
		except( function()use($kt){$kt->increment('i','one');}, 'OutOfBoundsException' );
		ok( $kt->set('i','one') );

	},

	'Test error when assertion is unactivated', function()
	{
		plan(3);
		assert_options(ASSERT_ACTIVE,false);
		$kt = kt(server_uri);
		ok( $kt->clear );
		is( $kt->increment('i'), 1 );
		except( function()use($kt){$kt->increment('i','one');}, 'OutOfBoundsException' );
	}

);

