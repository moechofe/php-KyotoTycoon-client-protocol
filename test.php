<?php

require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

define('server_uri','http://martibox:1978');

test(
/*
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
		plan(7);
		$kt = kt(server_uri);
		ok( $kt->clear );
		is( $kt->increment('i'), 1 );
		is( $kt->increment('i',1), 2 );
		is( $kt->increment('i',-1), 1 );
		is( $kt->increment('i','-1'), 0 );
		is( $kt->increment('i','-2'), -2 );
		ok( $kt->set('i','one') );
	},

	'Test cas command', function()
	{
		plan(11);
		$kt = kt(server_uri);
		ok( $kt->clear );
		except( function()use($kt){$kt->cas('b','bottle','battle');}, 'OutOfBoundsException' );
		ok( $kt->set('b','banana') );
		except( function()use($kt){$kt->cas('b','bottle','battle');}, 'OutOfBoundsException' );
		ok( $kt->set('b','bottle') );
		ok( $kt->cas('b','bottle','battle') );
		is( $kt->get('b'), 'battle' );
		ok( $kt->cas('b','battle',null) );
		except( function()use($kt){$kt->get('b');}, 'OutOfBoundsException' );
		ok( $kt->cas('b',null,'battle') );
		is( $kt->get('b'), 'battle' );
	},

	'Test match_prefix and match_regex', function()
	{
		plan(16);
		$kt = kt(server_uri);
		ok( $kt->clear );
		ok( $kt->set('a.b.c','ananas,banana,citrus') );
		ok( $kt->set('a.c.b','ananas,citrus,banana') );
		ok( $kt->set('b.c.a','banana,citrus,ananas') );
		ok( $kt->set('b.a.c','banana,ananas,citrus') );
		isanarray( $r=$kt->match_prefix('a.') );
		has( $r, 2 );
		ok( false!==array_search('a.b.c', $r) );
		ok( false!==array_search('a.c.b', $r) );
		isanarray( $r=$kt->match_prefix('b.') );
		has( $r, 2 );
		ok( false!==array_search('a.c.b', $r) );
		ok( false!==array_search('b.c.a', $r) );
		isanarray( $r=$kt->match_regex('\w\.c\.\w') );
		has( $r, 1 );
		ok( false!==array_search('a.c.b', $r) );
	},
*/
		'Test cursor functions: cur_jump, cur_step, cur_set_value, cur_remove, cur_get_key, cur_get_value, cur_get', function()
		{
			plan(8);
			$kt = kt(server_uri);
			ok( $kt->clear );
			ok( $kt->set('a','ananas') );
			ok( $kt->set('b','banana') );
			ok( $kt->set('c','citrus') );
			ok( $kt->cur_jump(1) );
			for( $i=0; $i<3; $i++ )
			{
				$r = $kt->cur_get(1);
				list($k,$v) = each($r);
				switch( $k )
				{
				case'a': is( $v, 'ananas' ); break;
				case'b': is( $v, 'banana' ); break;
				case'c': is( $v, 'citrus' ); break;
				}
			}
			except( function()use($kt){$kt->cur_get(1);}, 'OutOfBoundsException' );
		}

);


