<?php

/**
 * Class VoucherTest
 *
 * @package Smart_kwk
 */

/**
 * Voucher test case.
 */
class VoucherTest extends WP_UnitTestCase {

    public function setUp() {
        
        parent::setUp();
        
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE %s (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            referral_id BIGINT(20) NOT NULL,
            order_date TIMESTAMP NULL DEFAULT NULL,
            business_case VARCHAR(255) NULL DEFAULT NULL,
            api_status CHAR(20) NULL DEFAULT NULL,
            api_response TEXT NULL,
            paid TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            date_exported TIMESTAMP NULL DEFAULT NULL,
            date_inserted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY referral_id (referral_id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

        dbDelta(sprintf($sql, $wpdb->prefix . 'smart_kwk'));

        $sql = "CREATE TABLE %s (
            id VARCHAR(32) NOT NULL,
            voucher_code VARCHAR(255) NOT NULL,
            date_sent TIMESTAMP NULL DEFAULT NULL,
            referral_id BIGINT(20) NULL DEFAULT NULL,
            affiliate_id BIGINT(20) NULL DEFAULT NULL,
            date_inserted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY voucher_code (voucher_code)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

        dbDelta(sprintf($sql, $wpdb->prefix . 'smart_kwk_vouchers'));
    }

    public function tearDown() {
        
        parent::tearDown();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_kwk_vouchers';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

        $table_name = $wpdb->prefix . 'smart_kwk';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    public function test_get_voucher_code() {
        $voucher = new Smartkwk_Voucher();

        $voucher->set_voucher_code('123456');

        $this->assertEquals($voucher->get_voucher_code(), '123456');
    }

    public function test_voucher_code_is_trimmed() {
        $voucher = new Smartkwk_Voucher('   ABC-123-DEF-456 ');

        $this->assertEquals($voucher->get_voucher_code(), 'ABC-123-DEF-456');
    }

    public function test_save_failed_with_empty_voucher_code() {
        $voucher = new Smartkwk_Voucher();

        $ok1 = $voucher->save();

        $this->assertEquals($ok1, 0);

        $ok2 = $voucher->save();

        //voucher with voucher should neither be saved
        $voucher->set_voucher_id('qwertzuiiop');

        $this->assertEquals($ok2, 0);
    }

    public function test_voucher_code_is_set_on_construct() {
        $voucher = new Smartkwk_Voucher('123456');

        $this->assertEquals($voucher->get_voucher_code(), '123456');
    }

    public function test_voucher_id_generated_on_save() {
        $voucher = new Smartkwk_Voucher();
        $voucher->set_voucher_code('123456');
        $voucher->save();

        $isSet = $voucher->get_voucher_id() != '';

        $this->assertTrue($isSet);
    }

    public function test_voucher_exists_after_save() {
        $voucher = new Smartkwk_Voucher('123456');
        $voucher->save();

        $this->assertTrue($voucher->exists());
    }

    public function test_voucher_not_exists_after_delete() {
        $voucher = new Smartkwk_Voucher('123456');
        $voucher->save();

        $voucher->delete();

        $this->assertFalse($voucher->exists());
    }
    
    public function test_voucher_is_saved(){
        //created
        $voucher = new Smartkwk_Voucher('123456');
        $affected_rows = $voucher->save();
        $this->assertEquals($affected_rows, 1);
        
        //updated with no change
        $affected_rows = $voucher->save();
        $this->assertEquals($affected_rows, 0);
        
        //updated with change
        $voucher->set_voucher_code('ABCDEFGHIJKL');
        $affected_rows = $voucher->save();
        $this->assertEquals($affected_rows, 1);
    }

}
