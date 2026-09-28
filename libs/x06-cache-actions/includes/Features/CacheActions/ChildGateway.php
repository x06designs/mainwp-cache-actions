<?php
/**
 * Child site transport role.
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

namespace X06CacheActions\Features\CacheActions;

interface ChildGateway {

	/**
	 * Asks the child site to run an operation and returns MainWP's raw response.
	 */
	public function execute( int $site_id, Operation $operation ): mixed;
}
