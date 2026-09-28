<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Integration;

use WP_UnitTestCase;

/**
 * The suite loads the real bootstrap file without MainWP, so the feature must be waiting for
 * `mainwp_activated`.
 */
final class PluginBootTest extends WP_UnitTestCase {

	public function test_boot_discovers_the_feature_and_waits_for_mainwp(): void {
		$this->assertNotFalse( has_action( 'mainwp_activated' ) );
		$this->assertFalse( has_filter( 'mainwp_managesites_bulk_actions' ) );
	}

	public function test_the_plugin_file_constant_points_at_the_main_file(): void {
		$this->assertSame( dirname( __DIR__, 2 ) . '/x06-cache-actions.php', X06_CACHE_ACTIONS_FILE );
	}

	public function test_the_header_declares_mainwp_and_the_php_floor(): void {
		$header = get_file_data(
			X06_CACHE_ACTIONS_FILE,
			array(
				'requires_plugins' => 'Requires Plugins',
				'requires_php'     => 'Requires PHP',
				'text_domain'      => 'Text Domain',
			)
		);

		$this->assertSame(
			array(
				'requires_plugins' => 'mainwp',
				'requires_php'     => '8.3',
				'text_domain'      => 'x06-cache-actions',
			),
			$header
		);
	}
}
