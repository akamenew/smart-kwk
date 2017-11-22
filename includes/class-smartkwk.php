<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 * 
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       http://www.kamenew.com
 * @since      1.0.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/includes
 * @author     Artur Kamenew <artur@kamenew.com>
 */
class Smartkwk {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.4.0
     * @access   protected
     * @var      Smartkwk_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.4.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.4.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.4.0
     */
    public function __construct() {
        if (defined('SMARTKWK_PLUGIN_NAME_VERSION')) {
            $this->version = SMARTKWK_PLUGIN_NAME_VERSION;
        } else {
            $this->version = '1.4.0';
        }
        $this->plugin_name = 'smartkwk';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Smartkwk_Loader. Orchestrates the hooks of the plugin.
     * - Smartkwk_i18n. Defines internationalization functionality.
     * - Smartkwk_Admin. Defines all hooks for the admin area.
     * - Smartkwk_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.4.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-smartkwk-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-smartkwk-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-smartkwk-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-smartkwk-public.php';

        $this->loader = new Smartkwk_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Smartkwk_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.4.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Smartkwk_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.4.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Smartkwk_Admin($this->get_plugin_name(), $this->get_version());


        $this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu');

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('admin_post_exportkwk', $plugin_admin, 'admin_exportkwk');
        $this->loader->add_action('admin_post_export_overview', $plugin_admin, 'admin_export_overview');
        $this->loader->add_action('admin_post_importkwk', $plugin_admin, 'admin_importkwk');
        $this->loader->add_action('admin_post_getimport', $plugin_admin, 'admin_getimport');
        $this->loader->add_action('admin_post_addvoucher', $plugin_admin, 'admin_addvoucher');
        $this->loader->add_action('admin_post_importvouchers', $plugin_admin, 'admin_importvouchers');
        $this->loader->add_action('admin_post_importexpercash', $plugin_admin, 'admin_importexpercash');
        $this->loader->add_action('admin_post_save_emailtemplate', $plugin_admin, 'save_emailtemplate');

        $this->loader->add_action('display_tab', $plugin_admin, 'display_tab');

        $this->loader->add_action('plugin_action_links_' . SMARTKWK_PLUGIN_BASENAME, $plugin_admin, 'plugin_action_links', 10, 2);

        $this->loader->add_action('wp_ajax_api_request', $plugin_admin, 'api_request');
        $this->loader->add_action('wp_ajax_save_voucher', $plugin_admin, 'save_voucher');
        $this->loader->add_action('wp_ajax_send_voucher', $plugin_admin, 'send_voucher');
        $this->loader->add_action('wp_ajax_change_status', $plugin_admin, 'change_status');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.4.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Smartkwk_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.4.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.4.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.4.0
     * @return    Smartkwk_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.4.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
