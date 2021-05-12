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
 *  Include parent class definition
 */

/**
 *  Include class under test
 */

/**
 *  Include form definitions
 */

/**
 *  Include DAO to do queries
 */

/**
 *  Include dataProvider for tests
 */

use Civi\Api4\OptionValue;
use Civi\Api4\SavedSearch;

/**
 *  Test contact custom search functions
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_Custom_SampleTest extends CiviUnitTestCase {

  /**
   * Set up for test.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function setUp(): void {
    parent::setUp();
    OptionValue::create()->setValues([
      'option_group_id:name' => 'custom_search',
      'label' => 'CRM_Contact_Form_Search_Custom_Sample',
      'value' => 100,
      'name' => 'CRM_Contact_Form_Search_Custom_Sample',
      'description' => 'Household Name and State',
    ])->execute();
  }

  /**
   * Post test cleanup.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_address',
      'civicrm_saved_search',
      'civicrm_contact',
    ]);
    OptionValue::delete()->addWhere('name', '=', 'CRM_Contact_Form_Search_Custom_Sample')->execute();
    parent::tearDown();
  }

  /**
   * @return \CRM_Contact_Form_Search_Custom_SampleTestDataProvider
   */
  public function dataProvider(): CRM_Contact_Form_Search_Custom_SampleTestDataProvider {
    return new CRM_Contact_Form_Search_Custom_SampleTestDataProvider();
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::count()
   *
   * @dataProvider dataProvider
   *
   * @param array $fv
   * @param int $count
   */
  public function testCount(array $fv, int $count): void {
    $this->loadXMLDataSet(__DIR__ . '/datasets/sample-dataset.xml');
    $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
    $this->assertEquals($count, $obj->count());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::all()
   *
   * @dataProvider dataProvider
   *
   * @param array $fv
   * @param int $count
   * @param array $ids
   * @param array $full
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function testAll(array $fv, int $count, array $ids, array $full): void {
    $this->loadXMLDataSet(__DIR__ . '/datasets/sample-dataset.xml');

    $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
    $sql = $obj->all(0, 0, 'contact_id');
    $this->assertIsString($sql);
    $dao = CRM_Core_DAO::executeQuery($sql);
    $all = [];
    while ($dao->fetch()) {
      $all[] = [
        'contact_id' => $dao->contact_id,
        'contact_type' => $dao->contact_type,
        'household_name' => $dao->sort_name,
      ];
    }
    asort($all);
    $this->assertEquals($full, $all);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::contactIDs()
   *
   * @dataProvider dataProvider
   *
   * @param array $fv
   * @param int $count
   * @param array $ids
   * @param array $full
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function testContactIDs(array $fv, int $count, array $ids, array $full): void {
    $this->loadXMLDataSet(__DIR__ . '/datasets/sample-dataset.xml');
    $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
    $sql = $obj->contactIDs();
    $this->assertIsString($sql);
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
    $obj = new CRM_Contact_Form_Search_Custom_Sample($formValues);
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
   *  Test CRM_Contact_Form_Search_Custom_Sample::templateFile()
   *  Returns the path to the file as a string
   */
  public function testTemplateFile(): void {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $fileName = $obj->templateFile();
    $this->assertIsString($fileName);
    //FIXME: we would need to search the include path to do the following
    //$this->assertTrue( file_exists( $fileName ) );
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample with saved_search_id
   *  With true argument it returns list of contact IDs
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSavedSearch(): void {
    $this->loadXMLDataSet(__DIR__ . '/datasets/sample-dataset.xml');

    $dataset[1] = ['id' => [12]];
    $dataset[2] = ['id' => [10, 11]];

    $searches = SavedSearch::get()->addSelect('*')->execute();
    foreach ($searches as $search) {
      $fv = CRM_Contact_BAO_SavedSearch::getFormValues($search['id']);
      $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
      $sql = $obj->contactIDs();
      $this->assertIsString($sql);
      $dao = CRM_Core_DAO::executeQuery($sql);
      $contacts = [];
      while ($dao->fetch()) {
        $contacts[] = $dao->contact_id;
      }
      sort($contacts, SORT_NUMERIC);
      $this->assertEquals($dataset[$search['id']]['id'], $contacts);
    }
  }

}
