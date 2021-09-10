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

use Civi\Token\TokenProcessor;

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
   * Recurring contribution.
   *
   * @var array
   */
  protected $contributionRecur;

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

    // And check our deprecated tokens still work.
    $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseID, '{case.case_type_id} {case.status_id}');
    $this->assertEquals('Housing Support Ongoing', $tokenHtml);
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
      '{case.case_type_id:label}' => 'Case Type',
      '{case.subject}' => 'Case Subject',
      '{case.start_date}' => 'Case Start Date',
      '{case.end_date}' => 'Case End Date',
      '{case.details}' => 'Details',
      '{case.status_id:label}' => 'Case Status',
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
      $case_id = $this->callAPISuccess('Case', 'create', [
        'case_type_id' => 'housing_support',
        'activity_subject' => 'Case Subject',
        'client_id' => $this->getContactID(),
        'status_id' => 1,
        'subject' => 'Case Subject',
        'start_date' => '2021-07-23 15:39:20',
        // Note end_date is inconsistent with status Ongoing but for the
        // purposes of testing tokens is ok. Creating it with status Resolved
        // then ignores our known fixed end date.
        'end_date' => '2021-07-26 18:07:20',
        'medium_id' => 2,
        'details' => 'case details',
        'activity_details' => 'blah blah',
        'sequential' => 1,
      ])['id'];
      // Need to retrieve the case again because modified date might be updated a
      // split-second later than the original return value because of activity
      // triggers when the timeline is populated. The returned array from create
      // is determined before that happens.
      $this->case = $this->callAPISuccess('Case', 'getsingle', ['id' => $case_id]);
    }
    return $this->case['id'];
  }

  /**
   * Test that contribution recur tokens are consistently rendered.
   */
  public function testContributionRecurTokenConsistency(): void {
    $this->createLoggedInUser();
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contribution_recurId'],
    ]);
    $this->assertEquals($this->getContributionRecurTokens(), $tokenProcessor->listTokens());
    $tokenString = implode("\n", array_keys($this->getContributionRecurTokens()));

    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');
    $tokenProcessor->addRow(['contribution_recurId' => $this->getContributionRecurID()]);
    $tokenProcessor->evaluate();
    $this->assertEquals($this->getExpectedContributionRecurTokenOutPut(), $tokenProcessor->getRow(0)->render('html'));
  }

  /**
   * Get the contribution recur tokens keyed by the token.
   *
   * e.g {contribution_recur.id}
   *
   * @return array
   */
  protected function getContributionRecurTokens(): array {
    $return = [];
    foreach ($this->getContributionRecurTokensByField() as $key => $value) {
      $return['{contribution_recur.' . $key . '}'] = $value;
    }
    return $return;
  }

  protected function getContributionRecurTokensByField(): array {
    return [
      'id' => 'Recurring Contribution ID',
      'amount' => 'Amount',
      'currency' => 'Currency',
      'frequency_unit' => 'Frequency Unit',
      'frequency_interval' => 'Interval (number of units)',
      'installments' => 'Number of Installments',
      'start_date' => 'Start Date',
      'create_date' => 'Created Date',
      'modified_date' => 'Modified Date',
      'cancel_date' => 'Cancel Date',
      'cancel_reason' => 'Cancellation Reason',
      'end_date' => 'Recurring Contribution End Date',
      'processor_id' => 'Processor ID',
      'payment_token_id' => 'Payment Token ID',
      'trxn_id' => 'Transaction ID',
      'invoice_id' => 'Invoice ID',
      'contribution_status_id' => 'Status',
      'is_test:label' => 'Test',
      'cycle_day' => 'Cycle Day',
      'next_sched_contribution_date' => 'Next Scheduled Contribution Date',
      'failure_count' => 'Number of Failures',
      'failure_retry_date' => 'Retry Failed Attempt Date',
      'auto_renew:label' => 'Auto Renew',
      'payment_processor_id' => 'Payment Processor ID',
      'financial_type_id' => 'Financial Type ID',
      'payment_instrument_id' => 'Payment Method',
      'is_email_receipt:label' => 'Send email Receipt?',
      'frequency_unit:label' => 'Frequency Unit',
      'frequency_unit:name' => 'Machine name: Frequency Unit',
      'contribution_status_id:label' => 'Status',
      'contribution_status_id:name' => 'Machine name: Status',
      'payment_processor_id:label' => 'Payment Processor',
      'payment_processor_id:name' => 'Machine name: Payment Processor',
      'financial_type_id:label' => 'Financial Type',
      'financial_type_id:name' => 'Machine name: Financial Type',
      'payment_instrument_id:label' => 'Payment Method',
      'payment_instrument_id:name' => 'Machine name: Payment Method',
    ];
  }

  /**
   * Get contributionRecur ID.
   *
   * @return int
   */
  protected function getContributionRecurID(): int {
    if (!isset($this->contributionRecur)) {
      $paymentProcessorID = $this->processorCreate();
      $this->contributionRecur = $this->callAPISuccess('ContributionRecur', 'create', [
        'contact_id' => $this->getContactID(),
        'status_id' => 1,
        'is_email_receipt' => 1,
        'start_date' => '2021-07-23 15:39:20',
        'end_date' => '2021-07-26 18:07:20',
        'cancel_date' => '2021-08-19 09:12:45',
        'cancel_reason' => 'Because',
        'amount' => 5990.99,
        'currency' => 'EUR',
        'frequency_unit' => 'year',
        'frequency_interval' => 2,
        'installments' => 24,
        'payment_instrument_id' => 'Check',
        'financial_type_id' => 'Member dues',
        'processor_id' => 'abc',
        'payment_processor_id' => $paymentProcessorID,
        'trxn_id' => 123,
        'invoice_id' => 'inv123',
        'sequential' => 1,
        'failure_retry_date' => '2020-01-03',
        'auto_renew' => 1,
        'cycle_day' => '15',
        'is_test' => TRUE,
        'payment_token_id' => $this->callAPISuccess('PaymentToken', 'create', [
          'contact_id' => $this->getContactID(),
          'token' => 456,
          'payment_processor_id' => $paymentProcessorID,
        ])['id'],
      ])['values'][0];
    }
    return $this->contributionRecur['id'];
  }

  /**
   * Get rendered output for contribution tokens.
   *
   * @return string
   */
  protected function getExpectedContributionRecurTokenOutPut(): string {
    return $this->getContributionRecurID() . '
€ 5,990.99
EUR
year
2
24
July 23rd, 2021  3:39 PM
' . CRM_Utils_Date::customFormat($this->contributionRecur['create_date']) . '
' . CRM_Utils_Date::customFormat($this->contributionRecur['modified_date']) . '
August 19th, 2021  9:12 AM
Because
July 26th, 2021  6:07 PM
abc
1
123
inv123
2
Yes
15

0
January 3rd, 2020 12:00 AM
Yes
1
2
4
Yes
year
year
Pending Label**
Pending
Dummy (test)
Dummy (test)
Member Dues
Member Dues
Check
Check';
  }

}
