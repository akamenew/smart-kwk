<?php

/**
 * Creates neccessary database tables for this plugin
 *
 * @link       http://www.kamenew.com
 * @since      1.4.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/includes
 * @author     Artur Kamenew <artur@kamenew.com>
 */
class Smartkwk_Activator {

    /**
     * Short Description. (use period)
     * 	 *
     * @since    1.4.0
     */
    public static function activate() {

        // Require AffiliateWP plugin
        if (!is_plugin_active('affiliate-wp/affiliate-wp.php') and current_user_can('activate_plugins')) {
            // Stop activation redirect and show error
            wp_die('Required plugin "AffiliateWP" is not installed/activated! <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
        }

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE %s (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            referral_id BIGINT(20) NOT NULL,
            order_date TIMESTAMP NULL DEFAULT NULL,
            business_case VARCHAR(255) NULL DEFAULT NULL,
            api_status CHAR(20) NULL DEFAULT NULL,
            api_response TEXT NULL,
            paid TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            date_exported TIMESTAMP NULL DEFAULT NULL,
            date_inserted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY referral_id (referral_id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

        dbDelta(sprintf($sql, $wpdb->prefix . 'smart_kwk'));

        $sql = "CREATE TABLE %s (
            id VARCHAR(32) NOT NULL,
            voucher_code VARCHAR(255) NOT NULL,
            date_sent TIMESTAMP NULL DEFAULT NULL,
            referral_id BIGINT(20) NULL DEFAULT NULL,
            affiliate_id BIGINT(20) NULL DEFAULT NULL,
            date_inserted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY voucher_code (voucher_code)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

        dbDelta(sprintf($sql, $wpdb->prefix . 'smart_kwk_vouchers'));
    }

}
