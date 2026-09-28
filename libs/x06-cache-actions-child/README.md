# X06 Cache Actions Child

Companion plugin for MainWP child sites. It executes the cache operations that the
X06 Cache Actions dashboard extension sends through MainWP's `extra_execution` channel.

Runs on PHP 7.4+ and WordPress 6.2+ (the MainWP Child floor), without stored data. The only
bundled package is the update checker (`yahnis-elsts/plugin-update-checker`).

## Installing and updating

- Classic sites: install `x06-cache-actions-child.zip` from the latest `child-v*` GitHub release.
  New releases then show up on the site's update screen (and in MainWP's updates).
- Composer/Bedrock sites: add the Composer repository `https://x06designs.github.io/mainwp-cache-actions/`
  and require `x06designs/x06-cache-actions-child`. Updates go through Composer.

The update check is off where the site may not modify files (`DISALLOW_FILE_MODS`, the
`file_mod_allowed` filter) and on `local` and `development` environments.

## Contract with the dashboard

Request: `extra_execution` with `x06_cache_op` set to one operation.

| Operation | Steps, in order |
|---|---|
| `clear_caches` | `elementor_clear_cache` → `wpfc_delete_cache` |
| `clear_caches_minified` | `elementor_clear_cache` → `wpfc_delete_cache_and_minified` |
| `sync_library` | `elementor_sync_library` |

Every step runs, even after an earlier one failed. Any other value is ignored and the response
is left untouched, so a missing key in the response means the companion is absent or outdated.

Response: the result is added under `x06_cache_actions`; keys from other extensions are kept.

```json
{
  "x06_cache_actions": {
    "op": "clear_caches",
    "companion_version": "0.1.0",
    "api_version": 1,
    "steps": [
      { "step": "elementor_clear_cache", "status": "done", "detail": null },
      { "step": "wpfc_delete_cache", "status": "skipped", "detail": "plugin_inactive" }
    ]
  }
}
```

- `status`: `done`, `skipped` or `failed`.
- `detail`: `null` or one of `plugin_inactive`, `delete_failed`, `library_sync_failed`,
  `info_sync_failed`, `sync_failed`, `exception`. The dashboard translates these codes.
- `error`: only with `exception`; the exception class name, never its message.

## How success is detected

- **WP Fastest Cache:** `deleteCache()` returns nothing. WPFC fires `wpfc_delete_cache` only
  after a successful purge, so the step counts that action.
- **Elementor library sync:** the library refresh must return data, and Elementor must not have
  stored a `last_error` for the info refresh.

## Gates

```sh
bash scripts/lib-gates.sh x06-cache-actions-child   # from the repo root
```

Runs `composer audit`, PHPCS (WPCS), PHPStan level 6 (`phpVersion: 70400`), and `php -l` plus
the unit suite on PHP 8.3 and on every version in `php-gates.versions`. Everything runs in
Docker containers. The pre-commit hook runs the same script when files of this lib are staged.

PHPCompatibilityWP 2.x (PHPCompatibility 9) does not know PHP 8 syntax, so `php -l` and the
unit suite on PHP 7.4 carry the PHP floor check.
