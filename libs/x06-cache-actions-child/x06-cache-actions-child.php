<?php
/**
 * Plugin Name:       X06 Cache Actions Child
 * Description:       Runs X06 Cache Actions bulk operations sent from the MainWP Dashboard on this site.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            X-06 Designs
 * License:           GPL-2.0-or-later
 * Text Domain:       x06-cache-actions-child
 *
 * @package X06CacheActionsChild
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

const X06_CACHE_ACTIONS_CHILD_VERSION = '0.1.0';
const X06_CACHE_ACTIONS_CHILD_FILE    = __FILE__;

require_once __DIR__ . '/includes/autoload.php';

\X06CacheActionsChild\Core\Plugin::boot( __FILE__ );
