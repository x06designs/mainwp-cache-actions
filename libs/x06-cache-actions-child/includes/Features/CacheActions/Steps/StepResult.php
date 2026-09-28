<?php

declare(strict_types=1);

namespace X06CacheActionsChild\Features\CacheActions\Steps;

/**
 * Outcome of one step. `detail` is a machine code the dashboard translates; it never
 * carries messages, paths or other site data.
 */
final class StepResult {

	public const DONE    = 'done';
	public const SKIPPED = 'skipped';
	public const FAILED  = 'failed';

	public const PLUGIN_INACTIVE     = 'plugin_inactive';
	public const DELETE_FAILED       = 'delete_failed';
	public const LIBRARY_SYNC_FAILED = 'library_sync_failed';
	public const INFO_SYNC_FAILED    = 'info_sync_failed';
	public const SYNC_FAILED         = 'sync_failed';
	public const EXCEPTION           = 'exception';

	private string $step;

	private string $status;

	private ?string $detail;

	private ?string $error;

	private function __construct( string $step, string $status, ?string $detail, ?string $error ) {
		$this->step   = $step;
		$this->status = $status;
		$this->detail = $detail;
		$this->error  = $error;
	}

	public static function done( string $step ): self {
		return new self( $step, self::DONE, null, null );
	}

	public static function skipped( string $step, string $detail ): self {
		return new self( $step, self::SKIPPED, $detail, null );
	}

	public static function failed( string $step, string $detail ): self {
		return new self( $step, self::FAILED, $detail, null );
	}

	public static function crashed( string $step, \Throwable $throwable ): self {
		return new self( $step, self::FAILED, self::EXCEPTION, get_class( $throwable ) );
	}

	/**
	 * @return array{step: string, status: string, detail: string|null, error?: string}
	 */
	public function to_array(): array {
		$result = array(
			'step'   => $this->step,
			'status' => $this->status,
			'detail' => $this->detail,
		);
		if ( null !== $this->error ) {
			$result['error'] = $this->error;
		}
		return $result;
	}
}
