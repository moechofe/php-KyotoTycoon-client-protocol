A PHP API for Kyoto Tycoon
==========================

Experimental API to communicate with a [Kyoyo Tycoon](http://fallabs.com/kyototycoon/) server.
Using RPC protocol for now, but I'm plan to implement Rest and binary protocol too.

Short example using the UI:

	<?php
	// Start a server with the command line: ktserver
	$kt->kt('http://localhost:1978');
	// Setting records
	$kt->set('日本','東京')
		 ->set('france','paris');
	// Getting a record
	$city = $kt->get('日本');
	$city = $kt->france;
	// Browsing records
	foreach( $kt as $k => $v )
		echo "country:$k city:$v";

Look at the _test.php_ script for more examples.
