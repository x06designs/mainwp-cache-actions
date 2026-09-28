<?php
/**
 * AJAX endpoint.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

/**
 * Runs one operation on one child site per request; the browser queues the sites.
 */
final class CacheActionController {

	public const ACTION = 'x06_cache_actions_run';

	public const INVALID_REQUEST = 'invalid_request';
	public const FORBIDDEN       = 'forbidden';

	/**
	 * @param array<string, mixed> $schema The feature schema.
	 */
	public function __construct(
		private readonly array $schema,
		private readonly ChildGateway $gateway,
		private readonly ResultMapper $mapper
	) {}

	/**
	 * `wp_ajax_` callback. mainwp_secure_request() dies on a bad nonce or a non-admin user.
	 */
	public function handle(): void {
		mainwp_secure_request( self::ACTION );

		[ $status, $body ] = $this->dispatch( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by mainwp_secure_request().

		wp_send_json( $body, $status );
	}

	/**
	 * @param array<mixed> $input Unslashed request args.
	 * @return array{0: int, 1: array<string, mixed>}
	 */
	public function dispatch( array $input ): array {
		if ( ! mainwp_current_user_can( 'extension', CacheActionsModule::EXTENSION_SLUG ) ) {
			return array( 403, array( 'code' => self::FORBIDDEN ) );
		}

		$request = CacheActionRequest::from_input( $input, $this->schema );
		if ( is_wp_error( $request ) ) {
			return array(
				422,
				array(
					'code'    => self::INVALID_REQUEST,
					'message' => $request->get_error_message(),
				),
			);
		}

		$raw = $this->gateway->execute( $request->site_id, $request->operation );

		return array( 200, $this->mapper->map( $request, $raw ) );
	}
}
