<?php
/**
 * Plugin Name:       X06 Cache Actions
 * Description:       Bulk actions for Sites > Manage Sites: clear Elementor and WP Fastest Cache caches and sync the Elementor library on child sites.
 * Version:           0.1.1
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Requires Plugins:  mainwp
 * Author:            X-06 Designs
 * License:           GPL-2.0-or-later
 * Text Domain:       x06-cache-actions
 *
 * @package X06CacheActions
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

const X06_CACHE_ACTIONS_VERSION = '0.1.1';
const X06_CACHE_ACTIONS_FILE    = __FILE__;

require_once __DIR__ . '/vendor/autoload.php';

\X06CacheActions\Core\Plugin::boot( __FILE__ );
