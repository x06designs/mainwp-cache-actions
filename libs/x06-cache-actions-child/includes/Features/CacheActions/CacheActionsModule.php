<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions;

/**
 * Answers the dashboard's `extra_execution` calls. MainWP Child only dispatches
 * `extra_execution` on authenticated dashboard requests.
 */
final class CacheActionsModule {

	public const REQUEST_KEY  = 'x06_cache_op';
	public const RESPONSE_KEY = 'x06_cache_actions';

	/**
	 * Below the dashboard's 120 s request timeout, so the child answers before the
	 * dashboard gives up.
	 */
	private const TIME_LIMIT_SECONDS = 90;

	private OperationRunner $runner;

	public function __construct( ?OperationRunner $runner = null ) {
		$this->runner = $runner ?? new OperationRunner( StepPlan::default() );
	}

	public function register(): void {
		add_filter( 'mainwp_child_extra_execution', array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * @param mixed $information Response other extensions have built so far.
	 * @param mixed $post        The dashboard's request payload.
	 * @return mixed
	 */
	public function handle( $information, $post ) {
		$operation = Operation::try_from( is_array( $post ) ? ( $post[ self::REQUEST_KEY ] ?? null ) : null );
		if ( null === $operation ) {
			return $information;
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( self::TIME_LIMIT_SECONDS );
		}

		$information                       = is_array( $information ) ? $information : array();
		$information[ self::RESPONSE_KEY ] = $this->runner->run( $operation );

		return $information;
	}
}
