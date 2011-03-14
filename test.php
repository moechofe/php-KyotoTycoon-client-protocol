<?php

require_once 'zimple-test.php';
require_once 'kyoto-tycoon.php';

define('server_uri','http://martibox:1978');

skip_ok();

test(

	'Test simple operations: get,set,clear,replace,add,append,remove', function()
	{
		plan(15);
		$kt = KyotoTycoon/API(server_uri);
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
		$kt = KyotoTycoon/API(server_uri);
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
		$kt = KyotoTycoon/API(server_uri);
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
		$kt = KyotoTycoon/API(server_uri);
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

		'Test cursor functions: cur_jump, cur_step, cur_set_value, cur_remove, cur_get_key, cur_get_value, cur_get', function()
		{
			$kt = KyotoTycoon/API(server_uri);
			$get = function($r) { list($k,$v) = each($r); switch($k) {
				case'a': is( $v, 'ananas' ); break;
				case'b': is( $v, 'banana' ); break;
				case'c': is( $v, 'citrus' ); break; } };

			plan(4);
			ok( $kt->clear );
			ok( $kt->set('a','ananas') );
			ok( $kt->set('b','banana') );
			ok( $kt->set('c','citrus') );

			plan(5);
			ok( $kt->cur_jump(1) );
			for( $i=0; $i<3; $i++ ) $get( $kt->cur_get(1) );
			except( function()use($kt){$kt->cur_get(1);}, 'OutOfBoundsException' );

			plan(7);
			ok( $kt->cur_jump(1) );
			for( $i=0; $i<2; $i++ ) { $get( $kt->cur_get(1,false) ); ok( $kt->cur_step(1) ); }
			$get( $kt->cur_get(1,false) );
			except( function()use($kt){$kt->cur_step(1);}, 'OutOfBoundsException' );

			plan(11);
			ok( $kt->cur_jump(1) );
			for( $i=0; $i<3; $i++ ) $get( array($kt->cur_get_key(1,false) => $kt->cur_get_value(1,true) ) );
			for( $i=0; $i<3; $i++ ) { ok( $kt->cur_step_back(1) ); $get( array($kt->cur_get_key(1,false) => $kt->cur_get_value(1,false) ) ); }
			except( function()use($kt){$kt->cur_step_back(1);}, 'OutOfBoundsException' );

			plan(10);
			ok( $kt->cur_jump_back(1) );
			for( $i=0; $i<2; $i++ ) { $get( $kt->cur_get(1,false) ); ok( $kt->cur_remove(1) ); ok( $kt->cur_step_back(1) ); }
			$get( $kt->cur_get(1,false) ); ok( $kt->cur_remove(1) );
			except( function()use($kt){$kt->cur_step_back(1);}, 'OutOfBoundsException' );
		},

		'Test fluent and quick interface', function()
		{
			$kt = kt(server_uri);
			isnull( $kt->c );
			truly( $kt->clear->a('ananas')->bat('battle')->ban('banana')->c('citrus'), $kt );
			is( $kt->a, 'ananas' );
			is( $kt->ban, 'banana' );
			is( $kt->bat, 'battle' );
			is( $kt->c, 'citrus' );
			foreach( $kt->begin('ba') as $k => $v )
				is( $v, $k=='ban'?'banana':'battle' );
			foreach( $kt->search('.*a.*') as $k => $v ) switch( $k ) {
				case 'a': is( $v, 'ananas' ); break;
				case 'ban': is( $v, 'banana' ); break;
				case 'bat': is( $v, 'battle' ); break; }
			ok( isset($kt->c) );
			unset( $kt->c );
			isnull( $kt->c );
			notok( isset($kt->c) );
			foreach( $kt->forward('ban') as $k => $v )
				is( $v, $k=='ban'?'banana':'battle' );
			foreach( $kt->backward('ban') as $k => $v )
				is( $v, $k=='ban'?'banana':'ananas' );
			is( $kt->inc('i'), 1 ); 
			is( $kt->inc('i',2), 3 ); 
			is( $kt->inc('f',0.1), 0.1 );
			is( $kt->inc('f',0.2), 0.3 );
			is( $kt->set('a','akira')->cat('a',' kurozawa')->get('a'), 'akira kurozawa' );
			notok( $kt->add('a','alien') );
			ok( $kt->rep('a','alien') );
			ok( $kt->del('a') );
			notok( $kt->del('a') );
			notok( $kt->rep('a','alien') );
			ok( $kt->add('a','alien') );
			notok( $kt->cas('a','ananas','akira') );
			notok( $kt->cas('a','alien','akira') );
			from(&
			to(&
		}

);


