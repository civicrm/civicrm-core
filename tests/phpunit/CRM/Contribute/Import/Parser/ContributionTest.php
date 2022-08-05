<?php
/**
 * @file
 * File for the CRM_Contribute_Import_Parser_ContributionTest class.
 */

use Civi\Api4\Contribution;
use Civi\Api4\ContributionSoft;
use Civi\Api4\OptionValue;
use Civi\Api4\UserJob;

/**
 *  Test Contribution import parser.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contribute_Import_Parser_ContributionTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Import_ParserTrait;

  /**
   * Default entity for class.
   *
   * @var string
   */
  protected $entity = 'Contribution';

  /**
   * Cleanup function.
   *
   * @throws \API_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_user_job', 'civicrm_queue', 'civicrm_queue_item'], TRUE);
    OptionValue::delete()->addWhere('name', '=', 'random')->execute();
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
  public function testImportParserWithSoftCreditsByExternalIdentifier(string $thousandSeparator): void {
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

    $mapping = [
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'external_identifier'],
      ['name' => 'soft_credit', 'soft_credit_type_id' => 1, 'soft_credit_match_field' => 'external_identifier'],
    ];
    $this->importCSV('contributions_amount_validate.csv', $mapping);

    $contributionsOfMainContact = Contribution::get()->addWhere('contact_id', '=', $contact1Id)->execute();
    // Although there are 2 rows in the csv, 1 should fail each time due to conflicting money formats.
    $this->assertCount(1, $contributionsOfMainContact, 'Wrong number of contributions imported');
    $this->assertEquals(1230.99, $contributionsOfMainContact->first()['total_amount']);
    $this->assertEquals(1230.99, $contributionsOfMainContact->first()['net_amount']);
    $this->assertEquals(0, $contributionsOfMainContact->first()['fee_amount']);

    $contributionsOfSoftContact = ContributionSoft::get()->addWhere('contact_id', '=', $contact2Id)->execute();
    $this->assertCount(1, $contributionsOfSoftContact, 'Contribution Soft not added for primary contact');
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Import_Parser::ERROR]));
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Contribute_Import_Parser_Contribution::SOFT_CREDIT]));
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Import_Parser::VALID]));
  }

  /**
   * Test payment types are passed.
   *
   * Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
   */
  public function testPaymentTypeLabel(): void {
    $this->addRandomOption();
    $contactID = $this->individualCreate();

    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'Check'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertEquals('Check', $contribution['payment_instrument']);

    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'not at all random'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID, 'payment_instrument_id' => 'random']);
    $this->assertEquals('not at all random', $contribution['payment_instrument']);
  }

  /**
   * Test handling of contribution statuses.
   */
  public function testContributionStatusLabel(): void {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'Check', 'contribution_status_id' => 'Pending'];
    // Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertEquals('Pending Label**', $contribution['contribution_status']);

    $this->addRandomOption('contribution_status');
    $values['contribution_status_id'] = 'not at all random';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID, 'contribution_status_id' => 'random']);
    $this->assertEquals('not at all random', $contribution['contribution_status']);

    $values['contribution_status_id'] = 'just say no';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID], 2);

    // Per https://lab.civicrm.org/dev/core/issues/1285 it's a bit arguable but Ok we can support id...
    $values['contribution_status_id'] = 3;
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID, 'contribution_status_id' => 3], 1);

  }

  /**
   * Test dates are parsed.
   */
  public function testParsedCustomDates(): void {
    $this->createCustomGroupWithFieldOfType([], 'date');
    $this->individualCreate(['external_identifier' => 'ext-1']);
    $mapping = [
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'external_identifier'],
      ['name' => $this->getCustomFieldName('date')],
    ];
    $this->importCSV('contributions_date_validate.csv', $mapping, ['dateFormats' => 32]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals('2019-10-26 00:00:00', $contribution['receive_date']);
    $this->assertEquals('2019-10-20 00:00:00', $contribution[$this->getCustomFieldName('date')]);
  }

  public function testParsedCustomOption(): void {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'Check', 'contribution_status_id' => 'Pending'];
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
   * Test importing to a pledge.
   */
  public function testPledgeImport(): void {
    $contactID = $this->individualCreate(['email' => 'mum@example.com']);
    $pledgeID = $this->pledgeCreate(['contact_id' => $contactID]);
    $this->importCSV('pledge.csv', [
      ['name' => 'email'],
      ['name' => 'total_amount'],
      ['name' => 'pledge_id'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
    ], ['onDuplicate' => CRM_Import_Parser::NO_MATCH]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Contribute_Import_Parser_Contribution::PLEDGE_PAYMENT]));
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Import_Parser::VALID]));
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->callAPISuccessGetSingle('PledgePayment', ['pledge_id' => $pledgeID, 'contribution_id' => $contribution['id']]);
  }

  /**
   * Test phone is included if it is part of dedupe rule.
   *
   * @throws \API_Exception
   */
  public function testPhoneMatchOnContact(): void {
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
    $this->assertArrayHasKey('phone', $fields);
    $this->callApiSuccess('RuleGroup', 'create', [
      'id' => $unsupervisedRuleGroup['id'],
      'used' => 'Unsupervised',
    ]);
    Civi\Api4\DedupeRule::delete()->addWhere('dedupe_rule_group_id', '=', $ruleGroup['id'])->execute();
    Civi\Api4\DedupeRuleGroup::delete()->addWhere('id', '=', $ruleGroup['id'])->execute();
  }

  /**
   * Test custom multi-value checkbox field is imported properly.
   */
  public function testCustomSerializedCheckBox(): void {
    $this->createCustomGroupWithFieldOfType([], 'checkbox');
    $customField = $this->getCustomFieldName('checkbox');
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', $customField => 'L,V'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP, NULL);
    $initialContribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertContains('L', $initialContribution[$customField], "Contribution Duplicate Skip Import contains L");
    $this->assertContains('V', $initialContribution[$customField], "Contribution Duplicate Skip Import contains V");

    // Now update.
    $values['contribution_id'] = $initialContribution['id'];
    $values[$customField] = 'V';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, NULL);

    $updatedContribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $initialContribution['id']]);
    $this->assertNotContains('L', $updatedContribution[$customField], "Contribution Duplicate Update Import does not contain L");
    $this->assertContains('V', $updatedContribution[$customField], "Contribution Duplicate Update Import contains V");

  }

  /**
   * Test the full form-flow import.
   */
  public function testImport() :void {
    $this->importCSV('contributions.csv', [
      ['name' => 'first_name'],
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'email'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('No matching Contact found for (mum@example.com )', $row['_status_message']);
  }

  public function testImportWithMatchByExternalIdentifier() :void {
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_contact AUTO_INCREMENT = 1000000");

    $contactRubyParams = [
      'first_name' => 'Ruby',
      'external_identifier' => 'ruby',
      'contact_type' => 'Individual',
    ];
    $contactSapphireParams = [
      'first_name' => 'Sapphire',
      'external_identifier' => 'sapphire',
      'contact_type' => 'Individual',
    ];
    $contactRubyId = $this->individualCreate($contactRubyParams);
    $contactSapphireId = $this->individualCreate($contactSapphireParams);

    // make sure we're testing dev/core#3784
    self::assertEquals(1, substr($contactRubyId, 0, 1));
    self::assertEquals(1, substr($contactSapphireId, 0, 1));

    $mapping = [
      ['name' => 'external_identifier'],
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
    ];
    $this->importCSV('contributions_match_external_id.csv', $mapping);

    $contributionsOfRuby = Contribution::get()
      ->addWhere('contact_id', '=', $contactRubyId)->execute();
    $contributionsOfSapphire = Contribution::get()
      ->addWhere('contact_id', '=', $contactSapphireId)->execute();

    $this->assertCount(1, $contributionsOfRuby, 'Wrong number of contributions imported');
    $this->assertCount(1, $contributionsOfSapphire, 'Wrong number of contributions imported');
    $this->assertEquals(22222, $contributionsOfRuby->first()['total_amount']);
    $this->assertEquals(5, $contributionsOfSapphire->first()['total_amount']);

    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $this->assertEquals(0, $dataSource->getRowCount([CRM_Import_Parser::ERROR]));
    $this->assertEquals(2, $dataSource->getRowCount([CRM_Import_Parser::VALID]));
  }

  /**
   * Run the import parser.
   *
   * @param array $originalValues
   *
   * @param int $onDuplicateAction
   * @param int|null $expectedResult
   * @param array|null $mappings
   * @param array|null $fields
   *   Array of field names. Will be calculated from $originalValues if not passed in.
   */
  protected function runImport(array $originalValues, int $onDuplicateAction, ?int $expectedResult = NULL, array $mappings = [], array $fields = NULL): void {
    if (!$fields) {
      $fields = array_keys($originalValues);
    }
    if ($mappings) {
      $mapper = $this->getMapperFromFieldMappings($mappings);
    }
    else {
      foreach ($fields as $field) {
        $mapper[] = [$field];
      }
    }
    $values = array_values($originalValues);
    $parser = new CRM_Contribute_Import_Parser_Contribution($fields);
    $parser->setUserJobID($this->getUserJobID([
      'onDuplicate' => $onDuplicateAction,
      'mapper' => $mapper,
    ]));
    $parser->init();
    $parser->import($values);
  }

  /**
   * @param array $submittedValues
   *
   * @return array
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function getUserJobID(array $submittedValues = []): int {
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => array_merge([
          'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
          'contactSubType' => '',
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'sqlQuery' => 'SELECT first_name FROM civicrm_contact',
          'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
          'dedupe_rule_id' => NULL,
          'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
        ], $submittedValues),
      ],
      'status_id:name' => 'draft',
      'job_type' => 'contribution_import',
    ])->execute()->first()['id'];
    if ($submittedValues['dataSource'] ?? NULL === 'CRM_Import_DataSource') {
      $dataSource = new CRM_Import_DataSource_CSV($userJobID);
    }
    else {
      $dataSource = new CRM_Import_DataSource_SQL($userJobID);
    }
    $dataSource->initialize();
    return $userJobID;
  }

  /**
   * Add a random extra option value
   *
   * @param string $optionGroup
   */
  protected function addRandomOption(string $optionGroup = 'payment_instrument'): void {
    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => $optionGroup,
      'value' => 777,
      'name' => 'random',
      'label' => 'not at all random',
    ]);
  }

  /**
   * Get the import's datasource form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Contribute_Import_Form_DataSource|\CRM_Core_Form|\CRM_Custom_Import_Form_DataSource
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getDataSourceForm(array $submittedValues) {
    return $this->getFormObject('CRM_Contribute_Import_Form_DataSource', $submittedValues);
  }

  /**
   * Get the import's mapField form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Contribute_Import_Form_MapField
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getMapFieldForm(array $submittedValues): CRM_Contribute_Import_Form_MapField {
    /* @var \CRM_Contribute_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contribute_Import_Form_MapField', $submittedValues);
    return $form;
  }

  /**
   * Get the import's preview form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Contribute_Import_Form_Preview
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getPreviewForm(array $submittedValues): CRM_Contribute_Import_Form_Preview {
    /* @var CRM_Contribute_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Contribute_Import_Form_Preview', $submittedValues);
    return $form;
  }

}
