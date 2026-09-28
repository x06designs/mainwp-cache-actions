import type { Messages, ScriptConfig } from '../config.ts';

export const messages: Messages = {
  operations: {
    clear_caches: 'Clear caches',
    clear_caches_minified: 'Clear caches + minified',
    sync_library: 'Sync Elementor library',
  },
  confirm: '"%s" runs on every selected site:',
  effects: {
    clear_caches: ['Elementor effect', 'WPFC effect'],
    clear_caches_minified: ['Elementor effect', 'WPFC minified effect'],
    sync_library: ['Sync effect'],
  },
  progress: 'processed',
  codes: {
    invalid_request: 'Invalid.',
    forbidden: 'Forbidden.',
    extension_rejected: 'Rejected.',
    site_suspended: 'Suspended.',
    connection_failed: 'Unreachable.',
    companion_missing: 'Companion missing.',
    companion_outdated: 'Companion outdated.',
  },
  requestFailed: 'Request failed.',
  timeout: 'Timed out.',
  unexpected: 'Unexpected (HTTP %d).',
  steps: {
    elementor_clear_cache: 'Elementor cache',
    wpfc_delete_cache: 'WP Fastest Cache',
    wpfc_delete_cache_and_minified: 'WP Fastest Cache and minified files',
    elementor_sync_library: 'Elementor library',
  },
  statuses: { done: 'done', skipped: 'skipped', failed: 'failed' },
  details: {
    plugin_inactive: 'plugin not active',
    delete_failed: 'files could not be deleted',
    library_sync_failed: 'library download failed',
    info_sync_failed: 'remote info download failed',
    sync_failed: 'download failed',
    exception: 'unexpected error',
  },
  step: '%1$s: %2$s',
  stepDetail: '%1$s: %2$s (%3$s)',
};

export const config: ScriptConfig = {
  action: 'x06_cache_actions_run',
  prefix: 'x06_cache_actions_',
  concurrency: 3,
  timeoutMs: 150_000,
  i18n: messages,
};
