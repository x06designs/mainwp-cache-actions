<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use Elementor\Core\Files\Manager;
use Elementor\Plugin;
use X06CacheActionsChild\Features\CacheActions\Steps\ElementorClearCacheStep;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class ElementorClearCacheStepTest extends TestCase {

	protected function tear_down(): void {
		Plugin::$instance = null;
		parent::tear_down();
	}

	public function test_skips_when_elementor_is_not_initialised(): void {
		Plugin::$instance = null;

		$result = ( new ElementorClearCacheStep() )->run()->to_array();

		$this->assertSame( 'skipped', $result['status'] );
		$this->assertSame( 'plugin_inactive', $result['detail'] );
	}

	public function test_skips_when_the_files_manager_is_missing(): void {
		Plugin::$instance = new Plugin();

		$result = ( new ElementorClearCacheStep() )->run()->to_array();

		$this->assertSame( 'skipped', $result['status'] );
	}

	public function test_clears_files_and_data(): void {
		$manager                       = new Manager();
		Plugin::$instance              = new Plugin();
		Plugin::$instance->files_manager = $manager;

		$result = ( new ElementorClearCacheStep() )->run()->to_array();

		$this->assertSame( 1, $manager->clear_cache_calls );
		$this->assertSame(
			array(
				'step'   => 'elementor_clear_cache',
				'status' => 'done',
				'detail' => null,
			),
			$result
		);
	}
}
