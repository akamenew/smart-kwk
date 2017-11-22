<?php

/**
 * Class AdminTest
 *
 * @package Smart_kwk
 */

/**
 * Admin test case.
 */
class AdminTest extends WP_Ajax_UnitTestCase {

    private $_plugin_name = 'smart_kwk';
    private $_plugin_version = '1.4.0';
    private $_admin;

    public function setUp() {
        parent::setUp();

        $this->_admin = new Smartkwk_Admin($this->_plugin_name, $this->_plugin_version);
    }

    public function tearDown() {
        parent::tearDown();
        unset($this->_admin);
    }

    public function test_api_request_response_with_error() {
        global $_POST;

        $_POST['action'] = 'api_request';
        $_POST['rid'] = '1';

        try {
            $this->setExpectedException('WPAjaxDieStopException');
            $this->setExpectedException('WPAjaxDieContinueException');
            $this->_handleAjax($_POST['action']);
        } catch (WPAjaxDieStopException $e) {
            // We expected this, do nothing.
        }

        $this->assertTrue(isset($e));
        $response = json_decode($this->_last_response);

        $this->assertInternalType('object', $response);
        $this->assertObjectHasAttribute('success', $response);
        $this->assertObjectHasAttribute('data', $response);
        $this->assertFalse($response->success);

        $this->assertObjectHasAttribute('message', $response->data);
        $this->assertObjectHasAttribute('status', $response->data);
        $this->assertEquals($response->data->status, 'error');
    }

    public function test_verify_nonce_was_successful() {

        $nonce_field = '_wpnonce';

        $_POST[$nonce_field] = wp_create_nonce('export_overview', $nonce_field);
        $this->assertNotFalse($this->_admin->verify_nonce('export_overview', $nonce_field));
    }

}
