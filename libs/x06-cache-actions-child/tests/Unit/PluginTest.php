<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use X06CacheActionsChild\Core\Plugin;
use X06CacheActionsChild\Features\CacheActions\CacheActionsModule;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class PluginTest extends TestCase {

	public function test_boot_hooks_the_cache_actions_handler(): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'local' );
		Filters\expectAdded( 'mainwp_child_extra_execution' )
			->once()
			->with( \Mockery::on( static fn( $callback ): bool => is_array( $callback ) && $callback[0] instanceof CacheActionsModule && 'handle' === $callback[1] ), 10, 2 );

		Plugin::boot( __DIR__ . '/../../x06-cache-actions-child.php' );
	}
}
