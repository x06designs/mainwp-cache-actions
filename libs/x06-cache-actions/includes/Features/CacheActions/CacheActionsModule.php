<?php
/**
 * Cache Actions feature module.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

/**
 * Wires the bulk actions, their script and the AJAX endpoint once the MainWP Dashboard is active.
 */
final class CacheActionsModule implements ServiceModule, ExecutableModule {

	use ModuleClassNameIdTrait;

	public const EXTENSION_SLUG = 'x06-cache-actions';

	private const SCHEMA_FILE = __DIR__ . '/cache-actions.schema.json';

	/**
	 * @return array<string, callable>
	 */
	public function services(): array {
		return array(
			self::SCHEMA_FILE             => static fn (): array => self::load_schema(),
			ResultMapper::class           => static fn ( ContainerInterface $c ): ResultMapper => new ResultMapper(
				$c->get( self::SCHEMA_FILE )['definitions']['ChildResult']
			),
			MainWpChildGateway::class     => static fn (): MainWpChildGateway => new MainWpChildGateway( X06_CACHE_ACTIONS_FILE ),
			CacheActionController::class  => static fn ( ContainerInterface $c ): CacheActionController => new CacheActionController(
				$c->get( self::SCHEMA_FILE ),
				$c->get( MainWpChildGateway::class ),
				$c->get( ResultMapper::class )
			),
			BulkActions::class            => static fn (): BulkActions => new BulkActions(),
			ManageSitesAssets::class      => static fn (): ManageSitesAssets => new ManageSitesAssets( X06_CACHE_ACTIONS_FILE, \Kucrut\Vite\enqueue_asset( ... ) ),
		);
	}

	public function run( ContainerInterface $container ): bool {
		$register = static function () use ( $container ): void {
			add_filter( 'mainwp_managesites_bulk_actions', array( $container->get( BulkActions::class ), 'add' ) );
			do_action( 'mainwp_ajax_add_action', CacheActionController::ACTION, array( $container->get( CacheActionController::class ), 'handle' ) );
			add_action( 'admin_enqueue_scripts', array( $container->get( ManageSitesAssets::class ), 'enqueue' ) );
		};

		if ( apply_filters( 'mainwp_activated_check', false ) ) {
			$register();
		} else {
			add_action( 'mainwp_activated', $register );
		}

		return true;
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws \UnexpectedValueException When the schema file does not decode to an object.
	 */
	private static function load_schema(): array {
		$schema = json_decode( (string) file_get_contents( self::SCHEMA_FILE ), true, 512, JSON_THROW_ON_ERROR );
		if ( ! is_array( $schema ) ) {
			throw new \UnexpectedValueException( sprintf( 'Schema %s does not decode to an object.', esc_html( self::SCHEMA_FILE ) ) );
		}
		return $schema;
	}
}
