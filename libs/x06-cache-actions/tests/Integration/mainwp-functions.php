<?php
/**
 * MainWP Dashboard functions for the integration suite, which runs without MainWP.
 * Tests steer the capability answer through the `x06_cache_actions_test_can` filter.
 */

declare(strict_types=1);

if ( ! function_exists( 'mainwp_current_user_can' ) ) {
	/**
	 * @param string $cap_type Capability group.
	 * @param string $cap      Capability.
	 */
	function mainwp_current_user_can( $cap_type = '', $cap = '' ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- MainWP's own function name.
		return (bool) apply_filters( 'x06_cache_actions_test_can', true, $cap_type, $cap );
	}
}
