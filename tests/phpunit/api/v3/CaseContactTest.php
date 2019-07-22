<?php
/**
 *  Test APIv3 civicrm_case_* functions
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_CaseContactTest extends CiviCaseTestCase {
  protected $_params;
  protected $_entity;
  protected $_cid;
  protected $_cid2;
  /**
   * Activity ID of created case.
   *
   * @var int
   */
  protected $_caseActivityId;

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
    $this->_cid2 = $this->individualCreate([], 1);

    $this->_case = $this->callAPISuccess('case', 'create', [
      'case_type_id' => $this->caseTypeId,
      'subject' => __CLASS__,
      'contact_id' => $this->_cid,
    ]);

    $this->_params = [
      'case_id' => $this->_case['id'],
      'contact_id' => $this->_cid2,
    ];
  }

  public function testCaseContactGet() {
    $result = $this->callAPIAndDocument('CaseContact', 'get', [
      'contact_id' => $this->_cid,
    ], __FUNCTION__, __FILE__);
    $this->assertEquals($this->_case['id'], $result['id']);
  }

  /**
   * Test create function with valid parameters.
   */
  public function testCaseContactCreate() {
    $params = $this->_params;
    $result = $this->callAPIAndDocument('CaseContact', 'create', $params, __FUNCTION__, __FILE__);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('CaseContact', 'get', ['id' => $id]);
    $this->assertEquals($result['values'][$id]['case_id'], $params['case_id']);
    $this->assertEquals($result['values'][$id]['contact_id'], $params['contact_id']);
  }

}
