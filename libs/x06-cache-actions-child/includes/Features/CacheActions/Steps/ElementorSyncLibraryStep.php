<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions\Steps;

use Elementor\Api;
use Elementor\Plugin;

/**
 * Elementor → Tools → "Sync Library", plus the template library refresh that
 * `wp elementor library sync` performs.
 *
 * The info refresh reports nothing back; on failure Elementor stores a transient holding
 * only `last_error`, which is what this step reads.
 */
final class ElementorSyncLibraryStep implements Step {

	private const INFO_TRANSIENT_PREFIX = 'elementor_remote_info_api_data_';

	public function name(): string {
		return 'elementor_sync_library';
	}

	public function run(): StepResult {
		if ( ! class_exists( Api::class ) || ! class_exists( Plugin::class ) || ! isset( Plugin::$instance ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
			return StepResult::skipped( $this->name(), StepResult::PLUGIN_INACTIVE );
		}

		$isLibrarySynced = array() !== Api::get_library_data( true );

		Api::get_canary_deployment_info( true );
		$info         = get_transient( self::INFO_TRANSIENT_PREFIX . ELEMENTOR_VERSION );
		$isInfoSynced = is_array( $info ) && ! isset( $info['last_error'] );

		if ( $isLibrarySynced && $isInfoSynced ) {
			return StepResult::done( $this->name() );
		}
		if ( ! $isLibrarySynced && ! $isInfoSynced ) {
			return StepResult::failed( $this->name(), StepResult::SYNC_FAILED );
		}

		return StepResult::failed(
			$this->name(),
			$isLibrarySynced ? StepResult::INFO_SYNC_FAILED : StepResult::LIBRARY_SYNC_FAILED
		);
	}
}
