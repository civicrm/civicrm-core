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

/**
 *  Class api_v3_StatusPreferenceTest
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_StatusPreferenceTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_locationType;
  protected $_params;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = [
      'name' => 'test_check',
      'domain_id' => 1,
      'hush_until' => '20151212',
      'ignore_severity' => 4,
      'check_info' => NULL,
    ];
  }

  /**
   * @dataProvider versionThreeAndFour
   */
  public function testCreateStatusPreference($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $id = $result['id'];
    $this->assertEquals('test_check', $result['values'][$id]['name'], 'In line ' . __LINE__);
    $this->assertEquals(4, $result['values'][$id]['ignore_severity'], 'In line ' . __LINE__);

    $this->callAPISuccess('StatusPreference', 'delete', ['id' => $result['id']]);
  }

  /**
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteStatusPreference($version) {
    $this->_apiversion = $version;
    // create one
    $create = $this->callAPISuccess('StatusPreference', 'create', $this->_params);

    $result = $this->callAPISuccess('StatusPreference', 'delete', ['id' => $create['id']]);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);

    $get = $this->callAPISuccess('StatusPreference', 'get', [
      'id' => $create['id'],
    ]);
    $this->assertEquals(0, $get['count'], 'Status Preference not successfully deleted In line ' . __LINE__);
  }

  /**
   * Test a get with empty params.
   * @dataProvider versionThreeAndFour
   */
  public function testStatusPreferenceGetEmptyParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('StatusPreference', 'Get', []);
  }

  /**
   * Test a StatusPreference get.
   * @dataProvider versionThreeAndFour
   */
  public function testStatusPreferenceGet($version) {
    $this->_apiversion = $version;
    $statusPreference = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $id = $statusPreference['id'];
    $params = [
      'id' => $id,
    ];
    $result = $this->callAPISuccess('StatusPreference', 'Get', $params);
    $this->assertEquals($statusPreference['values'][$id]['name'], $result['values'][$id]['name'], 'In line ' . __LINE__);
    $this->assertEquals($statusPreference['values'][$id]['domain_id'], $result['values'][$id]['domain_id'], 'In line ' . __LINE__);
    $this->assertEquals('2015-12-12', $result['values'][$id]['hush_until'], 'In line ' . __LINE__);
    $this->assertEquals($statusPreference['values'][$id]['ignore_severity'], $result['values'][$id]['ignore_severity'], 'In line ' . __LINE__);
  }

  /**
   * Ensure you can't create a StatusPref with ignore_severity > 7.
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidMinimumReportSeverity($version) {
    $this->_apiversion = $version;
    $this->_params['ignore_severity'] = 45;
    $result = $this->callAPIFailure('StatusPreference', 'create', $this->_params);
  }

  /**
   * Test creating a severity by name, not integer.
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSeverityByName($version) {
    $this->_apiversion = $version;
    // Any permutation of uppercase/lowercase should work.
    $this->_params['ignore_severity'] = 'cRItical';
    $result = $this->callAPISuccess('StatusPreference', 'create', $this->_params);
    $id = $result['id'];
    $this->assertEquals(5, $result['values'][$id]['ignore_severity'], 'In line ' . __LINE__);
  }

  /**
   * Test creating an invalid severity by name.
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSeverityWithInvalidName($version) {
    $this->_apiversion = $version;
    $this->_params['ignore_severity'] = 'wdsadasdarning';
    $result = $this->callAPIFailure('StatusPreference', 'create', $this->_params);
  }

}
