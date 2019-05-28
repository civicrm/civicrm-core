<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2019                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    $dataSource = new CRM_Import_DataSource_Csv();
    $params = array(
      'uploadFile' => array(
        'name' => __DIR__ . '/' . $fileName,
      ),
      'skipColumnHeader' => TRUE,
    );

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
    return array(array('import.csv'), array('yogi.csv'));
  }

}
