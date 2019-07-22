<?php

require_once 'CRM/Utils/DeprecatedUtils.php';

/**
 * Class CRM_Utils_DeprecatedUtilsTest
 * @group headless
 */
class CRM_Utils_DeprecatedUtilsTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    // truncate a few tables
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_email',
      'civicrm_contribution',
      'civicrm_website',
    ];

    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test civicrm_contact_check_params with no contact type.
   */
  public function testCheckParamsWithNoContactType() {
    $params = ['foo' => 'bar'];
    $contact = _civicrm_api3_deprecated_contact_check_params($params, FALSE);
    $this->assertEquals(1, $contact['is_error']);
  }

  /**
   *  Test civicrm_contact_check_params with a duplicate.
   *  and request the error in array format
   */
  public function testCheckParamsWithDuplicateContact2() {
    $this->individualCreate(['first_name' => 'Test', 'last_name' => 'Contact', 'email' => 'TestContact@example.com']);

    $params = [
      'first_name' => 'Test',
      'last_name' => 'Contact',
      'email' => 'TestContact@example.com',
      'contact_type' => 'Individual',
    ];
    $contact = _civicrm_api3_deprecated_contact_check_params($params, TRUE);
    $this->assertEquals(1, $contact['is_error']);
    $this->assertRegexp("/matching contacts.*1/s",
      $contact['error_message']['message']
    );
  }

  /**
   *  Test civicrm_contact_check_params with check for required
   *  params and no params
   */
  public function testCheckParamsWithNoParams() {
    $params = [];
    $contact = _civicrm_api3_deprecated_contact_check_params($params, FALSE);
    $this->assertEquals(1, $contact['is_error']);
  }

}
