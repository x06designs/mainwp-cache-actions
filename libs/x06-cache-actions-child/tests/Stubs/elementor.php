<?php
/**
 * Stand-ins for the Elementor classes the companion calls. Public statics let tests steer them.
 */

declare(strict_types=1);

namespace Elementor\Core\Files {

	class Manager {

		public int $clear_cache_calls = 0;

		public function clear_cache(): void {
			++$this->clear_cache_calls;
		}
	}
}

namespace Elementor {

	const ELEMENTOR_STUB_VERSION = '4.3.2';

	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		define( 'ELEMENTOR_VERSION', ELEMENTOR_STUB_VERSION );
	}

	class Plugin {

		/** @var Plugin|null */
		public static $instance;

		/** @var \Elementor\Core\Files\Manager|null */
		public $files_manager;
	}

	class Api {

		/** @var array<mixed> */
		public static array $library_data = array();

		/** @var list<array{string, bool}> */
		public static array $calls = array();

		/**
		 * @return array<mixed>
		 */
		public static function get_library_data( bool $force_update = false ): array {
			self::$calls[] = array( 'library', $force_update );
			return self::$library_data;
		}

		/**
		 * @param bool $force Force a remote refresh.
		 * @return array<mixed>|false
		 */
		public static function get_canary_deployment_info( $force = false ) {
			self::$calls[] = array( 'info', (bool) $force );
			return false;
		}
	}
}
