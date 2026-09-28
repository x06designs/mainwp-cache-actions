<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Filters;
use RuntimeException;
use X06CacheActions\Features\CacheActions\MainWpChildGateway;
use X06CacheActions\Features\CacheActions\Operation;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class MainWpChildGatewayTest extends TestCase {

	private const PLUGIN_FILE = '/plugins/x06-cache-actions/x06-cache-actions.php';

	public function test_sends_the_operation_through_extra_execution_with_the_child_key(): void {
		Filters\expectApplied( 'mainwp_extension_enabled_check' )
			->once()
			->with( self::PLUGIN_FILE )
			->andReturn( array( 'key' => 'child-key' ) );
		Filters\expectApplied( 'mainwp_fetchurlauthed' )
			->once()
			->with( self::PLUGIN_FILE, 'child-key', 7, 'extra_execution', array( 'x06_cache_op' => 'sync_library' ) )
			->andReturn( array( 'raw' => true ) );

		$raw = ( new MainWpChildGateway( self::PLUGIN_FILE ) )->execute( 7, Operation::SyncLibrary );

		$this->assertSame( array( 'raw' => true ), $raw );
	}

	public function test_raises_the_timeout_for_extra_execution_only_and_only_during_the_call(): void {
		$timeout = null;
		Filters\expectAdded( 'mainwp_fetch_url_site_timeout' )
			->once()
			->with( \Mockery::type( 'Closure' ), 10, 2 )
			->whenHappen(
				static function ( callable $callback ) use ( &$timeout ): void {
					$timeout = $callback;
				}
			);
		Filters\expectApplied( 'mainwp_extension_enabled_check' )->andReturn( array( 'key' => 'k' ) );
		Filters\expectApplied( 'mainwp_fetchurlauthed' )->andReturnUsing(
			function () use ( &$timeout ): array {
				$this->assertNotFalse( has_filter( 'mainwp_fetch_url_site_timeout', $timeout ) );
				return array();
			}
		);

		( new MainWpChildGateway( self::PLUGIN_FILE ) )->execute( 1, Operation::ClearCaches );

		$this->assertIsCallable( $timeout );
		$this->assertSame( 120, $timeout( 20, 'extra_execution' ) );
		$this->assertSame( 20, $timeout( 20, 'sync' ) );
		$this->assertSame( 72000, $timeout( 72000 ) );
		$this->assertFalse( has_filter( 'mainwp_fetch_url_site_timeout', $timeout ) );
	}

	public function test_removes_the_timeout_filter_when_mainwp_throws(): void {
		$timeout = null;
		Filters\expectAdded( 'mainwp_fetch_url_site_timeout' )->whenHappen(
			static function ( callable $callback ) use ( &$timeout ): void {
				$timeout = $callback;
			}
		);
		Filters\expectApplied( 'mainwp_extension_enabled_check' )->andReturn( array( 'key' => 'k' ) );
		Filters\expectApplied( 'mainwp_fetchurlauthed' )->andReturnUsing(
			static function (): void {
				throw new RuntimeException( 'transport' );
			}
		);

		try {
			( new MainWpChildGateway( self::PLUGIN_FILE ) )->execute( 1, Operation::ClearCaches );
			$this->fail( 'Expected the MainWP exception to propagate.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'transport', $exception->getMessage() );
		}

		$this->assertFalse( has_filter( 'mainwp_fetch_url_site_timeout', $timeout ) );
	}

	public function test_sends_an_empty_key_when_mainwp_does_not_answer_the_enabled_check(): void {
		Filters\expectApplied( 'mainwp_extension_enabled_check' )->andReturn( self::PLUGIN_FILE );
		Filters\expectApplied( 'mainwp_fetchurlauthed' )
			->once()
			->with( self::PLUGIN_FILE, '', 3, 'extra_execution', array( 'x06_cache_op' => 'clear_caches' ) )
			->andReturn( false );

		$this->assertFalse( ( new MainWpChildGateway( self::PLUGIN_FILE ) )->execute( 3, Operation::ClearCaches ) );
	}
}
