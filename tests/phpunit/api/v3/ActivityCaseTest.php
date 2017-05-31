<?php

/**
 *  Test Activity.get API with the case_id field
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_ActivityCaseTest extends CiviCaseTestCase {
  protected $_params;
  protected $_entity;
  protected $_cid;

  /**
   * @var array
   *  APIv3 Result (Case.create)
   */
  protected $_case;

  /**
   * @var array
   *  APIv3 Result (Activity.create)
   */
  protected $_otherActivity;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp() {
    $this->_entity = 'case';

    parent::setUp();

    $this->_cid = $this->individualCreate();

    $this->_case = $this->callAPISuccess('case', 'create', array(
      'case_type_id' => $this->caseTypeId,
      'subject' => __CLASS__,
      'contact_id' => $this->_cid,
    ));

    $this->_otherActivity = $this->callAPISuccess('Activity', 'create', array(
      'source_contact_id' => $this->_cid,
      'activity_type_id' => 'Phone Call',
      'subject' => 'Ask not what your API can do for you, but what you can do for your API.',
    ));
  }

  public function testGet() {
    $this->assertTrue(is_numeric($this->_case['id']));
    $this->assertTrue(is_numeric($this->_otherActivity['id']));

    $getByCaseId = $this->callAPIAndDocument('Activity', 'get', array(
      'case_id' => $this->_case['id'],
    ), __FUNCTION__, __FILE__);
    $this->assertNotEmpty($getByCaseId['values']);
    $getByCaseId_ids = array_keys($getByCaseId['values']);

    $getByCaseNotNull = $this->callAPIAndDocument('Activity', 'get', array(
      'case_id' => array('IS NOT NULL' => 1),
    ), __FUNCTION__, __FILE__);
    $this->assertNotEmpty($getByCaseNotNull['values']);
    $getByCaseNotNull_ids = array_keys($getByCaseNotNull['values']);

    $getByCaseNull = $this->callAPIAndDocument('Activity', 'get', array(
      'case_id' => array('IS NULL' => 1),
    ), __FUNCTION__, __FILE__);
    $this->assertNotEmpty($getByCaseNull['values']);
    $getByCaseNull_ids = array_keys($getByCaseNull['values']);

    $this->assertTrue(in_array($this->_otherActivity['id'], $getByCaseNull_ids));
    $this->assertNotTrue(in_array($this->_otherActivity['id'], $getByCaseId_ids));
    $this->assertEquals($getByCaseId_ids, $getByCaseNotNull_ids);
    $this->assertEquals(array(), array_intersect($getByCaseId_ids, $getByCaseNull_ids));
  }

}
