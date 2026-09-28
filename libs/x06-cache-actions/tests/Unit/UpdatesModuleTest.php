<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Psr\Container\ContainerInterface;
use X06CacheActions\Features\Updates\UpdatesModule;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class UpdatesModuleTest extends TestCase {

	/** @var list<array{0: string, 1: string, 2: string}> */
	private array $built = array();

	protected function set_up(): void {
		parent::set_up();
		$this->built = array();
	}

	public function test_builds_the_checker_for_the_repository_on_production_sites(): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		$this->assertTrue( $this->module()->run( $this->createStub( ContainerInterface::class ) ) );
		$this->assertSame(
			array( array( 'https://github.com/x06designs/mainwp-cache-actions/', X06_CACHE_ACTIONS_FILE, 'x06-cache-actions' ) ),
			$this->built
		);
	}

	public function test_stays_off_where_the_site_must_not_modify_files(): void {
		Functions\expect( 'wp_is_file_mod_allowed' )->once()->with( 'x06_cache_actions_updates' )->andReturn( false );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		$this->assertFalse( $this->module()->run( $this->createStub( ContainerInterface::class ) ) );
		$this->assertSame( array(), $this->built );
	}

	/**
	 * @dataProvider provide_non_production_environments
	 */
	public function test_stays_off_on_local_and_development_sites( string $environment ): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( $environment );

		$this->assertFalse( $this->module()->run( $this->createStub( ContainerInterface::class ) ) );
		$this->assertSame( array(), $this->built );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function provide_non_production_environments(): array {
		return array(
			'local'       => array( 'local' ),
			'development' => array( 'development' ),
		);
	}

	public function test_runs_on_staging_sites(): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );

		$this->assertTrue( $this->module()->run( $this->createStub( ContainerInterface::class ) ) );
	}

	public function test_defaults_to_the_puc_factory(): void {
		$property = new \ReflectionProperty( UpdatesModule::class, 'build_checker' );
		$default  = new \ReflectionFunction( $property->getValue( new UpdatesModule() ) );

		$this->assertSame( 'buildUpdateChecker', $default->getName() );
		$this->assertTrue( is_a( PucFactory::class, (string) $default->getClosureScopeClass()?->getName(), true ) );
	}

	public function test_configures_release_assets_and_the_release_filter(): void {
		$api = \Mockery::mock();
		$api->shouldReceive( 'enableReleaseAssets' )->once()->with( '/^x06-cache-actions\.zip$/', 2 );
		$api->shouldReceive( 'setReleaseFilter' )
			->once()
			->with( \Mockery::on( static fn ( $filter ): bool => is_callable( $filter ) && $filter( '0.2.0' ) && ! $filter( 'child-v0.2.0' ) ), 1, 30 );
		$checker = \Mockery::mock();
		$checker->shouldReceive( 'getVcsApi' )->once()->andReturn( $api );
		$checker->shouldReceive( 'getUniqueName' )->once()->with( 'vcs_update_detection_strategies' )->andReturn( 'puc_strategies_x06' );
		Filters\expectAdded( 'puc_strategies_x06' )->once()->with( \Mockery::type( 'Closure' ) );

		UpdatesModule::configure( $checker );
	}

	public function test_keeps_only_the_release_strategy(): void {
		$release = static fn () => null;

		$this->assertSame(
			array( 'latest_release' => $release ),
			UpdatesModule::only_releases(
				array(
					'latest_release' => $release,
					'latest_tag'     => static fn () => null,
					'branch'         => static fn () => null,
				)
			)
		);
	}

	/**
	 * @dataProvider provide_release_tags
	 */
	public function test_accepts_only_extension_release_tags( string $version, bool $is_own ): void {
		$this->assertSame( $is_own, UpdatesModule::is_own_release( $version ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function provide_release_tags(): array {
		return array(
			'extension'          => array( '0.2.0', true ),
			'companion'          => array( 'child-v0.2.0', false ),
			'pre-release suffix' => array( '0.2.0-rc.1', false ),
		);
	}

	private function module(): UpdatesModule {
		return new UpdatesModule(
			function ( string $repository, string $file, string $slug ): object {
				$this->built[] = array( $repository, $file, $slug );
				$api           = \Mockery::mock();
				$api->shouldReceive( 'enableReleaseAssets' )->once();
				$api->shouldReceive( 'setReleaseFilter' )->once();
				$checker = \Mockery::mock();
				$checker->shouldReceive( 'getVcsApi' )->once()->andReturn( $api );
				$checker->shouldReceive( 'getUniqueName' )->once()->andReturn( 'puc_strategies_x06' );
				return $checker;
			}
		);
	}
}
