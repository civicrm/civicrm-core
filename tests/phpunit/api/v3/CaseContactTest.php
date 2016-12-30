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

    $this->_case = $this->callAPISuccess('case', 'create', array(
      'case_type_id' => $this->caseTypeId,
      'subject' => __CLASS__,
      'contact_id' => $this->_cid,
    ));
  }

  public function testCaseContactGet() {
    $result = $this->callAPIAndDocument('CaseContact', 'get', array(
      'contact_id' => $this->_cid,
    ), __FUNCTION__, __FILE__);
    $this->assertEquals($this->_case['id'], $result['id']);
  }

}
