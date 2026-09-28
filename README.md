# X06 Cache Actions for MainWP

Clear caches and sync the Elementor template library on many WordPress sites at once, from the
MainWP Dashboard.

The project has two plugins:

| Plugin | Installed on | Requires |
|---|---|---|
| **X06 Cache Actions** | the MainWP Dashboard site | WordPress 6.4+, PHP 8.3+, MainWP Dashboard |
| **X06 Cache Actions Child** | every child site | WordPress 6.2+, PHP 7.4+, MainWP Child |

## What it does

Three bulk actions appear in *Sites > Manage Sites*:

| Action | On each selected site |
|---|---|
| Clear caches | Elementor *Clear Files & Data*, then WP Fastest Cache *Delete Cache* |
| Clear caches + minified | Elementor *Clear Files & Data*, then WP Fastest Cache *Delete Cache and Minified CSS/JS* |
| Sync Elementor library | Elementor *Sync Library* |

Before an action runs, a confirmation lists its side effects. Examples: pages rebuild their
Elementor CSS on the next view; WP Fastest Cache may purge Cloudflare or Varnish and restart
preloading. The dashboard then works through the selected sites, three at a time, and shows the
result per site.

A site without Elementor or WP Fastest Cache skips that step; this is not an error. A site
without the child plugin, or a suspended or unreachable site, is reported as such.

## Installation

1. Download `x06-cache-actions.zip` and `x06-cache-actions-child.zip` from the
   [latest releases](https://github.com/x06designs/mainwp-cache-actions/releases).
2. Install and activate `x06-cache-actions.zip` on the MainWP Dashboard.
3. Install and activate `x06-cache-actions-child.zip` on each child site. MainWP can do this for
   you: *Plugins > Install*, upload the zip, select the sites.

Both plugins offer later releases on the normal WordPress update screen. The update check is off
on sites that may not modify files (`DISALLOW_FILE_MODS`) and on `local` and `development`
environments.

### With Composer

Add the repository and require the packages:

```json
{
  "repositories": [
    { "type": "composer", "url": "https://x06designs.github.io/mainwp-cache-actions/" }
  ],
  "require": {
    "x06designs/x06-cache-actions": "^0.1",
    "x06designs/x06-cache-actions-child": "^0.1"
  }
}
```

Both are `wordpress-plugin` packages; `composer/installers` puts them in your plugins directory.

## Privacy

Neither plugin stores data or adds database tables. The dashboard talks to child sites only
through MainWP's own authenticated connection. The update check asks the GitHub API for new
releases of this repository.

## Development

Requires Node.js 22+ and Docker (PHP runs in containers).

```sh
npm install
bash scripts/lib-gates.sh x06-cache-actions        # dashboard extension
bash scripts/lib-gates.sh x06-cache-actions-child  # child plugin
npx nx run x06-cache-actions:build                 # script bundle
```

The gates run Composer audit, PHPCS (WordPress Coding Standards), PHPStan, PHPUnit (the child
plugin also on PHP 7.4) and, for the dashboard extension, the TypeScript checks and unit tests.
Commits follow [Conventional Commits](https://www.conventionalcommits.org/).

Each plugin's own README describes its internals:
[dashboard extension](libs/x06-cache-actions/README.md),
[child plugin](libs/x06-cache-actions-child/README.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
