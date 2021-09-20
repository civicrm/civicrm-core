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
    $this->quickCleanup(['civicrm_case', 'civicrm_case_type'], TRUE);
    parent::tearDown();
  }

  /**
   * Test that case tokens are consistently rendered.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testCaseTokenConsistency(): void {
    $this->createLoggedInUser();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->createCustomGroupWithFieldOfType(['extends' => 'Case']);
    $tokens = CRM_Core_SelectValues::caseTokens();
    $this->assertEquals($this->getCaseTokens(), $tokens);
    $caseID = $this->getCaseID();
    $tokenString = implode("\n", array_keys($this->getCaseTokens()));
    $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseID, $tokenString, ['case' => $this->getCaseTokenKeys()]);
    $this->assertEquals($this->getExpectedCaseTokenOutput(), $tokenHtml);
    // Now do the same without passing in 'knownTokens'
    $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseID, $tokenString);
    $this->assertEquals($this->getExpectedCaseTokenOutput(), $tokenHtml);

    // And check our deprecated tokens still work.
    $tokenHtml = CRM_Utils_Token::replaceCaseTokens($caseID, '{case.case_type_id} {case.status_id}');
    $this->assertEquals('Housing Support Ongoing', $tokenHtml);

    $additionalTokensFromProcessor = [
      '{case.case_type_id}' => 'Case Type ID',
      '{case.status_id}' => 'Case Status',
      '{case.case_type_id:name}' => 'Machine name: Case Type',
      '{case.status_id:name}' => 'Machine name: Case Status',
    ];
    $expectedTokens = array_merge($this->getCaseTokens(), $additionalTokensFromProcessor);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['caseId'],
    ]);
    $this->assertEquals(array_merge($expectedTokens, $this->getDomainTokens()), $tokenProcessor->listTokens());
    $tokenProcessor->addRow([
      'caseId' => $this->getCaseID(),
    ]);
    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');

    $tokenProcessor->evaluate();
    foreach ($tokenProcessor->getRows() as $row) {
      $text = $row->render('html');
    }
    $this->assertEquals($this->getExpectedCaseTokenOutput(), $text);
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
' . CRM_Utils_Date::customFormat($this->case['created_date']) . '
' . CRM_Utils_Date::customFormat($this->case['modified_date']) . '
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
      '{case.is_deleted:label}' => 'Case is in the Trash',
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
    $this->assertEquals(array_merge($this->getContributionRecurTokens(), $this->getDomainTokens()), $tokenProcessor->listTokens());
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

  /**
   * Test that membership tokens are consistently rendered.
   *
   * @throws \API_Exception
   */
  public function testMembershipTokenConsistency(): void {
    $this->createLoggedInUser();
    $this->restoreMembershipTypes();
    $this->createCustomGroupWithFieldOfType(['extends' => 'Membership']);
    $tokens = CRM_Core_SelectValues::membershipTokens();
    $expectedTokens = $this->getMembershipTokens();
    $this->assertEquals($expectedTokens, $tokens);
    $newStyleTokens = "\n{membership.status_id:label}\n{membership.membership_type_id:label}\n";
    $tokenString = $newStyleTokens . implode("\n", array_keys($this->getMembershipTokens()));

    $memberships = CRM_Utils_Token::getMembershipTokenDetails([$this->getMembershipID()]);
    $messageToken = CRM_Utils_Token::getTokens($tokenString);
    $tokenHtml = CRM_Utils_Token::replaceEntityTokens('membership', $memberships[$this->getMembershipID()], $tokenString, $messageToken);
    $this->assertEquals($this->getExpectedMembershipTokenOutput(), $tokenHtml);

    // Custom fields work in the processor so test it....
    $tokenString .= "\n{membership." . $this->getCustomFieldName('text') . '}';
    // Now compare with scheduled reminder
    $mut = new CiviMailUtils($this);
    CRM_Utils_Time::setTime('2007-01-22 15:00:00');
    $this->callAPISuccess('action_schedule', 'create', [
      'title' => 'job',
      'subject' => 'job',
      'entity_value' => 1,
      'mapping_id' => 4,
      'start_action_date' => 'membership_join_date',
      'start_action_offset' => 1,
      'start_action_condition' => 'after',
      'start_action_unit' => 'day',
      'body_html' => $tokenString,
    ]);
    $this->callAPISuccess('job', 'send_reminder', []);
    $expected = $this->getExpectedMembershipTokenOutput();
    // Unlike the legacy method custom fields are resolved by the processor.
    $expected .= "\nmy field";
    $mut->checkMailLog([$expected]);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['membershipId'],
    ]);
    $tokens = $tokenProcessor->listTokens();
    // Add in custom tokens as token processor supports these.
    $expectedTokens['{membership.custom_1}'] = 'Enter text here :: Group with field text';
    $this->assertEquals(array_merge($expectedTokens, $this->getDomainTokens()), $tokens);
    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');
    $tokenProcessor->addRow(['membershipId' => $this->getMembershipID()]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expected, $tokenProcessor->getRow(0)->render('html'));

  }

  /**
   * Get declared membership tokens.
   *
   * @return string[]
   */
  public function getMembershipTokens(): array {
    return [
      '{membership.id}' => 'Membership ID',
      '{membership.status_id:label}' => 'Membership Status',
      '{membership.membership_type_id:label}' => 'Membership Type',
      '{membership.start_date}' => 'Membership Start Date',
      '{membership.join_date}' => 'Membership Join Date',
      '{membership.end_date}' => 'Membership End Date',
      '{membership.fee}' => 'Membership Fee',
    ];
  }

  /**
   * Get case ID.
   *
   * @return int
   */
  protected function getMembershipID(): int {
    if (!isset($this->ids['Membership'][0])) {
      $this->ids['Membership'][0] = $this->contactMembershipCreate([
        'contact_id' => $this->getContactID(),
        $this->getCustomFieldName('text') => 'my field',
      ]);
    }
    return $this->ids['Membership'][0];
  }

  /**
   * Get expected output from token parsing.
   *
   * @return string
   */
  protected function getExpectedMembershipTokenOutput(): string {
    return '
Expired
General
1
Expired
General
January 21st, 2007
January 21st, 2007
December 21st, 2007
100.00';
  }

  /**
   * Test that membership tokens are consistently rendered.
   *
   * @throws \API_Exception
   */
  public function testParticipantTokenConsistency(): void {
    $this->createLoggedInUser();
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant']);
    $tokens = CRM_Core_SelectValues::participantTokens();
    $this->assertEquals($this->getParticipantTokens(), $tokens);
  }

  /**
   * Get declared participant tokens.
   *
   * @return string[]
   */
  public function getParticipantTokens(): array {
    return [
      '{participant.participant_status_id}' => 'Status ID',
      '{participant.participant_role_id}' => 'Participant Role (ID)',
      '{participant.participant_register_date}' => 'Register date',
      '{participant.participant_source}' => 'Participant Source',
      '{participant.participant_fee_level}' => 'Fee level',
      '{participant.participant_fee_amount}' => 'Fee Amount',
      '{participant.participant_registered_by_id}' => 'Registered By Participant ID',
      '{participant.transferred_to_contact_id}' => 'Transferred to Contact ID',
      '{participant.participant_role}' => 'Participant Role (label)',
      '{participant.fee_label}' => 'Fee Label',
      '{participant.default_role_id}' => 'Default Role',
      '{participant.template_title}' => 'Event Template Title',
      '{participant.' . $this->getCustomFieldName('text') . '}' => 'Enter text here :: Group with field text',
    ];
  }

  /**
   * Test that domain tokens are consistently rendered.
   */
  public function testDomainTokenConsistency(): void {
    $tokens = CRM_Core_SelectValues::domainTokens();
    $this->assertEquals($this->getDomainTokens(), $tokens);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $tokens['{domain.id}'] = 'Domain ID';
    $tokens['{domain.description}'] = 'Domain Description';
    $this->assertEquals($tokens, $tokenProcessor->listTokens());
  }

  /**
   * Get declared participant tokens.
   *
   * @return string[]
   */
  public function getDomainTokens(): array {
    return [
      '{domain.name}' => ts('Domain name'),
      '{domain.address}' => ts('Domain (organization) address'),
      '{domain.phone}' => ts('Domain (organization) phone'),
      '{domain.email}' => 'Domain (organization) email',
      '{domain.id}' => ts('Domain ID'),
      '{domain.description}' => ts('Domain Description'),
    ];
  }

  /**
   * Test that domain tokens are consistently rendered.
   */
  public function testEventTokenConsistency(): void {
    $tokens = CRM_Core_SelectValues::eventTokens();
    $this->assertEquals($this->getEventTokens(), $tokens);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['eventId'],
    ]);
    $this->assertEquals(array_merge($tokens, $this->getDomainTokens()), $tokenProcessor->listTokens());
  }

  /**
   * Get expected event tokens.
   *
   * @return string[]
   */
  protected function getEventTokens(): array {
    return [
      '{event.event_id}' => 'Event ID',
      '{event.title}' => 'Event Title',
      '{event.start_date}' => 'Event Start Date',
      '{event.end_date}' => 'Event End Date',
      '{event.event_type}' => 'Event Type',
      '{event.summary}' => 'Event Summary',
      '{event.contact_email}' => 'Event Contact Email',
      '{event.contact_phone}' => 'Event Contact Phone',
      '{event.description}' => 'Event Description',
      '{event.location}' => 'Event Location',
      '{event.fee_amount}' => 'Event Fee',
      '{event.info_url}' => 'Event Info URL',
      '{event.registration_url}' => 'Event Registration URL',
      '{event.balance}' => 'Event Balance',
    ];
  }

}
