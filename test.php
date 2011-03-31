<?php

/**
 * Start a Kyoto Tycoon server with a TreeDB: ktserver +
 */

require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

define('server_uri','http://martibox:1978');

skip_ok();

test(/*
	// {{{ Test simple operations

	'Test simple operations: get,set,clear,replace,add,append,remove', function()
	{
		plan(15);
		$kt = new KyotoTycoon\API(server_uri);
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

	// }}}
	// {{{ Test sequence operations

	'Test sequence operations: increment, increment_double', function()
	{
		plan(7);
		$kt = new KyotoTycoon\API(server_uri);
		ok( $kt->clear );
		is( $kt->increment('i'), 1 );
		is( $kt->increment('i',1), 2 );
		is( $kt->increment('i',-1), 1 );
		is( $kt->increment('i','-1'), 0 );
		is( $kt->increment('i','-2'), -2 );
		ok( $kt->set('i','one') );
	},

	// }}}
	// {{{ Test cas command

	'Test cas command', function()
	{
		plan(11);
		$kt = new KyotoTycoon\API(server_uri);
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

	// }}}
	// {{{ Test match_prefix and match_regex

	'Test match_prefix and match_regex', function()
	{
		plan(17);
		$kt = new KyotoTycoon\API(server_uri);
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
		ok( false!==array_search('b.a.c', $r) );
		ok( false!==array_search('b.c.a', $r) );
		isanarray( $r=$kt->match_regex('\w\.c\.\w') );
		has( $r, 2 );
		ok( false!==array_search('a.c.b', $r) );
		ok( false!==array_search('b.c.a', $r) );
	},

	// }}}
	// {{{ Test cursor functions

	'Test cursor functions: cur_jump, cur_step, cur_set_value, cur_remove, cur_get_key, cur_get_value, cur_get', function()
	{
		$kt = new KyotoTycoon\API(server_uri);

		plan(4);
		ok( $kt->clear );
		ok( $kt->set('a','ananas') );
		ok( $kt->set('b','banana') );
		ok( $kt->set('c','citrus') );

		plan(5);
		ok( $kt->cur_jump(1) );
		is( $kt->cur_get(1), array('a'=>'ananas') );
		is( $kt->cur_get(1), array('b'=>'banana') );
		is( $kt->cur_get(1), array('c'=>'citrus') );
		except( function()use($kt){$kt->cur_get(1);}, 'OutOfBoundsException' );

		plan(7);
		ok( $kt->cur_jump(1) );
		is( $kt->cur_get(1,false), array('a'=>'ananas') );
		ok( $kt->cur_step(1) );
		is( $kt->cur_get(1,false), array('b'=>'banana') );
		ok( $kt->cur_step(1) );
		is( $kt->cur_get(1,false), array('c'=>'citrus') );
		except( function()use($kt){$kt->cur_step(1);}, 'OutOfBoundsException' );

		plan(17);
		ok( $kt->cur_jump(1) );
		is( $kt->cur_get_key(1,false), 'a' );
		is( $kt->cur_get_value(1,true), 'ananas' );
		is( $kt->cur_get_key(1,false), 'b' );
		is( $kt->cur_get_value(1,true), 'banana' );
		is( $kt->cur_get_key(1,false), 'c' );
		is( $kt->cur_get_value(1,true), 'citrus' );
		ok( $kt->cur_step_back(1) );
		is( $kt->cur_get_key(1,false), 'c' );
		is( $kt->cur_get_value(1,false), 'citrus' );
		ok( $kt->cur_step_back(1) );
		is( $kt->cur_get_key(1,false), 'b' );
		is( $kt->cur_get_value(1,false), 'banana' );
		ok( $kt->cur_step_back(1) );
		is( $kt->cur_get_key(1,false), 'a' );
		is( $kt->cur_get_value(1,false), 'ananas' );
		except( function()use($kt){$kt->cur_step_back(1);}, 'OutOfBoundsException' );

		plan(10);
		ok( $kt->cur_jump_back(1) );
		is( $kt->cur_get(1,false), array('c'=>'citrus') );
		ok( $kt->cur_remove(1) );
		ok( $kt->cur_step_back(1) );
		is( $kt->cur_get(1,false), array('b'=>'banana') );
		ok( $kt->cur_remove(1) );
		ok( $kt->cur_step_back(1) );
		is( $kt->cur_get(1,false), array('a'=>'ananas') );
		ok( $kt->cur_remove(1) );
		except( function()use($kt){$kt->cur_step_back(1);}, 'OutOfBoundsException' );
	},

	// }}}
	// {{{ Test fluent and quick interface

	'Test fluent and quick interface', function()
	{
		plan(52);
		$kt = kt(server_uri);
		isnull( $kt->clear->c );
		ok( $kt->a('ananas') );
		ok( $kt->bat('battle') );
		ok( $kt->ban('banana') );
		ok( $kt->c('citrus') );
		is( $kt->a, 'ananas' );
		is( $kt->ban, 'banana' );
		is( $kt->bat, 'battle' );
		is( $kt->c, 'citrus' );
		$a = array('ban'=>'banana','bat'=>'battle');
		foreach( $kt->begin('ba') as $k => $v )
		{ is( $k, key($a) ); is( $v, current($a) ); next($a); }
		$a = array('a'=>'ananas','ban'=>'banana','bat'=>'battle');
		foreach( $kt->search('.*a.*') as $k => $v )
		{ is( $k, key($a) ); is( $v, current($a) ); next($a); }
		$a = array('ban'=>'banana','bat'=>'battle');
		foreach( $kt->prefix('ba') as $k )
		{ is( $k, key($a) ); next($a); }
		$a = array('a'=>'ananas','ban'=>'banana','bat'=>'battle');
		foreach( $kt->regex('.*a.*') as $k )
		{ is( $k, key($a) ); next($a); }
		ok( isset($kt->c) );
		unset( $kt->c );
		isnull( $kt->c );
		notok( isset($kt->c) );
		$a = array('ban'=>'banana','bat'=>'battle');
		foreach( $kt->forward('ban') as $k => $v )
		{ is( $k, key($a) ); is( $v, current($a) ); next($a); }
		$a = array('ban'=>'banana','a'=>'ananas');
		foreach( $kt->backward('ban') as $k => $v )
		{ is( $k, key($a) ); is( $v, current($a) ); next($a); }
		is( $kt->inc('i'), 1 );
		is( $kt->inc('i',2), 3 );
		is( $kt->inc('f',0.1), 0.1 );
		is( $kt->inc('f',0.2), 0.3 );
		ok( $kt->set('a','akira') );
		ok( $kt->cat('a',' kurozawa') );
		is( $kt->get('a'), 'akira kurozawa' );
		notok( $kt->add('a','alien') );
		ok( $kt->rep('a','alien') );
		ok( $kt->del('a') );
		notok( $kt->del('a') );
		notok( $kt->rep('a','alien') );
		ok( $kt->add('a','alien') );
		notok( $kt->cas('a','ananas','akira') );
		ok( $kt->cas('a','alien','akira') );
		$a = null;
		$c = 'citrus';
		truly( $kt->to('a',$a)->from('c',$c), $kt );
		is( $kt->c, $c );
	},

	// }}}
	// {{{ Test ArrayAccess

	'Test ArrayAccess', function()
	{
		plan(6);
		$kt = kt(server_uri);
		ok( $kt->clear );
		$kt['a'] = 'ananas';
		$kt['b'] = 'banana';
		$kt['c'] = 'citrus';
		is( $kt['a'], 'ananas' );
		is( $kt['b'], 'banana' );
		is( $kt['c'], 'citrus' );
		ok( isset($kt['a']) );
		unset($kt['a']);
		notok( isset($kt['a']) );
	},

	// }}}
*/
	'Test REST procedures', function()
	{
		$kt = new KyotoTycoon\API(server_uri);
		ok( $kt->set( 'japan', 'tokyo' ) );
		is( $kt->getful('japan'), 'tokyo' );
	}

);

