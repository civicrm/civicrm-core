<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Utils/DeprecatedUtils.php';

/**
 * Test class for API utils
 *
 * @package   CiviCRM
 */
class api_v3_UtilsTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  public $DBResetRequired = FALSE;

  public $_contactID = 1;

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testAddFormattedParam() {
    $values = array('contact_type' => 'Individual');
    $params = array('something' => 1);
    $result = _civicrm_api3_deprecated_add_formatted_param($values, $params);
    $this->assertTrue($result);
  }

  public function testCheckPermissionReturn() {
    $check = array('check_permissions' => TRUE);
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $check), 'empty permissions should not be enough');
    $config->userPermissionClass->permissions = array('access CiviCRM');
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $check), 'lacking permissions should not be enough');
    $config->userPermissionClass->permissions = array('add contacts');
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $check), 'lacking permissions should not be enough');

    $config->userPermissionClass->permissions = array('access CiviCRM', 'add contacts');
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $check), 'exact permissions should be enough');

    $config->userPermissionClass->permissions = array('access CiviCRM', 'add contacts', 'import contacts');
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $check), 'overfluous permissions should be enough');
  }

  public function testCheckPermissionThrow() {
    $check = array('check_permissions' => TRUE);
    $config = CRM_Core_Config::singleton();
    try {
      $config->userPermissionClass->permissions = array('access CiviCRM');
      $this->runPermissionCheck('contact', 'create', $check, TRUE);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals($message, 'API permission check failed for Contact/create call; insufficient permission: require access CiviCRM and add contacts', 'lacking permissions should throw an exception');

    $config->userPermissionClass->permissions = array('access CiviCRM', 'add contacts', 'import contacts');
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $check), 'overfluous permissions should return true');
  }

  public function testCheckPermissionSkip() {
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array('access CiviCRM');
    $params = array('check_permissions' => TRUE);
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $params), 'lacking permissions should not be enough');
    $params = array('check_permissions' => FALSE);
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $params), 'permission check should be skippable');
  }

  /**
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param bool $throws
   *   Whether we should pass any exceptions for authorization failures.
   *
   * @throws API_Exception
   * @throws Exception
   * @return bool
   *   TRUE or FALSE depending on the outcome of the authorization check
   */
  public function runPermissionCheck($entity, $action, $params, $throws = FALSE) {
    $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\PermissionCheck());
    $kernel = new \Civi\API\Kernel($dispatcher);
    $apiRequest = \Civi\API\Request::create($entity, $action, $params, NULL);
    try {
      $kernel->authorize(NULL, $apiRequest);
      return TRUE;
    }
    catch (\API_Exception $e) {
      $extra = $e->getExtraParams();
      if (!$throws && $extra['error_code'] == API_Exception::UNAUTHORIZED) {
        return FALSE;
      }
      else {
        throw $e;
      }
    }
  }

  /**
   * Test verify mandatory - includes DAO & passed as well as empty & NULL fields
   */
  public function testVerifyMandatory() {
    _civicrm_api3_initialize(TRUE);
    $params = array(
      'entity_table' => 'civicrm_contact',
      'note' => '',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => NULL,
      'version' => $this->_apiversion,
    );
    try {
      $result = civicrm_api3_verify_mandatory($params, 'CRM_Core_BAO_Note', array('note', 'subject'));
    }
    catch (Exception $expected) {
      $this->assertEquals('Mandatory key(s) missing from params array: entity_id, note, subject', $expected->getMessage());
      return;
    }

    $this->fail('An expected exception has not been raised.');
  }

  /**
   * Test verify one mandatory - includes DAO & passed as well as empty & NULL fields
   */
  public function testVerifyOneMandatory() {
    _civicrm_api3_initialize(TRUE);
    $params = array(
      'entity_table' => 'civicrm_contact',
      'note' => '',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => NULL,
      'version' => $this->_apiversion,
    );

    try {
      $result = civicrm_api3_verify_one_mandatory($params, 'CRM_Core_BAO_Note', array('note', 'subject'));
    }
    catch (Exception $expected) {
      $this->assertEquals('Mandatory key(s) missing from params array: entity_id, one of (note, subject)', $expected->getMessage());
      return;
    }

    $this->fail('An expected exception has not been raised.');
  }

  /**
   * Test verify one mandatory - includes DAO & passed as well as empty & NULL fields
   */
  public function testVerifyOneMandatoryOneSet() {
    _civicrm_api3_initialize(TRUE);
    $params = array(
      'version' => 3,
      'entity_table' => 'civicrm_contact',
      'note' => 'note',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => NULL,
    );

    try {
      civicrm_api3_verify_one_mandatory($params, NULL, array('note', 'subject'));
    }
    catch (Exception$expected) {
      $this->fail('Exception raised when it shouldn\'t have been  in line ' . __LINE__);
    }
  }


  /**
   * Test GET DAO function returns DAO.
   */
  public function testGetDAO() {
    $params = array(
      'civicrm_api3_custom_group_get' => 'CRM_Core_DAO_CustomGroup',
      'custom_group' => 'CRM_Core_DAO_CustomGroup',
      'CustomGroup' => 'CRM_Core_DAO_CustomGroup',
      'civicrm_api3_custom_field_get' => 'CRM_Core_DAO_CustomField',
      'civicrm_api3_survey_get' => 'CRM_Campaign_DAO_Survey',
      'civicrm_api3_pledge_payment_get' => 'CRM_Pledge_DAO_PledgePayment',
      'civicrm_api3_website_get' => 'CRM_Core_DAO_Website',
      'Membership' => 'CRM_Member_DAO_Membership',
    );
    foreach ($params as $input => $expected) {
      $result = _civicrm_api3_get_DAO($input);
      $this->assertEquals($expected, $result);
    }
  }

  /**
   * Test GET BAO function returns BAO when it exists.
   */
  public function testGetBAO() {
    $params = array(
      'civicrm_api3_website_get' => 'CRM_Core_BAO_Website',
      'civicrm_api3_survey_get' => 'CRM_Campaign_BAO_Survey',
      'civicrm_api3_pledge_payment_get' => 'CRM_Pledge_BAO_PledgePayment',
      'Household' => 'CRM_Contact_BAO_Contact',
      // Note this one DOES NOT have a BAO so we expect to fall back on returning the DAO
      'mailing_group' => 'CRM_Mailing_DAO_MailingGroup',
      // Make sure we get null back with nonexistant entities
      'civicrm_this_does_not_exist' => NULL,
    );
    foreach ($params as $input => $expected) {
      $result = _civicrm_api3_get_BAO($input);
      $this->assertEquals($expected, $result);
    }
  }

  public function test_civicrm_api3_validate_fields() {
    $params = array('start_date' => '2010-12-20', 'end_date' => '');
    $fields = civicrm_api3('relationship', 'getfields', array('action' => 'get'));
    _civicrm_api3_validate_fields('relationship', 'get', $params, $fields['values']);
    $this->assertEquals('20101220000000', $params['start_date']);
    $this->assertEquals('', $params['end_date']);
  }

  public function test_civicrm_api3_validate_fields_membership() {
    $params = array(
      'start_date' => '2010-12-20',
      'end_date' => '',
      'membership_end_date' => '0',
      'join_date' => '2010-12-20',
      'membership_start_date' => '2010-12-20',
    );
    $fields = civicrm_api3('Membership', 'getfields', array('action' => 'get'));
    _civicrm_api3_validate_fields('Membership', 'get', $params, $fields['values']);
    $this->assertEquals('2010-12-20', $params['start_date']);
    $this->assertEquals('20101220000000', $params['membership_start_date']);
    $this->assertEquals('', $params['end_date']);
    $this->assertEquals('20101220000000', $params['join_date'], 'join_date not set in line ' . __LINE__);
  }

  public function test_civicrm_api3_validate_fields_event() {

    $params = array(
      'registration_start_date' => 20080601,
      'registration_end_date' => '2008-10-15',
      'start_date' => '2010-12-20',
      'end_date' => '',
    );
    $fields = civicrm_api3('Event', 'getfields', array('action' => 'create'));
    _civicrm_api3_validate_fields('event', 'create', $params, $fields['values']);
    $this->assertEquals('20101220000000', $params['start_date']);
    $this->assertEquals('20081015000000', $params['registration_end_date']);
    $this->assertEquals('', $params['end_date']);
    $this->assertEquals('20080601000000', $params['registration_start_date']);
  }

  public function test_civicrm_api3_validate_fields_exception() {
    $params = array(
      'join_date' => 'abc',
    );
    try {
      $fields = civicrm_api3('Membership', 'getfields', array('action' => 'get'));
      _civicrm_api3_validate_fields('Membership', 'get', $params, $fields['values']);
    }
    catch (Exception$expected) {
      $this->assertEquals('join_date is not a valid date: abc', $expected->getMessage());
    }
  }

  public function testGetFields() {
    $result = $this->callAPISuccess('membership', 'getfields', array());
    $this->assertArrayHasKey('values', $result);
    $result = $this->callAPISuccess('relationship', 'getfields', array());
    $this->assertArrayHasKey('values', $result);
    $result = $this->callAPISuccess('event', 'getfields', array());
    $this->assertArrayHasKey('values', $result);
  }

  public function testGetFields_AllOptions() {
    $result = $this->callAPISuccess('contact', 'getfields', array(
      'options' => array(
        'get_options' => 'all',
      ),
    ));
    $this->assertEquals('Household', $result['values']['contact_type']['options']['Household']);
    $this->assertEquals('HTML', $result['values']['preferred_mail_format']['options']['HTML']);
  }

}
