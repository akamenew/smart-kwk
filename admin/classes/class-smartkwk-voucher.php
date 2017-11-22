<?php

/**
 * Voucher class
 *
 * @link       http://www.kamenew.com
 * @since      1.4.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/admin
 * @author     Artur Kamenew <artur@kamenew.com>
 */
class Smartkwk_Voucher {

    /**
     * Voucher Id
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_voucher_id    Voucher Id
     */
    private $_voucher_id;
    
    /**
     * Voucher code
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_voucher_code    Voucher code
     */
    private $_voucher_code;
    
    /**
     * Date when the voucher was sent
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_date_sent    Date sent
     */
    private $_date_sent;
    
    /**
     * Date when the voucher was inserted into the database
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_date_inserted    Date inserted
     */
    private $_date_inserted;
    
    /**
     * Referral Id who the voucher was sent to
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_referral_id    Referral Id
     */
    private $_referral_id;
    
    /**
     * Affiliate Id who brought the new customer
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_affiliate_id    Affiliate Id
     */
    private $_affiliate_id;

    /**
     * Initialize object with a voucher code
     * @param type $_voucher_code
     */
    public function __construct($_voucher_code = '') {
        $this->_voucher_code = trim($_voucher_code);
    }

    /**
     * Get voucher id
     * @return string
     */
    public function get_voucher_id() {
        return $this->_voucher_id;
    }

    /**
     * Get voucher code
     * @return string
     */
    public function get_voucher_code() {
        return $this->_voucher_code;
    }

    /**
     * Get date when the voucher was sent
     * @return string
     */
    public function get_date_sent() {
        return $this->_date_sent;
    }

    /**
     * Get insert date
     * @return string
     */
    public function get_date_inserted() {
        return $this->_date_inserted;
    }

    /**
     * Get referral id
     * @return int
     */
    public function get_referral_id() {
        return $this->_referral_id;
    }

    /**
     * Get affiliate id
     * @return int
     */
    public function get_affiliate_id() {
        return $this->_affiliate_id;
    }

    /**
     * Set voucher id
     * @param string $_voucher_id
     */
    public function set_voucher_id($_voucher_id) {
        $this->_voucher_id = trim($_voucher_id);
    }

    /**
     * Set voucher code
     * @param string $_voucher_code
     */
    public function set_voucher_code($_voucher_code) {
        $this->_voucher_code = trim($_voucher_code);
    }

    /**
     * Set date sent
     * @param string $_date_sent
     */
    public function set_date_sent($_date_sent) {
        $this->_date_sent = $_date_sent;
    }

    /**
     * Set date inserted
     * @param string $_date_inserted
     */
    public function set_date_inserted($_date_inserted) {
        $this->_date_inserted = $_date_inserted;
    }

    /**
     * Set referral id
     * @param int $_referral_id
     */
    public function set_referral_id($_referral_id) {
        $this->_referral_id = $_referral_id;
    }

    /**
     * Set affiliate id
     * @param int $_affiliate_id
     */
    public function set_affiliate_id($_affiliate_id) {
        $this->_affiliate_id = $_affiliate_id;
    }

    /**
     * Save this object (update or insert)
     * @global type $wpdb
     * @return int
     */
    public function save() {

        if (!$this->get_voucher_code()) {
            return 0;
        }

        global $wpdb;
        $save = array();
        $save['id'] = $this->get_voucher_id();

        if (!isset($save['id'])) {
            $save['id'] = md5($this->get_voucher_code());
            $this->set_voucher_id($save['id']);
        }

        $save['voucher_code'] = $this->get_voucher_code();
        $save['date_sent'] = $this->get_date_sent();
        $save['date_inserted'] = $this->get_date_inserted();
        $save['referral_id'] = $this->get_referral_id();
        $save['affiliate_id'] = $this->get_affiliate_id();


        if ($this->exists()) {
            $updated = (int) $wpdb->update($wpdb->prefix . 'smart_kwk_vouchers', array('voucher_code' => $this->get_voucher_code()), array('id' => $this->get_voucher_id()));
        } else {
            $updated = (int) $wpdb->insert($wpdb->prefix . 'smart_kwk_vouchers', $save);
        }

        return $updated;
    }

    /**
     * Check if voucher exists
     * @global type $wpdb
     * @return bool
     */
    public function exists() {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}smart_kwk_vouchers WHERE id = '%s'", $this->get_voucher_id()));

        return isset($result);
    }

    /**
     * Delete this voucher
     * @global type $wpdb
     * @return number of rows deleted
     */
    public function delete() {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'smart_kwk_vouchers', array('id' => $this->get_voucher_id()), array('%s'));
    }

    /**
     * Get email of affiliate
     * @global type $wpdb
     * @return string
     */
    public function get_affiliate_email() {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare("SELECT u.user_email FROM {$wpdb->prefix}affiliate_wp_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID WHERE a.affiliate_id = %d", (int) $this->get_affiliate_id()));

        return $result;
    }

}
