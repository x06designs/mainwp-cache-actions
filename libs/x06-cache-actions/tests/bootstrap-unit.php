<?php

declare(strict_types=1);

// Unit bootstrap: autoloader only. WordPress is mocked via Brain Monkey in each test.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Minimal WP_Error stub for unit tests (Brain Monkey does not boot WordPress, so
// the real class is absent). Mirrors the subset the components rely on.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error { // phpcs:ignore

		/** @var array<int, array{code: string, message: string}> */
		private array $errors = array();

		/** @var array<string, mixed> */
		private array $error_data = array();

		/**
		 * @param int|string           $code    Error code.
		 * @param string               $message Error message.
		 * @param array<string, mixed> $data    Error data.
		 */
		public function __construct( $code = '', string $message = '', $data = array() ) {
			if ( '' !== $code ) {
				$this->errors[] = array(
					'code'    => (string) $code,
					'message' => $message,
				);
				if ( array() !== (array) $data ) {
					$this->error_data[ (string) $code ] = $data;
				}
			}
		}

		public function get_error_code(): string {
			return $this->errors[0]['code'] ?? '';
		}

		public function get_error_message(): string {
			return $this->errors[0]['message'] ?? '';
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			$code = $this->get_error_code();
			return $this->error_data[ $code ] ?? null;
		}

		/**
		 * @param array<string, mixed> $data Error data to merge.
		 */
		public function add_data( $data, string $code = '' ): void {
			$code                      = '' !== $code ? $code : $this->get_error_code();
			$this->error_data[ $code ] = $data;
		}
	}
}
define( 'X06_CACHE_ACTIONS_FILE', dirname( __DIR__ ) . '/x06-cache-actions.php' );
