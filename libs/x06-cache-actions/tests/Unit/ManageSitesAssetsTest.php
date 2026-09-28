<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Functions;
use X06CacheActions\Features\CacheActions\ManageSitesAssets;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class ManageSitesAssetsTest extends TestCase {

	/** @var array<string, mixed> */
	private static array $schema;

	/** @var list<array{0: string, 1: string, 2: array<string, mixed>}> */
	private array $enqueued = array();

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		self::$schema = json_decode(
			(string) file_get_contents( __DIR__ . '/../../includes/Features/CacheActions/cache-actions.schema.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);
	}

	protected function set_up(): void {
		parent::set_up();
		$this->stubTranslationFunctions();
		$this->enqueued = array();
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			define( 'WP_PLUGIN_DIR', '/app/web/app/plugins' );
		}
	}

	public function test_enqueues_on_manage_sites_with_the_config_before_the_bundle(): void {
		Functions\expect( 'mainwp_current_user_can' )->once()->with( 'extension', 'x06-cache-actions' )->andReturn( true );
		Functions\expect( 'plugin_basename' )->once()->with( '/repo/x06-cache-actions.php' )->andReturn( 'x06-cache-actions/x06-cache-actions.php' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with(
				'x06-cache-actions-manage-sites',
				\Mockery::on( static fn ( string $js ): bool => str_starts_with( $js, 'window.x06CacheActions = {"action":"x06_cache_actions_run",' ) && str_ends_with( $js, '};' ) ),
				'before'
			);

		$this->assets( true )->enqueue( 'mainwp_page_managesites' );

		$this->assertSame(
			array(
				array(
					'/app/web/app/plugins/x06-cache-actions/build',
					'web/backend/index.ts',
					array(
						'handle'       => 'x06-cache-actions-manage-sites',
						'dependencies' => array( 'jquery', 'wp-i18n', 'mainwp', 'mainwp-ui', 'mainwp-js-popup' ),
						'in-footer'    => true,
					),
				),
			),
			$this->enqueued
		);
	}

	public function test_skips_other_admin_pages(): void {
		Functions\expect( 'mainwp_current_user_can' )->never();

		$this->assets( true )->enqueue( 'mainwp_page_managesites-dashboard' );

		$this->assertSame( array(), $this->enqueued );
	}

	public function test_skips_without_the_extension_capability(): void {
		Functions\expect( 'mainwp_current_user_can' )->once()->andReturn( false );

		$this->assets( true )->enqueue( 'mainwp_page_managesites' );

		$this->assertSame( array(), $this->enqueued );
	}

	public function test_adds_no_config_when_the_bundle_is_not_enqueued(): void {
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\when( 'plugin_basename' )->justReturn( 'x06-cache-actions/x06-cache-actions.php' );
		Functions\expect( 'wp_add_inline_script' )->never();

		$this->assets( false )->enqueue( 'mainwp_page_managesites' );

		$this->assertCount( 1, $this->enqueued );
	}

	public function test_translates_every_operation_code_step_status_and_detail_of_the_schema(): void {
		$i18n     = $this->assets( true )->config()['i18n'];
		$response = self::$schema['definitions']['CacheActionResponse']['properties'];
		$step     = self::$schema['definitions']['ChildResult']['properties']['steps']['items']['properties'];

		$this->assertSame( self::$schema['properties']['op']['enum'], array_keys( $i18n['operations'] ) );
		$this->assertSame( self::$schema['properties']['op']['enum'], array_keys( $i18n['effects'] ) );
		$this->assertSame( $response['code']['enum'], array_keys( $i18n['codes'] ) );
		$this->assertSame( $step['step']['enum'], array_keys( $i18n['steps'] ) );
		$this->assertSame( $step['status']['enum'], array_keys( $i18n['statuses'] ) );
		$this->assertSame( array_values( array_filter( $step['detail']['enum'] ) ), array_keys( $i18n['details'] ) );
	}

	public function test_config_has_exactly_the_keys_the_script_reads(): void {
		$config = $this->assets( true )->config();

		$this->assertSame( self::interface_keys( 'ScriptConfig' ), self::sorted( array_keys( $config ) ) );
		$this->assertSame( self::interface_keys( 'Messages' ), self::sorted( array_keys( $config['i18n'] ) ) );
	}

	/**
	 * Field names of an interface in web/backend/config.ts, the script's side of the contract.
	 *
	 * @return list<string>
	 */
	private static function interface_keys( string $name ): array {
		$source = (string) file_get_contents( __DIR__ . '/../../web/backend/config.ts' );
		self::assertSame( 1, preg_match( '/export interface ' . $name . ' \{(.*?)\n\}/s', $source, $body ), "interface {$name} not found" );
		preg_match_all( '/^\s+(\w+)\??:/m', $body[1], $fields );
		return self::sorted( $fields[1] );
	}

	/**
	 * @param array<int|string, int|string> $keys Keys.
	 * @return list<string>
	 */
	private static function sorted( array $keys ): array {
		$keys = array_map( 'strval', array_values( $keys ) );
		sort( $keys );
		return $keys;
	}

	private function assets( bool $enqueue_result ): ManageSitesAssets {
		return new ManageSitesAssets(
			'/repo/x06-cache-actions.php',
			function ( string $dir, string $entry, array $options ) use ( $enqueue_result ): bool {
				$this->enqueued[] = array( $dir, $entry, $options );
				return $enqueue_result;
			}
		);
	}
}
