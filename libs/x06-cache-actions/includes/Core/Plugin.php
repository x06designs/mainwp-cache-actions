<?php
/**
 * Plugin bootstrap container.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Core;

use Inpsyde\Modularity\Module\Module;
use Inpsyde\Modularity\Package;
use Inpsyde\Modularity\Properties\PluginProperties;

/**
 * Builds the Inpsyde Modularity package and boots every feature module.
 *
 * Feature modules are AUTO-DISCOVERED: each `includes/Features/<Feature>/`
 * directory that ships a `<Feature>Module` class is registered automatically,
 * so the `feature` generator never has to hand-edit this file.
 */
final class Plugin {

	/**
	 * Boot the plugin from its main file.
	 *
	 * @param string $file Absolute path to the plugin's main file.
	 */
	public static function boot( string $file ): void {
		$properties = PluginProperties::new( $file );
		$package    = Package::new( $properties );

		foreach ( self::discover_modules( dirname( $file ) . '/includes/Features' ) as $module ) {
			$package->addModule( $module );
		}

		$package->boot();
	}

	/**
	 * Discover one `<Feature>Module` per `Features/<Feature>/` directory.
	 *
	 * Convention: `X06CacheActions\Features\<Feature>\<Feature>Module`.
	 *
	 * @param string $features_dir Absolute path to includes/Features.
	 * @return array<int, Module>
	 */
	private static function discover_modules( string $features_dir ): array {
		$modules = array();

		foreach ( (array) glob( $features_dir . '/*', GLOB_ONLYDIR ) as $dir ) {
			$feature = basename( (string) $dir );
			$class   = sprintf( 'X06CacheActions\\Features\\%s\\%sModule', $feature, $feature );

			if ( class_exists( $class ) ) {
				$module = new $class();
				if ( $module instanceof Module ) {
					$modules[] = $module;
				}
			}
		}

		return $modules;
	}
}
