# X06 Cache Actions

MainWP Dashboard extension. Adds three bulk actions to *Sites > Manage Sites* and runs them on
each selected child site through MainWP's authenticated `extra_execution` channel. The child
side is `libs/x06-cache-actions-child`.

| Bulk action | Operation |
|---|---|
| Clear caches | `clear_caches` |
| Clear caches + minified | `clear_caches_minified` |
| Sync Elementor library | `sync_library` |

Requires the MainWP Dashboard (`Requires Plugins: mainwp`) and PHP 8.3. Stores no data.

## Installing and updating

See the [repository README](../../README.md#installation). Composer package:
`x06designs/x06-cache-actions`.

## Layout

| Path | Purpose |
|---|---|
| `includes/Features/CacheActions/cache-actions.schema.json` | Request args, the companion's `ChildResult`, the AJAX `CacheActionResponse` |
| `CacheActionsModule` | Wires everything once MainWP is active (`mainwp_activated_check` / `mainwp_activated`) |
| `BulkActions` | Menu entries `x06_cache_actions_<operation>` via `mainwp_managesites_bulk_actions` |
| `CacheActionController` | AJAX `x06_cache_actions_run`, one site per request |
| `MainWpChildGateway` | `mainwp_fetchurlauthed` → `extra_execution`, timeout raised to 120 s for that call only |
| `ResultMapper` | MainWP's raw answer → `CacheActionResponse`; validates the companion payload |
| `ManageSitesAssets` | Enqueues the bundle on Manage Sites only, with the endpoint and all UI strings (translated in PHP) |
| `web/backend/` | Bulk-action script: MainWP confirm modal, queue with 3 parallel requests, per-site result in MainWP's sync modal |
| `Features/Updates/UpdatesModule` | Update checks against the `v*` GitHub releases |
| `generated/` | TS types + constants from the schema (committed, drift-checked) |

## AJAX endpoint

`POST admin-ajax.php`, `action=x06_cache_actions_run`, `security=<MainWP nonce for the action>`,
`site_id`, `op`. Access: MainWP administrator (`mainwp_secure_request`), the extension capability
(`mainwp_current_user_can`), and MainWP's per-site edit check.

| HTTP | Body |
|---|---|
| 200 | `{ site_id, op, result }` — the companion's `ChildResult` |
| 200 | `{ site_id, op, code[, message] }` — `extension_rejected`, `site_suspended`, `connection_failed`, `companion_missing`, `companion_outdated` |
| 403 | `{ code: "forbidden" }` |
| 422 | `{ code: "invalid_request", message }` |

MainWP's own nonce check answers `{ error }` with 200 before this plugin runs. Do not send a
second-resolution `dts` field: MainWP then rejects parallel requests as "Double request!".

## Gates

```sh
bash scripts/lib-gates.sh x06-cache-actions   # from the repo root
```

PHPCS, PHPStan L6, unit suite (Brain Monkey), schema drift, `tsc` and the web unit tests. The
integration suite (WP test library) runs where a local MainWP Dashboard environment is set up.
`CompanionContractTest` fails when the schema and the child plugin drift apart.
