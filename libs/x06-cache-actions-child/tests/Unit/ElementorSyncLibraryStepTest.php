<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use Brain\Monkey\Functions;
use Elementor\Api;
use Elementor\Plugin;
use X06CacheActionsChild\Features\CacheActions\Steps\ElementorSyncLibraryStep;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class ElementorSyncLibraryStepTest extends TestCase {

	private const INFO_TRANSIENT = 'elementor_remote_info_api_data_' . ELEMENTOR_VERSION;

	protected function set_up(): void {
		parent::set_up();
		Plugin::$instance = new Plugin();
		Api::$calls       = array();
		Api::$library_data = array( 'templates' => array( array( 'id' => 1 ) ) );
	}

	protected function tear_down(): void {
		Plugin::$instance = null;
		parent::tear_down();
	}

	public function test_skips_when_elementor_is_not_initialised(): void {
		Plugin::$instance = null;

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( 'skipped', $result['status'] );
		$this->assertSame( 'plugin_inactive', $result['detail'] );
		$this->assertSame( array(), Api::$calls );
	}

	public function test_force_refreshes_library_and_info_data(): void {
		$this->given_info_transient( array( 'canary_deployment' => false ) );

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( array( array( 'library', true ), array( 'info', true ) ), Api::$calls );
		$this->assertSame(
			array(
				'step'   => 'elementor_sync_library',
				'status' => 'done',
				'detail' => null,
			),
			$result
		);
	}

	public function test_fails_when_the_library_comes_back_empty(): void {
		Api::$library_data = array();
		$this->given_info_transient( array( 'canary_deployment' => false ) );

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( 'failed', $result['status'] );
		$this->assertSame( 'library_sync_failed', $result['detail'] );
	}

	public function test_fails_when_elementor_recorded_an_info_error(): void {
		$this->given_info_transient( array( 'last_error' => '2026-09-27T10:00:00+00:00' ) );

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( 'failed', $result['status'] );
		$this->assertSame( 'info_sync_failed', $result['detail'] );
	}

	public function test_fails_when_no_info_data_was_stored(): void {
		$this->given_info_transient( false );

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( 'info_sync_failed', $result['detail'] );
	}

	public function test_reports_both_failures(): void {
		Api::$library_data = array();
		$this->given_info_transient( array( 'last_error' => '2026-09-27T10:00:00+00:00' ) );

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( 'failed', $result['status'] );
		$this->assertSame( 'sync_failed', $result['detail'] );
	}

	public function test_reads_the_info_result_after_the_refresh_not_before(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( self::INFO_TRANSIENT )
			->andReturnUsing(
				static function (): array {
					$isRefreshed = in_array( array( 'info', true ), Api::$calls, true );
					return $isRefreshed
						? array( 'last_error' => '2026-09-27T10:00:00+00:00' )
						: array( 'canary_deployment' => false );
				}
			);

		$result = ( new ElementorSyncLibraryStep() )->run()->to_array();

		$this->assertSame( 'info_sync_failed', $result['detail'] );
	}

	/**
	 * @param mixed $value Transient value Elementor left behind.
	 */
	private function given_info_transient( $value ): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( self::INFO_TRANSIENT )
			->andReturn( $value );
	}
}
