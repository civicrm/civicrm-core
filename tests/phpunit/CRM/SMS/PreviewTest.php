<?php

/**
 *  Test SMS Preview
 *
 * @group headless
 */
class CRM_SMS_PreviewTest extends CiviUnitTestCase {

  /**
   * Set Up Function
   */
  public function setUp() {
    parent::setUp();
    $option = $this->callAPISuccess('option_value', 'create', ['option_group_id' => 'sms_provider_name', 'name' => 'test_provider_name', 'label' => 'Test Provider Label', 'value' => 1]);
    $this->option_value = $option['id'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    parent::tearDown();
    $this->callAPISuccess('option_value', 'delete', ['id' => $this->option_value]);
  }

  /**
   * Test SMS preview.
   */
  public function testSMSPreview() {
    $result = $this->callAPISuccess('SmsProvider', 'create', [
      'title' => 'test SMS provider',
      'username' => 'test',
      'password' => 'password',
      // 'name' is the option_value 'value' (not id, not name) we created in setUp()
      'name' => 1,
      'is_active' => 1,
      'is_default' => 1,
      'api_type' => 1,
    ]);
    $provider_id = $result['id'];
    $result = $this->callAPISuccess('Mailing', 'create', [
      'name' => "Test1",
      'from_name' => "+12223334444",
      'from_email' => "test@test.com",
      'replyto_email' => "test@test.com",
      'body_text' => "Testing body",
      'sms_provider_id' => $provider_id,
      'header_id' => NULL,
      'footer_id' => NULL,
      'unsubscribe_id' => NULL,
    ]);
    $mailing_id = $result['id'];
    $result = $this->callAPISuccess('Mailing', 'preview', [
      'id' => $mailing_id,
    ]);
  }

}
