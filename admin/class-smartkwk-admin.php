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
class Smartkwk_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smartkwk-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.4.0
     */
    public function enqueue_scripts() {

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smartkwk-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'SmartKwk', array('ajaxurl' => admin_url('admin-ajax.php'), 'img' => admin_url('images')));
    }

    /**
     * Sets plugin action links.
     *
     * @since    1.4.0
     * @param string $links Plugin links to be displayed
     * @param string $file Plugin file
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
        $active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->get_tabs()) ? $_GET['tab'] : DEFAULT_TAB;
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
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_getimport() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'getimport')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        $username = trim($_POST['u']);
        $password = trim($_POST['p']);
        if (!($username && $password)) {

            $query = array(
                'page' => $this->plugin_name,
                'tab' => 'expercash',
                'formerror' => 1,
            );

            $redirect_to = add_query_arg($query, admin_url('admin.php'));
            wp_safe_redirect($redirect_to);
            exit();
        }

        //file where the data will be written into
        $targetFile = SMARTKWK_PLUGIN_DIR . 'admin/external/valid_transactions.csv';

        @unlink($targetFile); //delete previous file

        $dateRange = date('d m Y', strtotime("-30 days")) . ' ' . date('d m Y');

// Change directory
        chdir(SMARTKWK_PLUGIN_DIR . '/admin/external');

        $cmd = "ruby expercash.rb $username $password $dateRange";

        //execute command
        $result = system($cmd);

        //verify that file has been written
        if (!file_exists($targetFile)) {

            $query = array(
                'page' => $this->plugin_name,
                'tab' => 'expercash',
                'rubyerror' => 1,
            );

            $redirect_to = add_query_arg($query, admin_url('admin.php'));
            wp_safe_redirect($redirect_to);
            exit();
        }

        //import targetfile
        $file = fopen($targetFile, "r");

        global $wpdb;
        $updated = 0;
        while (($row = fgetcsv($file, 0, ';'))) {

            $businessCaseNumber = $row[1];

            //update by business_case
            $ok = $wpdb->update($wpdb->prefix . 'smart_kwk', array('paid' => 1), array('business_case' => $businessCaseNumber));
            if ($ok) {
                $updated++;
            }
        }

        fclose($file);

        $query = array(
            'page' => $this->plugin_name,
            'tab' => 'expercash',
            'updated' => $updated,
        );

        $redirect_to = add_query_arg($query, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     * @param int $rid Referal ID
     */
    public function api_request($rid = null) {

        if (!$rid && isset($_POST['rid'])) {
            $rid = trim($_POST['rid']);
        }

        if (!($rid && is_numeric($rid))) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Parameter referral_id missing or invalid', 'smart-kwk')));
        }

        global $wpdb;

        $ref = $wpdb->get_row($wpdb->prepare("SELECT r.*,k.date_inserted FROM {$wpdb->prefix}affiliate_wp_referrals r LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id WHERE r.referral_id='%d'", $rid), ARRAY_A);

        if (!($ref && $ref['description'] && $ref['date'])) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Referal not found or Email/order date not specified', 'smart-kwk')));
        }

        if (!defined('BACKOFFICE_REQUEST_URL')) {
            wp_send_json_error(array('status' => 'error', 'message' => __('BACKOFFICE_REQUEST_URL in config.php not defined', 'smart-kwk')));
        }

        $timestamp = strtotime($ref['date']);
        $date = date('Y-m-d', $timestamp);

        $email = $ref['description'];

        $serviceURL = esc_url(BACKOFFICE_REQUEST_URL . "&email=" . $ref['description']);

        $args = array(
            'headers' => array(
                'Accept' => 'application/json',),
            'timeout' => BACKOFFICE_REQUEST_TIMEOUT,
        );

        $response = wp_remote_get($serviceURL, $args);

        if (is_wp_error($response)) {
            $return = array('status' => 'error', 'message' => $response->get_error_message());
        } else {
            //kunde nicht vorhanden
            $return = array('status' => 'denied', 'message' => __('Denied - Not a customer', 'smart-kwk'));

            //parsing response
            $backoffice = array();

            if (isset($response['body'])) {
                try {
                    $result['data'] = json_decode($response['body'], true);
                } catch (Exception $e) {
                    $return = array('status' => 'error', 'message' => $e->getMessage());
                }
            }

            if ($result) {
                foreach ($result as $r) {
                    $backoffice[$email][$r->date]['ordernumber'] = $r->ordernumber;
                    $backoffice[$email][$r->date]['invoicenumber'] = $r->invoicenumber;
                    $backoffice[$email][$r->date]['timestamp'] = $r->timestamp / 1000;

                    //prüfe, ob backofficedatum vom wp affiliate datum abweicht, berücksichtige puffer
                    if (ALLOWED_ORDERTIME_OFFSET > 0 && $date != $r->date) {

                        $datediff = $backoffice[$email][$r->date]['timestamp'] - strtotime("$date");

                        $diff = abs(floor($datediff / (60 * 60 * 24))); //zeitunterschied in tagen

                        if ($diff <= ALLOWED_ORDERTIME_OFFSET) {
                            $date = $r->date; //setze wp affiliate datum = backoffice datum, um bestätigung zu ermöglichen
                        }
                    }
                }
            }

            //kunde vorhanden?
            if ($backoffice[$email]) {

                //bestellung vorhanden?
                if ($backoffice[$email][$date]['ordernumber']) {

                    //mehrere bestellungen?
                    if (count($backoffice[$email]) > 1) {
                        $return = array('status' => 'denied', 'message' => __('Denied - customer already exists', 'smart-kwk'));
                    } elseif (!$backoffice[$email][$date]['invoicenumber']) {
                        $return = array('status' => 'error', 'message' => __('Error: customer invoicenumber missing', 'smart-kwk'));
                    } elseif (!$backoffice[$email][$date]['timestamp']) {
                        $return = array('status' => 'error', 'message' => __('Error: timestamp missing', 'smart-kwk'));
                    } else {
                        //nur eine bestellung zum datum vorhanden = OK
                        //K Nummer vor bestelldatum angelegt?
                        //ablehnen, wenn bestellzeitpunkt der backofficeerfassung VOR dem bestellzeitpunkt im plugin
                        if ($backoffice[$email][$date]['timestamp'] < $timestamp) {
                            $return = array('status' => 'denied', 'message' => __('Denied - customer already exists', 'smart-kwk'));
                        } else {
                            //bestätigt nur, wenn backofficeerfassung NACH bestellzeitpunkt im plugin
                            $return = array('status' => 'accepted', 'message' => __('Approved: ', 'smart-kwk') . $backoffice[$email][$date]['ordernumber'], 'businesscase' => $backoffice[$email][$date]['ordernumber']);
                        }
                    }
                } else {
                    //bestellung zum datum nicht vorhanden
                    $return = array('status' => 'denied', 'message' => __('Denied - Wrong date', 'smart-kwk'));
                }
            }

            //save data in db
            $save = array();
            $save['referral_id'] = $ref['referral_id'];
            $save['order_date'] = date('Y-m-d H:i:s', $timestamp); //Y-m-d
            $save['business_case'] = $return['businesscase'] ? $return['businesscase'] : NULL;
            $save['api_status'] = $return['status'];
            $save['api_response'] = $return['message'];

            //save if no error occurred
            if ($return['status'] != 'error') {

                //update if referral exists in smart_kwk
                if (isset($ref['date_inserted'])) {
                    $ok = $wpdb->update("{$wpdb->prefix}smart_kwk", $save, array('referral_id' => $ref['referral_id']));
                } else {
                    $ok = $wpdb->insert("{$wpdb->prefix}smart_kwk", $save);
                }


                if (!$ok) {
                    $return = array('status' => 'error', 'message' => $wpdb->last_error);
                }
            }
        }


        if ($return['status'] == 'error') {
            wp_send_json_error($return);
        } else {
            wp_send_json_success($return);
        }
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_addvoucher() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'addvoucher')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        $voucher = trim($_POST['code']);

        $updated = 0;

        if ($voucher) {
            global $wpdb;
            $save = array();
            $save['id'] = md5($voucher);
            $save['voucher_code'] = $voucher;
            $updated = (int) $wpdb->insert($wpdb->prefix . 'smart_kwk_vouchers', $save);
        }

        $query = array(
            'page' => $this->plugin_name,
            'tab' => 'voucher',
            'updated' => $updated,
        );

        $redirect_to = add_query_arg($query, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_importexpercash() {


        if (!wp_verify_nonce($_POST['_wpnonce'], 'importexpercash')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        $file = fopen($_FILES['expercashfile']['tmp_name'], "r");

        global $wpdb;
        $updated = 0;
        while (($row = fgetcsv($file, 0, ';'))) {

            if (stripos(trim($row[5]), 'Referenz') !== false) {
                continue; //skip header row
            }

            $businessCaseNumber = trim($row[5]);

            //update by business_case (alternatively by referal id)
            $ok = $wpdb->update($wpdb->prefix . 'smart_kwk', array('paid' => 1), array('business_case' => $businessCaseNumber));
            if ($ok) {
                $updated++;
            }
        }

        fclose($file);
        @unlink($_FILES['expercashfile']['tmp_name']);

        $query = array(
            'page' => $this->plugin_name,
            'tab' => 'backoffice',
            'updated' => $updated,
        );

        $redirect_to = add_query_arg($query, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_importvouchers() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'importvouchers')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        $file = fopen($_FILES['voucherfile']['tmp_name'], "r");

        global $wpdb;
        $inserted = 0;
        while (($row = fgetcsv($file, 0, ';'))) {

            //update by email (alternatively by referal id)
            $save = array();
            $save['id'] = md5($row[0]);
            $save['voucher_code'] = $row[0];
            $inserted += (int) $wpdb->insert($wpdb->prefix . 'smart_kwk_vouchers', $save);
        }

        fclose($file);
        @unlink($_FILES['voucherfile']['tmp_name']);

        $query = array(
            'page' => $this->plugin_name,
            'tab' => 'voucher',
            'updated' => $inserted,
        );

        $redirect_to = add_query_arg($query, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_importkwk() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'importkwk')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        $file = fopen($_FILES['importkwkfile']['tmp_name'], "r");
        $allowedStatus = array('paid', 'unpaid', 'rejected', 'pending');

        global $wpdb;
        $updated = 0;
        while (($row = fgetcsv($file, 0, ';'))) {
            if (in_array(strtolower(trim($row[6])), $allowedStatus)) {
                //update by email (alternatively by referal id)
                $updated = $wpdb->update($wpdb->prefix . 'affiliate_wp_referrals', array('status' => $row[6]), array('description' => trim($row[3])));
            }
        }

        fclose($file);
        @unlink($_FILES['importkwkfile']['tmp_name']);

        $query = array(
            'page' => $this->plugin_name,
            'tab' => 'import',
            'updated' => $updated,
        );

        $redirect_to = add_query_arg($query, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_export_overview() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'export_overview')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        global $wpdb;

        $sql = "SELECT k.id,u.user_email,r.description,k.api_response,k.paid,a.affiliate_id, r.referral_id, r.date, v.date_sent "
                . " FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
                . " LEFT JOIN {$wpdb->prefix}users u ON u.ID=a.user_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON k.referral_id=r.referral_id "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON v.referral_id=r.referral_id "
                . " WHERE k.date_exported IS NULL ORDER BY k.order_date DESC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $filename = 'Kwk_Overview_' . date('Y-m-d') . '.csv';

        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}");
        header("Expires: 0");
        header("Pragma: public");

        $fh = @fopen('php://output', 'w');

        $headerDisplayed = false;

        $header = array(
            __('Affiliate'),
            __('Affiliate ID'),
            __('Order date'),
            __('Referal'),
            __('Status'),
            __('Voucher sent'));

        $delimiter = ';';

        if ($results) {
            foreach ($results as $r) {

                if (!$headerDisplayed) {
                    fputcsv($fh, $header, $delimiter);
                    $headerDisplayed = true;
                }

                $data = array($r['user_email'], $r['affiliate_id'], $r['date'], $r['description'], utf8_decode($r['api_response']), $r['date_sent']);

                fputcsv($fh, $data, $delimiter);

                //flag as exported
                $wpdb->update($wpdb->prefix . 'smart_kwk', array('date_exported' => date('c')), array('id' => $r['id']));
            }
        }

        fclose($fh);
        exit;
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function admin_exportkwk() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'exportkwk')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        global $wpdb;

        $period = 14; //revocable period

        $results = $wpdb->get_results("SELECT r.description, r.referral_id, r.affiliate_id, r.status,r.date,r.amount,a.user_id,a.user_id "
                . "FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . "LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates a ON r.affiliate_id=a.affiliate_id "
                . "WHERE r.amount>0", ARRAY_A);

        $filename = 'KwK_' . date('Y-m-d') . '.csv';

        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}");
        header("Expires: 0");
        header("Pragma: public");

        $fh = @fopen('php://output', 'w');

        $headerDisplayed = false;

        $header = array(
            __('Order date'),
            __('Affiliate ID'),
            __('Affiliate'),
            __('Referal'),
            __('Identical'),
            __('Provision'),
            __('Status'),
            __('Backoffice Status'),
            __('BusinessCase'),
            __('Expercash Status'));

        $delimiter = ';';

        if ($results) {
            foreach ($results as $r) {

                if (!$headerDisplayed) {
                    fputcsv($fh, $header, $delimiter);
                    $headerDisplayed = true;
                }

                $email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM {$wpdb->users} WHERE id=%d", $r['user_id']));

                $paid = '';

                if ($r['referral_id'] && $r['date']) {

                    $d = date('Y-m-d', strtotime($r['date'])); //dont consider time of day

                    $response = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}smart_kwk WHERE referral_id=%d AND order_date LIKE '%s%'", $r['referral_id'], $d), ARRAY_A)[0];
                    if ($response) {
                        $paid = $response['paid'] == '1' ? __('Paid') : '';
                    }
                }

                $same = trim($email) == trim($r['description']) ? 'Y' : 'N';

                $data = array(date('d.m.Y', strtotime($r['date'])), $r['affiliate_id'], $email, $r['description'], $same, $r['amount'], $r['status'], utf8_decode($response['api_response']), $response['business_case'], $paid);

                fputcsv($fh, $data, $delimiter);
            }
        }

        fclose($fh);
        exit;
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function save_emailtemplate() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_emailtemplate')) {
            wp_die(esc_html__('Access token has expired, please reload the page.', 'smart-kwk'));
        }

        $content = stripslashes(trim($_POST['content']));

        $targetfile = $this->get_mail_template();

        if (file_put_contents($targetfile, $content)) {
            $ok = 1;
        } else {
            $ok = 0;
        }

        $query = array(
            'page' => $this->plugin_name,
            'tab' => 'emailtpl',
            'updated' => $ok,
        );

        $redirect_to = add_query_arg($query, admin_url('admin.php'));
        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    private function get_mail_template() {
        return SMARTKWK_PLUGIN_DIR . '/admin/templates/sendVoucher.html';
    }

    /**
     * TODO
     *
     * @since    1.4.0
     * @param int $rid Referal ID
     * @param string $voucher Voucher
     */
    public function save_voucher($vid = false, $voucher = false) {

        if (!$vid && isset($_POST['vid'])) {
            $vid = trim($_POST['vid']);
        }

        if (!$voucher && isset($_POST['code'])) {
            $voucher = trim($_POST['code']);
        }

        $return = array('status' => 'error', 'message' => __('Voucher could not be saved!', 'smart-kwk'));

        global $wpdb;

        if ($voucher && $vid) {
            $ok = $wpdb->update($wpdb->prefix . 'smart_kwk_vouchers', array('voucher_code' => $voucher), array('id' => $vid));
        } elseif ($vid) {
            $ok = $wpdb->delete($wpdb->prefix . 'smart_kwk_vouchers', array('id' => $vid), array('%s'));
        }

        if ($ok) {
            $return = array('status' => 'success', 'message' => __('Voucher saved!', 'smart-kwk'));
        } elseif (strlen($vid) === 32) {
            $voucher = $wpdb->get_var($wpdb->prepare("SELECT voucher_code FROM {$wpdb->prefix}smart_kwk_vouchers WHERE id = %s", $vid));
        }

        $return['code'] = $voucher ? $voucher : '';

        if ($return['status'] == 'error') {
            wp_send_json_error($return);
        } else {
            wp_send_json_success($return);
        }
    }

    /**
     * TODO
     *
     * @since    1.4.0
     * @param int $ref_id Referal ID
     */
    public function send_voucher($ref_id) {

        if (!$ref_id && isset($_POST['ref'])) {
            $ref_id = $_POST['ref']; //coming from ajax
        }

        global $wpdb;

        //check if this referral entitled for voucher
        $sql = "SELECT r.description,r.amount,r.affiliate_id,r.referral_id as ref, v.date_sent FROM {$wpdb->prefix}affiliate_wp_referrals r "
                . " LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id"
                . " LEFT JOIN {$wpdb->prefix}smart_kwk_vouchers v ON r.referral_id=v.referral_id"
                . " WHERE k.paid=1 "
                . " AND r.referral_id=%d";

        $data = $wpdb->get_results($wpdb->prepare($sql, $ref_id), ARRAY_A);

        if (!$data) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Referal not found!', 'smart-kwk')));
        } elseif (count($data) > 1) {
            wp_send_json_error(array('status' => 'error', 'message' => __('More than 1 referal found!', 'smart-kwk')));
        }

        $row = $data[0];

        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT u.user_email,a.* FROM {$wpdb->prefix}affiliate_wp_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID WHERE a.affiliate_id = %d", $row['affiliate_id']), ARRAY_A);

        if (!$affiliate) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Affiliate not found!', 'smart-kwk')));
        }

        $affiliateEmail = $affiliate['user_email'];

        //check if already sent
        if ($row['date_sent']) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Voucher already sent', 'smart-kwk')));
        }

        //grab a new unused voucher
        $voucher = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}smart_kwk_vouchers WHERE date_sent IS NULL ORDER BY date_inserted ASC", ARRAY_A);

        if (!$voucher) {
            wp_send_json_error(array('status' => 'error', 'message' => __('No unused vouchers available', 'smart-kwk')));
        }

        $subject = __('Your Amazon Voucher', 'smart-kwk');

        if (file_exists($this->get_mail_template())) {
            $content = file_get_contents($this->get_mail_template());
        } else {
            $content = '';
        }

        //check if placeholder for voucher is set
        if (stripos($content, PLACEHOLDER_VOUCHER) === false) {
            wp_send_json_error(array('status' => 'error', 'message' => __('Placeholder for voucher not found!', 'smart-kwk')));
        }

        //replace placeholder with actual voucher
        $content = str_replace(PLACEHOLDER_VOUCHER, $voucher['voucher_code'], $content);

        $addError = '';
        $sent = false;

        //sending using smtp
        if (USE_SMTP) {

            require_once ABSPATH . WPINC . '/class-phpmailer.php';
            require_once ABSPATH . WPINC . '/class-smtp.php';
            $phpmailer = new PHPMailer(true);

            $phpmailer->IsSMTP();
            $phpmailer->Host = SMTP_HOST;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = SMTP_USERNAME;
            $phpmailer->Password = SMTP_PASSWORD;
            $phpmailer->Port = SMTP_PORT;
            $phpmailer->SMTPSecure = SMTP_ENCRYPTION;
            $phpmailer->IsHTML();
            $phpmailer->From = SMTP_FROM_EMAIL;
            $phpmailer->FromName = SMTP_FROM_NAME;
            $phpmailer->AddAddress($affiliateEmail);
            $phpmailer->addBCC(BCC_EMAIL);
            $phpmailer->Subject = utf8_decode($subject);
            $phpmailer->Body = $content;

            try {
                $sent = $phpmailer->Send();

                if ($phpmailer->ErrorInfo) {
                    $addError = $phpmailer->ErrorInfo;
                }
            } catch (Exception $ex) {
                $addError = $ex->getMessage();
            }
        }

        if (!$sent) {

            $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
            $headers .= "BCC: " . BCC_EMAIL . "\r\n";
            $headers .= "Content-Type: text/html";

            try {
                $sent = wp_mail($affiliateEmail, utf8_decode($subject), $content, $headers);
            } catch (Exception $ex) {
                $addError = $ex->getMessage();
            }
        }

        if ($sent) {
            //mark voucher as "used"
            $ok = $wpdb->update($wpdb->prefix . 'smart_kwk_vouchers', array('date_sent' => date('c'), 'date_inserted' => $voucher['date_inserted'], 'referral_id' => $row['ref'], 'affiliate_id' => $row['affiliate_id']), array('id' => $voucher['id']));

            //mark referral as paid (affiliate stats are updated also)
            if (function_exists('affwp_set_referral_status')) {
                affwp_set_referral_status($ref_id, 'paid');
            }

            if (!$ok) {
                wp_send_json_success(array('status' => 'success', 'message' => __('Voucher sent but not devaluated!', 'smart-kwk')));
            } else {
                wp_send_json_success(array('status' => 'success', 'message' => __('Voucher successfully sent!', 'smart-kwk')));
            }
        } else {
            wp_send_json_error(array('status' => 'error', 'message' => __('Voucher could not be sent!', 'smart-kwk') . ' Error: ' . $addError));
        }
    }

    /**
     * TODO
     *
     * @since    1.4.0
     * @param int $ref_id Referal ID
     * @param string $new_status New status
     */
    function change_status($ref_id, $new_status = '') {
        if (!$ref_id && isset($_POST['ref'])) {
            $ref_id = $_POST['ref']; //coming from ajax
        }
        if (!$new_status && isset($_POST['newstatus'])) {
            $new_status = $_POST['newstatus']; //coming from ajax
        }

        if (!(is_numeric($ref_id) && in_array($new_status, array('denied', 'accepted')))) {
            $return = array('status' => 'error', 'message' => __('Parameters invalid', 'smart-kwk'));
        } else {
            global $wpdb;

            $sql = "SELECT id FROM {$wpdb->prefix}smart_kwk k WHERE k.referral_id=%d";
            $id = $wpdb->get_var($wpdb->prepare($sql, $ref_id));

            if (!$id) {
                //referral not found
                $return = array('status' => 'error', 'message' => __('Referal not found', 'smart-kwk'));
            } else {
                //change status
                $save = array();
                $save['api_status'] = $new_status;
                $save['api_response'] = $save['api_status'] == 'denied' ? __('Manually denied', 'smart-kwk') : __('Manually approved', 'smart-kwk');
                $save['date_exported'] = null;
                if ($save['api_status'] == 'accepted') {
                    $save['paid'] = 1;
                } else {
                    $save['paid'] = 0;
                }

                $ok = $wpdb->update($wpdb->prefix . 'smart_kwk', $save, array('id' => $id));

                if (!$ok) {
                    $return = array('status' => 'error', 'message' => __('Error while saving', 'smart-kwk'));
                } else {
                    $return = array('status' => 'success', 'message' => __('Status updated', 'smart-kwk'), 'newstatus' => $save['api_status'], 'newstatustext' => $save['api_response']);
                }
            }
        }

        if ($return['status'] == 'error') {
            wp_send_json_error($return);
        } else {
            wp_send_json_success($return);
        }
    }

    /**
     * TODO
     *
     * @since    1.4.0
     */
    public function display_tab($tab) {
        include_once 'partials/tab_' . $tab . '.php';
    }

}
