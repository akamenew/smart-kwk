<?php

/**
 * Handles all operations for the Backoffice API
 *
 * @link       http://www.kamenew.com
 * @since      1.4.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/admin
 * @author     Artur Kamenew <artur@kamenew.com>
 */
class Smartkwk_Backoffice {

    /**
     * ID from the database
     *
     * @since    1.4.0
     * @access   private
     * @var      int    $_id    ID
     */
    private $_id;

    /**
     * The request url for obtaining k-numbers
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_service_url    Smartsteuer Service url
     */
    private $_service_url = 'https://www.smartsteuer.de/portal/kwkOrders.html?token=A29F8KVcCGft';

    /**
     * The offset in days for comparison of actual order date 
     * with backoffice request order date
     *
     * @since    1.4.0
     * @access   private
     * @var      int    $_allowed_ordertime_offset    Offset in days
     */
    private $_allowed_ordertime_offset = 2;

    /**
     * request url timeout in seconds
     *
     * @since    1.4.0
     * @access   private
     * @var      int    $_request_timeout    Timeout in seconds
     */
    private $_request_timeout = 30;

    /**
     * Referral email
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_referral_email    Referral email
     */
    private $_referral_email;

    /**
     * Referral id
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_referral_id    Referral id
     */
    private $_referral_id;

    /**
     * Date when referral was created (has ordered at Smartsteuer)
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_order_date    Order date
     */
    private $_order_date;

    /**
     * Timestamp of order date
     *
     * @since    1.4.0
     * @access   private
     * @var      int    $_order_timestamp    Timestamp of order date
     */
    private $_order_timestamp;

    /**
     * Internal order id
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_business_case    Internal order id
     */
    private $_business_case;

    /**
     * Status from backoffice API
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_api_status    Status from backoffice API
     */
    private $_api_status;

    /**
     * Status description from backoffice API
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_api_status_description    Status description
     */
    private $_api_status_description;

    /**
     * Order is paid
     *
     * @since    1.4.0
     * @access   private
     * @var      int    $_is_paid    Order is paid
     */
    private $_is_paid;

    /**
     * Date when the data was inserted in db
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_date_inserted    Date inserted
     */
    private $_date_inserted;

    /**
     * Date when the data was exported
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_date_exported    Date exported
     */
    private $_date_exported;

    /**
     * JSON decoded response from backoffice API
     *
     * @since    1.4.0
     * @access   private
     * @var      array    $_api_response    Backoffice API response
     */
    private $_api_response;

    /**
     * Parsed response from backoffice API
     *
     * @since    1.4.0
     * @access   private
     * @var      array    $_parsed_data    Response parsed
     */
    private $_parsed_data;

    /**
     * Status used to deny an affiliate commission
     *
     * @since    1.4.0
     * @access   public
     * @var      string    $API_STATUS_DENIED    Status for denied
     */
    public static $API_STATUS_DENIED = 'denied';

    /**
     * Status used to accept an affiliate commission
     *
     * @since    1.4.0
     * @access   public
     * @var      string    $API_STATUS_ACCEPTED    Status for accept
     */
    public static $API_STATUS_ACCEPTED = 'accepted';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.0
     * @param      string    $ref_id       The referral ID
     */
    public function __construct($ref_id) {
        $this->_referral_id = $ref_id;
    }
    
    /**
     * Loads data from database into object
     *
     * @since    1.4.0
     * @throws Exception
     */
    public function init() {

        if (!($this->get_referral_id() && is_numeric($this->get_referral_id()))) {
            throw new Exception(__('Parameter referral_id missing or invalid', 'smart-kwk'));
        }

        global $wpdb;
        $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}smart_kwk WHERE referral_id=%s", $this->get_referral_id()), ARRAY_A);

