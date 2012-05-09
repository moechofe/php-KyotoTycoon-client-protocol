A PHP5.3+ API for Kyoto Tycoon
==============================

Personal implementation of the [Kyoto Tycoon](http://fallabs.com/kyototycoon/) client in PHP.

Short example using the UI:

```php
<?php
// Start a server with the command line: ktserver
require_once 'kyoto-tycoon.php';

// Get an UI object and clear the database
$kt = kt()->clear;

// Setting records
$kt['日本'] = '東京';
$kt->set('Coruscant','Coruscant');
$kt->France('Paris');

// Getting records
echo $kt['日本'],PHP_EOL;
echo $kt->get('Coruscant'),PHP_EOL;
echo $kt->France,PHP_EOL;

// Browsing records
foreach( $kt->forward() as $k => $v )
	echo "country:$k city:$v",PHP_EOL;
```

----

Read the partial [documentation](./wiki) to learn how to use it.

Look at the _test.php_ script for more examples.

