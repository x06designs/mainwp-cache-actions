<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Integration;

use X06CacheActions\Features\CacheActions\CacheActionController;
use X06CacheActions\Features\CacheActions\ResultMapper;
use X06CacheActions\Tests\Doubles\RecordingGateway;

final class CacheActionControllerTest extends SchemaTestCase {

	public function tear_down(): void {
		remove_all_filters( 'x06_cache_actions_test_can' );
		parent::tear_down();
	}

	public function test_returns_the_companion_result_for_a_valid_request(): void {
		$response = self::companion_result( 'sync_library' );

		[ $status, $body ] = $this->controller_answering( array( 'x06_cache_actions' => $response ) )->dispatch(
			array(
				'site_id' => '12',
				'op'      => 'sync_library',
			)
		);

		$this->assertSame( 200, $status );
		$this->assertSame(
			array(
				'site_id' => 12,
				'op'      => 'sync_library',
				'result'  => $response,
			),
			$body
		);
	}

	public function test_explains_invalid_input_with_422(): void {
		[ $status, $body ] = $this->controller_answering( array() )->dispatch(
			array(
				'site_id' => '12',
				'op'      => 'drop_database',
			)
		);

		$this->assertSame( 422, $status );
		$this->assertSame( 'invalid_request', $body['code'] );
		$this->assertStringContainsString( 'op', $body['message'] );
	}

	public function test_refuses_without_the_extension_capability(): void {
		add_filter( 'x06_cache_actions_test_can', '__return_false' );

		[ $status, $body ] = $this->controller_answering( array() )->dispatch(
			array(
				'site_id' => '12',
				'op'      => 'sync_library',
			)
		);

		$this->assertSame( 403, $status );
		$this->assertSame( 'forbidden', $body['code'] );
	}

	/**
	 * @param array<string, mixed> $raw What the fake MainWP transport returns.
	 */
	private function controller_answering( array $raw ): CacheActionController {
		return new CacheActionController(
			self::schema(),
			new RecordingGateway( $raw ),
			new ResultMapper( self::schema()['definitions']['ChildResult'] )
		);
	}
}
