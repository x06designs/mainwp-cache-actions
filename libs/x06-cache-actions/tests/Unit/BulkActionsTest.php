<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Functions;
use X06CacheActions\Features\CacheActions\BulkActions;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class BulkActionsTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		$this->stubTranslationFunctions();
	}

	public function test_appends_one_entry_per_operation_after_mainwp_actions(): void {
		Functions\expect( 'mainwp_current_user_can' )->once()->with( 'extension', 'x06-cache-actions' )->andReturn( true );

		$actions = ( new BulkActions() )->add(
			array(
				'sync'   => 'Sync Data',
				'delete' => 'Remove',
			)
		);

		$this->assertSame(
			array(
				'sync'                                    => 'Sync Data',
				'delete'                                  => 'Remove',
				'x06_cache_actions_clear_caches'          => 'Clear caches',
				'x06_cache_actions_clear_caches_minified' => 'Clear caches + minified',
				'x06_cache_actions_sync_library'          => 'Sync Elementor library',
			),
			$actions
		);
	}

	public function test_adds_nothing_without_the_extension_capability(): void {
		Functions\expect( 'mainwp_current_user_can' )->once()->with( 'extension', 'x06-cache-actions' )->andReturn( false );

		$this->assertSame( array( 'sync' => 'Sync Data' ), ( new BulkActions() )->add( array( 'sync' => 'Sync Data' ) ) );
	}
}
