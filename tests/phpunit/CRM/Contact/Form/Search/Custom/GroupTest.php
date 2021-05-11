<?php
/**
 *  File for the CRM_Contact_Form_Search_Custom_GroupTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 * Test contact custom search functions
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_Custom_GroupTest extends CiviUnitTestCase {

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_group_contact',
      'civicrm_group',
      'civicrm_saved_search',
      'civicrm_entity_tag',
      'civicrm_tag',
    ]);
    parent::tearDown();
  }

  /**
   * @return CRM_Contact_Form_Search_Custom_GroupTestDataProvider
   */
  public function dataProvider(): CRM_Contact_Form_Search_Custom_GroupTestDataProvider {
    return new CRM_Contact_Form_Search_Custom_GroupTestDataProvider();
  }

  /**
   * Test CRM_Contact_Form_Search_Custom_Group::count().
   *
   * @dataProvider dataProvider
   *
   * @param array $fv
   * @param int $count
   *
   * @throws \CRM_Core_Exception
   */
  public function testCount(array $fv, int $count): void {
    $this->loadXMLDataSet(__DIR__ . '/datasets/group-dataset.xml');

    $obj = new CRM_Contact_Form_Search_Custom_Group($fv);

    $sql = $obj->all();
    CRM_Core_DAO::executeQuery($sql);

    /**
     * echo "Count: $count, OBJ: ", $obj->count( ) . "\n";
     * while ( $dao->fetch( ) ) {
     * echo "{$dao->contact_id}, {$dao->contact_type}, {$dao->sort_name}, {$dao->group_names}\n";
     * }
     **/
    $this->assertEquals($count, $obj->count());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::all()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   * @throws \Exception
   */
  public function testAll($fv, $count, $ids, $full): void {
    $this->loadXMLDataSet(__DIR__ . '/datasets/group-dataset.xml');

    $obj = new CRM_Contact_Form_Search_Custom_Group($fv);
    $sql = $obj->all();
    $this->assertIsString($sql);
    $dao = CRM_Core_DAO::executeQuery($sql);
    $all = [];
    while ($dao->fetch()) {
      $all[] = [
        'contact_id' => $dao->contact_id,
        'contact_type' => $dao->contact_type,
        'sort_name' => $dao->sort_name,
      ];
    }
    asort($all);
    $this->assertEquals($full, $all);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::contactIDs()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   * @throws \Exception
   */
  public function testContactIDs($fv, $count, $ids, $full) {
    $this->loadXMLDataSet(__DIR__ . '/datasets/group-dataset.xml');

    $obj = new CRM_Contact_Form_Search_Custom_Group($fv);
    $sql = $obj->contactIDs();
    $this->assertTrue(is_string($sql));
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = [];
    while ($dao->fetch()) {
      $contacts[$dao->contact_id] = 1;
    }
    $contacts = array_keys($contacts);
    sort($contacts, SORT_NUMERIC);
    $this->assertEquals($ids, $contacts);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::columns()
   *  It returns an array of translated name => keys
   */
  public function testColumns(): void {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $columns = $obj->columns();
    $this->assertIsArray($columns);
    foreach ($columns as $key => $value) {
      $this->assertIsString($key);
      $this->assertIsString($value);
    }
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::summary()
   *  It returns NULL
   */
  public function testSummary(): void {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertNull($obj->summary());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::templateFile()
   *  Returns the path to the file as a string
   */
  public function testTemplateFile(): void {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $fileName = $obj->templateFile();
    $this->assertIsString($fileName);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With no arguments it returns '(1)'
   */
  public function testWhereNoArgs(): void {
    $formValues = [
      CRM_Core_Form::CB_PREFIX . '17' => TRUE,
      CRM_Core_Form::CB_PREFIX . '23' => TRUE,
    ];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1) ', $obj->where());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With false argument it returns '(1)'
   */
  public function testWhereFalse(): void {
    $formValues = [
      CRM_Core_Form::CB_PREFIX . '17' => TRUE,
      CRM_Core_Form::CB_PREFIX . '23' => TRUE,
    ];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1) ', $obj->where(FALSE));
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With true argument it returns list of contact IDs
   */
  public function testWhereTrue(): void {
    $formValues = [
      CRM_Core_Form::CB_PREFIX . '17' => TRUE,
      CRM_Core_Form::CB_PREFIX . '23' => TRUE,
    ];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1)  AND contact_a.id IN ( 17, 23 )', $obj->where(TRUE));
  }

}
