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

use Civi\Api4\Mapping;
use Civi\Api4\UserJob;

/**
 *  Test various forms extending CRM_Import_Forms.
 *
 * @package CiviCRM
 * @group import
 */
class CRM_Import_FormsTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_user_job', 'civicrm_mapping', 'civicrm_mapping_field', 'civicrm_queue']);
    parent::tearDown();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testLoadDataSourceSavedTemplate(): void {
    // First do a basic submission, creating a Mapping and UserJob template in the process.
    [$templateJob, $mapping] = $this->runImportSavingImportTemplate();

    // Now try this template in in the url to load the defaults for DataSource.
    $_REQUEST['template_id'] = $templateJob['id'];
    $form = $this->getFormObject('CRM_Contribute_Import_Form_DataSource');
    $this->formController = $form->controller;
    $form->buildForm();
    $defaults = $this->getFormDefaults($form);
    // These next 2 fields should be loaded as defaults from the UserJob template.
    $this->assertEquals('Organization', $defaults['contactType']);
    $this->assertEquals([$mapping['id']], $defaults['savedMapping']);
  }

  /**
   * Test that when we Process the MapField form without updating the saved template it is still retained.
   *
   * This is important because if we use the BACK button we still want 'Update Mapping'
   * to show.
   */
  public function testSaveRetainingMappingID(): void {
    // First do a basic submission, creating a Mapping and UserJob template in the process.
    [, $mapping] = $this->runImportSavingImportTemplate();
    $this->formController = NULL;

    $dataSourceForm = $this->processForm('CRM_Contribute_Import_Form_DataSource', [
      'contactType' => 'Organization',
      'savedMapping' => 1,
    ]);
    $userJobID = $dataSourceForm->getUserJobID();
    $this->processForm('CRM_Contribute_Import_Form_MapField', [
      'savedMapping' => $mapping['id'],
      'contactType' => 'Organization',
      'mapper' => [['id'], ['source']],
    ]);

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

    $userJob = UserJob::get()->addWhere('id', '=', $userJobID)->execute()->first();
    $this->assertEquals($mapping['id'], $userJob['metadata']['Template']['mapping_id']);
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
   *
   * @return \CRM_Core_Form
   */
  protected function processForm(string $class, array $formValues = []): CRM_Core_Form {
    $form = $this->getImportForm($class, $formValues);
    $form->buildForm();
    $form->mainProcess();
    return $form;
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
      'mapper' => [['id'], ['source']],
    ];
  }

  /**
   * @param array $submittedValues
   */
  protected function processContributionForms(array $submittedValues): void {
    try {
      $this->processForm('CRM_Contribute_Import_Form_DataSource', $submittedValues);
      $this->processForm('CRM_Contribute_Import_Form_MapField', $submittedValues);
      $this->processForm('CRM_Contribute_Import_Form_Preview', $submittedValues);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // We expect this to happen as it re-directs to the queue runner.
    }
  }

  /**
   * @param string $class
   * @param array $formValues
   *
   * @return \CRM_Core_Form
   */
  protected function getImportForm(string $class, array $formValues = []): CRM_Core_Form {
    $formValues = array_merge($this->getDefaultValues(), $formValues);
    return $this->getFormObject($class, $formValues);
  }

  /**
   * @return array
   */
  protected function runImportSavingImportTemplate(): array {
    $this->processContributionForms([
      'saveMapping' => 1,
      'saveMappingName' => 'mapping',
      'contactType' => 'Organization',
    ]);

    // Check that a template job and a mapping have been created.
    $templateJob = UserJob::get()
      ->addWhere('is_template', '=', 1)
      ->execute()
      ->first();
    $this->assertNotEmpty($templateJob);
    $this->assertArrayNotHasKey('table_name', $templateJob['metadata']['DataSource']);
    $mapping = Mapping::get()
      ->addWhere('name', '=', substr($templateJob['name'], 7))
      ->execute()
      ->first();
    $this->assertNotEmpty($mapping);
    // Reset the formController so this doesn't leak into further tests.
    $this->formController = NULL;
    return [$templateJob, $mapping];
  }

}
