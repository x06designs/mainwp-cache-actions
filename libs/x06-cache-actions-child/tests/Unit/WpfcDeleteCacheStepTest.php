<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use X06CacheActionsChild\Features\CacheActions\Steps\WpfcDeleteCacheStep;
use X06CacheActionsChild\Tests\Doubles\FakeWpFastestCache;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class WpfcDeleteCacheStepTest extends TestCase {

	protected function tear_down(): void {
		unset( $GLOBALS['wp_fastest_cache'] );
		parent::tear_down();
	}

	public function test_skips_when_wp_fastest_cache_is_not_loaded(): void {
		unset( $GLOBALS['wp_fastest_cache'] );

		$result = ( new WpfcDeleteCacheStep( false ) )->run()->to_array();

		$this->assertSame( 'skipped', $result['status'] );
		$this->assertSame( 'plugin_inactive', $result['detail'] );
	}

	public function test_skips_when_the_global_is_not_wp_fastest_cache(): void {
		$GLOBALS['wp_fastest_cache'] = 'something else';

		$result = ( new WpfcDeleteCacheStep( false ) )->run()->to_array();

		$this->assertSame( 'skipped', $result['status'] );
	}

	public function test_skips_when_the_global_object_has_no_delete_cache_method(): void {
		$GLOBALS['wp_fastest_cache'] = new \stdClass();

		$result = ( new WpfcDeleteCacheStep( false ) )->run()->to_array();

		$this->assertSame( 'skipped', $result['status'] );
		$this->assertSame( 'plugin_inactive', $result['detail'] );
	}

	public function test_deletes_cache_without_minified_files(): void {
		$wpfc                        = new FakeWpFastestCache();
		$GLOBALS['wp_fastest_cache'] = $wpfc;

		$result = ( new WpfcDeleteCacheStep( false ) )->run()->to_array();

		$this->assertSame( array( false ), $wpfc->delete_cache_calls );
		$this->assertSame(
			array(
				'step'   => 'wpfc_delete_cache',
				'status' => 'done',
				'detail' => null,
			),
			$result
		);
	}

	public function test_deletes_cache_and_minified_files(): void {
		$wpfc                        = new FakeWpFastestCache();
		$GLOBALS['wp_fastest_cache'] = $wpfc;

		$result = ( new WpfcDeleteCacheStep( true ) )->run()->to_array();

		$this->assertSame( array( true ), $wpfc->delete_cache_calls );
		$this->assertSame( 'wpfc_delete_cache_and_minified', $result['step'] );
		$this->assertSame( 'done', $result['status'] );
	}

	public function test_fails_when_wpfc_does_not_confirm_the_purge(): void {
		$GLOBALS['wp_fastest_cache'] = new FakeWpFastestCache( false );

		$result = ( new WpfcDeleteCacheStep( false ) )->run()->to_array();

		$this->assertSame( 'failed', $result['status'] );
		$this->assertSame( 'delete_failed', $result['detail'] );
	}

	public function test_an_earlier_purge_in_the_same_request_does_not_count_as_success(): void {
		( new FakeWpFastestCache() )->deleteCache();
		$GLOBALS['wp_fastest_cache'] = new FakeWpFastestCache( false );

		$result = ( new WpfcDeleteCacheStep( false ) )->run()->to_array();

		$this->assertSame( 'failed', $result['status'] );
	}
}
