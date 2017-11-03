<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.kamenew.com
 * @since             1.4.0
 * @package           Smartkwk
 *
 * @wordpress-plugin
 * Plugin Name:       SmartKwk
 * Plugin URI:        http://www.smartsteuer.de
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.4.0
 * Author:            Artur Kamenew
 * Author URI:        http://www.kamenew.com
 * Text Domain:       smartkwk
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('SMARTKWK_PLUGIN_NAME_VERSION', '1.4.0');
define('SMARTKWK_PLUGIN', __FILE__);
define('SMARTKWK_PLUGIN_BASENAME', plugin_basename(SMARTKWK_PLUGIN));
define('SMARTKWK_PLUGIN_NAME', trim(dirname(SMARTKWK_PLUGIN_BASENAME), '/'));
define('SMARTKWK_PLUGIN_DIR', untrailingslashit(dirname(SMARTKWK_PLUGIN)));
define('SMARTKWK_PLUGIN_URL', untrailingslashit(plugins_url('', SMARTKWK_PLUGIN)));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-smartkwk-activator.php
 */
function activate_smartkwk() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-smartkwk-activator.php';
    Smartkwk_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-smartkwk-deactivator.php
 */
function deactivate_smartkwk() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-smartkwk-deactivator.php';
    Smartkwk_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_smartkwk');
register_deactivation_hook(__FILE__, 'deactivate_smartkwk');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-smartkwk.php';
require plugin_dir_path(__FILE__) . 'config.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.4.0
 */
function run_smartkwk() {

    $plugin = new Smartkwk();
    $plugin->run();
}

run_smartkwk();