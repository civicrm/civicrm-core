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

use Civi\Api4\UserJob;

/**
 *  Test various forms extending CRM_Import_Forms.
 *
 * @package CiviCRM
 * @group import
 */
class CRM_Import_FormsTest extends CiviUnitTestCase {
  use CRMTraits_Import_ParserTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->callAPISuccess('Extension', 'install', ['keys' => 'civiimport']);
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_user_job', 'civicrm_mapping', 'civicrm_mapping_field', 'civicrm_queue']);
    parent::tearDown();
  }

  /**
   * Test that when we Process the MapField form without updating the saved template it is still retained.
   *
   * This is important because if we use the BACK button we still want 'Update Mapping'
   * to show.
   */
  public function testSaveRetainingMappingID(): void {
    // First do a basic submission, creating a Mapping and UserJob template in the process.
    $this->runImportSavingImportTemplate();
    $this->formController = NULL;

    $this->processForm('CRM_Contribute_Import_Form_DataSource', [
      'contactType' => 'Organization',
      'savedMapping' => 1,
    ]);

    $this->processForm('CRM_Contribute_Import_Form_MapField', [], [['name' => 'Contribution.id'], ['name' => 'Contribution.source']], 'Organization');

    // Now we want to submit this form without updating the mapping used & make sure the mapping_id
    // is still saved in the metadata.
    /* @var CRM_Contribute_Import_Form_MapField $mapFieldForm */
    $mapFieldValues = [
      'dataSource' => 'CRM_Import_DataSource_SQL',
      'sqlQuery' => 'SELECT id, source FROM civicrm_contact',
      'mapper' => [['id'], ['financial_type_id']],
    ];
    $mapFieldForm = $this->getFormObject('CRM_Contribute_Import_Form_MapField', $mapFieldValues);
    $mapFieldForm->buildForm();
  }

  /**
   * Get the values specified as defaults for the form.
   *
   * I originally wanted to make this a public function on `CRM_Core_Form`
   * but I think it might need to mature first.
   */
  public function getFormDefaults($form): array {
    $defaults = [];
    if (!empty($form->_elementIndex)) {
      foreach ($form->_elementIndex as $elementName => $elementIndex) {
        $element = $form->_elements[$elementIndex];
        $defaults[$elementName] = $element->getValue();
      }
    }
    return $defaults;
  }

  /**
   * @param string $class
   * @param array $formValues
   * @param array $importMappings
   * @param string $contactType
   */
  protected function processForm(string $class, array $formValues = [], array $importMappings = [], string $contactType = 'Individual'): void {
    if ($this->userJobID && $importMappings) {
      $this->updateJobMetadata($importMappings, $contactType);
    }
    $form = $this->getImportForm($class, $formValues);
    $form->buildForm();
    $form->mainProcess();
    $this->userJobID = $form->getUserJobID();
  }

  /**
   * Get some default values to use when we don't care.
   *
   * @return array
   */
  protected function getDefaultValues(): array {
    return [
      'contactType' => 'Individual',
      'contactSubType' => '',
      'dataSource' => 'CRM_Import_DataSource_SQL',
      'sqlQuery' => 'SELECT id, source FROM civicrm_contact',
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
      'mapper' => [['Contribution.id'], ['Contribution.source']],
    ];
  }

  protected function processContributionForms(array $submittedValues, array $importMappings = []): void {
    try {
      $this->processForm('CRM_Contribute_Import_Form_DataSource', $submittedValues);
      $this->processForm('CRM_Contribute_Import_Form_MapField', $submittedValues, $importMappings);
      $this->processForm('CRM_CiviImport_Form_Generic_Preview', $submittedValues);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // We expect this to happen as it re-directs to the queue runner.
    }
  }

  /**
   * @param string $class
   * @param array $formValues
   *
   * @return \CRM_Import_Forms
   * @throws \CRM_Core_Exception
   */
  protected function getImportForm(string $class, array $formValues = []): CRM_Core_Form {
    $formValues = array_merge($this->getDefaultValues(), $formValues);
    return $this->getFormObject($class, $formValues);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function runImportSavingImportTemplate(): void {
    $this->processContributionForms([
      'saveMapping' => 1,
      'saveMappingName' => 'mapping',
      'contactType' => 'Organization',
    ], [['name' => 'Contribution.id'], ['name' => 'Contribution.source']]);

    // Check that a template job and a mapping have been created.
    $templateJob = UserJob::get()
      ->addWhere('is_template', '=', 1)
      ->execute()
      ->first();
    $this->assertTrue(empty($templateJob['metadata']['DataSource']['table_name']));
    // Reset the formController so this doesn't leak into further tests.
    $this->formController = NULL;
  }

}
