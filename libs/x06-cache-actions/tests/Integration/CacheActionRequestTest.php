<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Integration;

use WP_Error;
use X06CacheActions\Features\CacheActions\CacheActionRequest;
use X06CacheActions\Features\CacheActions\Operation;

final class CacheActionRequestTest extends SchemaTestCase {

	public function test_parses_form_encoded_input(): void {
		$request = CacheActionRequest::from_input(
			array(
				'site_id'  => '5',
				'op'       => 'clear_caches_minified',
				'security' => 'ignored',
			),
			self::schema()
		);

		$this->assertInstanceOf( CacheActionRequest::class, $request );
		$this->assertSame( 5, $request->site_id );
		$this->assertSame( Operation::ClearCachesMinified, $request->operation );
	}

	/**
	 * @dataProvider invalid_inputs
	 *
	 * @param array<string, mixed> $input Raw request args.
	 */
	public function test_rejects_invalid_input( array $input ): void {
		$this->assertInstanceOf( WP_Error::class, CacheActionRequest::from_input( $input, self::schema() ) );
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function invalid_inputs(): array {
		return array(
			'missing site'     => array( array( 'op' => 'clear_caches' ) ),
			'missing op'       => array( array( 'site_id' => '1' ) ),
			'site zero'        => array(
				array(
					'site_id' => '0',
					'op'      => 'clear_caches',
				),
			),
			'site not numeric' => array(
				array(
					'site_id' => 'abc',
					'op'      => 'clear_caches',
				),
			),
			'site fraction'    => array(
				array(
					'site_id' => '1.5',
					'op'      => 'clear_caches',
				),
			),
			'site as array'    => array(
				array(
					'site_id' => array( '1' ),
					'op'      => 'clear_caches',
				),
			),
			'unknown op'       => array(
				array(
					'site_id' => '1',
					'op'      => 'drop_database',
				),
			),
			'op as array'      => array(
				array(
					'site_id' => '1',
					'op'      => array( 'clear_caches' ),
				),
			),
		);
	}
}
