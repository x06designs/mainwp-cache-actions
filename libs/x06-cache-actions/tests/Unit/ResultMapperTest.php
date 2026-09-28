<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use Brain\Monkey\Functions;
use WP_Error;
use X06CacheActions\Features\CacheActions\CacheActionRequest;
use X06CacheActions\Features\CacheActions\Operation;
use X06CacheActions\Features\CacheActions\ResultMapper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class ResultMapperTest extends TestCase {

	private const CHILD_SCHEMA = array( 'type' => 'object' );

	private ResultMapper $mapper;

	private CacheActionRequest $request;

	protected function set_up(): void {
		parent::set_up();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ): bool => $thing instanceof WP_Error );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		$this->mapper  = new ResultMapper( self::CHILD_SCHEMA );
		$this->request = new CacheActionRequest( 4, Operation::ClearCaches );
	}

	public function test_false_means_mainwp_rejected_the_extension(): void {
		$this->assertSame( 'extension_rejected', $this->mapper->map( $this->request, false )['code'] );
	}

	public function test_a_non_array_answer_is_a_connection_failure(): void {
		$this->assertSame( 'connection_failed', $this->mapper->map( $this->request, null )['code'] );
		$this->assertSame( 'connection_failed', $this->mapper->map( $this->request, 'garbage' )['code'] );
	}

	public function test_a_suspended_site_is_reported_as_such(): void {
		$response = $this->mapper->map(
			$this->request,
			array(
				'error'     => 'Suspended site.',
				'errorCode' => 'SUSPENDED_SITE',
			)
		);

		$this->assertSame(
			array(
				'site_id' => 4,
				'op'      => 'clear_caches',
				'code'    => 'site_suspended',
			),
			$response
		);
	}

	public function test_a_mainwp_error_is_a_connection_failure_with_its_text(): void {
		$response = $this->mapper->map(
			$this->request,
			array(
				'error'     => '<strong>HTTP error</strong> 500',
				'errorCode' => 'excep_fetch_url_authed',
			)
		);

		$this->assertSame( 'connection_failed', $response['code'] );
		$this->assertSame( 'HTTP error 500', $response['message'] );
	}

	public function test_a_response_without_the_companion_key_means_the_companion_is_missing(): void {
		$response = $this->mapper->map( $this->request, array( 'fetch_url_output' => array( 'http_status' => 200 ) ) );

		$this->assertSame( 'companion_missing', $response['code'] );
	}

	public function test_a_payload_failing_the_schema_means_the_companion_is_outdated(): void {
		Functions\expect( 'rest_validate_value_from_schema' )
			->once()
			->with( array( 'op' => 'clear_caches' ), self::CHILD_SCHEMA, 'x06_cache_actions' )
			->andReturn( new WP_Error( 'rest_invalid_param', 'steps is a required property' ) );

		$response = $this->mapper->map( $this->request, array( 'x06_cache_actions' => array( 'op' => 'clear_caches' ) ) );

		$this->assertSame( 'companion_outdated', $response['code'] );
	}

	public function test_a_result_for_another_operation_means_the_companion_is_outdated(): void {
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( true );

		$response = $this->mapper->map( $this->request, array( 'x06_cache_actions' => array( 'op' => 'sync_library' ) ) );

		$this->assertSame( 'companion_outdated', $response['code'] );
	}

	public function test_an_empty_error_next_to_a_result_is_not_a_failure(): void {
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( true );

		$response = $this->mapper->map(
			$this->request,
			array(
				'error'             => '',
				'x06_cache_actions' => array( 'op' => 'clear_caches' ),
			)
		);

		$this->assertSame( array( 'op' => 'clear_caches' ), $response['result'] );
	}

	public function test_a_null_companion_payload_means_outdated_not_missing(): void {
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( new WP_Error( 'rest_invalid_type', 'not an object' ) );

		$response = $this->mapper->map( $this->request, array( 'x06_cache_actions' => null ) );

		$this->assertSame( 'companion_outdated', $response['code'] );
	}

	public function test_a_valid_result_is_passed_on(): void {
		Functions\when( 'rest_validate_value_from_schema' )->justReturn( true );
		$result = array(
			'op'                => 'clear_caches',
			'companion_version' => '0.1.0',
			'api_version'       => 1,
			'steps'             => array(),
		);

		$response = $this->mapper->map( $this->request, array( 'x06_cache_actions' => $result ) );

		$this->assertSame(
			array(
				'site_id' => 4,
				'op'      => 'clear_caches',
				'result'  => $result,
			),
			$response
		);
	}
}
