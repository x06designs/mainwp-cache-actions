<?php
/**
 * MainWP extra_execution transport.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

/**
 * Sends the operation through MainWP's authenticated `extra_execution` channel.
 */
final class MainWpChildGateway implements ChildGateway {

	public const REQUEST_KEY = 'x06_cache_op';

	/**
	 * MainWP's default request timeout is far too short for a cache purge plus a remote
	 * library sync; the companion caps itself at 90 s, so 120 s leaves room for the answer.
	 */
	public const TIMEOUT_SECONDS = 120;

	public function __construct( private readonly string $plugin_file ) {}

	public function execute( int $site_id, Operation $operation ): mixed {
		$raise_timeout = static fn ( $timeout, $what = '' ) => 'extra_execution' === $what ? self::TIMEOUT_SECONDS : $timeout;

		add_filter( 'mainwp_fetch_url_site_timeout', $raise_timeout, 10, 2 );
		try {
			$enabled = apply_filters( 'mainwp_extension_enabled_check', $this->plugin_file );
			$key     = is_array( $enabled ) ? (string) ( $enabled['key'] ?? '' ) : '';

			return apply_filters(
				'mainwp_fetchurlauthed',
				$this->plugin_file,
				$key,
				$site_id,
				'extra_execution',
				array( self::REQUEST_KEY => $operation->value )
			);
		} finally {
			remove_filter( 'mainwp_fetch_url_site_timeout', $raise_timeout, 10 );
		}
	}
}
