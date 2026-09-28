<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Integration;

use WP_UnitTestCase;

abstract class SchemaTestCase extends WP_UnitTestCase {

	/**
	 * @return array<string, mixed>
	 */
	protected static function schema(): array {
		return json_decode(
			(string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/Features/CacheActions/cache-actions.schema.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);
	}

	/**
	 * A result exactly as the companion 0.1.0 sends it.
	 *
	 * @return array<string, mixed>
	 */
	protected static function companion_result( string $op = 'clear_caches' ): array {
		return array(
			'op'                => $op,
			'companion_version' => '0.1.0',
			'api_version'       => 1,
			'steps'             => array(
				array(
					'step'   => 'elementor_clear_cache',
					'status' => 'done',
					'detail' => null,
				),
				array(
					'step'   => 'wpfc_delete_cache',
					'status' => 'failed',
					'detail' => 'exception',
					'error'  => 'RuntimeException',
				),
			),
		);
	}
}
