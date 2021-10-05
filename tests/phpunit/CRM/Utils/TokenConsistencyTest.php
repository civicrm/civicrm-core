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
use Civi\Api4\LocBlock;
use Civi\Api4\Email;
use Civi\Api4\Phone;
use Civi\Api4\Address;

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
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_case', 'civicrm_case_type', 'civicrm_participant', 'civicrm_event'], TRUE);
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
    $tokenString = $this->getTokenString(array_keys($this->getCaseTokens()));
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
    return 'case.id :1
case.case_type_id:label :Housing Support
case.subject :Case Subject
case.start_date :July 23rd, 2021
case.end_date :July 26th, 2021
case.details :case details
case.status_id:label :Ongoing
case.is_deleted:label :No
case.created_date :' . CRM_Utils_Date::customFormat($this->case['created_date']) . '
case.modified_date :' . CRM_Utils_Date::customFormat($this->case['modified_date']) . '
case.custom_1 :' . '
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
    $tokenString = $this->getTokenString(array_keys($this->getContributionRecurTokens()));

    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');
    $tokenProcessor->addRow(['contribution_recurId' => $this->getContributionRecurID()]);
    $tokenProcessor->evaluate();
    $this->assertEquals($this->getExpectedContributionRecurTokenOutPut(), $tokenProcessor->getRow(0)->render('html'));
  }

  /**
   * Get tokens that are not advertised via listTokens.
   *
   * @return string[]
   */
  public function getUnadvertisedTokens(): array {
    return [
      '{membership.status_id}' => 'Status ID',
      '{membership.membership_type_id}' => 'Membership Type ID',
      '{membership.status_id:name}' => 'Machine name: Status',
      '{membership.membership_type_id:name}' => 'Machine name: Membership Type',
    ];
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
        'next_sched_contribution_date' => '2021-09-08',
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
    return 'contribution_recur.id :' . $this->getContributionRecurID() . '
contribution_recur.amount :€ 5,990.99
contribution_recur.currency :EUR
contribution_recur.frequency_unit :year
contribution_recur.frequency_interval :2
contribution_recur.installments :24
contribution_recur.start_date :July 23rd, 2021  3:39 PM
contribution_recur.create_date :' . CRM_Utils_Date::customFormat($this->contributionRecur['create_date']) . '
contribution_recur.modified_date :' . CRM_Utils_Date::customFormat($this->contributionRecur['modified_date']) . '
contribution_recur.cancel_date :August 19th, 2021  9:12 AM
contribution_recur.cancel_reason :Because
contribution_recur.end_date :July 26th, 2021  6:07 PM
contribution_recur.processor_id :abc
contribution_recur.payment_token_id :1
contribution_recur.trxn_id :123
contribution_recur.invoice_id :inv123
contribution_recur.contribution_status_id :2
contribution_recur.is_test:label :Yes
contribution_recur.cycle_day :15
contribution_recur.next_sched_contribution_date :September 8th, 2021
contribution_recur.failure_count :0
contribution_recur.failure_retry_date :January 3rd, 2020
contribution_recur.auto_renew:label :Yes
contribution_recur.payment_processor_id :1
contribution_recur.financial_type_id :2
contribution_recur.payment_instrument_id :4
contribution_recur.is_email_receipt:label :Yes
contribution_recur.frequency_unit:label :year
contribution_recur.frequency_unit:name :year
contribution_recur.contribution_status_id:label :Pending Label**
contribution_recur.contribution_status_id:name :Pending
contribution_recur.payment_processor_id:label :Dummy (test)
contribution_recur.payment_processor_id:name :Dummy (test)
contribution_recur.financial_type_id:label :Member Dues
contribution_recur.financial_type_id:name :Member Dues
contribution_recur.payment_instrument_id:label :Check
contribution_recur.payment_instrument_id:name :Check
';

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
    // Add in unadvertised tokens - for now they are included.
    $expectedTokens = array_merge($expectedTokens, $this->getUnadvertisedTokens());
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
      '{membership.status_id:label}' => 'Status',
      '{membership.membership_type_id:label}' => 'Membership Type',
      '{membership.start_date}' => 'Membership Start Date',
      '{membership.join_date}' => 'Member Since',
      '{membership.end_date}' => 'Membership Expiration Date',
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
  protected function getExpectedParticipantTokenOutput(): string {
    return 'participant.status_id :2
participant.role_id :1
participant.register_date :February 19th, 2007
participant.source :Wimbeldon
participant.fee_level :steep
participant.fee_amount :$ 50.00
participant.registered_by_id :
participant.transferred_to_contact_id :
participant.role_id:label :Attendee
participant.balance :
participant.custom_2 :99999
participant.id :2
participant.fee_currency :USD
participant.discount_amount :
participant.status_id:label :Attended
participant.status_id:name :Attended
participant.role_id:name :Attendee
participant.is_test:label :No
participant.must_wait :
';
  }

  /**
   * Get expected output from token parsing.
   *
   * @return string
   */
  protected function getExpectedEventTokenOutput(): string {
    return 'event.id :' . $this->ids['event'][0] . '
event.title :Annual CiviCRM meet
event.start_date :October 21st, 2008
event.end_date :October 23rd, 2008
event.event_type_id:label :Conference
event.summary :If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now
event.contact_email :event@example.com
event.contact_phone :456 789
event.description :event description
event.location :15 Walton St
Emerald City, Maine 90210

event.info_url :' . CRM_Utils_System::url('civicrm/event/info', NULL, TRUE) . '&reset=1&id=1
event.registration_url :' . CRM_Utils_System::url('civicrm/event/register', NULL, TRUE) . '&reset=1&id=1
event.custom_1 :my field
';
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
    $this->setupParticipantScheduledReminder();

    $tokens = CRM_Core_SelectValues::participantTokens();
    $this->assertEquals($this->getParticipantTokens(), $tokens);

    $mut = new CiviMailUtils($this);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['participantId'],
    ]);
    $this->assertEquals(array_merge($tokens, $this->getDomainTokens()), $tokenProcessor->listTokens());

    $this->callAPISuccess('job', 'send_reminder', []);
    $expected = $this->getExpectedParticipantTokenOutput();
    $mut->checkMailLog([$expected]);

    $tokenProcessor->addMessage('html', $this->getTokenString(array_keys($this->getParticipantTokens())), 'text/plain');
    $tokenProcessor->addRow(['participantId' => $this->ids['participant'][0]]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expected, $tokenProcessor->getRow(0)->render('html'));

  }

  /**
   * Test that membership tokens are consistently rendered.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testParticipantCustomDateToken(): void {
    $this->createEventAndParticipant();
    $dateFieldID = $this->createDateCustomField(['custom_group_id' => $this->ids['CustomGroup']['participant_'], 'default_value' => ''])['id'];
    $input = '{participant.custom_' . $dateFieldID . '}';
    $input .= '{participant.' . $this->getCustomFieldName('participant_int') . '}';
    $tokenHtml = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'messageTemplate' => ['msg_html' => $input],
      'tokenContext' => array_merge(['participantId' => $this->ids['participant'][0]], ['schema' => ['participantId', 'eventId']]),
    ])['html'];
    $this->assertEquals(99999, $tokenHtml);
  }

  /**
   * Get declared participant tokens.
   *
   * @return string[]
   */
  public function getParticipantTokens(): array {
    return [
      '{participant.status_id}' => 'Status ID',
      '{participant.role_id}' => 'Participant Role ID',
      '{participant.register_date}' => 'Register date',
      '{participant.source}' => 'Participant Source',
      '{participant.fee_level}' => 'Fee level',
      '{participant.fee_amount}' => 'Fee Amount',
      '{participant.registered_by_id}' => 'Registered By Participant ID',
      '{participant.transferred_to_contact_id}' => 'Transferred to Contact ID',
      '{participant.role_id:label}' => 'Participant Role',
      '{participant.balance}' => 'Event Balance',
      '{participant.' . $this->getCustomFieldName('participant_int') . '}' => 'Enter integer here :: participant_Group with field int',
      '{participant.id}' => 'Participant ID',
      '{participant.fee_currency}' => 'Fee Currency',
      '{participant.discount_amount}' => 'Discount Amount',
      '{participant.status_id:label}' => 'Status',
      '{participant.status_id:name}' => 'Machine name: Status',
      '{participant.role_id:name}' => 'Machine name: Participant Role',
      '{participant.is_test:label}' => 'Test',
      '{participant.must_wait}' => 'Must Wait on List',
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
    $tokens['{domain.now}'] = 'Current time/date';
    $this->assertEquals($tokens, $tokenProcessor->listTokens());
  }

  /**
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testDomainNow(): void {
    putenv('TIME_FUNC=frozen');
    CRM_Utils_Time::setTime('2021-09-18 23:58:00');
    $modifiers = [
      'shortdate' => '09/18/2021',
      '%B %Y' => 'September 2021',
    ];
    foreach ($modifiers as $filter => $expected) {
      $resolved = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => [
          'msg_text' => '{domain.now|crmDate:"' . $filter . '"}',
        ],
      ])['text'];
      $this->assertEquals($expected, $resolved);
    }
    $resolved = CRM_Core_BAO_MessageTemplate::renderTemplate([
      'messageTemplate' => [
        'msg_text' => '{domain.now}',
      ],
    ])['text'];
    $this->assertEquals('September 18th, 2021 11:58 PM', $resolved);

    // This example is malformed - no quotes
    try {
      $resolved = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => [
          'msg_text' => '{domain.now|crmDate:shortdate}',
        ],
      ])['text'];
      $this->fail('Expected unquoted parameter to fail');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertRegExp(';Malformed token param;', $e->getMessage());
    }
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
      '{domain.now}' => 'Current time/date',
    ];
  }

  /**
   * Test that event tokens are consistently rendered.
   *
   * @throws \API_Exception
   */
  public function testEventTokenConsistency(): void {
    $mut = new CiviMailUtils($this);
    $this->setupParticipantScheduledReminder();

    $tokens = CRM_Core_SelectValues::eventTokens();
    $unadvertisedTokens = [
      '{event.event_type_id}' => 'Event Type',
      '{event.event_type_id:name}' => 'Machine name: Event Type',
    ];
    $this->assertEquals(array_merge($this->getEventTokens(), $unadvertisedTokens), $tokens);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['eventId'],
    ]);
    $this->assertEquals(array_merge($tokens, $this->getDomainTokens()), $tokenProcessor->listTokens());

    $expectedEventString = $this->getExpectedEventTokenOutput();
    $this->callAPISuccess('job', 'send_reminder', []);
    $expectedParticipantString = $this->getExpectedParticipantTokenOutput();
    $toCheck = array_merge(explode("\n", $expectedEventString), explode("\n", $expectedParticipantString));
    $toCheck[] = $expectedEventString;
    $toCheck[] = $expectedParticipantString;
    $mut->checkMailLog($toCheck);
    $tokens = array_keys($this->getEventTokens());
    $html = $this->getTokenString($tokens);
    $tokenProcessor->addMessage('html', $html, 'text/plain');
    $tokenProcessor->addRow(['eventId' => $this->ids['event'][0]]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expectedEventString, $tokenProcessor->getRow(0)->render('html'));
  }

  /**
   * Test that event tokens work absent participant tokens.
   *
   * @throws \API_Exception
   */
  public function testEventTokenConsistencyNoParticipantTokens(): void {
    $mut = new CiviMailUtils($this);
    $this->setupParticipantScheduledReminder(FALSE);

    $this->callAPISuccess('job', 'send_reminder', []);
    $expected = $this->getExpectedEventTokenOutput();
    // Checking these individually is easier to decipher discrepancies
    // but we also want to check in entirety.
    $toCheck = explode("\n", $expected);
    $toCheck[] = $expected;
    $mut->checkMailLog($toCheck);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['eventId'],
    ]);
    $html = $this->getTokenString(array_keys($this->getEventTokens()));

    $tokenProcessor->addMessage('html', $html, 'text/plain');
    $tokenProcessor->addRow(['eventId' => $this->ids['event'][0]]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expected, $tokenProcessor->getRow(0)->render('html'));

  }

  /**
   * Set up scheduled reminder for participants.
   *
   * @throws \API_Exception
   */
  public function setupParticipantScheduledReminder($includeParticipant = TRUE): void {
    $this->createEventAndParticipant();
    $tokens = array_keys($this->getEventTokens());
    if ($includeParticipant) {
      $tokens = array_keys(array_merge($this->getEventTokens(), $this->getParticipantTokens()));
    }
    $html = $this->getTokenString($tokens);
    CRM_Utils_Time::setTime('2007-02-20 15:00:00');
    $this->callAPISuccess('action_schedule', 'create', [
      'title' => 'job',
      'subject' => 'job',
      'entity_value' => 1,
      'mapping_id' => 2,
      'start_action_date' => 'register_date',
      'start_action_offset' => 1,
      'start_action_condition' => 'after',
      'start_action_unit' => 'day',
      'body_html' => $html,
    ]);
  }

  /**
   * Get expected event tokens.
   *
   * @return string[]
   */
  protected function getEventTokens(): array {
    return [
      '{event.id}' => 'Event ID',
      '{event.title}' => 'Event Title',
      '{event.start_date}' => 'Event Start Date',
      '{event.end_date}' => 'Event End Date',
      '{event.event_type_id:label}' => 'Event Type',
      '{event.summary}' => 'Event Summary',
      '{event.contact_email}' => 'Event Contact Email',
      '{event.contact_phone}' => 'Event Contact Phone',
      '{event.description}' => 'Event Description',
      '{event.location}' => 'Event Location',
      '{event.info_url}' => 'Event Info URL',
      '{event.registration_url}' => 'Event Registration URL',
      '{event.' . $this->getCustomFieldName('text') . '}' => 'Enter text here :: Group with field text',
    ];
  }

  /**
   * @param array $tokens
   *
   * @return string
   */
  protected function getTokenString(array $tokens): string {
    $html = '';
    foreach ($tokens as $token) {
      $html .= substr($token, 1, -1) . ' :' . $token . "\n";
    }
    return $html;
  }

  /**
   * Create an event with a participant.
   *
   * @throws \API_Exception
   */
  protected function createEventAndParticipant(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Event']);
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant'], 'int', 'participant_');
    $emailID = Email::create()
      ->setValues(['email' => 'event@example.com'])
      ->execute()
      ->first()['id'];
    $addressID = Address::create()->setValues([
      'street_address' => '15 Walton St',
      'supplemental_address_1' => 'up the road',
      'city' => 'Emerald City',
      'state_province_id:label' => 'Maine',
      'postal_code' => 90210,
    ])->execute()->first()['id'];
    $phoneID = Phone::create()
      ->setValues(['phone' => '456 789'])
      ->execute()
      ->first()['id'];

    $locationBlockID = LocBlock::save(FALSE)->setRecords([
      [
        'email_id' => $emailID,
        'address_id' => $addressID,
        'phone_id' => $phoneID,
      ],
    ])->execute()->first()['id'];
    $this->ids['event'][0] = $this->eventCreate([
      'description' => 'event description',
      $this->getCustomFieldName('text') => 'my field',
      'loc_block_id' => $locationBlockID,
    ])['id'];
    // Create an unrelated participant record so that the ids don't match.
    // this prevents things working just because the id 'happens to be valid'
    $this->participantCreate([
      'register_date' => '2020-01-01',
      'event_id' => $this->ids['event'][0],
    ]);
    $this->ids['participant'][0] = $this->participantCreate([
      'event_id' => $this->ids['event'][0],
      'fee_amount' => 50,
      'fee_level' => 'steep',
      $this->getCustomFieldName('participant_int') => '99999',
    ]);
  }

}
