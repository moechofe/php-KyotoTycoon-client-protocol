<?php
/**
 * Start a Kyoto Tycoon server with a TreeDB: ktserver +
 */

require_once 'lib.test.php';
require_once 'lib.kyoto.php';

define('server_uri','http://localhost:1978');

qad\test\diag('Start the kyoto-tycoon server with "ktserver +"');

qad\test\skip_ok();

qad\test\test(
	// {{{ qad\test simple operations

	'Test simple operations: get,set,clear,replace,add,append,remove', function()
	{
		qad\test\plan(15);
		$kt = new qad\kyoto\API(server_uri);
		qad\test\ok( $kt->clear );
		qad\test\except( function()use($kt){$kt->replace('a','academy');}, 'qad\kyoto\InconsistencyException' );
		qad\test\except( function()use($kt){$kt->get('a');}, 'qad\kyoto\InconsistencyException' );
		qad\test\except( function()use($kt){$kt->remove('a');}, 'qad\kyoto\InconsistencyException' );
		qad\test\ok( $kt->add('a', 'alien') );
		qad\test\is( $kt->get('a'), 'alien' );
		qad\test\ok( $kt->set('a', 'ananas') );
		qad\test\is( $kt->get('a'), 'ananas' );
		qad\test\ok( $kt->replace('a', 'akira') );
		qad\test\is( $kt->get('a'), 'akira');
		qad\test\except( function()use($kt){$kt->add('a', 'aligator');}, 'qad\kyoto\InconsistencyException' );
		qad\test\is( $kt->get('a'), 'akira' );
		qad\test\ok( $kt->append('a', ' kurozawa') );
		qad\test\is( $kt->get('a'), 'akira kurozawa' );
		qad\test\ok( $kt->remove('a') );
	},

	// }}}
	// {{{ qad\test sequence operations

	'Test sequence operations: increment, increment_double', function()
	{
		qad\test\plan(8);
		$kt = new qad\kyoto\API(server_uri);
		qad\test\ok( $kt->clear );
		qad\test\is( $kt->increment('i'), 1 );
		qad\test\is( $kt->increment('i',1), 2 );
		qad\test\is( $kt->increment('i',-1), 1 );
		qad\test\is( $kt->increment('i','-1'), 0 );
		qad\test\is( $kt->increment('i','-2'), -2 );
		qad\test\ok( $kt->set('i','1') );
		qad\test\except( function()use($kt){ $kt->increment('i',1); }, '\qad\kyoto\InconsistencyException' );
	},

	// }}}
	// {{{ qad\test cas command

	'Test cas command', function()
	{
		qad\test\plan(11);
		$kt = new qad\kyoto\API(server_uri);
		qad\test\ok( $kt->clear );
		qad\test\except( function()use($kt){$kt->cas('b','bottle','battle');}, 'qad\kyoto\InconsistencyException' );
		qad\test\ok( $kt->set('b','banana') );
		qad\test\except( function()use($kt){$kt->cas('b','bottle','battle');}, 'qad\kyoto\InconsistencyException' );
		qad\test\ok( $kt->set('b','bottle') );
		qad\test\ok( $kt->cas('b','bottle','battle') );
		qad\test\is( $kt->get('b'), 'battle' );
		qad\test\ok( $kt->cas('b','battle',null) );
		qad\test\except( function()use($kt){$kt->get('b');}, 'qad\kyoto\InconsistencyException' );
		qad\test\ok( $kt->cas('b',null,'battle') );
		qad\test\is( $kt->get('b'), 'battle' );
	},

	// }}}
	// {{{ qad\test match_prefix and match_regex

	'Test match_prefix and match_regex', function()
	{
		qad\test\plan(17);
		$kt = new qad\kyoto\API(server_uri);
		qad\test\ok( $kt->clear );
		qad\test\ok( $kt->set('a.b.c','ananas,banana,citrus') );
		qad\test\ok( $kt->set('a.c.b','ananas,citrus,banana') );
		qad\test\ok( $kt->set('b.c.a','banana,citrus,ananas') );
		qad\test\ok( $kt->set('b.a.c','banana,ananas,citrus') );
		qad\test\isanarray( $r=$kt->match_prefix('a.') );
		qad\test\has( $r, 2 );
		qad\test\ok( false!==array_search('a.b.c', $r) );
		qad\test\ok( false!==array_search('a.c.b', $r) );
		qad\test\isanarray( $r=$kt->match_prefix('b.') );
		qad\test\has( $r, 2 );
		qad\test\ok( false!==array_search('b.a.c', $r) );
		qad\test\ok( false!==array_search('b.c.a', $r) );
		qad\test\isanarray( $r=$kt->match_regex('\w\.c\.\w') );
		qad\test\has( $r, 2 );
		qad\test\ok( false!==array_search('a.c.b', $r) );
		qad\test\ok( false!==array_search('b.c.a', $r) );
	},

	// }}}
	// {{{ qad\test cursor functions

	'Test cursor functions: cur_jump, cur_step, cur_set_value, cur_remove, cur_get_key, cur_get_value, cur_get', function()
	{
		$kt = new qad\kyoto\API(server_uri);

		qad\test\plan(4);
		qad\test\ok( $kt->clear );
		qad\test\ok( $kt->set('a','ananas') );
		qad\test\ok( $kt->set('b','banana') );
		qad\test\ok( $kt->set('c','citrus') );

		qad\test\plan(5);
		qad\test\ok( $kt->cur_jump(1) );
		qad\test\is( $kt->cur_get(1), array('a'=>'ananas') );
		qad\test\is( $kt->cur_get(1), array('b'=>'banana') );
		qad\test\is( $kt->cur_get(1), array('c'=>'citrus') );
		qad\test\except( function()use($kt){$kt->cur_get(1);}, 'qad\kyoto\InconsistencyException' );

		qad\test\plan(7);
		qad\test\ok( $kt->cur_jump(1) );
		qad\test\is( $kt->cur_get(1,false), array('a'=>'ananas') );
		qad\test\ok( $kt->cur_step(1) );
		qad\test\is( $kt->cur_get(1,false), array('b'=>'banana') );
		qad\test\ok( $kt->cur_step(1) );
		qad\test\is( $kt->cur_get(1,false), array('c'=>'citrus') );
		qad\test\except( function()use($kt){$kt->cur_step(1);}, 'qad\kyoto\InconsistencyException' );

		qad\test\plan(17);
		qad\test\ok( $kt->cur_jump(1) );
		qad\test\is( $kt->cur_get_key(1,false), 'a' );
		qad\test\is( $kt->cur_get_value(1,true), 'ananas' );
		qad\test\is( $kt->cur_get_key(1,false), 'b' );
		qad\test\is( $kt->cur_get_value(1,true), 'banana' );
		qad\test\is( $kt->cur_get_key(1,false), 'c' );
		qad\test\is( $kt->cur_get_value(1,true), 'citrus' );
		qad\test\ok( $kt->cur_step_back(1) );
		qad\test\is( $kt->cur_get_key(1,false), 'c' );
		qad\test\is( $kt->cur_get_value(1,false), 'citrus' );
		qad\test\ok( $kt->cur_step_back(1) );
		qad\test\is( $kt->cur_get_key(1,false), 'b' );
		qad\test\is( $kt->cur_get_value(1,false), 'banana' );
		qad\test\ok( $kt->cur_step_back(1) );
		qad\test\is( $kt->cur_get_key(1,false), 'a' );
		qad\test\is( $kt->cur_get_value(1,false), 'ananas' );
		qad\test\except( function()use($kt){$kt->cur_step_back(1);}, 'qad\kyoto\InconsistencyException' );

		qad\test\plan(10);
		qad\test\ok( $kt->cur_jump_back(1) );
		qad\test\is( $kt->cur_get(1,false), array('c'=>'citrus') );
		qad\test\ok( $kt->cur_remove(1) );
		qad\test\ok( $kt->cur_step_back(1) );
		qad\test\is( $kt->cur_get(1,false), array('b'=>'banana') );
		qad\test\ok( $kt->cur_remove(1) );
		qad\test\ok( $kt->cur_step_back(1) );
		qad\test\is( $kt->cur_get(1,false), array('a'=>'ananas') );
		qad\test\ok( $kt->cur_remove(1) );
		qad\test\except( function()use($kt){$kt->cur_step_back(1);}, 'qad\kyoto\InconsistencyException' );
	},

	// }}}
	// {{{ qad\test fluent and quick interface

	'Test fluent and quick interface', function()
	{
		qad\test\plan(52);
		$kt = qad\kyoto\UI(server_uri)->outofbound_return_null;
		qad\test\isnull( $kt->clear->c );
		qad\test\ok( $kt->a('ananas') );
		qad\test\ok( $kt->bat('battle') );
		qad\test\ok( $kt->ban('banana') );
		qad\test\ok( $kt->c('citrus') );
		qad\test\is( $kt->a, 'ananas' );
		qad\test\is( $kt->ban, 'banana' );
		qad\test\is( $kt->bat, 'battle' );
		qad\test\is( $kt->c, 'citrus' );
		$a = array('ban'=>'banana','bat'=>'battle');
		foreach( $kt->begin('ba') as $k => $v )
		{ qad\test\is( $k, key($a) ); qad\test\is( $v, current($a) ); next($a); }
		$a = array('a'=>'ananas','ban'=>'banana','bat'=>'battle');
		foreach( $kt->search('.*a.*') as $k => $v )
		{ qad\test\is( $k, key($a) ); qad\test\is( $v, current($a) ); next($a); }
		$a = array('ban'=>'banana','bat'=>'battle');
		foreach( $kt->prefix('ba') as $k )
		{ qad\test\is( $k, key($a) ); next($a); }
		$a = array('a'=>'ananas','ban'=>'banana','bat'=>'battle');
		foreach( $kt->regex('.*a.*') as $k )
		{ qad\test\is( $k, key($a) ); next($a); }
		qad\test\ok( isset($kt->c) );
		unset( $kt->c );
		qad\test\isnull( $kt->c );
		qad\test\notok( isset($kt->c) );
		$a = array('ban'=>'banana','bat'=>'battle');
		foreach( $kt->forward('ban') as $k => $v )
		{ qad\test\is( $k, key($a) ); qad\test\is( $v, current($a) ); next($a); }
		$a = array('ban'=>'banana','a'=>'ananas');
		foreach( $kt->backward('ban') as $k => $v )
		{ qad\test\is( $k, key($a) ); qad\test\is( $v, current($a) ); next($a); }
		qad\test\is( $kt->inc('i'), 1 );
		qad\test\is( $kt->inc('i',2), 3 );
		qad\test\is( $kt->inc('f',0.1), 0.1 );
		qad\test\is( $kt->inc('f',0.2), 0.3 );
		qad\test\ok( $kt->set('a','akira') );
		qad\test\ok( $kt->cat('a',' kurozawa') );
		qad\test\is( $kt->get('a'), 'akira kurozawa' );
		qad\test\notok( $kt->add('a','alien') );
		qad\test\ok( $kt->rep('a','alien') );
		qad\test\ok( $kt->del('a') );
		qad\test\notok( $kt->del('a') );
		qad\test\notok( $kt->rep('a','alien') );
		qad\test\ok( $kt->add('a','alien') );
		qad\test\notok( $kt->cas('a','ananas','akira') );
		qad\test\ok( $kt->cas('a','alien','akira') );
		$a = null;
		$c = 'citrus';
		qad\test\truly( $kt->to('a',$a)->from('c',$c), $kt );
		qad\test\is( $kt->c, $c );
	},

	// }}}
	// {{{ qad\test ArrayAccess

	'Test ArrayAccess', function()
	{
		qad\test\plan(6);
		$kt = qad\kyoto\UI(server_uri);
		qad\test\ok( $kt->clear );
		$kt['a'] = 'ananas';
		$kt['b'] = 'banana';
		$kt['c'] = 'citrus';
		qad\test\is( $kt['a'], 'ananas' );
		qad\test\is( $kt['b'], 'banana' );
		qad\test\is( $kt['c'], 'citrus' );
		qad\test\ok( isset($kt['a']) );
		unset($kt['a']);
		qad\test\notok( isset($kt['a']) );
	},

	// }}}

	'Test REST procedures', function()
	{
		$kt = new qad\kyoto\API(server_uri);
		qad\test\plan(2);
		qad\test\ok( $kt->set( 'japan', 'tokyo' ) );
		qad\test\is( $kt->getful('japan'), 'tokyo' );
	}

);

