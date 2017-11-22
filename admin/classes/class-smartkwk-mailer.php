<?php
/**
 * This class handles the email related functions like
 * saving the email template and sending vouchers
 *
 * @link       http://www.kamenew.com
 * @since      1.4.0
 *
 * @package    Smartkwk
 * @subpackage Smartkwk/admin
 * @author     Artur Kamenew <artur@kamenew.com>
 */

require_once ABSPATH . WPINC . '/class-phpmailer.php';
require_once ABSPATH . WPINC . '/class-smtp.php';

class SmartKwk_Mailer extends PHPMailer {

    /**
     * Whether to use SMTP or not
     *
     * @since    1.4.0
     * @access   private
     * @var      bool    $_use_smtp    true or false
     */
    private $_use_smtp;
    
    /**
     * Email subject
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_email_subject    Email subject
     */
    private $_email_subject;
    
    /**
     * Email of recipient
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_email_recipient    Email of recipient
     */
    private $_email_recipient;
    
    /**
     * Email content
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_email_content    Email content
     */
    private $_email_content;
    
    /**
     * Email template file
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_email_template_file    Email template file
     */
    private $_email_template_file;
    
    /**
     * Voucher placeholder
     *
     * @since    1.4.0
     * @access   private
     * @var      string    $_voucher_placeholder    Voucher placeholder
     */
    private $_voucher_placeholder;
    
    /**
     * Voucher
     *
     * @since    1.4.0
     * @access   private
     * @var      SmartKwk_Voucher    $_voucher    Voucher
     */
    private $_voucher;

    /**
     * Configure the mailer
     */
    public function __construct() {
        parent::__construct(true); //true = enable exceptions

        $this->IsHTML();
        $this->addBCC(BCC_EMAIL);

        $this->Host = SMTP_HOST;
        $this->SMTPAuth = true;
        $this->Username = SMTP_USERNAME;
        $this->Password = SMTP_PASSWORD;
        $this->Port = SMTP_PORT;
        $this->SMTPSecure = SMTP_ENCRYPTION;
        $this->From = SMTP_FROM_EMAIL;
        $this->FromName = SMTP_FROM_NAME;

        $this->_use_smtp = USE_SMTP;
        $this->_voucher_placeholder = PLACEHOLDER_VOUCHER;
        $this->_email_template_file = SMARTKWK_PLUGIN_DIR . '/admin/templates/sendVoucher.html';
    }

    /**
     * Get voucher object
     * @return SmartKwk_Voucher
     */
    function get_voucher() {
        return $this->_voucher;
    }

    /**
     * Set voucher
     * @param Smartkwk_Voucher $_voucher
     */
    function set_voucher(Smartkwk_Voucher $_voucher) {
        $this->_voucher = $_voucher;
    }

    /**
     * Get use smtp
     * @return bool true|false
     */
    function get_use_smtp() {
        return $this->_use_smtp;
    }

    /**
     * Set use smtp
     * @param bool $_use_smtp true|false
     */
    function set_use_smtp($_use_smtp) {
        $this->_use_smtp = $_use_smtp;
    }

    /**
     * Get email subject
     * @return string
     */
    function get_email_subject() {
        return $this->_email_subject;
    }

    /**
     * Get email recipient
     * @return string
     */
    function get_email_recipient() {
        return $this->_email_recipient;
    }

    /**
     * Get email content
     * @return string
     */
    function get_email_content() {
        return $this->_email_content;
    }

    /**
     * Get path of email template file
     * @return string
     */
    function get_email_template_file() {
        return $this->_email_template_file;
    }

    /**
     * Get voucher placeholder
     * @return string
     */
    function get_voucher_placeholder() {
        return $this->_voucher_placeholder;
    }

    /**
     * Set email subject
     * @param string $_email_subject
     */
    function set_email_subject($_email_subject) {
        $this->_email_subject = $_email_subject;
    }

    /**
     * Set email recipient
     * @param string $_email_recipient
     */
    function set_email_recipient($_email_recipient) {
        $this->_email_recipient = $_email_recipient;
    }

    /**
     * Set email content
     * @param string $_email_content
     */
    function set_email_content($_email_content) {
        $this->_email_content = $_email_content;
    }

    /**
     * Set path of email template file
     * @param string $_email_template_file
     */
    function set_email_template_file($_email_template_file) {
        $this->_email_template_file = $_email_template_file;
    }

    /**
     * Set voucher placeholder
     * @param string $_voucher_placeholder
     */
    function set_voucher_placeholder($_voucher_placeholder) {
        $this->_voucher_placeholder = $_voucher_placeholder;
    }

    /**
     * Initialize the email body
     * @throws Exception
     */
    public function init_template() {

        //check if template file exists
        if (!file_exists($this->get_email_template_file())) {
            throw new Exception(__('Email template not found. Please save some template content.', 'smart-kwk'));
        }

        //check if placeholder for voucher is set
        if (stripos($this->get_voucher_placeholder()) === false) {
            throw new Exception(__('Placeholder for voucher not found!', 'smart-kwk'));
        }

        $voucher = $this->get_voucher();

        if (!($voucher && $voucher->get_voucher_code())) {
            throw new Exception(__('Voucher code not provided', 'smart-kwk'));
        }

        //replace placeholder with actual voucher
        $this->set_email_content(str_replace($this->get_voucher_placeholder(), $voucher->get_voucher_code(), $this->get_email_content()));
    }

    /**
     * Send email
     *
     * @since    1.4.0
     * @return array $status Mixed
     */
    public function send_email() {

        $sent = false;

        //sending using smtp
        if ($this->get_use_smtp()) {

            $this->IsSMTP();

            $this->Subject = utf8_decode($this->get_email_subject());
            $this->Body = utf8_decode($this->get_email_content());

            try {
                $sent = $this->Send();

                if ($this->ErrorInfo) {
                    $error = $this->ErrorInfo;
                }
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        }

        //If smtp mailer failed, try standard method
        if (!$sent) {

            $headers = "From: " . $this->FromName . " <" . $this->From . ">\r\n";
            $headers .= "BCC: " . implode(',', $this->getBccAddresses()) . "\r\n";
            $headers .= "Content-Type: text/html";

            try {
                $sent = wp_mail($this->getAllRecipientAddresses(), utf8_decode($this->get_email_subject()), utf8_decode($this->get_email_content()), $headers, $this->getAttachments());
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        }

        if ($error) {
            throw new Exception($error);
        }

        return $sent;
    }

    /**
     * Get email template content
     *
     * @since    1.4.0
     * @return string Content of template file
     */
    public function get_email_template_content() {

        $targetfile = $this->get_email_template_file();

        if (file_exists($targetfile)) {
            $content = file_get_contents($targetfile);
        } else {
            $content = '';
        }

        return $content;
    }

    /**
     * Save content to email template file
     * @param type $content
     * @return int
     */
    public function save_email_template_content($content) {

        if (file_put_contents($this->get_email_template_file(), stripslashes(trim($content)))) {
            $ok = 1;
        } else {
            $ok = 0;
        }
        
        return $ok;
    }

}
