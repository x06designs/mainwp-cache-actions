<?php
/**
 * Cache operation enum.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

/**
 * The operations a child site can run. Values are the dashboard ↔ companion contract and
 * mirror the `op` enum in cache-actions.schema.json.
 */
enum Operation: string {

	case ClearCaches         = 'clear_caches';
	case ClearCachesMinified = 'clear_caches_minified';
	case SyncLibrary         = 'sync_library';
}
