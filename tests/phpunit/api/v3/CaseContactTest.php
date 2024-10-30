<?php
/**
 *  Test APIv3 civicrm_case_* functions
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_CaseContactTest extends CiviCaseTestCase {

  /**
   * @var array
   */
  protected $params;

  /**
   * @var int
   */
  protected $contactID;

  /**
   * Activity ID of created case.
   *
   * @var array
   */
  protected $case;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp(): void {
    parent::setUp();

    $this->contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate([], 1);

    $this->case = $this->callAPISuccess('case', 'create', [
      'case_type_id' => $this->caseTypeId,
      'subject' => __CLASS__,
      'contact_id' => $this->contactID,
    ]);

    $this->params = [
      'case_id' => $this->case['id'],
      'contact_id' => $contactID2,
    ];
  }

  public function testCaseContactGet(): void {
    $result = $this->callAPISuccess('CaseContact', 'get', [
      'contact_id' => $this->contactID,
    ]);
    $this->assertEquals($this->case['id'], $result['id']);
  }

  /**
   * Test create function with valid parameters.
   */
  public function testCaseContactCreate(): void {
    $params = $this->params;
    $result = $this->callAPISuccess('CaseContact', 'create', $params);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('CaseContact', 'get', ['id' => $id]);
    $this->assertEquals($result['values'][$id]['case_id'], $params['case_id']);
    $this->assertEquals($result['values'][$id]['contact_id'], $params['contact_id']);
  }

}
