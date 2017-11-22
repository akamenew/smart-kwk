<?php

/**
 * Class MailerTest
 *
 * @package Smart_kwk
 */

/**
 * Voucher test case.
 */
class MailerTest extends WP_UnitTestCase {

    private $_temp_dir;
    private $_email_template_file;

    public function setUp() {
        parent::setUp();

        $this->_temp_dir = dirname(__FILE__) . '/tmp';
        $this->_email_template_file = "$this->_temp_dir/mail.html";
        if (!is_dir($this->_temp_dir)) {
            mkdir($this->_temp_dir);
        }
    }

    public function tearDown() {
        parent::tearDown();

        //unlink($this->_email_template_file);
        //rmdir($this->_temp_dir);
    }

    public function test_create_email_template_file() {

        $mailer = new SmartKwk_Mailer();
        $mailer->set_email_template_file($this->_email_template_file);
        $mailer->save_email_template_content("Test content");

        $this->assertStringEqualsFile($mailer->get_email_template_file(), 'Test content');
        //$this->assertEquals($mailer->get_email_template_content(), 'Test content');
    }

    public function test_email_template_file_exists() {
        $mailer = new SmartKwk_Mailer();

        $this->assertFileExists($mailer->get_email_template_file());
    }

    public function test_voucher_is_instance_of_voucher() {
        $mailer = new SmartKwk_Mailer();
        $voucher = new Smartkwk_Voucher('12345');
        $mailer->set_voucher($voucher);

        $this->assertInstanceOf('SmartKwk_Voucher', $mailer->get_voucher());
    }

    public function test_email_template_shortcode_exists() {
        $mailer = new SmartKwk_Mailer();

        $mailer->set_email_template_file($this->_email_template_file);
        $mailer->save_email_template_content('Test content. Voucher: ' . PLACEHOLDER_VOUCHER);
        $content = $mailer->get_email_template_content();

        $this->assertContains(PLACEHOLDER_VOUCHER, $content);
    }

}
