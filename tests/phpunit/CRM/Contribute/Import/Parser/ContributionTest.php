<?php
/**
 * @file
 * File for the CRM_Contribute_Import_Parser_ContributionTest class.
 */

use Civi\Api4\Contribution;
use Civi\Api4\ContributionSoft;
use Civi\Api4\DedupeRule;
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Email;
use Civi\Api4\Import;
use Civi\Api4\Note;
use Civi\Api4\OptionValue;
use Civi\Api4\UserJob;

/**
 *  Test Contribution import parser.
 *
 * @package CiviCRM
 * @group headless
 * @group import
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
   * Original value for background processing.
   *
   * @var bool
   */
  protected $enableBackgroundQueueOriginalValue;

  /**
   * These extensions are inactive at the start. They may be activated during the test. They should be deactivated at the end.
   *
   * For the moment, the test is simply hard-coded to cleanup in a specific order. It's tempting to auto-detect and auto-uninstall these.
   * However, the shape of their dependencies makes it tricky to auto-uninstall (e.g. some exts have managed-entities that rely on other
   * exts -- you need to fully disable+uninstall the downstream managed-entity-ext before disabling or uninstalling the upstream
   * entity-provider-ext).
   *
   * You may need to edit `$toggleExts` whenever the dependency-graph changes.
   *
   * @var string[]
   */
  protected $toggleExts = ['civiimport', 'org.civicrm.afform', 'authx'];

  protected function setUp(): void {
    parent::setUp();
    $originalExtensions = array_column(CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'fullName');
    $this->assertEquals([], array_intersect($originalExtensions, $this->toggleExts), 'These extensions may be enabled and disabled during the test. The start-state and end-state should be the same. It appears that we have an unexpected start-state. Perhaps another test left us with a weird start-state?');
    $this->enableBackgroundQueueOriginalValue = Civi::settings()->get('enableBackgroundQueue');
  }

  /**
   * Cleanup function.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_user_job', 'civicrm_queue', 'civicrm_queue_item'], TRUE);
    OptionValue::delete()->addWhere('name', '=', 'random')->execute();
    DedupeRule::delete()
      ->addWhere('rule_table', '!=', 'civicrm_email')
      ->addWhere('dedupe_rule_group_id.name', '=', 'IndividualUnsupervised')->execute();
    foreach ($this->toggleExts as $ext) {
      CRM_Extension_System::singleton()->getManager()->disable([$ext]);
      CRM_Extension_System::singleton()->getManager()->uninstall([$ext]);
    }
    Civi::settings()->set('enableBackgroundQueue', $this->enableBackgroundQueueOriginalValue);
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
   * @throws \CRM_Core_Exception
   */
  public function testImportParserWithSoftCreditsByExternalIdentifier(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $mainContactID = $this->individualCreate([
      'first_name' => 'Contact',
      'last_name' => 'One',
      'external_identifier' => 'ext-1',
      'contact_type' => 'Individual',
    ]);
    $softCreditContactID = $this->individualCreate([
      'first_name' => 'Contact',
      'last_name' => 'Two',
      'external_identifier' => 'ext-2',
      'contact_type' => 'Individual',
    ]);

    $mapping = [
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'external_identifier'],
      ['name' => 'soft_credit.contact.external_identifier', 'soft_credit_type_id' => 1],
      ['name' => ''],
    ];
    $this->importCSV('contributions_amount_validate.csv', $mapping, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);

    $contributionsOfMainContact = Contribution::get()->addWhere('contact_id', '=', $mainContactID)->execute();
    // Although there are 2 rows in the csv, 1 should fail each time due to conflicting money formats.
    $this->assertCount(1, $contributionsOfMainContact, 'Wrong number of contributions imported');
    $this->assertEquals(1230.99, $contributionsOfMainContact->first()['total_amount']);
    $this->assertEquals(1230.99, $contributionsOfMainContact->first()['net_amount']);
    $this->assertEquals(0, $contributionsOfMainContact->first()['fee_amount']);

    $contributionsOfSoftContact = ContributionSoft::get()->addWhere('contact_id', '=', $softCreditContactID)->execute();
    $this->assertCount(1, $contributionsOfSoftContact, 'Contribution Soft not added for primary contact');
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Import_Parser::ERROR]));
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Contribute_Import_Parser_Contribution::SOFT_CREDIT]));
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Import_Parser::VALID]));

    // Now try the import with the dots swapped to double underscores. The parser
    // layer and api understand the dots - but QuickForm has to play switcheroo as the dots
    // break the hierarchical multiselect js. QuickForm uses a double underscore as a stand in.;
    $this->validateSoftCreditImport([
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'external_identifier'],
      ['name' => 'soft_credit__contact__external_identifier', 'soft_credit_type_id' => 1],
    ]);
    $this->validateSoftCreditImport([
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'external_identifier'],
      [],
      [],
      [],
      ['name' => 'soft_credit__contact__id', 'soft_credit_type_id' => 1],
    ]);
  }

  /**
   * Test import parser can add to a soft credit contact of a different type.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportParserWithSoftCreditDifferentContactType(): void {
    $mainContactID = $this->individualCreate([
      'first_name' => 'Contact',
      'last_name' => 'One',
      'email' => 'harry@example.com',
      'external_identifier' => 'ext-1',
      'contact_type' => 'Individual',
    ]);
    $softCreditContactID = $this->individualCreate([
      'organization_name' => 'The firm',
      'external_identifier' => 'ext-2',
      'email' => 'the-firm@example.com',
      'contact_type' => 'Organization',
    ]);

    $mapping = [
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => ''],
      ['name' => ''],
      ['name' => 'email_primary.email'],
      ['name' => 'soft_credit.contact.email_primary.email', 'soft_credit_type_id' => 1],
    ];
    $this->importCSV('contributions_amount_validate.csv', $mapping, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);

    $contributionsOfMainContact = Contribution::get()->addWhere('contact_id', '=', $mainContactID)->execute();
    // Although there are 2 rows in the csv, 1 should fail each time due to conflicting money formats.
    $this->assertCount(1, $contributionsOfMainContact, 'Wrong number of contributions imported');

    $contributionsOfSoftContact = ContributionSoft::get()->addWhere('contact_id', '=', $softCreditContactID)->execute();
    $this->assertCount(1, $contributionsOfSoftContact, 'Contribution Soft not added for primary contact');
  }

  /**
   * Test payment types are passed.
   *
   * Note that the expected result should logically be CRM_Import_Parser::valid
   * but writing test to reflect not fix here
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentTypeLabel(): void {
    $this->addRandomOption();
    $contactID = $this->individualCreate();

    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'Check'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertEquals('Check', $contribution['payment_instrument']);

    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'not at all random'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID, 'payment_instrument_id' => 'random']);
    $this->assertEquals('not at all random', $contribution['payment_instrument']);
  }

  /**
   * Test handling of contribution statuses.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionStatusLabel(): void {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'Check', 'contribution_status_id' => 'Pending'];
    // Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertEquals('Pending Label**', $contribution['contribution_status']);

    $this->addRandomOption('contribution_status');
    $values['contribution_status_id'] = 'not at all random';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID, 'contribution_status_id' => 'random']);
    $this->assertEquals('not at all random', $contribution['contribution_status']);

    $values['contribution_status_id'] = 'just say no';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID], 2);

    // Per https://lab.civicrm.org/dev/core/issues/1285 it's a bit arguable but Ok we can support id...
    $values['contribution_status_id'] = 3;
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $this->callAPISuccessGetCount('Contribution', ['contact_id' => $contactID, 'contribution_status_id' => 3], 1);

  }

  /**
   * Test the an import can be done based on saved configuration in the UserJob.
   *
   * This also demonstrates some advanced import handling that the quickForm
   * layer does not support but if you can get the config INTO the user_job
   * table it runs... (ie via the angular form).
   *
   * These features are
   *  - default_value for each field.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isBackGroundProcessing
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportFromUserJobConfiguration(bool $isBackGroundProcessing): void {
    Civi::settings()->set('enableBackgroundQueue', $isBackGroundProcessing);
    $this->createLoggedInUser();
    $importMappings = [
      ['name' => 'organization_name'],
      ['name' => 'legal_name'],
      ['name' => 'total_amount'],
      // Note that default_value is supported via the parser and the angular form
      // but there is no way to enter it on the quick form.
      ['name' => 'financial_type_id', 'default_value' => 'Donation'],
      ['name' => 'contribution_source'],
      ['name' => 'receive_date'],
      ['name' => 'external_identifier'],
      ['name' => 'soft_credit.contact.email_primary.email', 'entity_data' => ['soft_credit' => ['soft_credit_type_id' => 5]]],
      ['name' => 'soft_credit.contact.first_name', 'entity_data' => ['soft_credit' => ['soft_credit_type_id' => 5]]],
      ['name' => 'soft_credit.contact.last_name', 'entity_data' => ['soft_credit' => ['soft_credit_type_id' => 5]]],
      [],
    ];
    $submittedValues = [
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'contactType' => 'Organization',
      'mapper' => $this->getMapperFromFieldMappings($importMappings),
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
    ];
    $this->submitDataSourceForm('soft_credit_extended.csv', $submittedValues);
    $metadata = UserJob::get()->addWhere('id', '=', $this->userJobID)->addSelect('metadata')->execute()->first()['metadata'];
    $metadata['import_mappings'] = $importMappings;
    $metadata['entity_configuration'] = [
      'Contribution' => ['action' => 'create'],
      'Contact' => [
        'action' => 'create',
        'contact_type' => 'Organization',
        'dedupe_rule' => 'OrganizationUnsupervised',
      ],
      'SoftCreditContact' => [
        'contact_type' => 'Individual',
        'action' => 'create',
        'dedupe_rule' => 'IndividualSupervised',
        'entity_data' => [
          'soft_credit' => [
            'soft_credit_type_id' => 1,
          ],
        ],
      ],
    ];
    UserJob::update()->addWhere('id', '=', $this->userJobID)
      ->setValues(['metadata' => $metadata])->execute();
    $form = $this->getMapFieldForm($submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertTrue($form->validate());
    $form->postProcess();
    $row = $this->getDataSource()->getRow();
    // a valid status here means it has been able to incorporate the default_value.
    $this->assertEquals('VALID', $row['_status']);

    $this->submitPreviewForm($submittedValues);
    $row = $this->getDataSource()->getRow();
    // a valid status here means it has been able to incorporate the default_value.
    $this->assertEquals('soft_credit_imported', $row['_status']);
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

  /**
   * @throws \CRM_Core_Exception
   */
  public function testParsedCustomOption(): void {
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', 'payment_instrument_id' => 'Check', 'contribution_status_id' => 'Pending'];
    // Note that the expected result should logically be CRM_Import_Parser::valid but writing test to reflect not fix here
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['contact_id' => $contactID]);
    $this->createCustomGroupWithFieldOfType([], 'radio');
    $values['contribution_id'] = $contribution['id'];
    $values[$this->getCustomFieldName('radio')] = 'Red Testing';
    unset(Civi::$statics['CRM_Core_BAO_OptionGroup']);
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE);
    $contribution = $this->callAPISuccess('Contribution', 'get', ['contact_id' => $contactID, $this->getCustomFieldName('radio') => 'Red Testing']);
    $this->assertEquals(5, $contribution['values'][$contribution['id']]['custom_' . $this->ids['CustomField']['radio']]);
  }

  /**
   * Test importing to a pledge.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPledgeImport(): void {
    $contactID = $this->individualCreate(['email' => 'mum@example.com']);
    $pledgeID = $this->pledgeCreate(['contact_id' => $contactID]);
    $this->importCSV('pledge.csv', [
      ['name' => 'email_primary.email'],
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
   * @throws \CRM_Core_Exception
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
    $parser = new CRM_Contribute_Import_Parser_Contribution();
    $parser->setUserJobID($this->getUserJobID());
    $fields = $parser->getFieldsMetadata();
    $this->assertArrayHasKey('phone_primary.phone', $fields);
    $this->callApiSuccess('RuleGroup', 'create', [
      'id' => $unsupervisedRuleGroup['id'],
      'used' => 'Unsupervised',
    ]);
    Civi\Api4\DedupeRule::delete()->addWhere('dedupe_rule_group_id', '=', $ruleGroup['id'])->execute();
    Civi\Api4\DedupeRuleGroup::delete()->addWhere('id', '=', $ruleGroup['id'])->execute();
  }

  /**
   * Test custom multi-value checkbox field is imported properly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomSerializedCheckBox(): void {
    $this->createCustomGroupWithFieldOfType([], 'checkbox');
    $customField = $this->getCustomFieldName('checkbox');
    $contactID = $this->individualCreate();
    $values = ['contribution_contact_id' => $contactID, 'total_amount' => 10, 'financial_type_id' => 'Donation', $customField => 'L,V'];
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_SKIP);
    $initialContribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $contactID]);
    $this->assertContains('L', $initialContribution[$customField], 'Contribution Duplicate Skip Import contains L');
    $this->assertContains('V', $initialContribution[$customField], 'Contribution Duplicate Skip Import contains V');

    // Now update.
    $values['contribution_id'] = $initialContribution['id'];
    $values[$customField] = 'V';
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE);

    $updatedContribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $initialContribution['id']]);
    $this->assertNotContains('L', $updatedContribution[$customField], 'Contribution Duplicate Update Import does not contain L');
    $this->assertContains('V', $updatedContribution[$customField], 'Contribution Duplicate Update Import contains V');

  }

  /**
   * Test the full form-flow import.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportNoMatch() :void {
    $dataSource = $this->importContributionsDotCSV();
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('No matching Contact found', $row['_status_message']);
  }

  /**
   * Test the full form-flow import.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportMatch() :void {
    $this->individualCreate(['email' => 'mum@example.com']);
    $this->importContributionsDotCSV();
    $contribution = Contribution::get()->execute()->first();
    $this->assertEquals('Word of mouth', $contribution['source']);
    $note = Note::get()
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')->execute()->first();
    $this->assertEquals('Call him back', $note['note']);

    // Now change the note & re-do it. The same note should be updated.
    Note::update()
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addValue('note', 'changed')
      ->execute();
    $this->importContributionsDotCSV(['onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE]);
    $note = Note::get()
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')->execute()->first();
    $this->assertEquals('Call him back', $note['note']);

  }

  /**
   * Tests the form flow copes with QuickForm style dots.
   *
   * Because the QuickForm hierarchical select won't cope with dots
   * we are using a double underscore on that form. The test checks that works.
   */
  public function testImportQuickFormEmailMatch() :void {
    $this->individualCreate(['email' => 'jenny@example.com']);
    $this->importCSV('checkboxes.csv', [
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => ''],
      ['name' => 'email_primary__email'],
      ['name' => ''],
    ]);
  }

  /**
   * Test whether importing a contribution using email match will match a non-primary.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportMatchNonPrimary(): void {
    $anthony = $this->individualCreate();
    Email::create()->setValues([
      'contact_id' => $anthony,
      'location_type_id:name' => 'Billing',
      'is_primary' => FALSE,
      'email' => 'mum@example.com',
    ])->execute();
    $this->importContributionsDotCSV();
    $contribution = Contribution::get()->execute()->first();
    $this->assertEquals($anthony, $contribution['contact_id']);
  }

  /**
   * Test import parser will consider a rule valid including a custom field.
   *
   * @dataProvider validateData
   */
  public function testValidateMappingWithCustomDedupeRule($data): void {
    $this->addToDedupeRule();
    // First we try to create without total_amount mapped.
    // It will fail in create mode as total_amount is required for create.
    $mappings = [
      ['name' => 'financial_type_id'],
      ['name' => 'total_amount'],
    ];
    foreach ($data['fields'] as $field) {
      $mappings[] = ['name' => $field === 'custom' ? $this->getCustomFieldName() : $field];
    }
    $this->submitDataSourceForm('contributions.csv', ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);
    $form = $this->getMapFieldForm([
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
      'mapper' => $this->getMapperFromFieldMappings($mappings),
      'contactType' => 'Individual',
    ]);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertEquals($data['valid'], $form->validate(), print_r($form->_errors, TRUE));
  }

  /**
   * Get data to test validation on.
   *
   * Enough is email or any combo of first_name, last_name, custom field.
   *
   * @return array
   */
  public function validateData(): array {
    return [
      'email_first_name_last_name' => [['fields' => ['email', 'first_name', 'last_name'], 'valid' => TRUE]],
      'email_last_name' => [['fields' => ['email', 'last_name'], 'valid' => TRUE]],
      'email_first_name' => [['fields' => ['email', 'first_name'], 'valid' => TRUE]],
      'first_name_last_name' => [['fields' => ['first_name', 'last_name'], 'valid' => TRUE]],
      'email' => [['fields' => ['email'], 'valid' => TRUE]],
      'first_name' => [['fields' => ['first_name'], 'valid' => FALSE]],
      'last_name' => [['fields' => ['last_name'], 'valid' => FALSE]],
      'last_name_custom' => [['fields' => ['last_name', 'custom'], 'valid' => TRUE]],
      'first_name_custom' => [['fields' => ['first_name', 'custom'], 'valid' => TRUE]],
      'custom' => [['fields' => ['custom'], 'valid' => FALSE]],
    ];
  }

  /**
   * Test that a trxn_id is enough in update mode to void the total_amount requirement.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportFieldsNotRequiredWithTrxnID(): void {
    $this->individualCreate(['email' => 'mum@example.com']);
    $this->campaignCreate();
    $this->callAPISuccess('System', 'flush', []);
    $fieldMappings = [
      ['name' => 'first_name'],
      ['name' => ''],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'email_primary.email'],
      ['name' => ''],
      ['name' => ''],
      ['name' => 'trxn_id'],
      ['name' => 'contribution_campaign_id'],
      ['name' => 'contribution_contact_id'],
    ];
    // First we try to create without total_amount mapped.
    // It will fail in create mode as total_amount is required for create.
    $this->submitDataSourceForm('contributions.csv', $fieldMappings);
    $form = $this->getMapFieldForm([
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
      'mapper' => $this->getMapperFromFieldMappings($fieldMappings),
      'contactType' => 'Individual',
    ]);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertFalse($form->validate());
    $this->assertEquals(['_qf_default' => 'Missing required field: Total Amount'], $form->_errors);

    // Now we add in total amount - it works in create mode.
    $fieldMappings[1]['name'] = 'total_amount';
    $this->importCSV('contributions.csv', $fieldMappings, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);

    $row = $this->getDataSource()->getRows()[0];
    $this->assertEquals('IMPORTED', $row[11]);
    $contribution = Contribution::get()->addSelect('source', 'id')->execute()->first();
    $this->assertEmpty($contribution['source']);

    // Now we re-import as an update, only setting the 'source' field.
    $fieldMappings = [
      ['name' => ''],
      ['name' => ''],
      ['name' => ''],
      ['name' => ''],
      ['name' => ''],
      ['name' => ''],
      ['name' => 'contribution_source'],
      ['name' => 'trxn_id'],
      ['name' => 'contribution_campaign_id'],
    ];
    $this->importCSV('contributions.csv', $fieldMappings, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE]);

    $row = $this->getDataSource()->getRows()[0];
    $this->assertEquals('IMPORTED', $row[11]);
    $contribution = Contribution::get()->addSelect('source', 'id')->execute()->first();
    $this->assertEquals('Call him back', $contribution['source']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testImportWithMatchByExternalIdentifier() :void {
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contact AUTO_INCREMENT = 1000000');

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
    $this->assertEquals(1, substr($contactRubyId, 0, 1));
    $this->assertEquals(1, substr($contactSapphireId, 0, 1));

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
   * @param array|null $mappings
   * @param array|null $fields
   *   Array of field names. Will be calculated from $originalValues if not passed in.
   *
   * @throws \CRM_Core_Exception
   */
  protected function runImport(array $originalValues, int $onDuplicateAction, array $mappings = [], array $fields = NULL): void {
    if (!$fields) {
      $fields = array_keys($originalValues);
    }
    if ($mappings) {
      $mapper = $this->getMapperFromFieldMappings($mappings);
    }
    else {
      $mapper = [];
      foreach ($fields as $field) {
        $mapper[] = [$field];
      }
    }
    $values = array_values($originalValues);
    $parser = new CRM_Contribute_Import_Parser_Contribution();
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
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getUserJobID(array $submittedValues = []): int {
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => array_merge([
          'contactType' => 'Individual',
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
   * Test that existing contributions are found and updated.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportUpdateExisting(): void {
    $this->contributionCreate([
      'contact_id' => $this->individualCreate(),
      'trxn_id' => 'abc',
      'invoice_id' => '65',
      'total_amount' => 8,
      'financial_type_id:name' => 'Event Fee',
    ]);
    $mapping = [
      ['name' => 'contribution_id'],
      ['name' => 'invoice_id'],
      ['name' => 'trxn_id'],
      ['name' => ''],
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'contribution_source'],
      ['name' => ''],
    ];
    $this->importCSV('contributions_update.csv', $mapping, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE]);
    $rows = $this->getDataSource()->getRows();
    foreach ($rows as $row) {
      if ($row[8] === 'valid') {
        $this->assertEquals('IMPORTED', $row[10], $row[11]);
      }
      else {
        $this->assertEquals('ERROR', $row[10], $row[11] . print_r($rows, TRUE));
      }
    }
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
   * @return \CRM_Contribute_Import_Form_DataSource
   * @noinspection PhpIncompatibleReturnTypeInspection
   */
  protected function getDataSourceForm(array $submittedValues): CRM_Contribute_Import_Form_DataSource {
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
    /** @var \CRM_Contribute_Import_Form_MapField $form */
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
    /** @var CRM_Contribute_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Contribute_Import_Form_Preview', $submittedValues);
    return $form;
  }

  /**
   * @param array $submittedValues
   *
   * @return \CRM_Import_DataSource_CSV
   */
  private function importContributionsDotCSV(array $submittedValues = []): CRM_Import_DataSource_CSV {
    $this->importCSV('contributions.csv', [
      ['name' => 'first_name'],
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'email_primary.email'],
      ['name' => 'contribution_source'],
      ['name' => 'note'],
      ['name' => 'trxn_id'],
    ], $submittedValues);
    return new CRM_Import_DataSource_CSV($this->userJobID);
  }

  /**
   * Enhance field such that any combo of the custom field & first/last name is enough.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function addToDedupeRule(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Contact']);
    $dedupeRuleGroupID = DedupeRuleGroup::get()
      ->addWhere('name', '=', 'IndividualUnsupervised')
      ->addSelect('id')
      ->execute()
      ->first()['id'];
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $dedupeRuleGroupID,
      'rule_weight' => 5,
      'rule_table' => $this->getCustomGroupTable(),
      'rule_field' => $this->getCustomFieldColumnName('text'),
    ]);
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $dedupeRuleGroupID,
      'rule_weight' => 5,
      'rule_table' => 'civicrm_contact',
      'rule_field' => 'first_name',
    ]);
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $dedupeRuleGroupID,
      'rule_weight' => 5,
      'rule_table' => 'civicrm_contact',
      'rule_field' => 'last_name',
    ]);
  }

  /**
   * Test the Import api works from the extension when the extension is enabled after the import.
   */
  public function testEnableExtension(): void {
    $this->importContributionsDotCSV();
    $this->callAPISuccess('Extension', 'enable', ['key' => 'civiimport']);
    $result = Import::get($this->userJobID)->execute();
    $this->assertEquals('ERROR', $result->first()['_status']);
  }

  /**
   * @param array $mapping
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function validateSoftCreditImport(array $mapping): void {
    Contribution::delete()->addWhere('id', '>', 0)->execute();
    $this->callAPISuccessGetCount('ContributionSoft', [], 0);
    $this->importCSV('contributions_amount_validate.csv', $mapping, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    // Check a row imported.
    $this->assertEquals(1, $dataSource->getRowCount([CRM_Import_Parser::VALID]));
    $this->callAPISuccessGetCount('ContributionSoft', [], 1);
  }

}
