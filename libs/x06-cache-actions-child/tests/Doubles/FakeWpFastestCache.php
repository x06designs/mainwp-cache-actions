<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Doubles;

/**
 * Mimics WpFastestCache::deleteCache(): fires `wpfc_delete_cache` only when the purge succeeded.
 */
final class FakeWpFastestCache {

	/** @var list<bool> */
	public array $delete_cache_calls = array();

	private bool $is_purge_successful;

	public function __construct( bool $isPurgeSuccessful = true ) {
		$this->is_purge_successful = $isPurgeSuccessful;
	}

	public function deleteCache( bool $minified = false ): void {
		$this->delete_cache_calls[] = $minified;
		if ( $this->is_purge_successful ) {
			do_action( 'wpfc_delete_cache' );
		}
	}
}
