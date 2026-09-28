<?php
/**
 * Manage Sites script.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

/**
 * Enqueues the bulk-action script on Sites > Manage Sites and hands it the endpoint and its
 * translated strings. The strings are passed as data because the bundle's file name carries a
 * content hash, which JSON translation files cannot be keyed to.
 */
final class ManageSitesAssets {

	public const HANDLE = 'x06-cache-actions-manage-sites';

	public const HOOK_SUFFIX = 'mainwp_page_managesites';

	public const ENTRY = 'web/backend/index.ts';

	public const CONFIG_GLOBAL = 'x06CacheActions';

	private const CONCURRENCY = 3;

	private const TIMEOUT_MS = 150000;

	private const DEPENDENCIES = array( 'jquery', 'wp-i18n', 'mainwp', 'mainwp-ui', 'mainwp-js-popup' );

	/**
	 * @param string                                              $plugin_file   Plugin main file.
	 * @param \Closure(string, string, array<string, mixed>): bool $enqueue_asset `Kucrut\Vite\enqueue_asset()`.
	 */
	public function __construct(
		private readonly string $plugin_file,
		private readonly \Closure $enqueue_asset
	) {}

	/**
	 * `admin_enqueue_scripts` callback.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( self::HOOK_SUFFIX !== $hook_suffix || ! mainwp_current_user_can( 'extension', CacheActionsModule::EXTENSION_SLUG ) ) {
			return;
		}

		$enqueued = ( $this->enqueue_asset )(
			$this->build_dir(),
			self::ENTRY,
			array(
				'handle'       => self::HANDLE,
				'dependencies' => self::DEPENDENCIES,
				'in-footer'    => true,
			)
		);
		if ( ! $enqueued ) {
			return;
		}

		wp_add_inline_script(
			self::HANDLE,
			sprintf( 'window.%s = %s;', self::CONFIG_GLOBAL, wp_json_encode( $this->config(), JSON_THROW_ON_ERROR ) ),
			'before'
		);
	}

	/**
	 * Data for the script: endpoint, queue settings and every user-facing string.
	 *
	 * @return array<string, mixed>
	 */
	public function config(): array {
		$elementor_clear = __( 'Elementor deletes its generated CSS files and data. Every page rebuilds its CSS on its next view, so the first views are slower.', 'x06-cache-actions' );

		return array(
			'action'      => CacheActionController::ACTION,
			'prefix'      => BulkActions::PREFIX,
			'concurrency' => self::CONCURRENCY,
			'timeoutMs'   => self::TIMEOUT_MS,
			'i18n'        => array(
				'operations'    => BulkActions::labels(),
				/* translators: %s: bulk action name, e.g. "Clear caches". */
				'confirm'       => __( '"%s" runs on every selected site and has these side effects:', 'x06-cache-actions' ),
				'effects'       => array(
					Operation::ClearCaches->value         => array(
						$elementor_clear,
						__( 'WP Fastest Cache deletes the page cache. Where WP Fastest Cache is set up for it, it also purges Cloudflare or Varnish and restarts preloading.', 'x06-cache-actions' ),
					),
					Operation::ClearCachesMinified->value => array(
						$elementor_clear,
						__( 'WP Fastest Cache deletes the page cache and the minified CSS and JS files. Where WP Fastest Cache is set up for it, it also purges Cloudflare or Varnish and restarts preloading.', 'x06-cache-actions' ),
					),
					Operation::SyncLibrary->value         => array(
						__( 'Elementor downloads its template library and remote info from elementor.com again.', 'x06-cache-actions' ),
					),
				),
				'progress'      => __( 'processed', 'x06-cache-actions' ),
				'codes'         => array(
					CacheActionController::INVALID_REQUEST => __( 'The request was rejected as invalid.', 'x06-cache-actions' ),
					CacheActionController::FORBIDDEN       => __( 'You are not allowed to run this action.', 'x06-cache-actions' ),
					ResultMapper::EXTENSION_REJECTED       => __( 'The child site rejected the extension.', 'x06-cache-actions' ),
					ResultMapper::SITE_SUSPENDED           => __( 'The site is suspended.', 'x06-cache-actions' ),
					ResultMapper::CONNECTION_FAILED        => __( 'The child site could not be reached.', 'x06-cache-actions' ),
					ResultMapper::COMPANION_MISSING        => __( 'X06 Cache Actions Child is not active on the site.', 'x06-cache-actions' ),
					ResultMapper::COMPANION_OUTDATED       => __( 'X06 Cache Actions Child on the site is outdated.', 'x06-cache-actions' ),
				),
				'requestFailed' => __( 'The request to the dashboard failed.', 'x06-cache-actions' ),
				'timeout'       => __( 'The request timed out. The action may still finish on the site.', 'x06-cache-actions' ),
				/* translators: %d: HTTP status code. */
				'unexpected'    => __( 'The dashboard sent an unexpected response (HTTP %d).', 'x06-cache-actions' ),
				'steps'         => array(
					'elementor_clear_cache'          => __( 'Elementor cache', 'x06-cache-actions' ),
					'wpfc_delete_cache'              => __( 'WP Fastest Cache', 'x06-cache-actions' ),
					'wpfc_delete_cache_and_minified' => __( 'WP Fastest Cache and minified files', 'x06-cache-actions' ),
					'elementor_sync_library'         => __( 'Elementor library', 'x06-cache-actions' ),
				),
				'statuses'      => array(
					'done'    => __( 'done', 'x06-cache-actions' ),
					'skipped' => __( 'skipped', 'x06-cache-actions' ),
					'failed'  => __( 'failed', 'x06-cache-actions' ),
				),
				'details'       => array(
					'plugin_inactive'     => __( 'plugin not active', 'x06-cache-actions' ),
					'delete_failed'       => __( 'files could not be deleted', 'x06-cache-actions' ),
					'library_sync_failed' => __( 'library download failed', 'x06-cache-actions' ),
					'info_sync_failed'    => __( 'remote info download failed', 'x06-cache-actions' ),
					'sync_failed'         => __( 'download failed', 'x06-cache-actions' ),
					'exception'           => __( 'unexpected error', 'x06-cache-actions' ),
				),
				/* translators: 1: step name, 2: status, e.g. "Elementor cache: done". */
				'step'          => __( '%1$s: %2$s', 'x06-cache-actions' ),
				/* translators: 1: step name, 2: status, 3: reason, e.g. "WP Fastest Cache: skipped (plugin not active)". */
				'stepDetail'    => __( '%1$s: %2$s (%3$s)', 'x06-cache-actions' ),
			),
		);
	}

	/**
	 * The build directory under the plugins directory, not the resolved path: the plugin may be
	 * a symlink, and the asset URL is derived from the path's position below the content dir.
	 */
	private function build_dir(): string {
		return WP_PLUGIN_DIR . '/' . dirname( plugin_basename( $this->plugin_file ) ) . '/build';
	}
}
