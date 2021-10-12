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

use Civi\Api4\Contribution;
use Civi\Token\TokenProcessor;

/**
 * Class CRM_Contribute_ActionMapping_ByTypeTest
 * @group ActionSchedule
 *
 * This class tests various configurations of scheduled-reminders, with a focus on
 * reminders for *contribution types*. It follows a design/pattern described in
 * AbstractMappingTest.
 *
 * @see \Civi\ActionSchedule\AbstractMappingTest
 * @group headless
 */
class CRM_Contribute_ActionMapping_ByTypeTest extends \Civi\ActionSchedule\AbstractMappingTest {

  /**
   * Generate a list of test cases, where each is a distinct combination of
   * data, schedule-rules, and schedule results.
   *
   * @return array
   *   - targetDate: string; eg "2015-02-01 00:00:01"
   *   - setupFuncs: string, space-separated list of setup functions
   *   - messages: array; each item is a message that's expected to be sent
   *     each message may include keys:
   *        - time: approximate time (give or take a few seconds)
   *        - recipients: array of emails
   *        - subject: regex
   */
  public function createTestCases() {
    $cs = [];

    // FIXME: CRM-19415: The right email content goes out, but it appears that the dates are incorrect.
    //    $cs[] = array(
    //      '2015-02-01 00:00:00',
    //      'addAliceDues scheduleForAny startOnTime useHelloFirstName alsoRecipientBob',
    //      array(
    //        array(
    //          'time' => '2015-02-01 00:00:00',
    //          'to' => array('alice@example.org'),
    //          'subject' => '/Hello, Alice.*via subject/',
    //        ),
    //        array(
    //          'time' => '2015-02-01 00:00:00',
    //          'to' => array('bob@example.org'),
    //          'subject' => '/Hello, Bob.*via subject/',
    //          // It might make more sense to get Alice's details... but path of least resistance...
    //        ),
    //      ),
    //    );

    $cs[] = [
      '2015-02-01 00:00:00',
      'addAliceDues scheduleForAny startOnTime useHelloFirstName limitToRecipientBob',
      [],
    ];

    $cs[] = [
      '2015-02-01 00:00:00',
      'addAliceDues scheduleForAny startOnTime useHelloFirstName limitToRecipientAlice',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice.*via subject/',
        ],
      ],
    ];

    $cs[] = [
      '2015-02-01 00:00:00',
      // 'addAliceDues addBobDonation scheduleForDues startOnTime useHelloFirstName',
      'addAliceDues addBobDonation scheduleForDues startOnTime useHelloFirstNameStatus',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice. @Completed.*via subject/',
        ],
      ],
    ];

    $cs[] = [
      '2015-02-01 00:00:00',
      'addAliceDues addBobDonation scheduleForAny startOnTime useHelloFirstName',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice.*via subject/',
        ],
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
      ],
    ];

    $cs[] = [
      '2015-02-02 00:00:00',
      'addAliceDues addBobDonation scheduleForDonation startWeekBefore repeatTwoWeeksAfter useHelloFirstName',
      [
        [
          'time' => '2015-01-26 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
        [
          'time' => '2015-02-02 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
        [
          'time' => '2015-02-09 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
        [
          'time' => '2015-02-16 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
      ],
    ];

    $cs[] = [
      '2015-02-03 00:00:00',
      'addAliceDues addBobDonation scheduleForSoftCreditor startWeekAfter useHelloFirstName',
      [
        [
          'time' => '2015-02-10 00:00:00',
          'to' => ['carol@example.org'],
          'subject' => '/Hello, Carol.*via subject/',
        ],
      ],
    ];

    return $cs;
  }

  /**
   * Create a contribution record for Alice with type "Member Dues".
   */
  public function addAliceDues(): void {
    $this->enableCiviCampaign();
    $campaignID = $this->campaignCreate();
    $this->ids['Contribution']['alice'] = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->contacts['alice']['id'],
      'receive_date' => date('Ymd', strtotime($this->targetDate)),
      'total_amount' => '100',
      'currency' => 'EUR',
      'financial_type_id' => 1,
      'non_deductible_amount' => '10',
      'fee_amount' => '5',
      'net_amount' => '95',
      'source' => 'SSF',
      // Having a cancel date is a bit artificial here but we can test it....
      'cancel_date' => '2021-08-09',
      'contribution_status_id' => 1,
      'campaign_id' => $campaignID,
      'soft_credit' => [
        '1' => [
          'contact_id' => $this->contacts['carol']['id'],
          'amount' => 50,
          'soft_credit_type_id' => 3,
        ],
      ],
    ])['id'];
  }

  /**
   * Create a contribution record for Bob with type "Donation".
   */
  public function addBobDonation() {
    $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->contacts['bob']['id'],
      'receive_date' => date('Ymd', strtotime($this->targetDate)),
      'total_amount' => '150',
      'financial_type_id' => 2,
      'non_deductible_amount' => '10',
      'fee_amount' => '5',
      'net_amount' => '145',
      'source' => 'SSF',
      'contribution_status_id' => 2,
    ]);
  }

  /**
   * Schedule message delivery for contributions of type "Member Dues".
   */
  public function scheduleForDues() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded([1]);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded([1]);
  }

  /**
   * Schedule message delivery for contributions of type "Donation".
   */
  public function scheduleForDonation() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded([2]);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
  }

  /**
   * Schedule message delivery for any contribution, regardless of type.
   */
  public function scheduleForAny() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(NULL);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
  }

  /**
   * Schedule message delivery to the 'soft credit' assignee.
   */
  public function scheduleForSoftCreditor() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(NULL);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = 'soft_credit_type';
    $this->schedule->recipient_listing = CRM_Utils_Array::implodePadded([3]);
  }

  public function useHelloFirstNameStatus(): void {
    $this->schedule->subject = 'Hello, {contact.first_name}. @{contribution.contribution_status_id:name}. (via subject)';
    $this->schedule->body_html = '<p>Hello, {contact.first_name}. @{contribution.contribution_status_id:name}. (via body_html)</p>';
    $this->schedule->body_text = 'Hello, {contact.first_name}. @{contribution.contribution_status_id:name} (via body_text)';
  }

  /**
   * Test that reconciled tokens are rendered the same via multiple code paths.
   *
   * We expect that the list of tokens from the processor class === the selectValues function.
   * - once this is verified to be true selectValues can call the processor function internally.
   *
   * We also expect that rendering action action schedules will do the same as the
   * legacy processor function. Once this is true we can expose the listener on the
   * token processor for contribution and call it internally from the legacy code.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testTokenRendering(): void {
    $this->targetDate = '20150201000107';
    \CRM_Utils_Time::setTime('2015-02-01 00:00:00');
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_option_value SET label = 'Completed Label**' where label = 'Completed' AND name = 'Completed'");

    $this->addAliceDues();
    $this->scheduleForAny();
    $this->startOnTime();
    $this->schedule->save();
    $this->schedule->body_text = '
      first name = {contact.first_name}
      receive_date = {contribution.receive_date}
      contribution status id = {contribution.contribution_status_id}
      new style status = {contribution.contribution_status_id:name}
      new style label = {contribution.contribution_status_id:label}
      id {contribution.id}
      contribution_id {contribution.contribution_id} - not valid for action schedule
      cancel date {contribution.cancel_date}
      source {contribution.source}
      legacy source {contribution.contribution_source}
      financial type id = {contribution.financial_type_id}
      financial type name = {contribution.financial_type_id:name}
      financial type label = {contribution.financial_type_id:label}
      payment instrument id = {contribution.payment_instrument_id}
      payment instrument name = {contribution.payment_instrument_id:name}
      payment instrument label = {contribution.payment_instrument_id:label}
      non_deductible_amount = {contribution.non_deductible_amount}
      total_amount = {contribution.total_amount}
      net_amount = {contribution.net_amount}
      fee_amount = {contribution.fee_amount}
      campaign_id = {contribution.campaign_id}
      campaign name = {contribution.campaign_id:name}
      campaign label = {contribution.campaign_id:label}';

    $this->schedule->save();
    $this->callAPISuccess('job', 'send_reminder', []);
    $expected = [
      'first name = Alice',
      'receive_date = February 1st, 2015',
      'contribution status id = 1',
      'new style status = Completed',
      'new style label = Completed Label**',
      'id ' . $this->ids['Contribution']['alice'],
      'id  - not valid for action schedule',
      'cancel date August 9th, 2021',
      'source SSF',
      'financial type id = 1',
      'financial type name = Donation',
      'financial type label = Donation',
      'payment instrument id = 4',
      'payment instrument name = Check',
      'payment instrument label = Check',
      'non_deductible_amount = €10.00',
      'total_amount = €100.00',
      'net_amount = €95.00',
      'fee_amount = €5.00',
      'campaign_id = 1',
      'campaign name = big_campaign',
      'campaign label = Campaign',
    ];
    $this->mut->checkMailLog($expected);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contributionId'],
      'contributionId' => $this->ids['Contribution']['alice'],
      'contactId' => $this->contacts['alice']['id'],
    ]);
    $tokenProcessor->addRow([]);
    $tokenProcessor->addMessage('html', $this->schedule->body_text, 'text/plain');
    $tokenProcessor->evaluate();
    foreach ($tokenProcessor->getRows() as $row) {
      foreach ($expected as $value) {
        $this->assertStringContainsString($value, $row->render('html'));
      }
    }

    $messageToken = CRM_Utils_Token::getTokens($this->schedule->body_text);

    $contributionDetails = CRM_Contribute_BAO_Contribution::replaceContributionTokens(
      [$this->ids['Contribution']['alice']],
      $this->schedule->body_text,
      $messageToken,
      $this->schedule->body_text,
      $this->schedule->body_text,
      $messageToken,
      TRUE
    );
    $expected = [
      'receive_date = February 1st, 2015',
      'new style status = Completed',
      'contribution status id = 1',
      'id ' . $this->ids['Contribution']['alice'],
      'contribution_id ' . $this->ids['Contribution']['alice'],
      'financial type id = 1',
      'financial type name = Donation',
      'financial type label = Donation',
      'payment instrument id = 4',
      'payment instrument name = Check',
      'payment instrument label = Check',
      'legacy source SSF',
      'source SSF',
      'non_deductible_amount = € 10.00',
      'total_amount = € 100.00',
      'net_amount = € 95.00',
      'fee_amount = € 5.00',
      'campaign_id = 1',
      'campaign name = big_campaign',
      'campaign label = Campaign',
    ];
    foreach ($expected as $string) {
      $this->assertStringContainsString($string, $contributionDetails[$this->contacts['alice']['id']]['html']);
    }
    $tokens = [
      'id',
      'payment_instrument_id:label',
      'financial_type_id:label',
      'contribution_status_id:label',
    ];
    $legacyTokens = [];
    $realLegacyTokens = [];
    foreach (CRM_Core_SelectValues::contributionTokens() as $token => $label) {
      $legacyTokens[substr($token, 14, -1)] = $label;
      if (strpos($token, ':') === FALSE) {
        $realLegacyTokens[substr($token, 14, -1)] = $label;
      }
    }
    $fields = (array) Contribution::getFields()->addSelect('name', 'title')->execute()->indexBy('name');
    $allFields = [];
    foreach ($fields as $field) {
      if (!array_key_exists($field['name'], $this->getUnadvertisedTokens())) {
        $allFields[$field['name']] = $field['title'];
      }
    }
    // contact ID is skipped.
    unset($allFields['contact_id']);
    $this->assertEquals($allFields, $realLegacyTokens);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['contributionId'],
    ]);
    $comparison = [];
    foreach ($tokenProcessor->listTokens() as $token => $label) {
      if (strpos($token, '{domain.') === 0) {
        // domain token - ignore.
        continue;
      }
      $comparison[substr($token, 14, -1)] = $label;
    }
    $this->assertEquals($legacyTokens, $comparison);
    foreach ($tokens as $token) {
      $this->assertEquals(CRM_Core_SelectValues::contributionTokens()['{contribution.' . $token . '}'], $comparison[$token]);
    }
  }

  /**
   * Get tokens not advertised in the widget.
   *
   * @return string[]
   */
  public function getUnadvertisedTokens(): array {
    return [
      'financial_type_id' => 'Financial Type ID',
      'contribution_page_id' => 'Contribution Page ID',
      'payment_instrument_id' => 'Payment Method ID',
      'is_test' => 'Is test',
      'is_pay_later' => 'is pay later',
      'is_template' => 'is_template',
      'contribution_status_id' => 'Contribution Status ID',
      'campaign_id' => 'Campaign ID',
    ];
  }

}
