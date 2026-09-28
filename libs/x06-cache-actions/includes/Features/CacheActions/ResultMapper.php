<?php
/**
 * Maps MainWP's raw response to the AJAX response.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

/**
 * Turns whatever MainWP returned for one site into a CacheActionResponse. The companion's
 * payload is external input, so it is validated against `definitions.ChildResult` before
 * it is passed on.
 */
final class ResultMapper {

	public const RESPONSE_KEY = 'x06_cache_actions';

	public const EXTENSION_REJECTED = 'extension_rejected';
	public const SITE_SUSPENDED     = 'site_suspended';
	public const CONNECTION_FAILED  = 'connection_failed';
	public const COMPANION_MISSING  = 'companion_missing';
	public const COMPANION_OUTDATED = 'companion_outdated';

	/**
	 * @param array<string, mixed> $child_result_schema `definitions.ChildResult` of the feature schema.
	 */
	public function __construct( private readonly array $child_result_schema ) {}

	/**
	 * @return array<string, mixed>
	 */
	public function map( CacheActionRequest $request, mixed $raw ): array {
		$response = array(
			'site_id' => $request->site_id,
			'op'      => $request->operation->value,
		);

		if ( ! is_array( $raw ) ) {
			return $response + array( 'code' => false === $raw ? self::EXTENSION_REJECTED : self::CONNECTION_FAILED );
		}

		if ( 'SUSPENDED_SITE' === ( $raw['errorCode'] ?? null ) ) {
			return $response + array( 'code' => self::SITE_SUSPENDED );
		}

		if ( isset( $raw['error'] ) && '' !== $raw['error'] ) {
			return $response + array(
				'code'    => self::CONNECTION_FAILED,
				'message' => wp_strip_all_tags( is_scalar( $raw['error'] ) ? (string) $raw['error'] : '' ),
			);
		}

		if ( ! array_key_exists( self::RESPONSE_KEY, $raw ) ) {
			return $response + array( 'code' => self::COMPANION_MISSING );
		}

		$result = $raw[ self::RESPONSE_KEY ];
		if ( ! $this->is_valid_result( $result, $request->operation ) ) {
			return $response + array( 'code' => self::COMPANION_OUTDATED );
		}

		return $response + array( 'result' => $result );
	}

	private function is_valid_result( mixed $result, Operation $operation ): bool {
		if ( is_wp_error( rest_validate_value_from_schema( $result, $this->child_result_schema, self::RESPONSE_KEY ) ) ) {
			return false;
		}

		return is_array( $result ) && $operation->value === $result['op'];
	}
}
