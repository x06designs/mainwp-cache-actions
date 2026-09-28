<?php
/**
 * PSR-4 autoloader for the X06CacheActionsChild namespace. The plugin does not load Composer's
 * autoloader at runtime; the one bundled package, the update checker, brings its own loader.
 *
 * @package X06CacheActionsChild
 */

declare(strict_types=1);

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'X06CacheActionsChild\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$file = __DIR__ . '/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);
