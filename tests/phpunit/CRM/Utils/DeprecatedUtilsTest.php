<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Utils/DeprecatedUtils.php';

class CRM_Utils_DeprecatedUtilsTest extends CiviUnitTestCase {

  function get_info() {
    return array(
      'name'      => 'Deprecated Utils Test',
      'description' => 'Test functions that were copied from api v2 to support BAO code',
      'group'      => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }
  function tearDown() {
    // truncate a few tables
    $tablesToTruncate = array(
        'civicrm_contact',
        'civicrm_email',
        'civicrm_contribution',
        'civicrm_website',
    );

    $this->quickCleanup($tablesToTruncate);
    $this->contributionTypeDelete();
  }
  /**
   *  Test civicrm_contact_check_params with no contact type
   */
  function testCheckParamsWithNoContactType() {
    $params = array('foo' => 'bar');
    $contact = _civicrm_api3_deprecated_contact_check_params($params, FALSE);
    $this->assertEquals(1, $contact['is_error'], "In line " . __LINE__);
  }


  /**
   *  Test civicrm_contact_check_params with a duplicate
   */
  function testCheckParamsWithDuplicateContact() {
    //  Insert a row in civicrm_contact creating individual contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
        new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/../../api/v3/dataset/contact_17.xml'
        )
    );
    $op->execute($this->_dbconn,
        new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/../../api/v3/dataset/email_contact_17.xml'
        )
    );

    $params = array(
        'first_name' => 'Test',
        'last_name' => 'Contact',
        'email' => 'TestContact@example.com',
        'contact_type' => 'Individual',
    );
    $contact = _civicrm_api3_deprecated_contact_check_params($params, TRUE);
    $this->assertEquals(1, $contact['is_error']);
    $this->assertRegexp("/matching contacts.*17/s",
        CRM_Utils_Array::value('error_message', $contact)
    );
  }


  /**
   *  Test civicrm_contact_check_params with a duplicate
   *  and request the error in array format
   */
  function testCheckParamsWithDuplicateContact2() {
    //  Insert a row in civicrm_contact creating individual contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
        new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/../../api/v3/dataset/contact_17.xml'
        )
    );
    $op->execute($this->_dbconn,
        new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/../../api/v3/dataset/email_contact_17.xml'
        )
    );

    $params = array(
        'first_name' => 'Test',
        'last_name' => 'Contact',
        'email' => 'TestContact@example.com',
        'contact_type' => 'Individual',
    );
    $contact = _civicrm_api3_deprecated_contact_check_params($params, TRUE, TRUE);
    $this->assertEquals(1, $contact['is_error']);
    $this->assertRegexp("/matching contacts.*17/s",
        $contact['error_message']['message']
    );
  }
  /**
   *  Test civicrm_contact_check_params with check for required
   *  params and no params
   */
  function testCheckParamsWithNoParams() {
    $params = array();
    $contact = _civicrm_api3_deprecated_contact_check_params($params, FALSE);
    $this->assertEquals(1, $contact['is_error'], "In line " . __LINE__);
  }

}
