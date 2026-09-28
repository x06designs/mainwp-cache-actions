<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use X06CacheActionsChild\Features\Updates\UpdatesModule;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class UpdatesModuleTest extends TestCase {

	private const PLUGIN_FILE = __DIR__ . '/../../x06-cache-actions-child.php';

	/** @var list<array{0: string, 1: string, 2: string}> */
	private array $built = array();

	protected function set_up(): void {
		parent::set_up();
		$this->built = array();
	}

	public function test_stays_off_where_the_site_must_not_modify_files(): void {
		Functions\expect( 'wp_is_file_mod_allowed' )->once()->with( 'x06_cache_actions_child_updates' )->andReturn( false );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		$this->module( self::PLUGIN_FILE )->register();

		$this->assertSame( array(), $this->built );
	}

	public function test_builds_the_checker_for_the_repository_on_production_sites(): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		$this->module( self::PLUGIN_FILE )->register();

		$this->assertSame(
			array( array( 'https://github.com/x06designs/mainwp-cache-actions/', self::PLUGIN_FILE, 'x06-cache-actions-child' ) ),
			$this->built
		);
	}

	/**
	 * @dataProvider provide_non_production_environments
	 */
	public function test_stays_off_on_local_and_development_sites( string $environment ): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( $environment );

		$this->module( self::PLUGIN_FILE )->register();

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

		$this->module( self::PLUGIN_FILE )->register();

		$this->assertCount( 1, $this->built );
	}

	public function test_defaults_to_the_puc_factory(): void {
		$property = new \ReflectionProperty( UpdatesModule::class, 'build_checker' );
		$property->setAccessible( true );

		$this->assertSame(
			array( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory', 'buildUpdateChecker' ),
			$property->getValue( new UpdatesModule( self::PLUGIN_FILE ) )
		);
	}

	public function test_stays_off_without_the_bundled_update_checker(): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		$this->module( sys_get_temp_dir() . '/x06-cache-actions-child/x06-cache-actions-child.php' )->register();

		$this->assertSame( array(), $this->built );
	}

	public function test_configures_release_assets_the_release_filter_and_the_version_filter(): void {
		$api = \Mockery::mock();
		$api->shouldReceive( 'enableReleaseAssets' )->once()->with( '/^x06-cache-actions-child\.zip$/', 2 );
		$api->shouldReceive( 'setReleaseFilter' )->once()->with( array( UpdatesModule::class, 'is_own_release' ), 1, 30 );
		$checker = \Mockery::mock();
		$checker->shouldReceive( 'getVcsApi' )->once()->andReturn( $api );
		$checker->shouldReceive( 'addResultFilter' )->once()->with( array( UpdatesModule::class, 'strip_tag_prefix' ) );
		$checker->shouldReceive( 'getUniqueName' )->once()->with( 'vcs_update_detection_strategies' )->andReturn( 'puc_strategies_x06' );
		Filters\expectAdded( 'puc_strategies_x06' )->once()->with( array( UpdatesModule::class, 'only_releases' ) );

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
	public function test_accepts_only_companion_release_tags( string $version, bool $is_own ): void {
		$this->assertSame( $is_own, UpdatesModule::is_own_release( $version ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function provide_release_tags(): array {
		return array(
			'companion'           => array( 'child-v0.2.0', true ),
			'dashboard extension' => array( '0.2.0', false ),
			'pre-release suffix'  => array( 'child-v0.2.0-rc.1', false ),
			'other prefix'        => array( 'xchild-v0.2.0', false ),
		);
	}

	public function test_strips_the_tag_prefix_from_the_offered_version(): void {
		$info = (object) array( 'version' => 'child-v0.2.0' );

		$this->assertSame( '0.2.0', UpdatesModule::strip_tag_prefix( $info )->version );
		$this->assertNull( UpdatesModule::strip_tag_prefix( null ) );
		$this->assertSame( '0.2.0', UpdatesModule::strip_tag_prefix( (object) array( 'version' => '0.2.0' ) )->version );
	}

	private function module( string $plugin_file ): UpdatesModule {
		return new UpdatesModule(
			$plugin_file,
			function ( string $repository, string $file, string $slug ): object {
				$this->built[] = array( $repository, $file, $slug );
				$api           = \Mockery::mock();
				$api->shouldReceive( 'enableReleaseAssets' )->once();
				$api->shouldReceive( 'setReleaseFilter' )->once();
				$checker = \Mockery::mock();
				$checker->shouldReceive( 'getVcsApi' )->once()->andReturn( $api );
				$checker->shouldReceive( 'addResultFilter' )->once();
				$checker->shouldReceive( 'getUniqueName' )->once()->andReturn( 'puc_strategies_x06' );
				return $checker;
			}
		);
	}
}
