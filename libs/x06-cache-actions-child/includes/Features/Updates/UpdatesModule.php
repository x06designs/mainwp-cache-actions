<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\Updates;

/**
 * Offers new companion releases on the site's normal update screen. Releases are the
 * `child-v*` GitHub releases of the mainwp-cache-actions repository; the dashboard extension's
 * releases in the same repository are ignored.
 *
 * Off where the site must not update plugins itself (`DISALLOW_FILE_MODS` or the
 * `file_mod_allowed` filter, e.g. Bedrock sites that install the companion through Composer)
 * and on local and development sites.
 */
final class UpdatesModule {

	public const REPOSITORY = 'https://github.com/x06designs/mainwp-cache-actions/';
	public const SLUG       = 'x06-cache-actions-child';
	public const TAG_PREFIX = 'child-v';

	private const FILE_MOD_CONTEXT = 'x06_cache_actions_child_updates';

	private const ASSET_PATTERN = '/^x06-cache-actions-child\.zip$/';

	private const LOADER = '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

	/** `Api::REQUIRE_RELEASE_ASSETS`: a release without the plugin zip offers no update. */
	private const REQUIRE_RELEASE_ASSETS = 2;

	/** `Api::STRATEGY_LATEST_RELEASE`. */
	private const LATEST_RELEASE_STRATEGY = 'latest_release';

	/** `Api::RELEASE_FILTER_SKIP_PRERELEASE`. */
	private const SKIP_PRERELEASES = 1;

	/** Both plugins release from one repository, so the newest companion release can be further back. */
	private const RELEASES_TO_EXAMINE = 30;

	private string $plugin_file;

	/** @var callable(string, string, string): object */
	private $build_checker;

	/**
	 * @param string                                          $plugin_file   Plugin main file.
	 * @param (callable(string, string, string): object)|null $build_checker `PucFactory::buildUpdateChecker()` by default.
	 */
	public function __construct( string $plugin_file, ?callable $build_checker = null ) {
		$this->plugin_file   = $plugin_file;
		$this->build_checker = $build_checker ?? array( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory', 'buildUpdateChecker' );
	}

	/**
	 * The update checker ships in the release zip only. A checkout without `vendor/` (a
	 * development mount) has nothing to load and stays without update checks.
	 */
	public function register(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$loader = dirname( $this->plugin_file ) . self::LOADER;
		if ( ! is_file( $loader ) ) {
			return;
		}
		require_once $loader;

		self::configure( ( $this->build_checker )( self::REPOSITORY, $this->plugin_file, self::SLUG ) );
	}

	public static function is_enabled(): bool {
		if ( ! wp_is_file_mod_allowed( self::FILE_MOD_CONTEXT ) ) {
			return false;
		}

		return ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * @param object $checker A PUC `Vcs\PluginUpdateChecker` for the GitHub repository.
	 */
	public static function configure( object $checker ): void {
		$api = $checker->getVcsApi();
		$api->enableReleaseAssets( self::ASSET_PATTERN, self::REQUIRE_RELEASE_ASSETS );
		$api->setReleaseFilter( array( self::class, 'is_own_release' ), self::SKIP_PRERELEASES, self::RELEASES_TO_EXAMINE );
		$checker->addResultFilter( array( self::class, 'strip_tag_prefix' ) );
		add_filter( $checker->getUniqueName( 'vcs_update_detection_strategies' ), array( self::class, 'only_releases' ) );
	}

	/**
	 * PUC falls back to the highest tag and then to the branch, both as GitHub's source zip of the
	 * whole repository, whose tags also include the dashboard extension's. Only releases count.
	 *
	 * @param array<string, callable> $strategies PUC's update detection strategies by name.
	 * @return array<string, callable>
	 */
	public static function only_releases( array $strategies ): array {
		return array_intersect_key( $strategies, array( self::LATEST_RELEASE_STRATEGY => true ) );
	}

	/**
	 * @param string $version Release tag as PUC reads it.
	 */
	public static function is_own_release( string $version ): bool {
		return 1 === preg_match( '/^' . self::TAG_PREFIX . '\d+\.\d+\.\d+$/', $version );
	}

	/**
	 * PUC takes the version from the tag; the plugin's own version has no prefix.
	 *
	 * @param mixed $info PUC's plugin info, or null when no release was found.
	 * @return mixed
	 */
	public static function strip_tag_prefix( $info ) {
		if ( is_object( $info ) && isset( $info->version ) && is_string( $info->version ) && 0 === strpos( $info->version, self::TAG_PREFIX ) ) {
			$info->version = substr( $info->version, strlen( self::TAG_PREFIX ) );
		}
		return $info;
	}
}