        if ($results) {
            $this->set_id($results['id']);
            $this->set_order_date($results['order_date']);
            $this->set_business_case($results['business_case']);
            $this->set_api_status($results['api_status']);
            $this->set_api_response_description($results['api_response']);
            $this->set_is_paid((int) $results['paid']);
            $this->set_date_exported($results['date_exported']);
        }
    }

    /**
     * Get order timestamp
     * @since    1.4.0
     * @return int 
     */
    public function get_order_timestamp() {
        return $this->_order_timestamp;
    }

    /**
     * Set order timestamp
     * @since    1.4.0
     * @param int $timestamp
     */
    public function set_order_timestamp($timestamp) {
        $this->_order_timestamp = $timestamp;
    }

    /**
     * Get parsed data from backoffice API response
     * @since    1.4.0
     * @return array
     */
    public function get_parsed_data() {
        return $this->_parsed_data;
    }

    /**
     * Set parsed data
     * @since    1.4.0
     * @param array $data
     */
    public function set_parsed_data($data) {
        $this->_parsed_data = $data;
    }

    /**
     * Get the Id
     * @since    1.4.0
     * @return int
     */
    function get_id() {
        return $this->_id;
    }

    /**
     * Set the Id
     * @since    1.4.0
     * @param int $_id
     */
    function set_id($_id) {
        $this->_id = $_id;
    }

    /**
     * JSON decoded response from backoffice API
     * @since    1.4.0
     * @return array
     */
    public function get_api_response() {
        return $this->_api_response;
    }

    /**
     * Set response of backoffice API
     * @since    1.4.0
     * @param array $response
     */
    public function set_api_response($response) {
        $this->_api_response = $response;
    }

    /**
     * Get export date
     * @since    1.4.0
     * @return string
     */
    public function get_date_exported() {
        return $this->_date_exported;
    }

    /**
     * Set export date
     * @since    1.4.0
     * @param string $date
     */
    public function set_date_exported($date) {
        $this->_date_exported = $date;
    }

    /**
     * Get insert date
     * @since    1.4.0
     * @return string Date
     */
    public function get_date_inserted() {
        return $this->_date_inserted;
    }

    /**
     * Set insert date
     * @since    1.4.0
     * @param string $date
     */
    public function set_date_inserted($date) {
        $this->_date_inserted = $date;
    }

    /**
     * Get is paid
     * @since    1.4.0
     * @return int
     */
    public function get_is_paid() {
        return $this->_is_paid;
    }

    /**
     * Set is paid
     * @since    1.4.0
     * @param itn $is_paid
     */
    public function set_is_paid($is_paid) {
        $this->_is_paid = $is_paid;
    }

    /**
     * Get API response description for explaining the API status
     * @since    1.4.0
     * @return string
     */
    public function get_api_response_description() {
        return $this->_api_status_description;
    }

    /**
     * Set API response description
     * @since    1.4.0
     * @param type $text
     */
    public function set_api_response_description($text) {
        $this->_api_status_description = $text;
    }

    /**
     * Get API status
     * @since    1.4.0
     * @return string
     */
    public function get_api_status() {
        return $this->_api_status;
    }

    /**
     * Set API status
     * @since    1.4.0
     * @param string $status
     */
    public function set_api_status($status) {
        $this->_api_status = $status;
    }

    /**
     * Get business case (internal order id)
     * @since    1.4.0
     * @return string
     */
    public function get_business_case() {
        return $this->_business_case;
    }

    /**
     * Set business case
     * @since    1.4.0
     * @param string $business_case
     */
    public function set_business_case($business_case) {
        $this->_business_case = $business_case;
    }

    /**
     * Get order date
     * @since    1.4.0
     * @return string
     */
    public function get_order_date() {
        return $this->_order_date;
    }

    /**
     * Set order date
     * @since    1.4.0
     * @param string $date
     */
    public function set_order_date($date) {
        $this->_order_date = $date;
    }

    /**
     * Get referral email
     * @since    1.4.0
     * @return string
     */
    public function get_referral_email() {
        return $this->_referral_email;
    }

    /**
     * Set referral email
     * @since    1.4.0
     * @param string $mail
     */
    public function set_referral_email($mail) {
        $this->_referral_email = $mail;
    }

    /**
     * Get referral id
     * @since    1.4.0
     * @return int
     */
    public function get_referral_id() {
        return $this->_referral_id;
    }

    /**
     * Set referral id
     * @since    1.4.0
     * @param int $ref_id
     */
    public function set_referral_id($ref_id) {
        $this->_referral_id = $ref_id;
    }

    /**
     * Get request timeout
     * @since    1.4.0
     * @return int
     */
    public function get_reqest_timeout() {
        return $this->_request_timeout;
    }

    /**
     * Set request timeout
     * @since    1.4.0
     * @param int $timeout
     */
    public function set_reqest_timeout($timeout) {
        $this->_request_timeout = $timeout;
    }

    /**
     * Get ordertime offset
     * @since    1.4.0
     * @return int
     */
    public function get_ordertime_offset() {
        return $this->_allowed_ordertime_offset;
    }

    /**
     * Set ordertime offset
     * @since    1.4.0
     * @param int $offset
     */
    public function set_ordertime_offset($offset) {
        $this->_allowed_ordertime_offset = $offset;
    }

    /**
     * Get backoffice API service URL
     * @since    1.4.0
     * @return string
     */
    public function get_service_url() {
        return $this->_service_url;
    }

    /**
     * Set backoffice API service URL
     * @since    1.4.0
     * @param string $url
     */
    public function set_service_url($url) {
        $this->_service_url = $url;
    }

    /**
     * Initialize this object with data from database
     * @since    1.4.0
     * @global type $wpdb
     * @throws Exception
     */
    public function init_referral() {
        global $wpdb;

        $referral = $wpdb->get_row($wpdb->prepare("SELECT r.*,k.date_inserted FROM {$wpdb->prefix}affiliate_wp_referrals r LEFT JOIN {$wpdb->prefix}smart_kwk k ON r.referral_id=k.referral_id WHERE r.referral_id='%d'", $this->_ref_id), ARRAY_A);

        if (!($referral && $referral['description'] && $referral['date'])) {
            throw new Exception(__('Referral not found or Email/order date not specified'));
        } else {
            $this->set_date_inserted($referral['date_inserted']);
            $this->set_order_date($referral['date']);
            $this->set_order_timestamp(strtotime($referral['date']));
            $this->set_referral_email($referral['description']);
        }
    }

    /**
     * Perform the request to the backoffice API service URL
     * @since    1.4.0
     * @throws Exception
     */
    public function perform_api_request() {

        $serviceURL = esc_url($this->get_service_url() . "&email=" . $this->get_referral_email());

        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'timeout' => $this->get_reqest_timeout(),
        );

        $response = wp_remote_get($serviceURL, $args);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        if (isset($response['body'])) {
            $this->set_api_response(json_decode($response['body'], true));
        }
    }

    /**
     * Parse data from API response
     * @since    1.4.0
     * @throws Exception
     */
    public function parse_data() {

        $reponse_data = $this->get_api_response();

        if (!is_array($reponse_data) || empty($reponse_data)) {
            throw new Exception(__('Response must be an array and cannot be empty!', 'smart-kwk'));
        }


        $timestamp = $this->get_order_timestamp();
        $date = date('Y-m-d', $timestamp);

        //parsing response
        $return = array();

        foreach ($reponse_data as $r) {
            $return[$r->date]['ordernumber'] = $r->ordernumber;
            $return[$r->date]['invoicenumber'] = $r->invoicenumber;
            $return[$r->date]['timestamp'] = $r->timestamp / 1000;

            //check if backoffice order date deviates from wp affiliate order date, consider offset
            if ($this->get_ordertime_offset() > 0 && $date != $r->date) {

                $diff = $this->_get_time_difference($return[$r->date]['timestamp'], strtotime($date)); //difference in days

                if ($diff <= $this->get_ordertime_offset()) {
                    $date = $r->date; //set wp affiliate date = backoffice date to allow approval
                }
            }
        }

        $this->set_parsed_data($return);
    }

    /**
     * Get difference of two timestamps in days
     * @since    1.4.0
     * @param int $date1 Timestamp 1
     * @param int $date2 Timestamp 2
     * @return float
     */
    private function _get_time_difference($date1, $date2) {
        $datediff = abs($date1 - $date2);
        return floor($datediff / (60 * 60 * 24)); //difference in days
    }

    /**
     * Validate the parsed data from API response
     * @since    1.4.0
     * @return type
     * @throws Exception
     */
    public function validate_data() {

        $parsed_data = $this->get_parsed_data();

        if (empty($parsed_data)) {
            $this->set_api_status(self::$API_STATUS_DENIED);
            $this->set_api_response_description(__('Denied - Not a customer', 'smart-kwk'));
            return;
        }

        //check if data contains order for given date
        if ($parsed_data[$this->get_order_date()]['ordernumber']) {

            //multiple orders for given date?
            if (count($parsed_data) > 1) {
                $this->set_api_response('denied');
                $this->set_api_response_description(__('Denied - customer already exists', 'smart-kwk'));
            } elseif (!$parsed_data[$this->get_order_date()]['invoicenumber']) {
                throw new Exception(__('Error: customer invoicenumber missing', 'smart-kwk'));
            } elseif (!$parsed_data[$this->get_order_date()]['timestamp']) {
                throw new Exception(__('Error: timestamp missing', 'smart-kwk'));
            } else {

                //only one order exists for order date = OK
                //deny if K Nummer created BEFORE order date
                if ($parsed_data[$this->get_order_date()]['timestamp'] < $this->get_order_timestamp()) {
                    $this->set_api_status(self::$API_STATUS_DENIED);
                    $this->set_api_response_description(__('Denied - customer already exists', 'smart-kwk'));
                } else {
                    //else approve
                    $this->set_api_status(self::$API_STATUS_ACCEPTED);
                    $this->set_api_response_description(sprintf(__('Approved: %s', 'smart-kwk'), $parsed_data[$this->_order_date]['ordernumber']));
                    $this->set_business_case($parsed_data[$this->get_order_date()]['ordernumber']);
                }
            }
        } else {
            //order for this date does not exist
            $this->set_api_status(self::$API_STATUS_DENIED);
            $this->set_api_response_description(__('Denied - Wrong date', 'smart-kwk'));
        }
    }

    /**
     * Check if referral exists
     * @since    1.4.0
     * @global type $wpdb
     * @return bool
     */
    public function referral_exists() {
        global $wpdb;

        $sql = "SELECT id FROM {$wpdb->prefix}smart_kwk k WHERE k.referral_id=%d";
        $id = (int) $wpdb->get_var($wpdb->prepare($sql, $this->get_referral_id()));

        return $id > 0;
    }

    /**
     * Save data in the database
     * @since    1.4.0
     * @global type $wpdb
     * @return int|bool number of affected rows or false
     * @throws Exception
     */
    public function save() {

        global $wpdb;

        //save data in db
        $save = array();
        $save['referral_id'] = $this->get_referral_id();
        $save['order_date'] = $this->get_order_date();
        $save['business_case'] = $this->get_business_case();
        $save['api_status'] = $this->get_api_status();
        $save['api_response'] = $this->get_api_response_description();
        $save['paid'] = (int) $this->get_is_paid();
        $save['date_exported'] = $this->get_date_exported();

        //update if referral exists in smart_kwk
        if ($this->get_id()) {
            $ok = $wpdb->update("{$wpdb->prefix}smart_kwk", $save, array('id' => $this->get_id()));
        } else {
            $ok = $wpdb->insert("{$wpdb->prefix}smart_kwk", $save);
        }

        if (!$ok) {
            throw new Exception($wpdb->last_error);
        }

        return $ok;
    }

}