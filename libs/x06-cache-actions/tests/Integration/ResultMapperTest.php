<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Integration;

use X06CacheActions\Features\CacheActions\CacheActionRequest;
use X06CacheActions\Features\CacheActions\Operation;
use X06CacheActions\Features\CacheActions\ResultMapper;

final class ResultMapperTest extends SchemaTestCase {

	private ResultMapper $mapper;

	public function set_up(): void {
		parent::set_up();
		$this->mapper = new ResultMapper( self::schema()['definitions']['ChildResult'] );
	}

	public function test_passes_on_a_companion_result(): void {
		$result = self::companion_result();

		$response = $this->mapper->map( new CacheActionRequest( 3, Operation::ClearCaches ), array( 'x06_cache_actions' => $result ) );

		$this->assertSame( $result, $response['result'] );
		$this->assertArrayNotHasKey( 'code', $response );
	}

	public function test_accepts_fields_a_newer_companion_adds(): void {
		$result                         = self::companion_result();
		$result['duration_ms']          = 812;
		$result['steps'][0]['duration'] = 12;

		$response = $this->mapper->map( new CacheActionRequest( 3, Operation::ClearCaches ), array( 'x06_cache_actions' => $result ) );

		$this->assertSame( $result, $response['result'] );
	}

	/**
	 * @dataProvider broken_results
	 *
	 * @param callable(array<string, mixed>): mixed $mutate Turns a valid result into a broken one.
	 */
	public function test_flags_a_result_outside_the_contract_as_outdated( callable $mutate ): void {
		$response = $this->mapper->map(
			new CacheActionRequest( 3, Operation::ClearCaches ),
			array( 'x06_cache_actions' => $mutate( self::companion_result() ) )
		);

		$this->assertSame( 'companion_outdated', $response['code'] );
	}

	/**
	 * @return array<string, array{callable(array<string, mixed>): mixed}>
	 */
	public function broken_results(): array {
		return array(
			'not an object'      => array( static fn (): string => 'done' ),
			'newer api version'  => array( static fn ( array $r ): array => array( 'api_version' => 2 ) + $r ),
			'missing steps'      => array(
				static function ( array $r ): array {
					unset( $r['steps'] );
					return $r;
				},
			),
			'steps not a list'   => array( static fn ( array $r ): array => array( 'steps' => 'done' ) + $r ),
			'unknown step'       => array(
				static function ( array $r ): array {
					$r['steps'][0]['step'] = 'purge_everything';
					return $r;
				},
			),
			'unknown status'     => array(
				static function ( array $r ): array {
					$r['steps'][0]['status'] = 'ok';
					return $r;
				},
			),
			'unknown detail'     => array(
				static function ( array $r ): array {
					$r['steps'][0]['detail'] = 'weird';
					return $r;
				},
			),
			'detail missing'     => array(
				static function ( array $r ): array {
					unset( $r['steps'][0]['detail'] );
					return $r;
				},
			),
			'unknown operation'  => array( static fn ( array $r ): array => array( 'op' => 'purge' ) + $r ),
			'other operation'    => array( static fn ( array $r ): array => array( 'op' => 'sync_library' ) + $r ),
		);
	}
}
