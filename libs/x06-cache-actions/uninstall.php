<?php
/**
 * Uninstall handler. Fired by WordPress when the plugin is deleted.
 *
 * AUTO-DISCOVERS every feature module (mirroring Core\Plugin) and calls its
 * optional static `uninstall()` so each feature owns its own teardown
 * (drop tables / delete options). No per-feature edit required.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

foreach ( (array) glob( __DIR__ . '/includes/Features/*', GLOB_ONLYDIR ) as $dir ) {
	$feature = basename( (string) $dir );
	$module  = sprintf( '\\X06CacheActions\\Features\\%s\\%sModule', $feature, $feature );
	if ( class_exists( $module ) && method_exists( $module, 'uninstall' ) ) {
		$module::uninstall();
	}
}
