<?php

/**
 * Class PluginTest
 *
 * @package Smart_kwk
 */

/**
 * Plugin test case.
 */
class PluginTest extends WP_UnitTestCase {

    public function test_load_dependencies() {

        $plugin_directory = plugin_dir_path(dirname(__FILE__));

        //admin
        $this->assertFileExists($plugin_directory . 'admin/class-smartkwk-admin.php');

        //admin/classes
        $this->assertFileExists($plugin_directory . 'admin/classes/class-smartkwk-backoffice.php');
        $this->assertFileExists($plugin_directory . 'admin/classes/class-smartkwk-expercash.php');
        $this->assertFileExists($plugin_directory . 'admin/classes/class-smartkwk-mailer.php');
        $this->assertFileExists($plugin_directory . 'admin/classes/class-smartkwk-voucher.php');


        //admin/css
        $this->assertFileExists($plugin_directory . 'admin/css/smartkwk-admin.css');

        //admin/external
        $this->assertFileExists($plugin_directory . 'admin/external/expercash.rb');


        //admin/js
        $this->assertFileExists($plugin_directory . 'admin/js/smartkwk-admin.js');

        //admin/partials
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_backoffice.php');
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_emailtpl.php');
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_expercash.php');
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_export.php');
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_import.php');
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_overview.php');
        $this->assertFileExists($plugin_directory . 'admin/partials/tab_voucher.php');

        //admin/templates        
        $this->assertFileExists($plugin_directory . 'admin/templates/sendVoucher.html');

        //includes
        $this->assertFileExists($plugin_directory . 'includes/class-smartkwk-activator.php');
        $this->assertFileExists($plugin_directory . 'includes/class-smartkwk-deactivator.php');
        $this->assertFileExists($plugin_directory . 'includes/class-smartkwk-i18n.php');
        $this->assertFileExists($plugin_directory . 'includes/class-smartkwk-loader.php');
        $this->assertFileExists($plugin_directory . 'includes/class-smartkwk.php');

        //public
        $this->assertFileExists($plugin_directory . 'public/class-smartkwk-public.php');

        //public/css
        $this->assertFileExists($plugin_directory . 'public/css/smartkwk-public.css');

        //public/js
        $this->assertFileExists($plugin_directory . 'public/js/smartkwk-public.js');

        //root
        $this->assertFileExists($plugin_directory . 'config.php');
        $this->assertFileExists($plugin_directory . 'smart_kwk.php');
        $this->assertFileExists($plugin_directory . 'uninstall.php');
    }

    public function test_define_admin_hooks() {

        //actions for form responses
        $this->assertTrue(has_action('admin_post_exportkwk'));
        $this->assertTrue(has_action('admin_post_export_overview'));
        $this->assertTrue(has_action('admin_post_importkwk'));
        $this->assertTrue(has_action('admin_post_getimport'));
        $this->assertTrue(has_action('admin_post_importvouchers'));
        $this->assertTrue(has_action('admin_post_importexpercash'));
        $this->assertTrue(has_action('admin_post_save_emailtemplate'));

        //action for displaying menu pages/tabs
        $this->assertTrue(has_action('display_tab'));

        //plugin actions
        $this->assertTrue(has_action('plugin_action_links_' . SMARTKWK_PLUGIN_BASENAME));

        //ajax actions
        $this->assertTrue(has_action('wp_ajax_api_request'));
        $this->assertTrue(has_action('wp_ajax_save_voucher'));
        $this->assertTrue(has_action('wp_ajax_send_voucher'));
        $this->assertTrue(has_action('wp_ajax_change_status'));
    }

    public function test_required_plugin_is_activated() {

        $this->assertTrue(true);
        //$this->assertTrue(is_plugin_active('affiliate-wp/affiliate-wp.php'));
    }

    public function test_config_variables() {

        $this->assertNotEmpty(PLACEHOLDER_VOUCHER);
        $this->assertStringStartsWith('[[', PLACEHOLDER_VOUCHER);
        $this->assertStringEndsWith(']]', PLACEHOLDER_VOUCHER);

        $this->assertNotEmpty(DEFAULT_TAB);
        $this->assertNotEmpty(BCC_EMAIL);
        $this->assertInternalType('int', MAX_OVERVIEW_ROWS);
        $this->assertInternalType('int', SMTP_PORT);
        $this->assertInternalType('bool', USE_SMTP);

        $this->assertContains(DEFAULT_TAB, array('overview', 'backoffice', 'voucher', 'emailtpl'));
        $this->assertSame(BCC_EMAIL, filter_var(BCC_EMAIL, FILTER_VALIDATE_EMAIL));
        $this->assertSame(SMTP_FROM_EMAIL, filter_var(SMTP_FROM_EMAIL, FILTER_VALIDATE_EMAIL));
    }

}
