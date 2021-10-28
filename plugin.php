<?php

/**
 * Plugin Name: Geniem WP Security Plugin
 * Plugin URI:  https://github.com/devgeniem/geniem-wp-security-plugin
 * Description: Checks WordPress core and plugin versions and reports them to the dashboard.
 * Version:     1.2.1
 * Author:      Geniem
 * Author URI:  http://www.geniem.fi/
 */

namespace Geniem\WPSecurityPlugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

$plugin = new WPSecurityPlugin();

register_deactivation_hook( __FILE__, function() {
    wp_unschedule_hook( 'geniem_wp_security_plugin_scan' );
} );
