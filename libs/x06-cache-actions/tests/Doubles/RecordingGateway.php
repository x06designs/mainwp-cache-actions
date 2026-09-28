<?php

declare(strict_types=1);

namespace X06CacheActions\Tests\Doubles;

use X06CacheActions\Features\CacheActions\ChildGateway;
use X06CacheActions\Features\CacheActions\Operation;

/**
 * Returns a canned MainWP response and records every call.
 */
final class RecordingGateway implements ChildGateway {

	/** @var list<array{int, Operation}> */
	public array $calls = array();

	public function __construct( private readonly mixed $response ) {}

	public function execute( int $site_id, Operation $operation ): mixed {
		$this->calls[] = array( $site_id, $operation );
		return $this->response;
	}
}
