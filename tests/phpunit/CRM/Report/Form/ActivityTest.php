<?php

 /*
   --------------------------------------------------------------------
  | CiviCRM version 4.7                                                |
   --------------------------------------------------------------------
  | Copyright CiviCRM LLC (c) 2004-2017                                |
   --------------------------------------------------------------------
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
   --------------------------------------------------------------------
 */

 /**
  * Test Activity Report
  * @package CiviCRM
  * @group headless
  */
class CRM_Report_Form_ActivityTest extends CiviReportTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * @return array
   */
  public function dataProvider() {
    return array(
      'report_class' => 'CRM_Report_Form_Activity',
      'input_params' => array(
        'fields' => array(
          'contact_target',
        ),
      ),
    );
  }

  /**
   * Test to check the report with custom fields with larger group name. (e.g. >64 characters)
   * Generating report will throw PEAR_Exception without fix with Unknown DB error,
   * which is getting trgigger because of long group names.
   * Exception will be thrown during generting SQL and creating temp table, so it will fail without any data.
   */
  public function testAliasNamesLength() {
    try {
      $ids = $this->CustomGroupMultipleCreateWithFields(array('title' => 'Professional qualifications and Work Practice', 'extends' => 'Activity'));
      $dataProvider = $this->dataProvider();
      $dataProvider['input_params']['fields'][] = 'custom_' . $ids['custom_group_id']; // Adding custom field as param in output.
      $this->getReportOutputAsCsv($dataProvider['report_class'], $dataProvider['input_params']);
      $this->assertTrue(TRUE); // CSV is created, so test is successful.
    }
    catch(PEAR_Exception $e) {
      $this->fail('PEAR_Exception occured: ' . $e->getMessage()); // If exception caught test is failing.
    }
  }

}
