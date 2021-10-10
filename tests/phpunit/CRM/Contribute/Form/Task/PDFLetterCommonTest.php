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

  use CRMTraits_Custom_CustomDataTrait;

  protected $_individualId;

  protected $_docTypes;

  protected $_contactIds;

  /**
   * Count how many times the hookTokens is called.
   *
   * This only needs to be called once, check refactoring doesn't change this.
   *
   * @var int
   */
  protected $hookTokensCalled = 0;

  protected function setUp(): void {
    parent::setUp();
    $this->_individualId = $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']);
    $this->_docTypes = CRM_Core_SelectValues::documentApplicationType();
  }

  /**
   * Clean up after each test.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match', 'civicrm_campaign'], TRUE);
    CRM_Utils_Hook::singleton()->reset();
    parent::tearDown();
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
    /* @var CRM_Contribute_Form_Task_PDFLetter $form */
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
      $this->assertStringContainsString('USD, USD * $60.00, $70.00 * January 1st, 2021  1:21 PM, February 1st, 2021  2:21 AM', $e->errorData['html']);
    }
  }

  /**
   * Test the buildContributionArray function.
   *
   * @throws \CRM_Core_Exception|\CiviCRM_API3_Exception
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
      $customFieldKey => 'Text_',
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

    $form = $this->getFormObject('CRM_Contribute_Form_Task_PDFLetter');
    [$contributions, $contacts] = $form->buildContributionArray('contact_id', $contributionIDs, $returnProperties, TRUE, TRUE, $messageToken, 'test', '**', FALSE);

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
  public function hookTokenValues(&$details, $contactIDs, $jobID, $tokens, $className): void {
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
    $this->createLoggedInUser();;
    foreach (['docx', 'odt'] as $docType) {
      $formValues = [
        'group_by' => NULL,
        'document_file' => [
          'name' => __DIR__ . "/sample_documents/Template.$docType",
          'type' => $this->_docTypes[$docType],
        ],
      ];

      $contributionId = $this->createContribution();
      /* @var $form CRM_Contribute_Form_Task_PDFLetter */
      $form = $this->getFormObject('CRM_Contribute_Form_Task_PDFLetter', $formValues);
      $form->setContributionIds([$contributionId]);
      $format = Civi::settings()->get('dateformatFull');
      $date = CRM_Utils_Date::getToday();
      $displayDate = CRM_Utils_Date::customFormat($date, $format);

      try {
        $form->postProcess();
        $this->fail('Exception expected');
      }
      catch (CRM_Core_Exception_PrematureExitException $e) {
        $html = $e->errorData['html'];
      }
      $expectedValues = [
        'Hello Anthony',
        '$ 100.00',
        $displayDate,
        'Donation',
        'Domain Name - Default Domain Name',
      ];

      foreach ($expectedValues as $val) {
        $this->assertNotSame(strpos($html[$contributionId], $val), 0);
      }
    }
  }

  /**
   * Test that no notice or errors occur if no contribution tokens are requested.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testNoContributionTokens(): void {
    $this->createLoggedInUser();
    $formValues = [
      'html_message' => '{contact.display_name}',
      'document_type' => 'pdf',
    ];
    /* @var $form CRM_Contribute_Form_Task_PDFLetter */
    $form = $this->getFormObject('CRM_Contribute_Form_Task_PDFLetter', $formValues);
    $form->setContributionIds([$this->createContribution()]);
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $html = $e->errorData['html'];
    }
    $this->assertStringContainsString('Mr. Anthony Anderson II', $html);
  }

  /**
   * Test all contribution tokens.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAllContributionTokens(): void {
    $this->createLoggedInUser();
    $this->createCustomGroupWithFieldsOfAllTypes(['extends' => 'Contribution']);
    $this->campaignCreate(['name' => 'Big one', 'title' => 'Big one']);
    $tokens = $this->getAllContributionTokens();
    $formValues = [
      'document_type' => 'pdf',
      'html_message' => '',
    ];
    foreach (array_keys($this->getAllContributionTokens()) as $token) {
      $formValues['html_message'] .= "$token : {contribution.$token}\n";
    }
    /* @var $form CRM_Contribute_Form_Task_PDFLetter */
    $form = $this->getFormObject('CRM_Contribute_Form_Task_PDFLetter', $formValues);
    $form->setContributionIds([$this->createContribution(array_merge(['campaign_id' => $tokens['campaign_id:label']], $tokens))]);
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $html = $e->errorData['html'];
    }
    $this->assertEquals('
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>@page { margin: 0.75in 0.75in 0.75in 0.75in; }</style>
    <style type="text/css">@import url(' . CRM_Core_Config::singleton()->userFrameworkResourceURL . 'css/print.css);</style>
' . "    \n" . '  </head>
  <body>
    <div id="crm-container">
id : 1
total_amount : €9,999.99
fee_amount : €1,111.11
net_amount : €7,777.78
non_deductible_amount : €2,222.22
receive_date : July 20th, 2018
payment_instrument_id:label : Check
trxn_id : 1234
invoice_id : 568
currency : EUR
cancel_date : December 30th, 2019
cancel_reason : Contribution Cancel Reason
receipt_date : October 30th, 2019
thankyou_date : November 30th, 2019
source : Contribution Source
amount_level : Amount Level
contribution_status_id : 2
check_number : 6789
campaign_id:label : Big one
' . $this->getCustomFieldName('text') . ' : Bobsled
' . $this->getCustomFieldName('select_string') . ' : Red
' . $this->getCustomFieldName('select_date') . ' : 01/20/2021 12:00AM
' . $this->getCustomFieldName('int') . ' : 999
' . $this->getCustomFieldName('link') . ' : <a href="http://civicrm.org" target="_blank">http://civicrm.org</a>
' . $this->getCustomFieldName('country') . ' : New Zealand
' . $this->getCustomFieldName('multi_country') . ' : France, Canada
' . $this->getCustomFieldName('contact_reference') . ' : Mr. Spider Man II
' . $this->getCustomFieldName('state') . ' : Queensland
' . $this->getCustomFieldName('multi_state') . ' : Victoria, New South Wales
' . $this->getCustomFieldName('boolean') . ' : Yes
' . $this->getCustomFieldName('checkbox') . ' : Purple
    </div>
  </body>
</html>', $html);
  }

  /**
   * Get all the tokens available to contributions.
   *
   * @return array
   */
  public function getAllContributionTokens(): array {
    return [
      'id' => '',
      'total_amount' => '9999.99',
      'fee_amount' => '1111.11',
      'net_amount' => '7777.78',
      'non_deductible_amount' => '2222.22',
      'receive_date' => '2018-07-20',
      'payment_instrument_id:label' => 'Check',
      'trxn_id' => '1234',
      'invoice_id' => '568',
      'currency' => 'EUR',
      'cancel_date' => '2019-12-30',
      'cancel_reason' => 'Contribution Cancel Reason',
      'receipt_date' => '2019-10-30',
      'thankyou_date' => '2019-11-30',
      'source' => 'Contribution Source',
      'amount_level' => 'Amount Level',
      'contribution_status_id' => 'Pending',
      'check_number' => '6789',
      'campaign_id:label' => 'Big one',
      $this->getCustomFieldName('text') => 'Bobsled',
      $this->getCustomFieldName('select_string') => 'R',
      $this->getCustomFieldName('select_date') => '2021-01-20',
      $this->getCustomFieldName('int') => 999,
      $this->getCustomFieldName('link') => 'http://civicrm.org',
      $this->getCustomFieldName('country') => 'New Zealand',
      $this->getCustomFieldName('multi_country') => ['France', 'Canada'],
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
      $this->getCustomFieldName('state') => 'Queensland',
      $this->getCustomFieldName('multi_state') => ['Victoria', 'New South Wales'],
      $this->getCustomFieldName('boolean') => TRUE,
      $this->getCustomFieldName('checkbox') => 'P',
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
    ];
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
    $htmlMessage = '{aggregate.rendered_token}';
    $formValues = [
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

    /* @var \CRM_Contribute_Form_Task_PDFLetter $form */
    $form = $this->getFormObject('CRM_Contribute_Form_Task_PDFLetter', $formValues);
    $form->setContributionIds($contributionIDs);

    try {
      $form->postProcess();
      $this->fail('exception expected.');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $html = $e->errorData['html'];
    }
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
    // By calling the cached function we can get this down to 1
    $this->assertEquals(3, $this->hookTokensCalled);
    $this->mut->checkAllMailLog($html);

  }

  /**
   * Implements civicrm_tokens().
   */
  public function hook_tokens(&$tokens): void {
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
  public function hook_aggregateTokenValues(array &$values, $contactIDs, $job = NULL, $tokens = [], $context = NULL) {
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
  public function testIsHtmlTokenInTableCell($token, $entity, $textToSearch, $expected): void {
    $this->assertEquals($expected,
      CRM_Contribute_Form_Task_PDFLetter::isHtmlTokenInTableCell($token, $entity, $textToSearch)
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

  /**
   * @param array $contributionParams
   *
   * @return mixed
   */
  protected function createContribution(array $contributionParams = []) {
    $contributionParams = array_merge([
      'contact_id' => $this->individualCreate(),
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
      'source' => 'Contribution Source',
    ], $contributionParams);
    return $this->callAPISuccess('Contribution', 'create', $contributionParams)['id'];
  }

}
