<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions;

/**
 * The operations the MainWP Dashboard extension may request. Values are part of the
 * dashboard ↔ child contract and must match the dashboard's schema enum.
 */
final class Operation {

	public const CLEAR_CACHES          = 'clear_caches';
	public const CLEAR_CACHES_MINIFIED = 'clear_caches_minified';
	public const SYNC_LIBRARY          = 'sync_library';

	public const ALL = array(
		self::CLEAR_CACHES,
		self::CLEAR_CACHES_MINIFIED,
		self::SYNC_LIBRARY,
	);

	/**
	 * @param mixed $value Raw request value.
	 */
	public static function try_from( $value ): ?string {
		return is_string( $value ) && in_array( $value, self::ALL, true ) ? $value : null;
	}
}
