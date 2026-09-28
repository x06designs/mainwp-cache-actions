<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Psr\Container\ContainerInterface;
use WP_Error;
use X06CacheActions\Features\CacheActions\BulkActions;
use X06CacheActions\Features\CacheActions\CacheActionController;
use X06CacheActions\Features\CacheActions\CacheActionRequest;
use X06CacheActions\Features\CacheActions\Operation;
use X06CacheActions\Features\CacheActions\CacheActionsModule;
use X06CacheActions\Features\CacheActions\MainWpChildGateway;
use X06CacheActions\Features\CacheActions\ManageSitesAssets;
use X06CacheActions\Features\CacheActions\ResultMapper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class CacheActionsModuleTest extends TestCase {

	private CacheActionsModule $module;

	private ContainerInterface $container;

	protected function set_up(): void {
		parent::set_up();
		$this->module    = new CacheActionsModule();
		$this->container = $this->container_for( $this->module->services() );
	}

	public function test_services_build_the_feature_graph_from_the_schema(): void {
		$this->assertInstanceOf( CacheActionController::class, $this->container->get( CacheActionController::class ) );
		$this->assertInstanceOf( MainWpChildGateway::class, $this->container->get( MainWpChildGateway::class ) );
		$this->assertInstanceOf( ResultMapper::class, $this->container->get( ResultMapper::class ) );
		$this->assertInstanceOf( BulkActions::class, $this->container->get( BulkActions::class ) );
		$this->assertInstanceOf( ManageSitesAssets::class, $this->container->get( ManageSitesAssets::class ) );
	}

	public function test_the_mapper_validates_against_the_child_result_definition(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\expect( 'rest_validate_value_from_schema' )
			->once()
			->with( array( 'op' => 'sync_library' ), self::schema()['definitions']['ChildResult'], 'x06_cache_actions' )
			->andReturn( true );

		$this->container->get( ResultMapper::class )->map(
			new CacheActionRequest( 1, Operation::SyncLibrary ),
			array( 'x06_cache_actions' => array( 'op' => 'sync_library' ) )
		);
	}

	public function test_the_controller_validates_requests_against_the_top_level_properties(): void {
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ): bool => $thing instanceof WP_Error );
		Functions\expect( 'rest_validate_value_from_schema' )
			->once()
			->with(
				array( 'op' => 'x' ),
				array(
					'type'       => 'object',
					'properties' => self::schema()['properties'],
					'required'   => self::schema()['required'],
				),
				'request'
			)
			->andReturn( new WP_Error( 'rest_invalid_param', 'invalid' ) );

		$this->container->get( CacheActionController::class )->dispatch( array( 'op' => 'x' ) );
	}

	public function test_the_gateway_identifies_itself_with_the_plugin_main_file(): void {
		Filters\expectApplied( 'mainwp_extension_enabled_check' )->once()->with( X06_CACHE_ACTIONS_FILE )->andReturn( array( 'key' => 'k' ) );
		Filters\expectApplied( 'mainwp_fetchurlauthed' )
			->once()
			->with( X06_CACHE_ACTIONS_FILE, 'k', 1, 'extra_execution', \Mockery::type( 'array' ) )
			->andReturn( array() );

		$this->container->get( MainWpChildGateway::class )->execute( 1, Operation::ClearCaches );
	}

	public function test_registers_right_away_when_mainwp_is_active(): void {
		Filters\expectApplied( 'mainwp_activated_check' )->once()->with( false )->andReturn( true );
		Filters\expectAdded( 'mainwp_managesites_bulk_actions' )
			->once()
			->with( array( $this->container->get( BulkActions::class ), 'add' ) );
		Actions\expectDone( 'mainwp_ajax_add_action' )
			->once()
			->with( 'x06_cache_actions_run', array( $this->container->get( CacheActionController::class ), 'handle' ) );
		Actions\expectAdded( 'admin_enqueue_scripts' )
			->once()
			->with( array( $this->container->get( ManageSitesAssets::class ), 'enqueue' ) );

		$this->assertTrue( $this->module->run( $this->container ) );
	}

	public function test_waits_for_mainwp_when_it_is_not_active_yet(): void {
		Filters\expectApplied( 'mainwp_activated_check' )->once()->andReturn( false );
		Filters\expectAdded( 'mainwp_managesites_bulk_actions' )->never();
		Actions\expectAdded( 'admin_enqueue_scripts' )->never();
		Actions\expectAdded( 'mainwp_activated' )->once()->with( \Mockery::type( 'Closure' ) );

		$this->assertTrue( $this->module->run( $this->container ) );
	}

	public function test_registers_once_mainwp_activates(): void {
		$register = null;
		Filters\expectApplied( 'mainwp_activated_check' )->once()->andReturn( false );
		Actions\expectAdded( 'mainwp_activated' )
			->once()
			->whenHappen(
				static function ( \Closure $callback ) use ( &$register ): void {
					$register = $callback;
				}
			);
		$this->module->run( $this->container );

		Filters\expectAdded( 'mainwp_managesites_bulk_actions' )
			->once()
			->with( array( $this->container->get( BulkActions::class ), 'add' ) );
		Actions\expectDone( 'mainwp_ajax_add_action' )
			->once()
			->with( 'x06_cache_actions_run', array( $this->container->get( CacheActionController::class ), 'handle' ) );
		Actions\expectAdded( 'admin_enqueue_scripts' )
			->once()
			->with( array( $this->container->get( ManageSitesAssets::class ), 'enqueue' ) );

		$this->assertInstanceOf( \Closure::class, $register );
		$register();
	}

	public function test_the_assets_resolve_the_build_of_the_plugin_main_file(): void {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			define( 'WP_PLUGIN_DIR', '/app/web/app/plugins' );
		}
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\when( 'esc_html' )->returnArg();
		Functions\expect( 'plugin_basename' )->once()->with( X06_CACHE_ACTIONS_FILE )->andReturn( 'x06-cache-actions/x06-cache-actions.php' );
		Functions\expect( 'wp_add_inline_script' )->never();

		$this->container->get( ManageSitesAssets::class )->enqueue( 'mainwp_page_managesites' );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function schema(): array {
		return json_decode(
			(string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/Features/CacheActions/cache-actions.schema.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);
	}

	/**
	 * @param array<string, callable> $factories Service factories.
	 */
	private function container_for( array $factories ): ContainerInterface {
		return new class( $factories ) implements ContainerInterface {

			/** @var array<string, mixed> */
			private array $instances = array();

			/**
			 * @param array<string, callable> $factories Service factories.
			 */
			public function __construct( private readonly array $factories ) {}

			public function get( string $id ): mixed {
				$this->instances[ $id ] ??= ( $this->factories[ $id ] )( $this );
				return $this->instances[ $id ];
			}

			public function has( string $id ): bool {
				return isset( $this->factories[ $id ] );
			}
		};
	}
}
