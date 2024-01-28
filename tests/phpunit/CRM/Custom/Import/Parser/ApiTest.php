<?php
/**
 * @file
 * File for the CRM_Custom_Import_Parser_ContributionTest class.
 */

/**
 *  Test Contribution import parser.
 *
 * @package CiviCRM
 * @group headless
 * @group import
 */
class CRM_Custom_Import_Parser_ApiTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Import_ParserTrait;

  /**
   * Test the full form-flow import.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImport(): void {
    $this->individualCreate();
    $this->createCustomGroupWithFieldOfType(['is_multiple' => TRUE, 'extends' => 'Contact'], 'select', 'level', ['serialize' => 1]);

    $customGroupID = $this->ids['CustomGroup']['level'];
    $dateFieldID = $this->createDateCustomField(['date_format' => 'yy', 'custom_group_id' => $customGroupID])['id'];
    $this->importCSV('custom_data_date_select.csv', [
      ['name' => 'contact_id'],
      ['name' => $this->getCustomFieldName('levelselect')],
      ['name' => 'do_not_import'],
      ['name' => 'custom_' . $dateFieldID],
    ], ['multipleCustomData' => $customGroupID]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
  }

  /**
   * Get the import's datasource form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Custom_Import_Form_DataSource
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getDataSourceForm(array $submittedValues): CRM_Custom_Import_Form_DataSource {
    /** @var \CRM_Custom_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Custom_Import_Form_DataSource', $submittedValues);
    return $form;
  }

  /**
   * Get the import's mapField form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Custom_Import_Form_MapField
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getMapFieldForm(array $submittedValues): CRM_Custom_Import_Form_MapField {
    /** @var \CRM_Custom_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Custom_Import_Form_MapField', $submittedValues);
    return $form;
  }

  /**
   * Get the import's preview form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Custom_Import_Form_Preview
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getPreviewForm(array $submittedValues): CRM_Custom_Import_Form_Preview {
    /** @var CRM_Custom_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Custom_Import_Form_Preview', $submittedValues);
    return $form;
  }

}
