<?php

declare(strict_types=1);

// Integration bootstrap: boots the real WordPress test suite, then loads the plugin.
$testsDir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

require_once $testsDir . '/includes/functions.php';
require_once __DIR__ . '/Integration/mainwp-functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/x06-cache-actions.php';
	}
);

require $testsDir . '/includes/bootstrap.php';
