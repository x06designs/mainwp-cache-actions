<?php
/**
 * Signatures of the MainWP Dashboard functions this plugin calls (symbol source for PHPStan).
 */

declare(strict_types=1);

/**
 * @param string $cap_type Capability group.
 * @param string $cap      Capability.
 */
function mainwp_current_user_can( $cap_type = '', $cap = '' ): bool {
	return true;
}

/**
 * @param string $action Nonce action.
 */
function mainwp_secure_request( $action ): void {}
