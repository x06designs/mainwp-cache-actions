<?php
/**
 * Parsed AJAX request.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

use WP_Error;

/**
 * A validated request to run one operation on one child site.
 */
final readonly class CacheActionRequest {

	public function __construct(
		public int $site_id,
		public Operation $operation
	) {}

	/**
	 * Validates the raw request args against the schema's `properties`.
	 *
	 * @param array<mixed>         $input  Raw (unslashed) request args.
	 * @param array<string, mixed> $schema The feature schema.
	 */
	public static function from_input( array $input, array $schema ): self|WP_Error {
		$args_schema = array(
			'type'       => 'object',
			'properties' => $schema['properties'],
			'required'   => $schema['required'],
		);
		$args        = array_intersect_key( $input, $schema['properties'] );

		$valid = rest_validate_value_from_schema( $args, $args_schema, 'request' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$clean = rest_sanitize_value_from_schema( $args, $args_schema, 'request' );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		return new self( (int) $clean['site_id'], Operation::from( (string) $clean['op'] ) );
	}
}
