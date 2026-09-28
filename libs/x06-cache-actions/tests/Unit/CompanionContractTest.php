<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Unit;

use X06CacheActions\Features\CacheActions\MainWpChildGateway;
use X06CacheActions\Features\CacheActions\Operation;
use X06CacheActions\Features\CacheActions\ResultMapper;
use X06CacheActionsChild\Features\CacheActions\CacheActionsModule as ChildModule;
use X06CacheActionsChild\Features\CacheActions\Operation as ChildOperation;
use X06CacheActionsChild\Features\CacheActions\OperationRunner as ChildRunner;
use X06CacheActionsChild\Features\CacheActions\StepPlan as ChildStepPlan;
use X06CacheActionsChild\Features\CacheActions\Steps\Step as ChildStep;
use X06CacheActionsChild\Features\CacheActions\Steps\StepResult as ChildStepResult;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Fails when the dashboard schema and the companion drift apart. Both libs live in this repo,
 * so the companion's own classes are the reference.
 */
final class CompanionContractTest extends TestCase {

	private const CHILD_INCLUDES = __DIR__ . '/../../../x06-cache-actions-child/includes/';

	/** @var array<string, mixed> */
	private static array $schema;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		spl_autoload_register(
			static function ( string $class_name ): void {
				$prefix = 'X06CacheActionsChild\\';
				if ( str_starts_with( $class_name, $prefix ) ) {
					require_once self::CHILD_INCLUDES . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
				}
			}
		);
		self::$schema = json_decode(
			(string) file_get_contents( __DIR__ . '/../../includes/Features/CacheActions/cache-actions.schema.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);
	}

	public function test_operations_match(): void {
		$dashboard = array_map( static fn ( Operation $op ): string => $op->value, Operation::cases() );

		$this->assertSame( ChildOperation::ALL, $dashboard );
		$this->assertSame( ChildOperation::ALL, self::$schema['properties']['op']['enum'] );
		$this->assertSame( ChildOperation::ALL, $this->child_result()['properties']['op']['enum'] );
		$this->assertSame( ChildOperation::ALL, self::$schema['definitions']['CacheActionResponse']['properties']['op']['enum'] );
	}

	public function test_step_names_match(): void {
		$plan  = ChildStepPlan::default();
		$names = array();
		foreach ( ChildOperation::ALL as $operation ) {
			foreach ( $plan->steps_for( $operation ) as $step ) {
				$names[] = $step->name();
			}
		}

		$this->assertEqualsCanonicalizing( array_values( array_unique( $names ) ), $this->step_schema()['properties']['step']['enum'] );
	}

	public function test_statuses_and_detail_codes_match(): void {
		$constants = ( new \ReflectionClass( ChildStepResult::class ) )->getConstants();
		$statuses  = array( ChildStepResult::DONE, ChildStepResult::SKIPPED, ChildStepResult::FAILED );
		$details   = array_values( array_diff( $constants, $statuses ) );

		$this->assertEqualsCanonicalizing( $statuses, $this->step_schema()['properties']['status']['enum'] );
		$this->assertEqualsCanonicalizing( array_merge( $details, array( null ) ), $this->step_schema()['properties']['detail']['enum'] );
	}

	public function test_api_version_and_keys_match(): void {
		$this->assertSame( array( ChildRunner::API_VERSION ), $this->child_result()['properties']['api_version']['enum'] );
		$this->assertSame( ChildModule::REQUEST_KEY, MainWpChildGateway::REQUEST_KEY );
		$this->assertSame( ChildModule::RESPONSE_KEY, ResultMapper::RESPONSE_KEY );
		$this->assertTrue( interface_exists( ChildStep::class ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function child_result(): array {
		return self::$schema['definitions']['ChildResult'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function step_schema(): array {
		return $this->child_result()['properties']['steps']['items'];
	}
}
