<?php
/**
 * @file
 * File for the CRM_Contribute_Import_Parser_ContributionTest class.
 */
/**
 *  Test Contribution import parser.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contribute_Import_Parser_ContributionTest extends CiviUnitTestCase {
  protected $_tablesToTruncate = [];
  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Default entity for class.
   *
   * @var string
   */
  protected $entity = 'Contribution';

  /**
   * Setup function.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test import parser will add contribution and soft contribution each for different contact.
   *
   * In this case primary contact and secondary contact both are identified by external identifier.
   *
   * @dataProvider getThousandSeparators
   *
   * @param string $thousandSeparator
   *
   * @throws \Exception
   */
  public function testImportParserWithSoftCreditsByExternalIdentifier($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $contact1Params = [
      'first_name' => 'Contact',
      'last_name' => 'One',
      'external_identifier' => 'ext-1',
      'contact_type' => 'Individual',
    ];
    $contact2Params = [
      'first_name' => 'Contact',
      'last_name' => 'Two',
      'external_identifier' => 'ext-2',
      'contact_type' => 'Individual',
    ];
    $contact1Id = $this->individualCreate($contact1Params);
    $contact2Id = $this->individualCreate($contact2Params);
    $values = [
      "total_amount" => $this->formatMoneyInput(1230.99),
      "financial_type" => "Donation",
      "external_identifier" => "ext-1",
      "soft_credit" => "ext-2",
    ];
    $mapperSoftCredit = [NULL, NULL, NULL, "external_identifier"];
    $mapperSoftCreditType = [NULL, NULL, NULL, "1"];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Contribute_Import_Parser::SOFT_CREDIT, $mapperSoftCredit, NULL, $mapperSoftCreditType);
    $params = ['contact_id' => $contact1Id];
    $values = [];
    $contributionsOfMainContact = CRM_Contribute_BAO_Contribution::retrieve($params, $values, $values);
    $this->assertEquals(1230.99, $contributionsOfMainContact->total_amount);
    $this->assertEquals(1230.99, $contributionsOfMainContact->net_amount);
    $this->assertEquals(0, $contributionsOfMainContact->fee_amount);

    $params["contact_id"] = $contact2Id;
    $contributionsOfSoftContact = CRM_Contribute_BAO_ContributionSoft::retrieve($params, $values);
    $this->assertEquals(1, $contributionsOfMainContact->N, 'Contribution not added for primary contact');
    $this->assertEquals(1, $contributionsOfSoftContact->N, 'Soft Contribution not added for secondary contact');
    $this->callAPISuccess('ContributionSoft', 'Delete', ['id' => $contributionsOfSoftContact->id]);
    $this->callAPISuccess('Contribution', 'Delete', ['id' => $contributionsOfMainContact->id]);
  }

  /**
   * Test dates are parsed
   */
  public function testParsedDates() {
    $mapperKeys = [];
    $form = new CRM_Contribute_Import_Parser_Contribution($mapperKeys);
    $params = ['receive_date' => '20/10/2019'];
    CRM_Core_Session::singleton()->set('dateTypes', 32);
    $form->formatDateFields($params);
    $this->assertEquals('20191020', $params['receive_date']);

    $params = ['receive_date' => '20/10/2019'];
    CRM_Core_Session::singleton()->set('dateTypes', 32);
    $form->formatInput($params);
    $this->assertEquals('20191020', $params['receive_date']);
  }

  /**
   * Test payment types are passed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentTypeLabel() {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type' => 'Donation', 'payment_instrument' => 'Check'];
    // Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertEquals('Check', $contribution['payment_instrument']);

    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'payment_instrument',
      'value' => 777,
      'name' => 'random',
      'label' => 'not at all random',
    ]);
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type' => 'Donation', 'payment_instrument' => 'not at all random'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID, 'payment_instrument_id' => 'random']);
    $this->assertEquals('not at all random', $contribution['payment_instrument']);
  }

  /**
   * Test handling of contribution statuses.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionStatusLabel() {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type' => 'Donation', 'payment_instrument' => 'Check', 'contribution_status_id' => 'Pending'];
    // Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertEquals('Pending Label**', $contribution['contribution_status']);

    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'contribution_status',
      'value' => 777,
      'name' => 'random',
      'label' => 'not at all random',
    ]);
    $values['contribution_status_id'] = 'not at all random';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID, 'contribution_status_id' => 'random']);
    $this->assertEquals('not at all random', $contribution['contribution_status']);

    $values['contribution_status_id'] = 'just say no';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::ERROR);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID], 2);

    // Per https://lab.civicrm.org/dev/core/issues/1285 it's a bit arguable but Ok we can support id...
    $values['contribution_status_id'] = 3;
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID, 'contribution_status_id' => 3], 1);

  }

  /**
   * Test dates are parsed
   *
   * @throws \CRM_Core_Exception
   */
  public function testParsedCustomDates() {
    $this->createCustomGroupWithFieldOfType([], 'date');
    $mapperKeys = [];
    $form = new CRM_Contribute_Import_Parser_Contribution($mapperKeys);
    $params = [$this->getCustomFieldName('date') => '20/10/2019'];
    CRM_Core_Session::singleton()->set('dateTypes', 32);
    $formatted = [];
    $form->formatInput($params, $formatted);
    // @todo I feel like we should work towards this actually parsing $params here -
    // & dropping formatting but
    // per https://github.com/civicrm/civicrm-core/pull/14986 for now $formatted is parsing
    // The issue I hit was that when I tried to extend to checking they were correctly imported
    // I was not actually sure what correct behaviour was for what dates were accepted since
    // on one hand the custom fields have a date format & on the other there is an input format &
    // it seems to ignore the latter in favour of the former - which seems wrong.
    $this->assertEquals('20191020000000', $formatted[$this->getCustomFieldName('date')]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $this->ids['CustomField']['date']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $this->ids['CustomGroup']['Custom Group']]);
  }

  public function testParsedCustomOption() {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type' => 'Donation', 'payment_instrument' => 'Check', 'contribution_status_id' => 'Pending'];
    // Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['contact_id' => $contactID]);
    $this->createCustomGroupWithFieldOfType([], 'radio');
    $values['contribution_id'] = $contribution['id'];
    $values[$this->getCustomFieldName('radio')] = 'Red Testing';
    unset(Civi::$statics['CRM_Core_BAO_OptionGroup']);
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccess('Contribution', 'get', ['contact_id' => $contactID, $this->getCustomFieldName('radio') => 'Red Testing']);
    $this->assertEquals(5, $contribution['values'][$contribution['id']]['custom_' . $this->ids['CustomField']['radio']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $this->ids['CustomField']['radio']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $this->ids['CustomGroup']['Custom Group']]);
  }

  /**
   * Test phone is included if it is part of dedupe rule.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPhoneMatchOnContact() {
    // Update existing unsupervised rule, change to general.
    $unsupervisedRuleGroup = $this->callApiSuccess('RuleGroup', 'getsingle', [
      'used' => 'Unsupervised',
      'contact_type' => 'Individual',
    ]);
    $this->callApiSuccess('RuleGroup', 'create', [
      'id' => $unsupervisedRuleGroup['id'],
      'used' => 'General',
    ]);

    // Create new unsupervised rule with Phone field.
    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'Unsupervised',
      'name' => 'MatchingPhone',
      'title' => 'Matching Phone',
      'is_reserved' => 0,
    ]);
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $ruleGroup['id'],
      'rule_table' => 'civicrm_phone',
      'rule_weight' => 10,
      'rule_field' => 'phone_numeric',
    ]);
    $fields = CRM_Contribute_BAO_Contribution::importableFields();
    $this->assertTrue(array_key_exists('phone', $fields));
  }

  /**
   * Run the import parser.
   *
   * @param array $originalValues
   *
   * @param int $onDuplicateAction
   * @param int $expectedResult
   * @param array|null $mapperSoftCredit
   * @param array|null $mapperPhoneType
   * @param array|null $mapperSoftCreditType
   * @param array|null $fields
   *   Array of field names. Will be calculated from $originalValues if not passed in.
   */
  protected function runImport($originalValues, $onDuplicateAction, $expectedResult, $mapperSoftCredit = NULL, $mapperPhoneType = NULL, $mapperSoftCreditType = NULL, $fields = NULL) {
    if (!$fields) {
      $fields = array_keys($originalValues);
    }
    $values = array_values($originalValues);
    $parser = new CRM_Contribute_Import_Parser_Contribution($fields, $mapperSoftCredit, $mapperPhoneType, $mapperSoftCreditType);
    $parser->_contactType = 'Individual';
    $parser->init();
    $this->assertEquals($expectedResult, $parser->import($onDuplicateAction, $values), 'Return code from parser import was not as expected');
  }

}
