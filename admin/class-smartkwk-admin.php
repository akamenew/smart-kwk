<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.kamenew.com
 * @since      1.4.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/admin
 * @author     Artur Kamenew <artur@kamenew.com>
 */
require_once dirname(__FILE__) . '/classes/class-smartkwk-backoffice.php';
require_once dirname(__FILE__) . '/classes/class-smartkwk-expercash.php';
require_once dirname(__FILE__) . '/classes/class-smartkwk-mailer.php';
require_once dirname(__FILE__) . '/classes/class-smartkwk-voucher.php';

class Smartkwk_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_plugin_name    The ID of this plugin.
     */
    private $_plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_version    The current version of this plugin.
     */
    private $_version;

    /**
     * Temporary directory for writing export files
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_tempdir    Temp directory
     */
    private $_tempdir;

    /**
     * Status for a PAID order according to AffiliateWP plugin
     *
     * @since    1.4.0
     * @access   public
     * @var      string    $AFFILIATEWP_PAID    Status for a paid order
     */
    public static $AFFILIATEWP_PAID = 'paid';

    /**
     * Status for an UNPAID order according to AffiliateWP plugin
     *
     * @since    1.4.0
     * @access   public
     * @var      string    $AFFILIATEWP_UNPAID    Status for an upaid order
     */
    public static $AFFILIATEWP_UNPAID = 'unpaid';

    /**
     * Status for a REJECTED order according to AffiliateWP plugin
     *
     * @since    1.4.0
     * @access   public
     * @var      string    $AFFILIATEWP_REJECTED    Status for a rejected order
     */
    public static $AFFILIATEWP_REJECTED = 'rejected';

    /**
     * Status for a PENDING order according to AffiliateWP plugin
     *
     * @since    1.4.0
     * @access   public
     * @var      string    $AFFILIATEWP_PENDING    Status for a pending order
     */
    public static $AFFILIATEWP_PENDING = 'pending';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->_plugin_name = $plugin_name;
        $this->_version = $version;
        $this->_tempdir = dirname(__FILE__) . '/tmp';
    }

    /**
     * Get temp directory for file exports
     * @return type
     */
    private function _get_tempdir() {
        return $this->_tempdir;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.4.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->_plugin_name, plugin_dir_url(__FILE__) . 'css/smartkwk-admin.css', array(), $this->_version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.4.0
     */
    public function enqueue_scripts() {

        wp_enqueue_script($this->_plugin_name, plugin_dir_url(__FILE__) . 'js/smartkwk-admin.js', array('jquery'), $this->_version, false);
        wp_localize_script($this->_plugin_name, 'SmartKwk', array('ajaxurl' => admin_url('admin-ajax.php'), 'img' => admin_url('images')));
    }

    /**
     * Sets plugin action links.
     *
     * @since    1.4.0
     * @param string $links Plugin links to be displayed
     * @param string $file Plugin file
     * @return array Links
     */
    public function plugin_action_links($links, $file) {
        if ($file != SMARTKWK_PLUGIN_BASENAME) {
            return $links;
        }

        $settings_link = '<a href="' . menu_page_url('smartkwk', false) . '">'
                . esc_html(__('Go to plugin', 'smart-kwk')) . '</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Create plugin menu page
     *
     * @since    1.4.0
     */
    public function admin_menu() {
        add_menu_page(__('Smart KwK', 'smart-kwk'), __('Smart KwK', 'smart-kwk'), 'manage_options', 'smartkwk', array($this, 'main_page'), 'dashicons-clipboard');
    }

    /**
     * Define tabs for plugin menu
     *
     * @since    1.4.0
     * @return array Tabs
     */
    public function get_tabs() {

        $tabs = array();
        $tabs['overview'] = __('Overview', 'smart-kwk');
        //$tabs['import'] = __('Import', 'smart-kwk');
        //$tabs['export'] = __('Export', 'smart-kwk');
        $tabs['backoffice'] = __('Backoffice', 'smart-kwk');
        $tabs['expercash'] = __('Expercash', 'smart-kwk');
        $tabs['voucher'] = __('Vouchers', 'smart-kwk');
        $tabs['emailtpl'] = __('Email Template', 'smart-kwk');
        return $tabs;
    }

    /**
     * Displays the main plugin page
     *
     * @since    1.4.0
     */
    public function main_page() {
        $active_tab = $this->get_active_tab();
        ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <?php
                foreach ($this->get_tabs() as $tab_id => $tab_name) {

                    $tab_url = add_query_arg(array(
                        'tab' => $tab_id
                    ));

                    $active = $active_tab == $tab_id ? ' nav-tab-active' : '';

                    echo '<a href="' . esc_url($tab_url) . '" title="' . esc_attr($tab_name) . '" class="nav-tab' . $active . '">';
                    echo esc_html($tab_name);
                    echo '</a>';
                }
                ?>
            </h2>
            <div id="tab_container">
                <?php do_action('display_tab', $active_tab); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get selected tab
     *
     * @since    1.4.0
     * @return string Active tab
     */
    public function get_active_tab() {
        $active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->get_tabs()) ? $_GET['tab'] : DEFAULT_TAB;

        return $active_tab;
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_getimport() {

        $this->verify_nonce('getimport');

        $expercash = new Smartkwk_Expercash();

        try {

            //generate the expercash file (ruby script)
            $expercash->generate_expercash_file($_POST['u'], $_POST['p']);

            //import file contents
            $updated = $expercash->import_generated_expercash_file();
        } catch (Exception $ex) {
            $error = $ex->getMessage();
        }


        if ($error) {
            $this->redirect('expercash', array('error' => 1));
        }

        $this->redirect('expercash', array('updated' => $updated));
    }

    /**
     * Custom redirect
     *
     * @since    1.4.0
     * @param string $tab Tab name
     * @param array $params Parameter
     */
    public function redirect($tab, $params = array()) {

        if (!is_array($params)) {
            $params = array();
        }

        if (!in_array($tab, array_keys($this->get_tabs()))) {
            $tab = DEFAULT_TAB;
        }

        $params['page'] = $this->_plugin_name;
        $params['tab'] = $tab;

        $redirect_to = add_query_arg($params, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     * @param int $rid Referral ID
     */
    public function api_request($rid = null) {

        if (!$rid && isset($_POST['rid'])) {
            $rid = trim($_POST['rid']);
        }

        $backoffice = new Smartkwk_Backoffice($rid);

        try {

            //init object with necessary data
            $backoffice->init_referral();

            //get data from API
            $backoffice->perform_api_request();

            //prepare data for validation
            $backoffice->parse_data();

            //validate data
            $backoffice->validate_data();

            //save gathered information
            $backoffice->save();
        } catch (Exception $ex) {
            $error = $ex->getMessage();
        }


        if ($error) {
            wp_send_json_error(array(
                'status' => 'error',
                'message' => $error));
        } else {
            wp_send_json_success(array(
                'status' => $backoffice->get_api_status(),
                'message' => $backoffice->get_api_response_description()));
        }
    }

    /**
     * Add a new voucher
     *
     * @since    1.4.0
     */
    public function admin_addvoucher() {

        $this->verify_nonce('addvoucher');

        //create voucher and save
        $voucher = new Smartkwk_Voucher($_POST['code']);
        $updated = $voucher->save();

        $this->redirect('voucher', array('updated' => $updated));
    }

    /**
     * Verify Wordpress nonce with option to die.
     * @param type $value
     * @param type $name
     * @param type $die
     * @return bool|void $verfified
     */
    public function verify_nonce($value, $name = '_wpnonce', $die = true) {

        $verified = wp_verify_nonce($_POST[$name], $value);

        if (!$verified && $die) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        return $verified;
    }

    /**
     * Upload and import Expercash file
     *
     * @since    1.4.0
     */
    public function admin_importexpercash() {

        $this->verify_nonce('importexpercash');

        $expercash = new Smartkwk_Expercash();
        try {
            $expercash->set_uploaded_filepath($_FILES['expercashfile']['tmp_name']);
            $updated = $expercash->import_uploaded_expercash_file();
        } catch (Exception $ex) {
            $errror = $ex->getMessage();
        }

        if ($errror) {
            $this->redirect('backoffice', array('error' => 1));
        }

        $this->redirect('backoffice', array('updated' => $updated));
    }

    /**
     * Import vouchers from file
     *
     * @since    1.4.0
     */
    public function admin_importvouchers() {

        $this->verify_nonce('importvouchers');

        $file = fopen($_FILES['voucherfile']['tmp_name'], "r");

        $inserted = 0;
        while (($row = fgetcsv($file, 0, ';'))) {

            //update by email (alternatively by referral id)
            $voucher = new Smartkwk_Voucher($row[0]);
            $inserted += $voucher->save();
        }

        fclose($file);
        @unlink($_FILES['voucherfile']['tmp_name']);

        $this->redirect('voucher', array('updated' => $inserted));
    }

    /**
     * Update order status of referrals
     *
     * @since    1.4.0
     */
    public function admin_importkwk() {

        $this->verify_nonce('importkwk');

        $file = fopen($_FILES['importkwkfile']['tmp_name'], "r");
        $allowedStatus = $this->get_allowed_status();

        global $wpdb;
        $updated = 0;
        while (($row = fgetcsv($file, 0, ';'))) {
            if (in_array(strtolower(trim($row[6])), $allowedStatus)) {
                //update by email (alternatively by referral id)
                $updated = $wpdb->update($wpdb->prefix . 'affiliate_wp_referrals', array('status' => $row[6]), array('description' => trim($row[3])
                        )
                );
            }
        }

        fclose($file);
        @unlink($_FILES['importkwkfile']['tmp_name']);

        $this->redirect('import', array('updated' => $updated));
    }

    /**
     * Get allowed status for AffiliateWP Plugin
     *
     * @since    1.4.0
     * @return array Allowed status
     */
    public function get_allowed_status() {
        $allowedStatus = array(
            static::$AFFILIATEWP_PAID,
            static::$AFFILIATEWP_UNPAID,
            static::$AFFILIATEWP_REJECTED,
            static::$AFFILIATEWP_PENDING
        );
        return $allowedStatus;
    }

    /**
     * Export the not exported affiliates and referrals
     *
     * @since    1.4.0
     */
    public function admin_export_overview() {

        $this->verify_nonce('export_overview');

        global $wpdb;

        //get not exported referrals
        $results = $this->get_not_exported_referrals();

        //create dir if neccessary
        mkdir($this->_get_tempdir());

        $file = $this->_get_tempdir() . '/Kwk_Overview_' . date('Y-m-d') . '.csv';

        $fh = fopen($file, 'w');

        $header = array(
            __('Affiliate'),
            __('Affiliate ID'),
            __('Order date'),
            __('Referral'),
            __('Status'),
            __('Voucher sent'));

        $delimiter = ';';

        //write column names into file
        fputcsv($fh, $header, $delimiter);

        if ($results) {
            foreach ($results as $r) {

                //data to write
                $data = array($r['user_email'], $r['affiliate_id'], $r['date'], $r['description'], utf8_decode($r['api_response']), $r['date_sent']);

                fputcsv($fh, $data, $delimiter);

                //flag as exported
                $wpdb->update($wpdb->prefix . 'smart_kwk', array('date_exported' => date('c')), array('id' => $r['id']));
            }
        }

        fclose($fh);

        $this->force_download($file);
    }

    /**
     * Get not exported referrals
     * @global type $wpdb
     * @return array | null
     */
    public function get_not_exported_referrals() {
        global $wpdb;

        $sql = "SELECT k.id,u.user_email,r.description,k.api_response,k.paid,a.affiliate_id, r.referral_id, r.date, v.date_sent "
                . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
                . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
                . " WHERE k.date_exported IS NULL ORDER BY k.order_date DESC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results;
    }

    /**
     * Get paid affiliates
     *
     * @since    1.4.0
     * @return array
     */
    public function get_paid_affiliates() {
        global $wpdb;

        $results = $wpdb->get_results("SELECT r.description, r.referral_id, r.affiliate_id, r.status,r.date,r.amount,a.user_id,a.user_id "
                . "FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . "LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
                . "WHERE r.amount>0", ARRAY_A);

        return $results;
    }

    /**
     * Export paid affiliates to a csv file
     *
     * @since    1.4.0
     */
    public function admin_exportkwk() {

        $this->verify_nonce('exportkwk');

        global $wpdb;

        $results = $this->get_paid_affiliates();

        //create dir if neccessary
        mkdir($this->_get_tempdir());

        $file = $this->_get_tempdir() . '/KwK_' . date('Y-m-d') . '.csv';

        $fh = fopen($file, 'w');

        //column names for export file
        $header = array(
            __('Order date'),
            __('Affiliate ID'),
            __('Affiliate'),
            __('Referral'),
            __('Identical'),
            __('Provision'),
            __('Status'),
            __('Backoffice Status'),
            __('BusinessCase'),
            __('Expercash Status'));

        $delimiter = ';';

        //write header columns
        fputcsv($fh, $header, $delimiter);

        if ($results) {
            foreach ($results as $r) {

                $email = $this->get_user_email($r['user_id']);

                $paid = '';

                $response = $this->get_referral_order_by_date($r['referral_id'], $r['date']);

                if ($response) {
                    $paid = $response['paid'] == '1' ? __('Paid') : '';
                }

                //check if email is the same
                $same = $email == trim($r['description']) ? 'Y' : 'N';

                $data = array(date('d.m.Y', strtotime($r['date'])), $r['affiliate_id'], $email, $r['description'], $same, $r['amount'], $r['status'], utf8_decode($response['api_response']), $response['business_case'], $paid);

                fputcsv($fh, $data, $delimiter);
            }
        }

        fclose($fh);

        //send file to browser
        $this->force_download($file);
    }

    /**
     * Get referral order
     * @global type $wpdb
     * @param int $ref_id Referral id
     * @param string $date Order date
     * @return array|null
     */
    public function get_referral_order_by_date($ref_id, $date) {
        global $wpdb;
        $d = date('Y-m-d', strtotime($date));
        $response = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}smart_kwk WHERE referral_id=%d AND order_date LIKE '%s%'", $ref_id, $d), ARRAY_A);
        return $response;
    }

    /**
     * Get the email of a wordpress user by Id
     * @global type $wpdb
     * @param int $user_id
     * @return string Email
     */
    public function get_user_email($user_id) {
        global $wpdb;

        $email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM {$wpdb->users} WHERE id=%d", $user_id));

        return $email;
    }

    /**
     * Forces a file download in the browser
     *
     * @since    1.4.0
     * @param string $filepath File to download
     * @param bool $delete Description
     */
    private function force_download($filepath, $delete = true) {

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header('Content-Length: ' . filesize($filepath));
        header("Content-disposition: attachment; filename=\"" . basename($filepath) . "\"");
        readfile($filepath);

        ignore_user_abort(true);
        if ($delete && connection_aborted()) {
            unlink($filepath);
        }
        exit;
    }

    /**
     * Save email template
     *
     * @since    1.4.0
     */
    public function save_emailtemplate() {

        $this->verify_nonce('save_emailtemplate');

        $mailer = new SmartKwk_Mailer();
        $ok = $mailer->save_email_template_content($_POST['content']);

        $this->redirect('emailtpl', array('updated' => $ok));
    }

    /**
     * Get a single voucher by id
     *
     * @since    1.4.0
     * @param string $vid Voucher Id
     * @return object $voucher
     */
    public function get_voucher_by_id($vid) {
        global $wpdb;

        $voucher = new Smartkwk_Voucher();

        $v = $wpdb->get_results($wpdb->prepare("SELECT voucher_code FROM {$wpdb->prefix}smart_kwk_vouchers WHERE id = %s", $vid), ARRAY_A);

        if ($v) {

            $voucher->set_voucher_id($v['id']);
            $voucher->set_voucher_code($v['voucher_code']);
            $voucher->set_referral_id((int) $v['referral_id']);
            $voucher->set_date_sent($v['date_sent']);
            $voucher->set_date_inserted($v['date_inserted']);
            $voucher->set_affiliate_id((int) $v['affiliate_id']);
        }

        return $voucher;
    }

    /**
     * Get all vouchers from database
     *
     * @since    1.4.0
     * @return array
     */
    public function get_all_vouchers() {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}smart_kwk_vouchers WHERE 1 ORDER BY date_sent DESC, date_inserted DESC";

        $vouchers = $wpdb->get_results($sql, ARRAY_A);
        $voucherCollection = array();

        if ($vouchers) {
            foreach ($vouchers as $v) {
                $voucher = new Smartkwk_Voucher();
                $voucher->set_voucher_code($v['voucher_code']);
                $voucher->set_voucher_id($v['id']);
                $voucher->set_affiliate_id((int) $v['affiliate_id']);
                $voucher->set_date_inserted($v['date_inserted']);
                $voucher->set_date_sent($v['date_sent']);
                $voucher->set_referral_id((int) $v['referral_id']);
                array_push($voucherCollection, $voucher);
            }
        }

        return $voucherCollection;
    }

    /**
     * Save edited voucher
     *
     * @since    1.4.0
     * @param int $vid Referral ID
     * @param string $_voucher_code Voucher
     * @return json response
     */
    public function save_voucher($vid = '', $_voucher_code = '') {

        if (!$vid && isset($_POST['vid'])) {
            $vid = trim($_POST['vid']);
        }

        if (!$_voucher_code && isset($_POST['code'])) {
            $_voucher_code = trim($_POST['code']);
        }

        $voucher = new Smartkwk_Voucher($_voucher_code);
        $voucher->set_voucher_id($vid);

        //set default return message
        $return = array('status' => 'error', 'message' => __('Voucher could not be saved!', 'smart-kwk'));


        //empty voucher code means we delete the voucher by voucher id
        if ($voucher->get_voucher_code() != '' && $voucher->get_voucher_id() != '') {
            $ok = $voucher->save();
        } elseif ($voucher->get_voucher_id() != '' && strlen($voucher->get_voucher_id()) === 32) {
            $ok = $voucher->delete();
        }

        if ($ok) {
            $return = array('status' => 'success', 'message' => __('Voucher saved!', 'smart-kwk'));
        } else {
            //if not saved, track the voucher code for notice
            $notice_voucher = new Smartkwk_Voucher();
            $notice_voucher->set_voucher_id($vid);
            $notice_voucher->init_voucher();

            $_voucher_code = $this->get_voucher_by_id($vid)->get_voucher_code();
        }

        $return['code'] = $_voucher_code ? $_voucher_code : '';

        if ($return['status'] == 'error') {
            wp_send_json_error($return);
        } else {
            wp_send_json_success($return);
        }
    }

    /**
     * Send voucher to affiliate partner
     *
     * @since    1.4.0
     * @param int $ref_id Referral ID
     * @return json response
     */
    public function send_voucher($ref_id) {

        if (!$ref_id && isset($_POST['ref'])) {
            $ref_id = $_POST['ref']; //coming from ajax
        }

        try {

            //get approved referral
            $row = $this->get_approved_referral($ref_id);

            //get affiliate data
            $affiliate = $this->get_affiliate($row['affiliate_id']);

            //grab a new unused voucher
            $voucher = $this->get_unused_voucher();

            //send email
            $mailer = new SmartKwk_Mailer();
            $mailer->init_template();
            $mailer->set_email_subject(__('Your Amazon Voucher', 'smart-kwk'));
            $mailer->AddAddress($affiliate['user_email']);
            $mailer->set_voucher($voucher);
            $sent = $mailer->send_email();
        } catch (Exception $ex) {
            wp_send_json_error(array('status' => 'error', 'message' => $ex->getMessage()));
        }

        if ($sent) {

            //mark voucher as "used"
            $voucher->set_date_sent(date('c'));
            $voucher->set_referral_id((int) $ref_id);
            $voucher->set_affiliate_id((int) $row['affiliate_id']);

            $ok = $voucher->save();

            //flag referral as paid (affiliate stats are updated also)
            if (function_exists('affwp_set_referral_status')) {
                affwp_set_referral_status($ref_id, 'paid');
            }

            if (!$ok) {
                wp_send_json_success(array('status' => 'success', 'message' => __('Voucher sent but not devaluated!', 'smart-kwk')));
            } else {
                wp_send_json_success(array('status' => 'success', 'message' => __('Voucher successfully sent!', 'smart-kwk')));
            }
        } else {
            wp_send_json_error(array('status' => 'error', 'message' => __('Voucher could not be sent!', 'smart-kwk') . ' Error: ' . $sent['error']));
        }
    }

    /**
     * Get unused voucher
     *
     * @since    1.4.0
     * @return object
     */
    public function get_unused_voucher() {
        global $wpdb;

        $v = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}smart_kwk_vouchers WHERE date_sent IS NULL ORDER BY date_inserted ASC", ARRAY_A);

        $voucher = new Smartkwk_Voucher();

        if ($v) {
            $voucher->set_voucher_id($v['id']);
            $voucher->set_voucher_code($v['voucher_code']);
            $voucher->set_referral_id((int) $v['referral_id']);
            $voucher->set_date_sent($v['date_sent']);
            $voucher->set_date_inserted($v['date_inserted']);
            $voucher->set_affiliate_id($v['affiliate_id']);
        } else {
            throw new Exception(__('No unused vouchers available', 'smart-kwk'));
        }

        return $voucher;
    }

    /**
     * Get approved referral id
     *
     * @since    1.4.0
     * @param int $referral_id referral ID
     * @throws Exception
     * @return array
     */
    public function get_approved_referral($referral_id) {

        if (!$referral_id) {
            throw new Exception(__('Faild to send voucher because referral id not provided.', 'smart-kwk'));
        }

        global $wpdb;

        $sql = "SELECT r.description,r.amount,r.affiliate_id,r.referral_id as ref, v.date_sent"
                . " FROM {$wpdb->prefix}affiliate_wp_referrals r"
                . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id"
                . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON r.referral_id=v.referral_id"
                . " WHERE k.paid=1 "
                . " AND r.referral_id=%d";

        $data = $wpdb->get_results($wpdb->prepare($sql, $referral_id), ARRAY_A);

        if (!$data) {
            throw new Exception(__('Referral not found!', 'smart-kwk'));
        } elseif (count($data) > 1) {
            throw new Exception(__('More than 1 referral found!', 'smart-kwk'));
        } elseif ($data[0]['date_sent']) {
            throw new Exception(__('Voucher already sent', 'smart-kwk'));
        }

        return $data[0];
    }

    /**
     * Get affiliate by id
     *
     * @since    1.4.0
     * @param int $affiliate_id Affiliate ID
     * @return array
     */
    public function get_affiliate($affiliate_id) {

        if (!$affiliate_id) {
            throw new Exception(__('Affiliate not found!', 'smart-kwk'));
        }

        global $wpdb;
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT u.user_email,a.* FROM {$wpdb->prefix}affiliate_wp_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID WHERE a.affiliate_id = %d", $affiliate_id), ARRAY_A);
        return $affiliate;
    }

    /**
     * Approve or deny the referral commission
     *
     * @since    1.4.0
     * @param int $ref_id Referral ID
     * @param string $new_status New status
     * @return json response
     */
    function change_status($ref_id, $new_status = '') {
        if (!$ref_id && isset($_POST['ref'])) {
            $ref_id = $_POST['ref']; //coming from ajax
        }
        if (!$new_status && isset($_POST['newstatus'])) {
            $new_status = $_POST['newstatus']; //coming from ajax
        }

        $backoffice = new Smartkwk_Backoffice($ref_id);

        if (!(is_numeric($ref_id) && in_array($new_status, array($backoffice::$API_STATUS_DENIED, $backoffice::$API_STATUS_ACCEPTED)))) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Parameters invalid', 'smart-kwk')));
        }

        try {
            $backoffice->init();

            if (!$backoffice->referral_exists()) {
                //referral not found
                wp_send_json_error(array('status' => 'error', 'message' => __('Referral not found', 'smart-kwk')));
            }

            //change status
            $backoffice->set_api_status($new_status);

            if ($backoffice->get_api_status() == $backoffice::$API_STATUS_ACCEPTED) {
                $backoffice->set_api_response_description(__('Manually approved', 'smart-kwk'));
                $backoffice->set_is_paid(true);
            } else {
                $backoffice->set_api_response_description(__('Manually denied', 'smart-kwk'));
                $backoffice->set_is_paid(false);
            }

            $backoffice->set_date_exported(null);
            $ok = $backoffice->save();
        } catch (Exception $ex) {
            wp_send_json_error(array('status' => 'error', 'message' => $ex->getMessage()));
        }

        if (!$ok) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Error while saving', 'smart-kwk')));
        } else {
            wp_send_json_success(array('status' => 'success', 'message' => __('Status updated', 'smart-kwk'), 'newstatus' => $backoffice->get_api_status(), 'newstatustext' => $backoffice->get_api_response_description()));
        }
    }

    /**
     * Displays the tab
     * @param string $tab Tab name to display
     * @since    1.4.0
     */
    public function display_tab($tab) {

        $allowed_tabs = array_keys($this->get_tabs());

        if (!in_array($tab, $allowed_tabs)) {
            $tab = DEFAULT_TAB;
        }

        include 'partials/tab_' . $tab . '.php';
    }

    /**
     * Get unpaid referrals
     * @since    1.4.0
     * @return mixed Array of referrals
     */
    private function get_unpaid_referrals() {
        global $wpdb;

        $sql = "SELECT r.*, k.api_response, k.paid FROM {$wpdb->prefix}affiliate_wp_referrals r LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id WHERE r.status='unpaid'";

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get the number of referrals in the database
     * @since    1.4.0
     * @return int
     */
    public function get_referrals_count() {
        global $wpdb;

        $countSql = "SELECT count(r.description) "
                . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
                . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
                . " WHERE 1 ORDER BY k.date_inserted DESC";

        return (int) $wpdb->get_var($countSql);
    }

    /**
     * Get referral information for the overview
     * @since    1.4.0
     * @param int $pagenum Page number for pagination
     * @return array
     */
    public function get_referrals($pagenum = 1) {
        global $wpdb;

        $limit = MAX_OVERVIEW_ROWS;
        $offset = ( $pagenum - 1 ) * $limit;

        $sql = "SELECT u.user_email,r.description,k.order_date,k.api_status,k.api_response,k.paid,a.affiliate_id, r.referral_id, v.date_sent "
                . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
                . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
                . " WHERE 1 ORDER BY k.date_inserted DESC LIMIT %d, %d";

        return $wpdb->get_results($wpdb->prepare($sql, $offset, $limit), ARRAY_A);
    }

}
