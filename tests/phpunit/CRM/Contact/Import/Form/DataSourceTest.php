<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @file
 * File for the CRM_Contact_Import_Form_DataSourceTest class.
 */

use Civi\Api4\UserJob;

/**
 *  Test contact import datasource.
 *
 * @package CiviCRM
 * @group headless
 * @group import
 */
class CRM_Contact_Import_Form_DataSourceTest extends CiviUnitTestCase {

  /**
   * Post test cleanup.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_user_job', 'civicrm_mapping']);
    parent::tearDown();
  }

  /**
   * Test the form loads without error / notice and mappings are assigned.
   *
   * (Added in conjunction with fixed noting on mapping assignment).
   */
  public function testBuildForm(): void {
    $this->callAPISuccess('Mapping', 'create', ['name' => 'Well dressed ducks', 'mapping_type_id' => 'Import Contact']);
    $form = $this->getFormObject('CRM_Contact_Import_Form_DataSource');
    $form->buildQuickForm();
    $element = $form->getElement('savedMapping');
    $this->assertEquals('Well dressed ducks', $element->_options[1]['text']);
  }

  /**
   * Test sql and csv data-sources load and save user jobs.
   *
   * This test mimics a scenario where the form is submitted more than once
   * and the user_job is updated to reflect the new data source.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDataSources(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'import SQL datasource']);
    $this->callAPISuccess('Mapping', 'create', ['name' => 'Well dressed ducks', 'mapping_type_id' => 'Import Contact']);

    $sqlFormValues = [
      'dataSource' => 'CRM_Import_DataSource_SQL',
      'sqlQuery' => 'SELECT "bob" as first_name FROM civicrm_option_value LIMIT 5',
      'contactType' => 'Individual',
    ];
    $form = $this->submitDataSourceForm($sqlFormValues);
    $userJobID = $form->getUserJobID();
    // Load the user job, using TRUE so permissions apply.
    $userJob = UserJob::get(TRUE)
      ->addWhere('id', '=', $userJobID)
      ->addSelect('metadata')
      ->execute()->first();
    // Submitted values should be stored in the user job.
    // There are some null values in the submitted_values array - we can
    // filter these out as we have not passed in all possible values.
    $this->assertEquals($sqlFormValues, array_filter($userJob['metadata']['submitted_values']));

    // The user job holds the name of the table  - which should have 5 rows of bob.
    $this->assertNotEmpty($userJob['metadata']['DataSource']['table_name']);
    $sqlTableName = $userJob['metadata']['DataSource']['table_name'];
    $this->assertEquals(5, CRM_Core_DAO::singleValueQuery(
      'SELECT count(*) FROM ' . $sqlTableName
      . " WHERE first_name = 'Bob'"
    ));

    // Now we imitate the scenario where the user goes back and
    // re-submits the form selecting the csv datasource.
    $csvFormValues = [
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'skipColumnHeader' => 1,
      'contactType' => 'Individual',
      'uploadFile' => [
        'name' => __DIR__ . '/data/yogi.csv',
        'type' => 'text/csv',
      ],
    ];
    // Mimic form re-submission with new values.
    $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $csvFormValues;
    $form->buildForm();
    $form->postProcess();
    // The user job id should not have changed.
    $this->assertEquals($userJobID, $form->getUserJobID());

    $userJob = UserJob::get(TRUE)
      ->addWhere('id', '=', $form->getUserJobID())
      ->addSelect('metadata')
      ->execute()->first();
    // Submitted values should be updated in the user job.
    $this->assertEquals($csvFormValues, array_filter($userJob['metadata']['submitted_values']));

    $csvTableName = $userJob['metadata']['DataSource']['table_name'];
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery(
      'SELECT count(*) FROM ' . $csvTableName
      . " WHERE first_name = 'yogi'"
    ));
  }

  /**
   * Submit the dataSoure form with the provided form values.
   *
   * @param array $sqlFormValues
   *
   * @return CRM_Contact_Import_Form_DataSource
   * @throws \CRM_Core_Exception
   */
  private function submitDataSourceForm(array $sqlFormValues): CRM_Contact_Import_Form_DataSource {
    /** @var CRM_Contact_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_DataSource', $sqlFormValues);
    $form->buildForm();
    $form->postProcess();
    return $form;
  }

}
