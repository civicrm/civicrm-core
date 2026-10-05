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
use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;

/**
 * Trait ParserTrait
 *
 * Trait for testing imports.
 */
trait CRMTraits_Import_ParserTrait {

  use FormTrait;

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
   * @param string $action
   * @param array $entityConfiguration
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function importCSV(string $csv, array $fieldMappings, array $submittedValues = [], string $action = 'create', array $entityConfiguration = []): void {
    $submittedValues = array_merge([
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'mapper' => $this->getMapperFromFieldMappings($fieldMappings),
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'groups' => [],
    ], $submittedValues);
    $this->submitDataSourceForm($csv, $submittedValues);
    $userJobMetadata = UserJob::get()
      ->addWhere('id', '=', $this->userJobID)
      ->execute()->first()['metadata'];
    $userJobMetadata['entity_configuration'][$userJobMetadata['base_entity']]['action'] = $action;
    $userJobMetadata['entity_configuration']['Contact']['contact_type'] = $submittedValues['contactType'] ?? 'Individual';
    $userJobMetadata['entity_configuration']['Contact']['dedupe_rule'] = ['IndividualUnsupervised'];
    foreach ($fieldMappings as $index => $mapping) {
      if (isset($mapping['entity_data'])) {
        $userJobMetadata['entity_configuration']['SoftCreditContact'] = $mapping['entity_data']['soft_credit'];
        unset($fieldMappings[$index]['entity_data']);
      }
    }
    $userJobMetadata['import_mappings'] = $fieldMappings;
    if ($entityConfiguration) {
      foreach ($entityConfiguration as $entity => $configuration) {
        if (isset($userJobMetadata['entity_configuration'][$entity])) {
          $userJobMetadata['entity_configuration'][$entity] = $configuration + $userJobMetadata['entity_configuration'][$entity];
        }
        else {
          $userJobMetadata['entity_configuration'][$entity] = $configuration;
        }
      }
    }
    UserJob::update()
      ->addWhere('id', '=', $this->userJobID)
      ->setValues([
        'metadata' => $userJobMetadata,
      ])
      ->execute();
    $wrapper = $this->getMapFieldForm($submittedValues);
    $wrapper->processForm(FormWrapper::VALIDATED);
    $this->assertEquals([], $wrapper->getValidationOutput(), 'Form failed to validate that the fields submitted met the form / dedupe rule requirements ' . print_r($wrapper->getValidationOutput(), TRUE));
    $wrapper->postProcess();
    $this->submitPreviewForm($submittedValues);
  }

  /**
   * Submit the preview form, triggering the import.
   *
   * @param array $submittedValues
   */
  protected function submitPreviewForm(array $submittedValues): void {
    $wrapper = $this->getPreviewForm($submittedValues);
    $wrapper->processForm(FormWrapper::VALIDATED);
    $this->assertEquals([], $wrapper->getValidationOutput());
    $wrapper->postProcess();
    $this->assertInstanceOf(CRM_Core_Exception_PrematureExitException::class, $wrapper->getException(), 'Expected a redirect');

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
  protected function submitDataSourceForm(string $csv, array $submittedValues = []): void {
    // A prior getMapFieldForm()/getPreviewForm() call in the same test may have left
    // a job id in $_REQUEST/$_GET (via FormWrapper's urlParameters); submitting the
    // DataSource form always creates a fresh job and must not inherit that.
    unset($_GET['id'], $_REQUEST['id']);
    $reflector = new ReflectionClass(get_class($this));
    $directory = dirname($reflector->getFileName());
    $submittedValues = array_merge([
      'uploadFile' => ['name' => $directory . '/data/' . $csv],
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
      'groups' => [],
    ], $submittedValues);
    $wrapper = $this->getDataSourceForm($submittedValues);
    $wrapper->processForm();
    $this->userJobID = $wrapper->getValueSetOnForm('user_job_id');
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

  /**
   * Update saved userJob metadata - as the angular screen would do.
   *
   * @param array $mappings
   * @param string $contactType
   * @param array|null $dedupeRules
   *
   * @return void
   */
  public function updateJobMetadata(array $mappings, string $contactType, ?array $dedupeRules = NULL): void {
    try {
      $metadata = UserJob::get()->addWhere('id', '=', $this->userJobID)
        ->execute()->single()['metadata'];
      if ($mappings) {
        $metadata['import_mappings'] = $mappings;
      }
      if ($contactType) {
        $metadata['entity_configuration']['Contact']['contact_type'] = $contactType;
      }
      if ($dedupeRules) {
        $metadata['entity_configuration']['Contact']['dedupe_rule'] = $dedupeRules;
      }
      UserJob::update()
        ->addWhere('id', '=', $this->userJobID)
        ->addValue('metadata', $metadata)
        ->execute();
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('Failed to update UserJob: ' . $e->getMessage());
    }
  }

}
