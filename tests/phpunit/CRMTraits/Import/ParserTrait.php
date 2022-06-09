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
   */
  protected function importCSV(string $csv, array $fieldMappings, array $submittedValues = []): void {
    $reflector = new ReflectionClass(get_class($this));
    $directory = dirname($reflector->getFileName());
    $submittedValues = array_merge([
      'uploadFile' => ['name' => $directory . '/data/' . $csv],
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'mapper' => $this->getMapperFromFieldMappings($fieldMappings),
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
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

    $form = $this->getMapFieldForm($submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertTrue($form->validate());
    $form->postProcess();
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
      $runner = new CRM_Queue_Runner([
        'queue' => $queue,
        'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      ]);
      $runner->runAll();
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
      $fieldInput = [$mapping['name']];
      if (!empty($mapping['soft_credit_type_id'])) {
        $fieldInput[1] = $mapping['soft_credit_match_field'];
        $fieldInput[2] = $mapping['soft_credit_type_id'];
      }
      $mapper[] = $fieldInput;
    }
    return $mapper;
  }

}
