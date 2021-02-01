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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  protected $_individualId;

  protected $_docTypes = NULL;

  protected $_contactIds;

  /**
   * Count how many times the hookTokens is called.
   *
   * This only needs to be called once, check refactoring doesn't change this.
   *
   * @var int
   */
  protected $hookTokensCalled = 0;

  protected function setUp() {
    parent::setUp();
    $this->_individualId = $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']);
    $this->_docTypes = CRM_Core_SelectValues::documentApplicationType();
  }

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match']);
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test thank you send with grouping.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGroupedThankYous(): void {
    $this->ids['Contact'][0] = $this->individualCreate();
    $this->createLoggedInUser();
    $contribution1ID = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->ids['Contact'][0],
      'total_amount' => '60',
      'financial_type_id' => 'Donation',
      'currency' => 'USD',
      'receive_date' => '2021-01-01 13:21',
    ])['id'];
    $contribution2ID = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->ids['Contact'][0],
      'total_amount' => '70',
      'financial_type_id' => 'Donation',
      'receive_date' => '2021-02-01 2:21',
      'currency' => 'USD',
    ])['id'];
    $form = $this->getFormObject('CRM_Contribute_Form_Task_PDFLetter', [
      'campaign_id' => '',
      'subject' => '',
      'format_id' => '',
      'paper_size' => 'letter',
      'orientation' => 'portrait',
      'metric' => 'in',
      'margin_left' => '0.75',
      'margin_right' => '0.75',
      'margin_top' => '0.75',
      'margin_bottom' => '0.75',
      'document_type' => 'pdf',
      'html_message' => '{contribution.currency} * {contribution.total_amount} * {contribution.receive_date}',
      'template' => '',
      'saveTemplateName' => '',
      'from_email_address' => '185',
      'thankyou_update' => '1',
      'group_by' => 'contact_id',
      'group_by_separator' => 'comma',
      'email_options' => '',
    ]);
    $this->setSearchSelection([$contribution1ID, $contribution2ID], $form);
    $form->preProcess();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertContains('USD, USD * $ 60.00, $ 70.00 * January 1st, 2021  1:21 PM, February 1st, 2021  2:21 AM', $e->errorData['html']);
    }
  }

  /**
   * Test the buildContributionArray function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBuildContributionArray(): void {
    $this->_individualId = $this->individualCreate();

    $customGroup = $this->callAPISuccess('CustomGroup', 'create', [
      'title' => 'Test Custom Set for Contribution',
      'extends' => 'Contribution',
      'is_active' => TRUE,
    ]);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Text field',
      'html_type' => 'Text',
      'data_type' => 'String',
      'weight' => 1,
      'is_active' => 1,
    ];
    $customField = $this->callAPISuccess('CustomField', 'create', $params);
    $customFieldKey = 'custom_' . $customField['id'];
    $campaignTitle = 'Test Campaign ';

    $params = [
      'contact_id' => $this->_individualId,
      'total_amount' => 6,
      'campaign_id' => $this->campaignCreate(['title' => $campaignTitle], FALSE),
      'financial_type_id' => 'Donation',
      $customFieldKey => 'Text_' . substr(sha1(rand()), 0, 7),
    ];
    $contributionIDs = $returnProperties = [];
    $result = $this->callAPISuccess('Contribution', 'create', $params);
    $contributionIDs[] = $result['id'];
    $this->hookClass->setHook('civicrm_tokenValues', [$this, 'hookTokenValues']);

    // assume that there are two token {contribution.financial_type} and
    // {contribution.custom_N} in message content
    $messageToken = [
      'contribution' => [
        'financial_type',
        'payment_instrument',
        'campaign',
        $customFieldKey,
      ],
    ];

    [$contributions, $contacts] = CRM_Contribute_Form_Task_PDFLetterCommon::buildContributionArray('contact_id', $contributionIDs, $returnProperties, TRUE, TRUE, $messageToken, 'test', '**', FALSE);

    $this->assertEquals('Anthony', $contacts[$this->_individualId]['first_name']);
    $this->assertEquals('emo', $contacts[$this->_individualId]['favourite_emoticon']);
    $this->assertEquals('Donation', $contributions[$result['id']]['financial_type']);
    $this->assertEquals($campaignTitle, $contributions[$result['id']]['campaign']);
    $this->assertEquals('Check', $contributions[$result['id']]['payment_instrument']);
    // CRM-20359: assert that contribution custom field token is rightfully replaced by its value
    $this->assertEquals($params[$customFieldKey], $contributions[$result['id']][$customFieldKey]);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Implement token values hook.
   *
   * @param array $details
   * @param array $contactIDs
   * @param int $jobID
   * @param array $tokens
   * @param string $className
   */
  public function hookTokenValues(&$details, $contactIDs, $jobID, $tokens, $className) {
    foreach ($details as $index => $detail) {
      $details[$index]['favourite_emoticon'] = 'emo';
    }
  }

  /**
   * Test contribution token replacement in
   * html returned by postProcess function.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPostProcess(): void {
    $this->createLoggedInUser();
    $this->_individualId = $this->individualCreate();
    foreach (['docx', 'odt'] as $docType) {
      $formValues = [
        'is_unit_test' => TRUE,
        'group_by' => NULL,
        'document_file' => [
          'name' => __DIR__ . "/sample_documents/Template.$docType",
          'type' => $this->_docTypes[$docType],
        ],
      ];

      $contributionParams = [
        'contact_id' => $this->_individualId,
        'total_amount' => 100,
        'financial_type_id' => 'Donation',
      ];
      $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
      $contributionId = $contribution['id'];
      $form = new CRM_Contribute_Form_Task_PDFLetter();
      $form->setContributionIds([$contributionId]);
      $format = Civi::settings()->get('dateformatFull');
      $date = CRM_Utils_Date::getToday();
      $displayDate = CRM_Utils_Date::customFormat($date, $format);

      $html = CRM_Contribute_Form_Task_PDFLetterCommon::postProcess($form, $formValues);
      $expectedValues = [
        'Hello Anthony',
        '$ 100.00',
        $displayDate,
        'Donation',
        'Domain Name - Default Domain Name',
      ];

      foreach ($expectedValues as $val) {
        $this->assertTrue(strpos($html[$contributionId], $val) !== 0);
      }
    }
  }

  /**
   * Test assignment of variables when using the group by function.
   *
   * We are looking to see that the contribution aggregate and contributions
   * arrays reflect the most recent contact rather than a total aggregate,
   * since we are using group by.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPostProcessGroupByContact(): void {
    $this->createLoggedInUser();
    $this->hookClass->setHook('civicrm_tokenValues', [$this, 'hook_aggregateTokenValues']);
    $this->hookClass->setHook('civicrm_tokens', [$this, 'hook_tokens']);
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->_individualId = $this->individualCreate();
    $this->_individualId2 = $this->individualCreate();
    $htmlMessage = "{aggregate.rendered_token}";
    $formValues = [
      'is_unit_test' => TRUE,
      'group_by' => 'contact_id',
      'html_message' => $htmlMessage,
      'email_options' => 'both',
      'subject' => 'Testy test test',
      'from' => 'info@example.com',
    ];

    $contributionIDs = [];
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
      'receive_date' => '2016-12-25',
    ]);
    $contributionIDs[] = $contribution['id'];
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->_individualId2,
      'total_amount' => 10,
      'financial_type_id' => 'Donation',
      'receive_date' => '2016-12-25',
    ]);
    $contributionIDs[] = $contribution['id'];

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->_individualId2,
      'total_amount' => 1,
      'financial_type_id' => 'Donation',
      'receive_date' => '2016-12-25',
    ]);
    $contributionIDs[] = $contribution['id'];

    $form = new CRM_Contribute_Form_Task_PDFLetter();
    $form->setContributionIds($contributionIDs);

    $html = CRM_Contribute_Form_Task_PDFLetterCommon::postProcess($form, $formValues);
    $this->assertEquals("<table border='1' cellpadding='2' cellspacing='0' class='table'>
  <tbody>
  <tr>
    <th>Date</th>
    <th>Amount</th>
    <th>Financial Type</th>
    <th>Source</th>
  </tr>
  <!--
   -->
  <tr>
    <td>25 December 2016</td>
    <td>$ 100.00</td>
    <td>Donation</td>
    <td></td>
  </tr>
  <!--
    -->
  <tr>
    <td><strong>Total</strong></td>
    <td><strong>$ 100.00</strong></td>
    <td></td>
    <td></td>
  </tr>
  </tbody>
</table>", $html[1]);
    $this->assertEquals("<table border='1' cellpadding='2' cellspacing='0' class='table'>
  <tbody>
  <tr>
    <th>Date</th>
    <th>Amount</th>
    <th>Financial Type</th>
    <th>Source</th>
  </tr>
  <!--
    -->
  <tr>
    <td>25 December 2016</td>
    <td>$ 10.00</td>
    <td>Donation</td>
    <td></td>
  </tr>
  <!--
     -->
  <tr>
    <td>25 December 2016</td>
    <td>$ 1.00</td>
    <td>Donation</td>
    <td></td>
  </tr>
  <!--
  -->
  <tr>
    <td><strong>Total</strong></td>
    <td><strong>$ 11.00</strong></td>
    <td></td>
    <td></td>
  </tr>
  </tbody>
</table>", $html[2]);

    $activities = $this->callAPISuccess('Activity', 'get', ['activity_type_id' => 'Print PDF Letter', 'sequential' => 1]);
    $this->assertEquals(2, $activities['count']);
    $this->assertEquals($html[1], $activities['values'][0]['details']);
    $this->assertEquals($html[2], $activities['values'][1]['details']);
    // Checking it is not called multiple times.
    // once for each contact create + once for the activities.
    $this->assertEquals(3, $this->hookTokensCalled);
    $this->mut->checkAllMailLog($html);

  }

  /**
   * Implements civicrm_tokens().
   */
  public function hook_tokens(&$tokens) {
    $this->hookTokensCalled++;
    $tokens['aggregate'] = ['rendered_token' => 'rendered_token'];
  }

  /**
   * Get the html message.
   *
   * @return string
   */
  public function getHtmlMessage() {
    return '{assign var=\'contact_aggregate\' value=0}
<table border=\'1\' cellpadding=\'2\' cellspacing=\'0\' class=\'table\'>
  <tbody>
  <tr>
    <th>Date</th>
    <th>Amount</th>
    <th>Financial Type</th>
    <th>Source</th>
  </tr>
  <!--
{foreach from=$contributions item=contribution}
 {if $contribution.contact_id == $messageContactID}
 {assign var=\'date\' value=$contribution.receive_date|date_format:\'%d %B %Y\'}
 {assign var=contact_aggregate
value=$contact_aggregate+$contribution.total_amount}
-->
  <tr>
    <td>{$date}</td>
    <td>{$contribution.total_amount|crmMoney}</td>
    <td>{$contribution.financial_type}</td>
    <td></td>
  </tr>
  <!--
  {/if}
{/foreach}
-->
  <tr>
    <td><strong>Total</strong></td>
    <td><strong>{$contact_aggregate|crmMoney}</strong></td>
    <td></td>
    <td></td>
  </tr>
  </tbody>
</table>';
  }

  /**
   * Implements CiviCRM hook.
   *
   * @param array $values
   * @param array $contactIDs
   * @param null $job
   * @param array $tokens
   * @param null $context
   */
  public function hook_aggregateTokenValues(&$values, $contactIDs, $job = NULL, $tokens = [], $context = NULL) {
    foreach ($contactIDs as $contactID) {
      CRM_Core_Smarty::singleton()->assign('messageContactID', $contactID);
      $values[$contactID]['aggregate.rendered_token'] = CRM_Core_Smarty::singleton()
        ->fetch('string:' . $this->getHtmlMessage());
    }
  }

  /**
   * @param string $token
   * @param string $entity
   * @param string $textToSearch
   * @param bool $expected
   *
   * @dataProvider isHtmlTokenInTableCellProvider
   */
  public function testIsHtmlTokenInTableCell($token, $entity, $textToSearch, $expected) {
    $this->assertEquals($expected,
      CRM_Contribute_Form_Task_PDFLetterCommon::isHtmlTokenInTableCell($token, $entity, $textToSearch)
    );
  }

  public function isHtmlTokenInTableCellProvider() {
    return [

      'simplest TRUE' => [
        'token',
        'entity',
        '<td>{entity.token}</td>',
        TRUE,
      ],

      'simplest FALSE' => [
        'token',
        'entity',
        '{entity.token}',
        FALSE,
      ],

      'token between two tables' => [
        'token',
        'entity',
        ' <table><tr><td>Top</td></tr></table>
          {entity.token}
          <table><tr><td>Bottom</td></tr></table>',
        FALSE,
      ],

      'token in two tables' => [
        'token',
        'entity',
        ' <table><tr><td>{entity.token}</td></tr><tr><td>foo</td></tr></table>
          <table><tr><td>{entity.token}</td></tr><tr><td>foo</td></tr></table>',
        TRUE,
      ],

      'token outside of table and inside of table' => [
        'token',
        'entity',
        ' {entity.token}
          <table><tr><td>{entity.token}</td></tr><tr><td>foo</td></tr></table>',
        FALSE,
      ],

      'token inside more complicated table' => [
        'token',
        'entity',
        ' <table><tr><td class="foo"><em>{entity.token}</em></td></tr></table>',
        TRUE,
      ],

      'token inside something that looks like table cell' => [
        'token',
        'entity',
        ' <tdata>{entity.token}</tdata>
          <table><tr><td>Bottom</td></tr></table>',
        FALSE,
      ],

    ];
  }

  /**
   * @param array $entities
   * @param \CRM_Core_Form $form
   */
  protected function setSearchSelection(array $entities, CRM_Core_Form $form): void {
    $_SESSION['_' . $form->controller->_name . '_container']['values']['Search'] = [
      'radio_ts' => 'ts_sel',
    ];
    foreach ($entities as $entityID) {
      $_SESSION['_' . $form->controller->_name . '_container']['values']['Search']['mark_x_' . $entityID] = TRUE;
    }
  }

}
