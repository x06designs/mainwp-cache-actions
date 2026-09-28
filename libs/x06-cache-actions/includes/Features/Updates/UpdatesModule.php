<?php
/**
 * Updates feature module.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\Updates;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Psr\Container\ContainerInterface;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Offers new releases of the extension on the dashboard's update screen. Releases are the `v*`
 * GitHub releases of the mainwp-cache-actions repository; the companion's `child-v*` releases in the
 * same repository are ignored.
 *
 * Off where the site must not update plugins itself (`DISALLOW_FILE_MODS` or the
 * `file_mod_allowed` filter) and on local and development sites.
 */
final class UpdatesModule implements ExecutableModule {

	use ModuleClassNameIdTrait;

	public const REPOSITORY = 'https://github.com/x06designs/mainwp-cache-actions/';
	public const SLUG       = 'x06-cache-actions';

	private const FILE_MOD_CONTEXT = 'x06_cache_actions_updates';

	private const ASSET_PATTERN = '/^x06-cache-actions\.zip$/';

	/** `Api::REQUIRE_RELEASE_ASSETS`: a release without the plugin zip offers no update. */
	private const REQUIRE_RELEASE_ASSETS = 2;

	/** `Api::STRATEGY_LATEST_RELEASE`. */
	private const LATEST_RELEASE_STRATEGY = 'latest_release';

	/** `Api::RELEASE_FILTER_SKIP_PRERELEASE`. */
	private const SKIP_PRERELEASES = 1;

	/** Both plugins release from one repository, so the newest extension release can be further back. */
	private const RELEASES_TO_EXAMINE = 30;

	/** @var \Closure(string, string, string): object */
	private readonly \Closure $build_checker;

	/**
	 * @param (\Closure(string, string, string): object)|null $build_checker `PucFactory::buildUpdateChecker()` by default.
	 */
	public function __construct( ?\Closure $build_checker = null ) {
		$this->build_checker = $build_checker ?? PucFactory::buildUpdateChecker( ... );
	}

	public function run( ContainerInterface $container ): bool {
		if ( ! self::is_enabled() ) {
			return false;
		}

		self::configure( ( $this->build_checker )( self::REPOSITORY, X06_CACHE_ACTIONS_FILE, self::SLUG ) );
		return true;
	}

	public static function is_enabled(): bool {
		return wp_is_file_mod_allowed( self::FILE_MOD_CONTEXT )
			&& ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * @param object $checker A PUC `Vcs\PluginUpdateChecker` for the GitHub repository.
	 */
	public static function configure( object $checker ): void {
		$api = $checker->getVcsApi();
		$api->enableReleaseAssets( self::ASSET_PATTERN, self::REQUIRE_RELEASE_ASSETS );
		$api->setReleaseFilter( self::is_own_release( ... ), self::SKIP_PRERELEASES, self::RELEASES_TO_EXAMINE );
		add_filter( $checker->getUniqueName( 'vcs_update_detection_strategies' ), self::only_releases( ... ) );
	}

	/**
	 * PUC falls back to the highest tag and then to the branch, both as GitHub's source zip of the
	 * whole repository, whose tags also include the companion's. Only releases count.
	 *
	 * @param array<string, callable> $strategies PUC's update detection strategies by name.
	 * @return array<string, callable>
	 */
	public static function only_releases( array $strategies ): array {
		return array_intersect_key( $strategies, array( self::LATEST_RELEASE_STRATEGY => true ) );
	}

	/**
	 * @param string $version Release tag as PUC reads it, with a leading `v` removed.
	 */
	public static function is_own_release( string $version ): bool {
		return 1 === preg_match( '/^\d+\.\d+\.\d+$/', $version );
	}
}
