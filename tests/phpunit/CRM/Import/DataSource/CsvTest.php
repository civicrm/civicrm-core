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
 * Tests for the CRM_Import_Datasource_Csv class.
 */
class CRM_Import_DataSource_CsvTest extends CiviUnitTestCase {

  /**
   * Test the to csv function.
   *
   * @param string $fileName
   *
   * @dataProvider getCsvFiles
   */
  public function testToCsv($fileName) {
    $dataSource = new CRM_Import_DataSource_CSV();
    $params = [
      'uploadFile' => [
        'name' => __DIR__ . '/' . $fileName,
      ],
      'skipColumnHeader' => TRUE,
    ];

    // Get the PEAR::DB object
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    $form = new CRM_Contact_Import_Form_DataSource();
    $form->controller = new CRM_Contact_Import_Controller();

    $dataSource->postProcess($params, $db, $form);
    $tableName = $form->get('importTableName');
    $this->assertEquals(4,
      CRM_Core_DAO::singleValueQuery("SELECT LENGTH(last_name) FROM $tableName"),
      $fileName . ' failed on last_name'
    );
    $this->assertEquals(21,
      CRM_Core_DAO::singleValueQuery("SELECT LENGTH(email) FROM $tableName"),
      $fileName . ' failed on email'
    );
    CRM_Core_DAO::executeQuery("DROP TABLE $tableName");
  }

  /**
   * Get csv files to test.
   *
   * @return array
   */
  public function getCsvFiles() {
    return [['import.csv'], ['yogi.csv']];
  }

}
