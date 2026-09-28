<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Core;

use X06CacheActionsChild\Features\CacheActions\CacheActionsModule;
use X06CacheActionsChild\Features\Updates\UpdatesModule;

final class Plugin {

	/**
	 * @param string $file Absolute path to the plugin's main file.
	 */
	public static function boot( string $file ): void {
		( new CacheActionsModule() )->register();
		( new UpdatesModule( $file ) )->register();
	}
}
