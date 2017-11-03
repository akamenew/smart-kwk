<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://www.kamenew.com
 * @since      1.4.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/includes
 * @author     Artur Kamenew <artur@kamenew.com>
 */
class Smartkwk_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.4.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
                'smartkwk', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

}
