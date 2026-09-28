<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Tests\Unit;

use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * The plugin ships without Composer, so its own autoloader and bootstrap file are exercised in a
 * fresh PHP process that has nothing else loaded.
 */
final class ShippedEntryPointsTest extends TestCase {

	public function test_autoloader_resolves_plugin_classes_only(): void {
		$output = $this->run_isolated(
			'require "includes/autoload.php";
			echo class_exists("X06CacheActionsChild\\\\Features\\\\CacheActions\\\\Steps\\\\StepResult") ? "nested:yes " : "nested:no ";
			echo class_exists("Foreign\\\\Thing") ? "foreign:yes " : "foreign:no ";
			echo class_exists("X06CacheActionsChild\\\\Missing\\\\Thing") ? "missing:yes" : "missing:no";'
		);

		$this->assertSame( 'nested:yes foreign:no missing:no', $output );
	}

	public function test_bootstrap_exits_without_wordpress(): void {
		$output = $this->run_isolated( 'require "x06-cache-actions-child.php"; echo "loaded";' );

		$this->assertSame( '', $output );
	}

	public function test_bootstrap_hooks_the_handler_inside_wordpress(): void {
		$output = $this->run_isolated(
			'define("ABSPATH", "/tmp/");
			function add_filter( $hook, $callback, $priority, $args ) { echo $hook, ":", get_class( $callback[0] ), ":", $priority, ":", $args; }
			function wp_is_file_mod_allowed( $context ) { return true; }
			function wp_get_environment_type() { return "local"; }
			require "x06-cache-actions-child.php";'
		);

		$this->assertSame(
			'mainwp_child_extra_execution:X06CacheActionsChild\\Features\\CacheActions\\CacheActionsModule:10:2',
			$output
		);
	}

	/**
	 * A stand-in `v5\PucFactory` is declared first; the bundled loader skips its own when the class
	 * exists but still has to run, or the versioned classes are missing.
	 */
	public function test_bootstrap_loads_the_bundled_update_checker_on_production_sites(): void {
		$output = $this->run_isolated(
			'namespace YahnisElsts\PluginUpdateChecker\v5 {
				class PucFactory {
					public static function addVersion( ...$args ) {}
					public static function buildUpdateChecker( $url, $file, $slug ) {
						echo $url, "|", basename( $file ), "|", $slug, "|";
						echo class_exists( "YahnisElsts\\\\PluginUpdateChecker\\\\v5p7\\\\PucFactory", false ) ? "loader" : "no-loader";
						return new class {
							public function getVcsApi() { return $this; }
							public function enableReleaseAssets( ...$args ) {}
							public function setReleaseFilter( ...$args ) {}
							public function addResultFilter( ...$args ) {}
							public function getUniqueName( $name ) { return $name; }
						};
					}
				}
			}
			namespace {
				define( "ABSPATH", "/tmp/" );
				function add_filter( ...$args ) {}
				function wp_is_file_mod_allowed( $context ) { return true; }
				function wp_get_environment_type() { return "production"; }
				require "x06-cache-actions-child.php";
			}'
		);

		$this->assertSame( 'https://github.com/x06designs/mainwp-cache-actions/|x06-cache-actions-child.php|x06-cache-actions-child|loader', $output );
	}

	private function run_isolated( string $code ): string {
		$command = sprintf(
			'cd %s && %s -d display_errors=stderr -r %s 2>&1',
			escapeshellarg( dirname( __DIR__, 2 ) ),
			escapeshellarg( PHP_BINARY ),
			escapeshellarg( $code )
		);
		exec( $command, $lines, $exitCode ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		$output = implode( "\n", $lines );
		$this->assertSame( 0, $exitCode, $output );
		return $output;
	}
}
