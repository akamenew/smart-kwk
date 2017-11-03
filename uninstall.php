<?php
/**
 * Removes tables from database, created and this plugin.
 * 
 * @version 1.9.13
 * @since      1.4
 * @package    SmartKwk
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


function stkwk_uninstall_plugin() {
    global $wpdb;

    $wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $wpdb->prefix . 'smart_kwk'));

    $wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $wpdb->prefix . 'smart_kwk_vouchers'));
}

stkwk_uninstall_plugin();