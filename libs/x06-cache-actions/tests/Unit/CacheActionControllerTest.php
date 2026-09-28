<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Functions;
use WP_Error;
use X06CacheActions\Features\CacheActions\CacheActionController;
use X06CacheActions\Features\CacheActions\Operation;
use X06CacheActions\Features\CacheActions\ResultMapper;
use X06CacheActions\Tests\Doubles\RecordingGateway;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class CacheActionControllerTest extends TestCase {

	private const SCHEMA = array(
		'properties' => array(
			'site_id' => array( 'type' => 'integer' ),
			'op'      => array( 'type' => 'string' ),
		),
		'required'   => array( 'site_id', 'op' ),
	);

	private RecordingGateway $gateway;

	private CacheActionController $controller;

	protected function set_up(): void {
		parent::set_up();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ): bool => $thing instanceof WP_Error );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'rest_sanitize_value_from_schema' )->returnArg();

		$this->gateway    = new RecordingGateway( array( 'error' => 'offline' ) );
		$this->controller = new CacheActionController( self::SCHEMA, $this->gateway, new ResultMapper( array() ) );
	}

	public function test_handle_checks_the_mainwp_nonce_and_sends_the_status_code(): void {
		$input = array(
			'site_id' => '5',
			'op'      => 'drop',
		);
		$_POST = $input;
		Functions\expect( 'mainwp_secure_request' )->once()->with( 'x06_cache_actions_run' );
		Functions\expect( 'wp_unslash' )->once()->with( $input )->andReturnFirstArg();
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( new WP_Error( 'rest_invalid_param', 'bad op' ) );
		Functions\expect( 'wp_send_json' )
			->once()
			->with(
				array(
					'code'    => 'invalid_request',
					'message' => 'bad op',
				),
				422
			);

		$this->controller->handle();
	}

	public function test_handle_stops_before_dispatch_when_the_nonce_check_dies(): void {
		$_POST = array(
			'site_id' => '5',
			'op'      => 'clear_caches',
		);
		Functions\expect( 'mainwp_secure_request' )->once()->andThrow( new \RuntimeException( 'died' ) );
		Functions\expect( 'wp_send_json' )->never();

		try {
			$this->controller->handle();
			$this->fail( 'Expected the nonce check to stop the request.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'died', $exception->getMessage() );
		}
		$this->assertSame( array(), $this->gateway->calls );
	}

	protected function tear_down(): void {
		$_POST = array();
		parent::tear_down();
	}

	public function test_refuses_users_without_the_extension_capability(): void {
		Functions\expect( 'mainwp_current_user_can' )->once()->with( 'extension', 'x06-cache-actions' )->andReturn( false );

		$this->assertSame(
			array( 403, array( 'code' => 'forbidden' ) ),
			$this->controller->dispatch(
				array(
					'site_id' => '1',
					'op'      => 'clear_caches',
				)
			)
		);
		$this->assertSame( array(), $this->gateway->calls );
	}

	public function test_rejects_invalid_input_with_422_and_never_calls_the_child(): void {
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( new WP_Error( 'rest_invalid_param', 'op is not one of clear_caches' ) );

		[ $status, $body ] = $this->controller->dispatch( array( 'op' => 'drop' ) );

		$this->assertSame( 422, $status );
		$this->assertSame( 'invalid_request', $body['code'] );
		$this->assertSame( 'op is not one of clear_caches', $body['message'] );
		$this->assertSame( array(), $this->gateway->calls );
	}

	public function test_runs_the_operation_on_the_requested_site_and_maps_the_answer(): void {
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( true );

		[ $status, $body ] = $this->controller->dispatch(
			array(
				'site_id'  => '9',
				'op'       => 'sync_library',
				'action'   => 'x06_cache_actions_run',
				'security' => 'nonce',
			)
		);

		$this->assertSame( array( array( 9, Operation::SyncLibrary ) ), $this->gateway->calls );
		$this->assertSame( 200, $status );
		$this->assertSame(
			array(
				'site_id' => 9,
				'op'      => 'sync_library',
				'code'    => 'connection_failed',
				'message' => 'offline',
			),
			$body
		);
	}

	public function test_only_the_schema_properties_reach_the_validator(): void {
		Functions\when( 'mainwp_current_user_can' )->justReturn( true );
		Functions\expect( 'rest_validate_value_from_schema' )
			->once()
			->with(
				array(
					'site_id' => '2',
					'op'      => 'clear_caches',
				),
				\Mockery::type( 'array' ),
				'request'
			)
			->andReturn( true );

		$this->controller->dispatch(
			array(
				'site_id' => '2',
				'op'      => 'clear_caches',
				'dts'     => '123',
			)
		);
	}
}
