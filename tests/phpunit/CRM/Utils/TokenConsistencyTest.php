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
 * CRM_Utils_TokenConsistencyTest
 *
 * Class for ensuring tokens have internal consistency.
 *
 * @group Tokens
 *
 * @group headless
 */
class CRM_Utils_TokenConsistencyTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Created case.
   *
   * @var array
   */
  protected $case;

  /**
   * Post test cleanup.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_case', 'civicrm_case_type']);
    parent::tearDown();
  }

  /**
   * Test that case tokens are consistently rendered.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCaseTokenConsistency(): void {
    $this->createLoggedInUser();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->createCustomGroupWithFieldOfType(['extends' => 'Case']);
    $tokens = CRM_Core_SelectValues::caseTokens();
    $this->assertEquals($this->getCaseTokens(), $tokens);
    $caseID = $this->getCaseID();
    $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseID, implode("\n", array_keys($this->getCaseTokens())), ['case' => $this->getCaseTokenKeys()]);
    $this->assertEquals($this->getExpectedCaseTokenOutput(), $tokenHtml);
    // Now do the same without passing in 'knownTokens'
    $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseID, implode("\n", array_keys($this->getCaseTokens())));
    $this->assertEquals($this->getExpectedCaseTokenOutput(), $tokenHtml);
  }

  /**
   * Get expected output from token parsing.
   *
   * @return string
   */
  protected function getExpectedCaseTokenOutput(): string {
    return '1
Housing Support
Case Subject
July 23rd, 2021
July 26th, 2021
case details
Ongoing
No
' . $this->case['created_date'] . '
' . $this->case['modified_date'] . '
';
  }

  /**
   * @return int
   */
  protected function getContactID(): int {
    if (!isset($this->ids['Contact'][0])) {
      $this->ids['Contact'][0] = $this->individualCreate();
    }
    return $this->ids['Contact'][0];
  }

  /**
   * Get the keys for the case tokens.
   *
   * @return array
   */
  public function getCaseTokenKeys(): array {
    $return = [];
    foreach (array_keys($this->getCaseTokens()) as $key) {
      $return[] = substr($key, 6, -1);
    }
    return $return;
  }

  /**
   * Get declared tokens.
   *
   * @return string[]
   */
  public function getCaseTokens(): array {
    return [
      '{case.id}' => 'Case ID',
      '{case.case_type_id}' => 'Case Type ID',
      '{case.subject}' => 'Case Subject',
      '{case.start_date}' => 'Case Start Date',
      '{case.end_date}' => 'Case End Date',
      '{case.details}' => 'Details',
      '{case.status_id}' => 'Case Status',
      '{case.is_deleted}' => 'Case is in the Trash',
      '{case.created_date}' => 'Created Date',
      '{case.modified_date}' => 'Modified Date',
      '{case.custom_1}' => 'Enter text here :: Group with field text',
    ];
  }

  /**
   * Get case ID.
   *
   * @return int
   */
  protected function getCaseID(): int {
    if (!isset($this->case)) {
      $this->case = $this->callAPISuccess('Case', 'create', [
        'case_type_id' => 'housing_support',
        'activity_subject' => 'Case Subject',
        'client_id' => $this->getContactID(),
        'status_id' => 1,
        'subject' => 'Case Subject',
        'start_date' => '2021-07-23 15:39:20',
        'end_date' => '2021-07-26 18:07:20',
        'medium_id' => 2,
        'details' => 'case details',
        'activity_details' => 'blah blah',
        'sequential' => 1,
      ])['values'][0];
    }
    return $this->case['id'];
  }

}
