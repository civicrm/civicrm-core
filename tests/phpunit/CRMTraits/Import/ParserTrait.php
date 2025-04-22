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

use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\UserJob;

/**
 * Trait ParserTrait
 *
 * Trait for testing imports.
 */
trait CRMTraits_Import_ParserTrait {

  /**
   * @var int
   */
  protected $userJobID;

  /**
   * Import the csv file values.
   *
   * This function uses a flow that mimics the UI flow.
   *
   * @param string $csv Name of csv file.
   * @param array $fieldMappings
   * @param array $submittedValues
   *
   * @throws \CRM_Core_Exception
   */
  protected function importCSV(string $csv, array $fieldMappings, array $submittedValues = []): void {
    $submittedValues = array_merge([
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'contactType' => 'Individual',
      'mapper' => $this->getMapperFromFieldMappings($fieldMappings),
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
      'groups' => [],
    ], $submittedValues);
    $this->submitDataSourceForm($csv, $submittedValues);
    $form = $this->getMapFieldForm($submittedValues);
    $form->setUserJobID($this->userJobID);
    $userJobMetadata = UserJob::get()
      ->addWhere('id', '=', $this->userJobID)
      ->execute()->first()['metadata'];
    $userJobMetadata['import_mappings'] = $fieldMappings;
    UserJob::update()
      ->addWhere('id', '=', $this->userJobID)
      ->setValues([
        'metadata' => $userJobMetadata,
      ])
      ->execute();
    $form->buildForm();
    $this->assertTrue($form->validate());
    $form->postProcess();
    $this->submitPreviewForm($submittedValues);
  }

  /**
   * Submit the preview form, triggering the import.
   *
   * @param array $submittedValues
   */
  protected function submitPreviewForm(array $submittedValues): void {
    $form = $this->getPreviewForm($submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertTrue($form->validate());

    try {
      $form->postProcess();
      $this->fail('Expected a redirect');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $queue = Civi::queue('user_job_' . $this->userJobID);
      if (CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_queue_item')) {
        $item = $queue->claimItem(0);
        $this->assertEquals(['contactId' => CRM_Core_Session::getLoggedInContactID(), 'domainId' => CRM_Core_Config::domainID()], $item->data->runAs);
        $queue->releaseItem($item);
        $runner = new CRM_Queue_Runner([
          'queue' => $queue,
          'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
        ]);
        $result = $runner->runAll();
        $this->assertEquals(TRUE, $result, $result === TRUE ? '' : CRM_Core_Error::formatTextException($result['exception']));
      }
      else {
        throw new CRM_Core_Exception('nothing queued');
      }
    }
  }

  /**
   * @param array $mappings
   *
   * @return array
   */
  protected function getMapperFromFieldMappings(array $mappings): array {
    $mapper = [];
    foreach ($mappings as $mapping) {
      $fieldInput = [$mapping['name'] ?? ''];
      if (!empty($mapping['soft_credit_type_id'])) {
        $fieldInput[1] = $mapping['soft_credit_type_id'];
      }
      $mapper[] = $fieldInput;
    }
    return $mapper;
  }

  /**
   * @return \CRM_Import_DataSource
   */
  protected function getDataSource(): CRM_Import_DataSource {
    return new CRM_Import_DataSource_CSV($this->userJobID);
  }

  /**
   * Submit the data source form.
   *
   * @param string $csv
   * @param array $submittedValues
   */
  protected function submitDataSourceForm(string $csv, $submittedValues): void {
    $reflector = new ReflectionClass(get_class($this));
    $directory = dirname($reflector->getFileName());
    $submittedValues = array_merge([
      'uploadFile' => ['name' => $directory . '/data/' . $csv],
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'contactType' => 'Individual',
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
      'groups' => [],
    ], $submittedValues);
    $form = $this->getDataSourceForm($submittedValues);
    $values = $_SESSION['_' . $form->controller->_name . '_container']['values'];
    $form->buildForm();
    $form->postProcess();
    $this->userJobID = $form->getUserJobID();
    // This gets reset in DataSource so re-do....
    $_SESSION['_' . $form->controller->_name . '_container']['values'] = $values;
  }

  /**
   * Enhance field such that any combo of the custom field & first/last name is enough.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function addToDedupeRule(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Contact']);
    $dedupeRuleGroup = DedupeRuleGroup::get()
      ->addWhere('name', '=', 'IndividualUnsupervised')
      ->addSelect('id', 'threshold')
      ->execute()
      ->first();
    $this->assertEquals(10, $dedupeRuleGroup['threshold']);
    $dedupeRuleGroupID = $this->ids['DedupeRule']['unsupervised'] = $dedupeRuleGroup['id'];
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
    $this->createTestEntity('DedupeRule', [
      'dedupe_rule_group_id' => $dedupeRuleGroupID,
      'rule_weight' => 5,
      'rule_table' => 'civicrm_address',
      'rule_field' => 'street_address',
    ]);
  }

}
