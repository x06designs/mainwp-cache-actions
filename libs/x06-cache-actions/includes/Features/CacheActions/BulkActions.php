<?php
/**
 * Manage Sites bulk-action entries.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

/**
 * Adds one entry per operation to the Sites > Manage Sites bulk-actions menu. The value is
 * the operation prefixed, so the web layer can recognise its own entries.
 */
final class BulkActions {

	public const PREFIX = 'x06_cache_actions_';

	/**
	 * @param array<string, string> $actions Existing bulk actions.
	 * @return array<string, string>
	 */
	public function add( array $actions ): array {
		if ( ! mainwp_current_user_can( 'extension', CacheActionsModule::EXTENSION_SLUG ) ) {
			return $actions;
		}

		$entries = array();
		foreach ( self::labels() as $operation => $label ) {
			$entries[ self::PREFIX . $operation ] = $label;
		}

		return $actions + $entries;
	}

	/**
	 * Translated name per operation value, shown in the menu and as the result modal's title.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			Operation::ClearCaches->value         => __( 'Clear caches', 'x06-cache-actions' ),
			Operation::ClearCachesMinified->value => __( 'Clear caches + minified', 'x06-cache-actions' ),
			Operation::SyncLibrary->value         => __( 'Sync Elementor library', 'x06-cache-actions' ),
		);
	}
}
