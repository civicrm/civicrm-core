<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

require_once 'CRM/Utils/DeprecatedUtils.php';

/**
 * Test class for API utils
 *
 * @package   CiviCRM
 * @group headless
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
  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testAddFormattedParam() {
    $values = ['contact_type' => 'Individual'];
    $params = ['something' => 1];
    $result = _civicrm_api3_deprecated_add_formatted_param($values, $params);
    $this->assertTrue($result);
  }

  public function testCheckPermissionReturn() {
    $check = ['check_permissions' => TRUE];
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $check), 'empty permissions should not be enough');
    $config->userPermissionClass->permissions = ['access CiviCRM'];
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $check), 'lacking permissions should not be enough');
    $config->userPermissionClass->permissions = ['add contacts'];
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $check), 'lacking permissions should not be enough');

    $config->userPermissionClass->permissions = ['access CiviCRM', 'add contacts'];
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $check), 'exact permissions should be enough');

    $config->userPermissionClass->permissions = ['access CiviCRM', 'add contacts', 'import contacts'];
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $check), 'overfluous permissions should be enough');
  }

  public function testCheckPermissionThrow() {
    $check = ['check_permissions' => TRUE];
    $config = CRM_Core_Config::singleton();
    try {
      $config->userPermissionClass->permissions = ['access CiviCRM'];
      $this->runPermissionCheck('contact', 'create', $check, TRUE);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals($message, 'API permission check failed for Contact/create call; insufficient permission: require access CiviCRM and add contacts', 'lacking permissions should throw an exception');

    $config->userPermissionClass->permissions = ['access CiviCRM', 'add contacts', 'import contacts'];
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $check), 'overfluous permissions should return true');
  }

  public function testCheckPermissionSkip() {
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = ['access CiviCRM'];
    $params = ['check_permissions' => TRUE];
    $this->assertFalse($this->runPermissionCheck('contact', 'create', $params), 'lacking permissions should not be enough');
    $params = ['check_permissions' => FALSE];
    $this->assertTrue($this->runPermissionCheck('contact', 'create', $params), 'permission check should be skippable');
  }

  public function getCamelCaseFuncs() {
    // There have been two slightly different functions for normalizing names;
    // _civicrm_api_get_camel_name() and \Civi\API\Request::normalizeEntityName().
    return [
      // These are the typical cases - where the two have always agreed.
      ['Foo', 'Foo'],
      ['foo', 'Foo'],
      ['FooBar', 'FooBar'],
      ['foo_bar', 'FooBar'],
      ['fooBar', 'FooBar'],
      ['Im', 'Im'],
      ['ACL', 'Acl'],
      ['HTTP', 'HTTP'],

      // These are some atypical cases - where the two have always agreed.
      ['foo__bar', 'FooBar'],
      ['Foo_Bar', 'FooBar'],
      ['one_two_three', 'OneTwoThree'],
      ['oneTwo_three', 'OneTwoThree'],
      ['Got2B', 'Got2B'],
      ['got2_BGood', 'Got2BGood'],

      // These are some atypical cases - where they have traditionally disagreed.
      // _civicrm_api_get_camel_name() has now changed to match normalizeEntityName()
      // because the latter is more defensive.
      ['Foo-Bar', 'FooBar'],
      ['Foo+Bar', 'FooBar'],
      ['Foo.Bar', 'FooBar'],
      ['Foo/../Bar/', 'FooBar'],
      ['./Foo', 'Foo'],
    ];
  }

  /**
   * @param string $inputValue
   *   The user-supplied/untrusted entity name.
   * @param string $expectValue
   *   The normalized/UpperCamelCase entity name.
   * @dataProvider getCamelCaseFuncs
   */
  public function testCamelName($inputValue, $expectValue) {
    $actualValue = _civicrm_api_get_camel_name($inputValue);
    $this->assertEquals($expectValue, $actualValue);
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
    $params['version'] = 3;
    $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\PermissionCheck());
    $kernel = new \Civi\API\Kernel($dispatcher);
    $apiRequest = \Civi\API\Request::create($entity, $action, $params);
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
    $params = [
      'entity_table' => 'civicrm_contact',
      'note' => '',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => NULL,
      'version' => $this->_apiversion,
    ];
    try {
      civicrm_api3_verify_mandatory($params, 'CRM_Core_BAO_Note', ['note', 'subject']);
    }
    catch (Exception $expected) {
      $this->assertEquals('Mandatory key(s) missing from params array: note, subject', $expected->getMessage());
      return;
    }

    $this->fail('An expected exception has not been raised.');
  }

  /**
   * Test verify one mandatory - includes DAO & passed as well as empty & NULL fields
   */
  public function testVerifyOneMandatory() {
    _civicrm_api3_initialize(TRUE);
    $params = [
      'entity_table' => 'civicrm_contact',
      'note' => '',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => NULL,
      'version' => $this->_apiversion,
    ];

    try {
      civicrm_api3_verify_one_mandatory($params, 'CRM_Core_BAO_Note', ['note', 'subject']);
    }
    catch (Exception $expected) {
      $this->assertEquals('Mandatory key(s) missing from params array: one of (note, subject)', $expected->getMessage());
      return;
    }

    $this->fail('An expected exception has not been raised.');
  }

  /**
   * Test verify one mandatory - includes DAO & passed as well as empty & NULL fields
   */
  public function testVerifyOneMandatoryOneSet() {
    _civicrm_api3_initialize(TRUE);
    $params = [
      'version' => 3,
      'entity_table' => 'civicrm_contact',
      'note' => 'note',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => NULL,
    ];

    try {
      civicrm_api3_verify_one_mandatory($params, NULL, ['note', 'subject']);
    }
    catch (Exception$expected) {
      $this->fail('Exception raised when it shouldn\'t have been  in line ' . __LINE__);
    }
  }

  /**
   * Test GET DAO function returns DAO.
   */
  public function testGetDAO() {
    $params = [
      'civicrm_api3_custom_group_get' => 'CRM_Core_DAO_CustomGroup',
      'custom_group' => 'CRM_Core_DAO_CustomGroup',
      'CustomGroup' => 'CRM_Core_DAO_CustomGroup',
      'civicrm_api3_custom_field_get' => 'CRM_Core_DAO_CustomField',
      'civicrm_api3_survey_get' => 'CRM_Campaign_DAO_Survey',
      'civicrm_api3_pledge_payment_get' => 'CRM_Pledge_DAO_PledgePayment',
      'civicrm_api3_website_get' => 'CRM_Core_DAO_Website',
      'Membership' => 'CRM_Member_DAO_Membership',
    ];
    foreach ($params as $input => $expected) {
      $result = _civicrm_api3_get_DAO($input);
      $this->assertEquals($expected, $result);
    }
  }

  /**
   * Test GET BAO function returns BAO when it exists.
   */
  public function testGetBAO() {
    $params = [
      'civicrm_api3_website_get' => 'CRM_Core_BAO_Website',
      'civicrm_api3_survey_get' => 'CRM_Campaign_BAO_Survey',
      'civicrm_api3_pledge_payment_get' => 'CRM_Pledge_BAO_PledgePayment',
      'Household' => 'CRM_Contact_BAO_Contact',
      // Note this one DOES NOT have a BAO so we expect to fall back on returning the DAO
      'mailing_group' => 'CRM_Mailing_DAO_MailingGroup',
      // Make sure we get null back with nonexistant entities
      'civicrm_this_does_not_exist' => NULL,
    ];
    foreach ($params as $input => $expected) {
      $result = _civicrm_api3_get_BAO($input);
      $this->assertEquals($expected, $result);
    }
  }

  /**
   * Test the validate function transforms dates.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function test_civicrm_api3_validate_fields() {
    $params = ['relationship_start_date' => '2010-12-20', 'relationship_end_date' => ''];
    $fields = civicrm_api3('relationship', 'getfields', ['action' => 'get']);
    _civicrm_api3_validate_fields('relationship', 'get', $params, $fields['values']);
    $this->assertEquals('20101220000000', $params['relationship_start_date']);
    $this->assertEquals('', $params['relationship_end_date']);
  }

  public function test_civicrm_api3_validate_fields_membership() {
    $params = [
      'start_date' => '2010-12-20',
      'end_date' => '',
      'membership_end_date' => '0',
      'membership_join_date' => '2010-12-20',
      'membership_start_date' => '2010-12-20',
    ];
    $fields = civicrm_api3('Membership', 'getfields', ['action' => 'get']);
    _civicrm_api3_validate_fields('Membership', 'get', $params, $fields['values']);
    $this->assertEquals('2010-12-20', $params['start_date']);
    $this->assertEquals('20101220000000', $params['membership_start_date']);
    $this->assertEquals('', $params['end_date']);
    $this->assertEquals('20101220000000', $params['membership_join_date'], 'join_date not set in line ' . __LINE__);
  }

  public function test_civicrm_api3_validate_fields_event() {

    $params = [
      'registration_start_date' => 20080601,
      'registration_end_date' => '2008-10-15',
      'start_date' => '2010-12-20',
      'end_date' => '',
    ];
    $fields = civicrm_api3('Event', 'getfields', ['action' => 'create']);
    _civicrm_api3_validate_fields('event', 'create', $params, $fields['values']);
    $this->assertEquals('20101220000000', $params['start_date']);
    $this->assertEquals('20081015000000', $params['registration_end_date']);
    $this->assertEquals('', $params['end_date']);
    $this->assertEquals('20080601000000', $params['registration_start_date']);
  }

  public function test_civicrm_api3_validate_fields_exception() {
    $params = [
      'membership_join_date' => 'abc',
    ];
    try {
      $fields = civicrm_api3('Membership', 'getfields', ['action' => 'get']);
      _civicrm_api3_validate_fields('Membership', 'get', $params, $fields['values']);
    }
    catch (Exception$expected) {
      $this->assertEquals('membership_join_date is not a valid date: abc', $expected->getMessage());
    }
  }

  public function testGetFields() {
    $result = $this->callAPISuccess('membership', 'getfields', []);
    $this->assertArrayHasKey('values', $result);
    $result = $this->callAPISuccess('relationship', 'getfields', []);
    $this->assertArrayHasKey('values', $result);
    $result = $this->callAPISuccess('event', 'getfields', []);
    $this->assertArrayHasKey('values', $result);
  }

  public function testGetFields_AllOptions() {
    $result = $this->callAPISuccess('contact', 'getfields', [
      'options' => [
        'get_options' => 'all',
      ],
    ]);
    $this->assertEquals('Household', $result['values']['contact_type']['options']['Household']);
    $this->assertEquals('HTML', $result['values']['preferred_mail_format']['options']['HTML']);
  }

  public function basicArrayCases() {
    $records = [
      ['snack_id' => 'a', 'fruit' => 'apple', 'cheese' => 'swiss'],
      ['snack_id' => 'b', 'fruit' => 'grape', 'cheese' => 'cheddar'],
      ['snack_id' => 'c', 'fruit' => 'apple', 'cheese' => 'cheddar'],
      ['snack_id' => 'd', 'fruit' => 'apple', 'cheese' => 'gouda'],
      ['snack_id' => 'e', 'fruit' => 'apple', 'cheese' => 'provolone'],
    ];

    $cases[] = [
      $records,
      // params
      ['version' => 3],
      // expected results
      ['a', 'b', 'c', 'd', 'e'],
    ];

    $cases[] = [
      $records,
      // params
      ['version' => 3, 'fruit' => 'apple'],
      // expected results
      ['a', 'c', 'd', 'e'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'cheese' => 'cheddar', 'options' => ['sort' => 'fruit desc']],
      ['b', 'c'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'cheese' => 'cheddar', 'options' => ['sort' => 'fruit']],
      ['c', 'b'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'cheese' => ['IS NOT NULL' => 1], 'options' => ['sort' => 'fruit, cheese']],
      ['c', 'd', 'e', 'a', 'b'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'id' => 'd'],
      ['d'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'fruit' => ['!=' => 'apple']],
      ['b'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'cheese' => ['LIKE' => '%o%']],
      ['d', 'e'],
    ];

    $cases[] = [
      $records,
      ['version' => 3, 'cheese' => ['IN' => ['swiss', 'cheddar', 'gouda']]],
      ['a', 'b', 'c', 'd'],
    ];

    return $cases;
  }

  /**
   * Make a basic API (Widget.get) which allows getting data out of a simple in-memory
   * list of records.
   *
   * @param $records
   *   The list of all records.
   * @param $params
   *   The filter criteria
   * @param array $resultIds
   *   The records which are expected to match.
   * @dataProvider basicArrayCases
   */
  public function testBasicArrayGet($records, $params, $resultIds) {
    $params['version'] = 3;

    $kernel = new \Civi\API\Kernel(new \Symfony\Component\EventDispatcher\EventDispatcher());

    $provider = new \Civi\API\Provider\AdhocProvider($params['version'], 'Widget');
    $provider->addAction('get', 'access CiviCRM', function ($apiRequest) use ($records) {
      return _civicrm_api3_basic_array_get('Widget', $apiRequest['params'], $records, 'snack_id', ['snack_id', 'fruit', 'cheese']);
    });
    $kernel->registerApiProvider($provider);

    $r1 = $kernel->runSafe('Widget', 'get', $params);
    $this->assertEquals(count($resultIds), $r1['count']);
    $this->assertEquals($resultIds, array_keys($r1['values']));
    $this->assertEquals($resultIds, array_values(CRM_Utils_Array::collect('snack_id', $r1['values'])));
    $this->assertEquals($resultIds, array_values(CRM_Utils_Array::collect('id', $r1['values'])));

    $r2 = $kernel->runSafe('Widget', 'get', $params + ['sequential' => 1]);
    $this->assertEquals(count($resultIds), $r2['count']);
    $this->assertEquals($resultIds, array_values(CRM_Utils_Array::collect('snack_id', $r2['values'])));
    $this->assertEquals($resultIds, array_values(CRM_Utils_Array::collect('id', $r2['values'])));

    $params['options']['offset'] = 1;
    $params['options']['limit'] = 2;
    $r3 = $kernel->runSafe('Widget', 'get', $params);
    $slice = array_slice($resultIds, 1, 2);
    $this->assertEquals(count($slice), $r3['count']);
    $this->assertEquals($slice, array_values(CRM_Utils_Array::collect('snack_id', $r3['values'])));
    $this->assertEquals($slice, array_values(CRM_Utils_Array::collect('id', $r3['values'])));
  }

  public function testBasicArrayGetReturn() {
    $records = [
      ['snack_id' => 'a', 'fruit' => 'apple', 'cheese' => 'swiss'],
      ['snack_id' => 'b', 'fruit' => 'grape', 'cheese' => 'cheddar'],
      ['snack_id' => 'c', 'fruit' => 'apple', 'cheese' => 'cheddar'],
    ];

    $kernel = new \Civi\API\Kernel(new \Symfony\Component\EventDispatcher\EventDispatcher());
    $provider = new \Civi\API\Provider\AdhocProvider(3, 'Widget');
    $provider->addAction('get', 'access CiviCRM', function ($apiRequest) use ($records) {
      return _civicrm_api3_basic_array_get('Widget', $apiRequest['params'], $records, 'snack_id', ['snack_id', 'fruit', 'cheese']);
    });
    $kernel->registerApiProvider($provider);

    $r1 = $kernel->runSafe('Widget', 'get', [
      'version' => 3,
      'snack_id' => 'b',
      'return' => 'fruit',
    ]);
    $this->assertAPISuccess($r1);
    $this->assertEquals(['b' => ['id' => 'b', 'fruit' => 'grape']], $r1['values']);

    $r2 = $kernel->runSafe('Widget', 'get', [
      'version' => 3,
      'snack_id' => 'b',
      'return' => ['fruit', 'cheese'],
    ]);
    $this->assertAPISuccess($r2);
    $this->assertEquals(['b' => ['id' => 'b', 'fruit' => 'grape', 'cheese' => 'cheddar']], $r2['values']);

    $r3 = $kernel->runSafe('Widget', 'get', [
      'version' => 3,
      'cheese' => 'cheddar',
      'return' => ['fruit'],
    ]);
    $this->assertAPISuccess($r3);
    $this->assertEquals([
      'b' => ['id' => 'b', 'fruit' => 'grape'],
      'c' => ['id' => 'c', 'fruit' => 'apple'],
    ], $r3['values']);
  }

  /**
   * CRM-20892 Add Tests of new timestamp checking function
   *
   * @throws \CRM_Core_Exception
   */
  public function testTimeStampChecking() {
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing (id, modified_date) VALUES (25, '2016-06-30 12:52:52')");
    $this->assertTrue(_civicrm_api3_compare_timestamps('2017-02-15 16:00:00', 25, 'Mailing'));
    $this->callAPISuccess('Mailing', 'create', ['id' => 25, 'subject' => 'Test Subject']);
    $this->assertFalse(_civicrm_api3_compare_timestamps('2017-02-15 16:00:00', 25, 'Mailing'));
    $this->callAPISuccess('Mailing', 'delete', ['id' => 25]);
  }

  /**
   * Test that the foreign key constraint test correctly interprets pseudoconstants.
   *
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public function testKeyConstraintCheck() {
    $fieldInfo = $this->callAPISuccess('Contribution', 'getfields', [])['values']['financial_type_id'];
    _civicrm_api3_validate_constraint(1, 'financial_type_id', $fieldInfo, 'Contribution');
    _civicrm_api3_validate_constraint('Donation', 'financial_type_id', $fieldInfo, 'Contribution');
    try {
      _civicrm_api3_validate_constraint('Blah', 'financial_type_id', $fieldInfo, 'Contribution');
    }
    catch (API_Exception $e) {
      $this->assertEquals("'Blah' is not a valid option for field financial_type_id", $e->getMessage());
      return;
    }
    $this->fail('Last function call should have thrown an exception');
  }

}
