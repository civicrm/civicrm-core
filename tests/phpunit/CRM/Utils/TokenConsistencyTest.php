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

use Civi\Api4\FinancialTrxn;
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
    $this->quickCleanup(['civicrm_case', 'civicrm_case_type'], TRUE);
    $this->quickCleanUpFinancialEntities();

    // WORKAROUND: CRM_Event_Tokens copies `civicrm_event` data into metadata cache. That should probably change, but that's a different scope-of-work.
    // `clear()` works around it. This should be removed if that's updated, but it will be safe either way.
    Civi::cache('metadata')->clear();
    $this->revertSetting('mailing_format');
    parent::tearDown();
  }

  /**
   * Test that case tokens are consistently rendered.
   */
  public function testCaseTokenConsistency(): void {
    $this->createLoggedInUser();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->createCustomGroupWithFieldOfType(['extends' => 'Case']);
    $tokens = CRM_Core_SelectValues::caseTokens();
    $this->assertEquals($this->getCaseTokens(), $tokens);
    $tokenString = $this->getTokenString(array_keys($this->getCaseTokens()));
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['caseId'],
    ]);
    $this->assertEquals(array_merge($this->getCaseTokens(), $this->getDomainTokens()), $tokenProcessor->listTokens());
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
    $expectedTokens = array_merge($this->getContributionRecurTokens(), $this->getDomainTokens());
    $this->assertEquals(array_diff_key($expectedTokens, $this->getUnadvertisedTokens()), $tokenProcessor->listTokens());
    $tokenString = $this->getTokenString(array_keys($this->getContributionRecurTokens()));

    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');
    $tokenProcessor->addRow(['contribution_recurId' => $this->getContributionRecurID()]);
    $tokenProcessor->evaluate();
    $this->assertEquals($this->getExpectedContributionRecurTokenOutPut(), $tokenProcessor->getRow(0)->render('html'));
  }

  /**
   * Test contribution tokens pulled from the contribution page.
   */
  public function testContributionPageTokens(): void {
    $tokenValues = [
      'frontend_title' => 'public title',
      'pay_later_text' => 'pay later text',
      'pay_later_receipt' => '<p>first line</p><p>second line</p>',
      'is_share' => TRUE,
      'receipt_text' => "Text in\n non html",
    ];
    $tokens = [];
    foreach (array_keys($tokenValues) as $token) {
      $tokens[] = '{contribution.contribution_page_id.' . $token . '}';
    }
    $tokenString = trim($this->getTokenString($tokens));
    $this->contributionPageCreate($tokenValues);

    $tokenProcessor = $this->getTokenProcessor(['schema' => ['contributionId']]);
    $tokenProcessor->addMessage('text', $tokenString, 'text/plain');
    $tokenProcessor->addMessage('html', $tokenString, 'text/html');
    $tokenProcessor->addRow(['contributionId' => $this->contributionCreate(['contribution_page_id' => $this->ids['ContributionPage']['test'], 'contact_id' => $this->individualCreate()])]);
    $tokenProcessor->evaluate();
    $this->assertEquals('contribution.contribution_page_id.frontend_title :public title
contribution.contribution_page_id.pay_later_text :pay later text
contribution.contribution_page_id.pay_later_receipt :<p>first line</p><p>second line</p>
contribution.contribution_page_id.is_share :1
contribution.contribution_page_id.receipt_text :Text in<br />
 non html', $tokenProcessor->getRow(0)->render('html'));
    $this->assertEquals('contribution.contribution_page_id.frontend_title :public title
contribution.contribution_page_id.pay_later_text :pay later text
contribution.contribution_page_id.pay_later_receipt :first line

second line
contribution.contribution_page_id.is_share :1
contribution.contribution_page_id.receipt_text :Text in
 non html', $tokenProcessor->getRow(0)->render('text'));

  }

  /**
   * Test that contribution recur tokens are consistently rendered.
   */
  public function testContributionRecurTokenRaw(): void {
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contribution_recurId'],
    ]);
    $tokenProcessor->addMessage('not_specified', '{contribution_recur.amount}', 'text/plain');
    $tokenProcessor->addMessage('money', '{contribution_recur.amount|crmMoney}', 'text/plain');
    $tokenProcessor->addMessage('raw', '{contribution_recur.amount|raw}', 'text/plain');
    $tokenProcessor->addMessage('moneyNumber', '{contribution_recur.amount|crmMoneyNumber}', 'text/plain');
    $tokenProcessor->addRow(['contribution_recurId' => $this->getContributionRecurID()]);
    $tokenProcessor->evaluate();
    $this->assertEquals('€5,990.99', $tokenProcessor->getRow(0)->render('not_specified'));
    $this->assertEquals('€5,990.99', $tokenProcessor->getRow(0)->render('money'));
    $this->assertEquals('5990.99', $tokenProcessor->getRow(0)->render('raw'));
  }

  /**
   * Test money format tokens can respect passed in locale.
   *
   * @group locale
   */
  public function testMoneyFormat(): void {
    // Our 'migration' off configured thousand separators at the moment is a define.
    putenv('IGNORE_SEPARATOR_CONFIG=1');
    $this->createLoggedInUser();
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contribution_recurId'],
    ]);
    $tokenString = '{contribution_recur.amount}';
    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');
    $tokenProcessor->addRow([
      'contribution_recurId' => $this->getContributionRecurID(),
      'locale' => 'nb_NO',
    ]);
    $tokenProcessor->evaluate();
    $this->assertEquals('€ 5 990,99', $tokenProcessor->getRow(0)
      ->render('html'));
  }

  /**
   * Test various contribution tokens.
   *
   * There is additional testing for contribution tokens in CRM_Contribute_ActionMapping_ByTypeTest
   * so this does not attempt to be complete.
   */
  public function testContributionTokens(): void {
    $this->createTestEntity('Address', ['name' => 'Bob Smith', 'street_address' => '123 Sesame Street', 'country_id' => 1058, 'supplemental_address_1' => 'The End']);
    $contributionID = $this->contributionCreate(['contact_id' => $this->individualCreate(), 'address_id' => $this->ids['Address']['default']]);
    $tokenString = '{contribution.address_id.name} ---- {contribution.address_id.display}';
    $html = $this->renderText(['contributionId' => $contributionID], $tokenString);
    $this->assertEquals('Bob Smith ---- 123 Sesame Street<br />
The End<br />
Czech Republic<br />', $html);
    $text = $this->renderText(['contributionId' => $contributionID], $tokenString, [], FALSE);
    $this->assertEquals('Bob Smith ---- 123 Sesame Street
The End
Czech Republic
', $text);
    $financialTrxn = FinancialTrxn::get()->addWhere('is_payment', '=', TRUE)->execute()->first();
    $text = $this->renderText(['financial_trxnId' => $financialTrxn['id']], '{financial_trxn.total_amount}', [], FALSE);
    $this->assertEquals('$100.00', $text);
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
      '{contribution_recur.frequency_unit}' => 'Frequency Unit',
      '{contribution_recur.contribution_status_id}' => 'Status',
      '{contribution_recur.payment_processor_id}' => 'Payment Processor ID',
      '{contribution_recur.financial_type_id}' => 'Financial Type ID',
      '{contribution_recur.payment_instrument_id}' => 'Payment Method',
      '{contribution_recur.frequency_unit:name}' => 'Machine name: Frequency Unit',
      '{contribution_recur.payment_instrument_id:name}' => 'Machine name: Payment Method',
      '{contribution_recur.contribution_status_id:name}' => 'Machine name: Status',
      '{contribution_recur.payment_processor_id:name}' => 'Machine name: Payment Processor',
      '{contribution_recur.financial_type_id:name}' => 'Machine name: Financial Type',
      '{participant.status_id:name}' => 'Machine name: Status',
      '{participant.role_id:name}' => 'Machine name: Participant Role',
      '{participant.status_id}' => 'Status ID',
      '{participant.role_id}' => 'Participant Role ID',
    ];
  }

  /**
   * Test the standard new location token format - which matches apiv4 return properties.
   *
   * @throws \CRM_Core_Exception
   */
  public function testLocationTokens(): void {
    $contactID = $this->individualCreate(['email' => 'me@example.com']);
    $this->createTestEntity('Address', [
      'contact_id' => $contactID,
      'is_primary' => TRUE,
      'street_address' => 'Heartbreak Hotel',
      'supplemental_address_1' => 'Lonely Street',
      'state_province_id:name' => 'New York',
    ], 'primary');

    $this->createTestEntity('Address', [
      'contact_id' => $contactID,
      'is_billing' => TRUE,
      'street_address' => 'Heartbreak Motel',
      'supplemental_address_1' => 'Lonely Avenue',
      'country_id:name' => 'United States',
      'state_province_id:name' => 'California',
    ], 'billing');
    $template = '{contact.first_name} {contact.email_primary.email} {contact.address_primary.street_address} {contact.address_billing.supplemental_address_1} {contact.address_billing.state_province_id:abbr}';
    $text = $this->renderText(['contactId' => $contactID], $template);
    $this->assertEquals('Anthony me@example.com Heartbreak Hotel Lonely Avenue CA', $text);
    Address::delete()->addWhere('id', '=', $this->ids['Address']['billing'])->execute();
    $text = $this->renderText(['contactId' => $contactID], $template);
    $this->assertEquals('Anthony me@example.com Heartbreak Hotel Lonely Street NY', $text);
  }

  /**
   * Test tokens in 2 ways to ensure consistent handling.
   *
   * 1) as part of the greeting processing
   * 2) via the token processor.
   *
   */
  public function testOddTokens(): void {

    $variants = [
      [
        'string' => '{contact.individual_prefix}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.individual_suffix}',
        'expected' => 'Mr. Anthony Anderson II',
      ],
      [
        'string' => '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}',
        'expected' => 'Mr. Anthony Anderson II',
      ],
    ];
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'smarty' => FALSE,
      'schema' => ['contactId'],
    ]);
    $contactID = $this->individualCreate(['middle_name' => '']);
    $tokenProcessor->addRow(['contactId' => $contactID]);
    foreach ($variants as $index => $variant) {
      $tokenProcessor->addMessage($index, $variant['string'], 'text/plain');
    }
    $tokenProcessor->evaluate();
    $result = $tokenProcessor->getRow(0);
    foreach ($variants as $index => $variant) {
      $greetingString = $variant['string'];
      CRM_Utils_Token::replaceGreetingTokens($greetingString, $this->callAPISuccessGetSingle('Contact', ['id' => $contactID]), $contactID);
      $this->assertEquals($variant['expected'], $greetingString, 'replaceGreetingTokens() should render expected output');
      $this->assertEquals($variant['expected'], $result->render($index), 'TokenProcessor should render expected output');
    }
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
contribution_recur.amount :€5,990.99
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
contribution_recur.payment_processor_id:name :Dummy_test
contribution_recur.financial_type_id:label :Member Dues
contribution_recur.financial_type_id:name :Member Dues
contribution_recur.payment_instrument_id:label :Check
contribution_recur.payment_instrument_id:name :Check
';

  }

  /**
   * Test balance token works can be rendered as raw & boolean.
   *
   * This is a stand in for money tokens in general but as a pseudo-field
   * it has some added charm.
   */
  public function testContributionBalanceToken(): void {
    $contributionID = $this->contributionCreate(['contact_id' => $this->individualCreate(), 'contribution_status_id' => 'Pending']);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contributionID,
      'total_amount' => 1,
    ]);
    $text = $this->renderText(['contributionId' => $contributionID],
      '{contribution.balance_amount} {contribution.balance_amount|raw} {contribution.balance_amount|boolean} {contribution.tax_amount|boolean}'
    );
    $this->assertEquals('$99.00 99.00 1 0', $text);
  }

  /**
   * Test tokens can be capitalized.
   */
  public function testCapitalize(): void {
    $this->individualCreate(['first_name' => 'bob m smith']);
    $text = $this->renderText(['contactId' => $this->ids['Contact']['individual_0']], '{contact.first_name|capitalize}');
    $this->assertEquals('Bob M Smith', $text);
  }

  /**
   * Test that membership tokens are consistently rendered.
   *
   */
  public function testMembershipTokenConsistency(): void {
    CRM_Utils_Time::setTime('2007-01-22 15:00:00');
    $this->createLoggedInUser();
    $this->restoreMembershipTypes();
    $this->createCustomGroupWithFieldOfType(['extends' => 'Membership']);
    $expectedTokens = $this->getMembershipTokens();
    // This get also creates...
    $this->getMembershipID();
    $newStyleTokens = "\n{membership.status_id:label}\n{membership.membership_type_id:label}\n";
    $tokenString = $newStyleTokens . implode("\n", array_keys($this->getMembershipTokens()));

    // Custom fields work in the processor so test it....
    $tokenString .= "\n{membership." . $this->getCustomFieldName('text') . '}';
    // Now compare with scheduled reminder
    $mut = new CiviMailUtils($this);
    $this->callAPISuccess('ActionSchedule', 'create', [
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
    $this->callAPISuccess('Job', 'send_reminder', []);
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
    $expectedTokens = array_merge($expectedTokens, $this->getTokensAdvertisedByTokenProcessorButNotLegacy());
    // Token 'fee' is deprecated & no longer advertised.
    unset($expectedTokens['{membership.fee}']);
    $this->assertEquals(array_merge($expectedTokens, $this->getDomainTokens(), $this->getRecurEntityTokens('membership')), $tokens);
    $tokenProcessor->addMessage('html', $tokenString, 'text/plain');
    $tokenProcessor->addRow(['membershipId' => $this->getMembershipID()]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expected, $tokenProcessor->getRow(0)->render('html'));

  }

  public function testPledgeTokens(): void {
    $pledgeID = $this->pledgeCreate(['contact_id' => $this->individualCreate(), 'create_date' => '2022-04-07', 'start_date' => '2022-04-07']);
    $tokenProcessor = $this->getTokenProcessor(['schema' => ['pledgeId']]);
    $list = $tokenProcessor->listTokens();
    $this->assertEquals(array_merge($this->getPledgeTokens(), $this->getDomainTokens()), $list);
    $tokenString = implode(', ', array_keys($this->getPledgeTokens()));
    $tokenProcessor->addMessage('text', $tokenString, 'text/plain');
    $tokenProcessor->addRow(['pledgeId' => $pledgeID]);
    $tokenProcessor->evaluate();
    $this->assertEquals($this->getExpectedPledgeTokenOutput(), $tokenProcessor->getRow(0)->render('text'));
  }

  public function getExpectedPledgeTokenOutput(): string {
    return '$100.00, US Dollar, year, 5, 15, 5, April 7th, 2022, April 7th, 2022, , ';
  }

  public function getPledgeTokens(): array {
    return [
      '{pledge.amount}' => 'Total Pledged',
      '{pledge.currency:label}' => 'Pledge Currency',
      '{pledge.frequency_unit:label}' => 'Pledge Frequency Unit',
      '{pledge.frequency_interval}' => 'Pledge Frequency Interval',
      '{pledge.frequency_day}' => 'Pledge day',
      '{pledge.installments}' => 'Pledge Number of Installments',
      '{pledge.start_date}' => 'Pledge Start Date',
      '{pledge.create_date}' => 'Pledge Made',
      '{pledge.cancel_date}' => 'Pledge Cancelled Date',
      '{pledge.end_date}' => 'Pledge End Date',
    ];
  }

  /**
   * Get the advertised tokens the legacy function doesn't know about.
   *
   * @return string[]
   */
  public function getTokensAdvertisedByTokenProcessorButNotLegacy(): array {
    return [
      '{membership.custom_1}' => 'Enter text here :: Group with field text',
      '{membership.source}' => 'Membership Source',
      '{membership.status_override_end_date}' => 'Status Override End Date',
    ];
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
      '{membership.membership_type_id.minimum_fee}' => 'Minimum Fee',
      '{membership.fee}' => 'Membership Fee',
      '{membership.status_id.is_new}' => 'Is new membership status',
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
    return "participant.status_id :2
participant.role_id :1
participant.register_date :February 19th, 2007
participant.source :Wimbledon
participant.fee_level :steep
participant.fee_amount :$50.00
participant.registered_by_id :
participant.transferred_to_contact_id :
participant.created_id :{$this->ids['Contact']['logged_in']}
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
";
  }

  /**
   * Get expected output from token parsing.
   *
   * @return string
   */
  protected function getExpectedEventTokenOutput(): string {
    return 'event.id :' . $this->ids['Event'][0] . '
event.title :Annual CiviCRM meet
event.start_date :October 21st, 2008
event.end_date :October 23rd, 2008
event.event_type_id:label :Conference
event.summary :If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now
event.loc_block_id.email_id.email :event@example.com
event.loc_block_id.phone_id.phone :456 789
event.description :event description
event.location :15 Walton St<br />
up the road<br />
Emerald City, Maine 90210-1234<br />
United States<br />
event.info_url :' . CRM_Utils_System::url('civicrm/event/info', NULL, TRUE) . '&reset=1&id=1
event.registration_url :' . CRM_Utils_System::url('civicrm/event/register', NULL, TRUE) . '&reset=1&id=1
event.pay_later_receipt :Please transfer funds to our bank account.
event.custom_1 :my field
event.confirm_email_text :
event.fee_label :Event fees
';
  }

  /**
   * Get expected output from token parsing.
   *
   * @return string
   */
  protected function getExpectedMembershipTokenOutput(): string {
    return '
New
General
1
New
General
January 21st, 2007
January 21st, 2007
December 21st, 2007
$100.00
$100.00
1';
  }

  /**
   * Test that membership tokens are consistently rendered.
   *
   * @throws \CRM_Core_Exception
   */
  public function testParticipantTokenConsistency(): void {
    $this->createLoggedInUser();
    $this->setupParticipantScheduledReminder();

    $mut = new CiviMailUtils($this);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['participantId'],
    ]);
    $this->callAPISuccess('Job', 'send_reminder', []);
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
      '{participant.created_id}' => 'Created by Contact ID',
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testDomainTokenConsistency(): void {
    $tokens = CRM_Core_SelectValues::domainTokens();
    $this->assertEquals($this->getDomainTokens(), $tokens);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $contactID = \Civi\Api4\Domain::get()->addSelect('contact_id')->execute()->first()['contact_id'];
    Address::create()->setValues(['contact_id' => $contactID, 'city' => 'Beverley Hills', 'state_province_id:label' => 'California', 'country_id:label' => 'United States', 'postal_code' => 90210])->execute();
    $this->assertEquals($tokens, $tokenProcessor->listTokens());
    $tokenProcessor->addMessage('message', implode("\n", array_keys($tokens)), 'text/plain');
    $tokenProcessor->addRow();
    $tokenProcessor->evaluate();
    $this->assertStringContainsString('Beverley Hills
90210
California
United States', $tokenProcessor->getRow(0)->render('message'));
  }

  /**
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
      $this->assertMatchesRegularExpression(';Malformed token param;', $e->getMessage());
    }
  }

  /**
   * Get declared participant tokens.
   *
   * @return string[]
   */
  public function getDomainTokens(): array {
    return [
      '{domain.name}' => ts('Domain Name'),
      '{domain.address}' => ts('Domain (Organization) Full Address'),
      '{domain.phone}' => ts('Domain (Organization) Phone'),
      '{domain.email}' => 'Domain (Organization) Email',
      '{domain.id}' => ts('Domain ID'),
      '{domain.description}' => ts('Domain Description'),
      '{domain.now}' => 'Current time/date',
      '{domain.base_url}' => 'Domain absolute base url',
      '{domain.tax_term}' => 'Sales tax term (e.g VAT)',
      '{domain.street_address}' => 'Domain (Organization) Street Address',
      '{domain.supplemental_address_1}' => 'Domain (Organization) Supplemental Address',
      '{domain.supplemental_address_2}' => 'Domain (Organization) Supplemental Address 2',
      '{domain.supplemental_address_3}' => 'Domain (Organization) Supplemental Address 3',
      '{domain.city}' => 'Domain (Organization) City',
      '{domain.postal_code}' => 'Domain (Organization) Postal Code',
      '{domain.state_province_id:label}' => 'Domain (Organization) State',
      '{domain.country_id:label}' => 'Domain (Organization) Country',
      '{domain.empowered_by_civicrm_image_url}' => 'Empowered By CiviCRM Image',
      '{site.message_header}' => 'Message Header',
    ];
  }

  /**
   * Test that event tokens are consistently rendered.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEventTokenConsistency(): void {
    $mut = new CiviMailUtils($this);
    $this->createLoggedInUser();
    $this->setupParticipantScheduledReminder();

    $tokens = array_merge($this->getEventTokens());
    $tokenProcessor = $this->getTokenProcessor(['schema' => ['eventId']]);
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
    $tokenProcessor->addMessage('html', $html, 'text/html');
    $tokenProcessor->addRow(['eventId' => $this->ids['Event'][0]]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expectedEventString, $tokenProcessor->getRow(0)->render('html'));
  }

  /**
   * Test that event tokens work absent participant tokens.
   *
   * @throws \CRM_Core_Exception
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

    $tokenProcessor->addMessage('html', $html, 'text/html');
    $tokenProcessor->addRow(['eventId' => $this->ids['Event'][0]]);
    $tokenProcessor->evaluate();
    $this->assertEquals($expected, $tokenProcessor->getRow(0)->render('html'));

  }

  /**
   * Set up scheduled reminder for participants.
   *
   * @throws \CRM_Core_Exception
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
      '{event.event_type_id:label}' => 'Type',
      '{event.summary}' => 'Event Summary',
      '{event.loc_block_id.email_id.email}' => 'Event Contact Email',
      '{event.loc_block_id.phone_id.phone}' => 'Event Contact Phone',
      '{event.description}' => 'Event Description',
      '{event.location}' => 'Event Location',
      '{event.info_url}' => 'Event Info URL',
      '{event.registration_url}' => 'Event Registration URL',
      '{event.pay_later_receipt}' => 'Pay Later Receipt Text',
      '{event.' . $this->getCustomFieldName('text') . '}' => 'Enter text here :: Group with field text',
      '{event.confirm_email_text}' => 'Confirmation Email Text',
      '{event.fee_label}' => 'Fee Label',
    ];
  }

  /**
   * @param string $entity
   *
   * @return string[]
   */
  protected function getRecurEntityTokens(string $entity): array {
    return [
      '{' . $entity . '.contribution_recur_id.id}' => 'Recurring Contribution ID',
      '{' . $entity . '.contribution_recur_id.contact_id}' => 'Contact ID',
      '{' . $entity . '.contribution_recur_id.amount}' => 'Amount',
      '{' . $entity . '.contribution_recur_id.currency}' => 'Currency',
      '{' . $entity . '.contribution_recur_id.frequency_unit}' => 'Frequency Unit',
      '{' . $entity . '.contribution_recur_id.frequency_interval}' => 'Interval (number of units)',
      '{' . $entity . '.contribution_recur_id.start_date}' => 'Start Date',
      '{' . $entity . '.contribution_recur_id.cancel_date}' => 'Cancel Date',
      '{' . $entity . '.contribution_recur_id.cancel_reason}' => 'Cancellation Reason',
      '{' . $entity . '.contribution_recur_id.end_date}' => 'Recurring Contribution End Date',
      '{' . $entity . '.contribution_recur_id.financial_type_id}' => 'Financial Type ID',
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
   * @throws \CRM_Core_Exception
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
      'postal_code_suffix' => 1234,
    ])->execute()->first()['id'];
    \Civi::settings()->set('mailing_format', '{contact.street_address}{ }{contact.supplemental_address_1}{ }{contact.supplemental_address_2}{ }{contact.city}{ }{contact.state_province}{ }{contact.postal_code}');
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
    $this->ids['Event'][0] = $this->eventCreateUnpaid([
      'description' => 'event description',
      'end_date' => 20081023,
      'registration_end_date' => 20081015,
      $this->getCustomFieldName('text', 4) => 'my field',
      'loc_block_id' => $locationBlockID,
    ])['id'];
    // Create an unrelated participant record so that the ids don't match.
    // this prevents things working just because the id 'happens to be valid'
    $this->participantCreate([
      'register_date' => '2020-01-01',
      'event_id' => $this->ids['Event'][0],
    ]);
    $this->ids['participant'][0] = $this->participantCreate([
      'event_id' => $this->ids['Event'][0],
      'fee_amount' => 50,
      'fee_level' => 'steep',
      $this->getCustomFieldName('participant_int', 4) => '99999',
    ]);
  }

  public function testEscaping(): void {
    $context = [];
    $context['contactId'] = $this->individualCreate([
      'first_name' => '<b>ig</b>illy brackets',
    ]);
    $context['eventId'] = $this->eventCreateUnpaid([
      'title' => 'The Webinar',
      'description' => '<p>Some online webinar thingy.</p><p>Attendees will need to install the <a href="http://telefoo.example.com">TeleFoo</a> app.</p>',
    ])['id'];

    $messages = $expected = [];

    // The `first_name` does not allow HTML. Any funny characters are presented like literal text.
    $messages['contact_text'] = 'Hello {contact.first_name}!';
    $expected['contact_text'] = 'Hello <b>ig</b>illy brackets!';

    $messages['contact_html'] = '<p>Hello {contact.first_name}!</p>';
    $expected['contact_html'] = '<p>Hello &lt;b&gt;ig&lt;/b&gt;illy brackets!</p>';

    // The `description` does allow HTML. Any funny characters are filtered out of text.
    $messages['event_text'] = 'You signed up for this event: {event.title}: {event.description}';
    $expected['event_text'] = 'You signed up for this event: The Webinar: Some online webinar thingy.

Attendees will need to install the [TeleFoo](http://telefoo.example.com) app.';

    $messages['event_html'] = '<p>You signed up for this event:</p> <h3>{event.title}</h3> {event.description}';
    $expected['event_html'] = '<p>You signed up for this event:</p> <h3>The Webinar</h3> <p>Some online webinar thingy.</p><p>Attendees will need to install the <a href="http://telefoo.example.com">TeleFoo</a> app.</p>';

    $rendered = CRM_Core_TokenSmarty::render($messages, $context);

    $this->assertEquals($expected, $rendered);
  }

  /**
   * @param array $override
   *
   * @return \Civi\Token\TokenProcessor
   */
  protected function getTokenProcessor(array $override = []): TokenProcessor {
    return new TokenProcessor(\Civi::dispatcher(), array_merge([
      'controller' => __CLASS__,
    ], $override));
  }

  /**
   * Render the text via the token processor.
   *
   * @param array $rowContext
   * @param string $text
   * @param array $context
   * @param bool $isHtml
   *
   * @return string
   */
  protected function renderText(array $rowContext, string $text, array $context = [], $isHtml = TRUE): string {
    $context['schema'] ??= [];
    foreach (array_keys($rowContext) as $key) {
      $context['schema'][] = $key;
    }
    $tokenProcessor = $this->getTokenProcessor($context);
    $tokenProcessor->addRow($rowContext);
    $tokenProcessor->addMessage('text', $text, 'text/' . ($isHtml ? 'html' : 'plain'));
    $tokenProcessor->evaluate();
    return $tokenProcessor->getRow(0)->render('text');
  }

  public function testQuotedTokens(): void {
    $quoteOptions = [
      '"',
      '&lquote;',
      '&rquote;',
      '&quot;',
      '&#8221;',
      '&#8220;',
      '&#x22;',
    ];
    Civi::settings()->set('dateformatFull', '%B %E%f, %Y');
    foreach ($quoteOptions as $quote) {
      $date = CRM_Utils_Date::customFormat(date('Y-m-d H:i:s'), '%B %E%f, %Y');
      $this->assertEquals($date, $this->renderText([], '{domain.now|crmDate:' . $quote . 'Full' . $quote . '}'), 'render with quote type :' . $quote);
    }
  }

}
