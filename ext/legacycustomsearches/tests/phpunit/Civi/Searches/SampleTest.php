<?php

namespace Civi\Searches;

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\SavedSearch;
use Civi\Api4\OptionValue;
use CRM_Contact_BAO_SavedSearch;
use CRM_Contact_Form_Search_Custom_Group;
use CRM_Contact_Form_Search_Custom_Sample;
use CRM_Core_DAO;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test
 * class. Simply create corresponding functions (e.g. "hook_civicrm_post(...)"
 * or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or
 * test****() functions will rollback automatically -- as long as you don't
 * manipulate schema or truncate tables. If this test needs to manipulate
 * schema or truncate tables, then either: a. Do all that using setupHeadless()
 * and Civi\Test. b. Disable TransactionalInterface, and handle all
 * setup/teardown yourself.
 *
 * @group headless
 */
class SampleTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and
   * sqlFile(). See:
   * https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['legacycustomsearches'])
      ->apply();
  }

  /**
   * Set up for test.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function setUp(): void {
    OptionValue::create()->setValues([
      'option_group_id:name' => 'custom_search',
      'label' => 'CRM_Contact_Form_Search_Custom_Sample',
      'value' => 100,
      'name' => 'CRM_Contact_Form_Search_Custom_Sample',
      'description' => 'Household Name and State',
    ])->execute();
  }

  /**
   * Get data for tests.
   *
   * @return array
   */
  public function dataProvider(): array {
    return [
      //  Search by Household name: 'Household 9'
      [
        'form_values' => ['household_name' => 'Household - No state'],
        'names' => [
          'Household - No state',
        ],
      ],
      //  Search by Household name: 'Household'
      [
        'form_values' => ['household_name' => 'Household'],
        'id' => [
          'Household - No state',
          'Household - CA',
          'Household - CA - 2',
          'Household - NY',
        ],
      ],
      //  Search by State: California
      [
        'form_values' => ['state_province_id' => '1004'],
        'id' => [
          'Household - CA',
          'Household - CA - 2',
        ],
      ],
      //  Search by State: New York
      [
        'form_values' => ['state_province_id' => '1031'],
        'id' => [
          'Household - NY',
        ],
      ],
    ];
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::count()
   *
   * @dataProvider dataProvider
   *
   * @param array $formValues
   * @param array $names
   *
   * @throws \CRM_Core_Exception
   */
  public function testCount(array $formValues, array $names): void {
    $this->setupSampleData();
    $obj = new CRM_Contact_Form_Search_Custom_Sample($formValues);
    $this->assertEquals(count($names), $obj->count());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::all()
   *
   * @dataProvider dataProvider
   *
   * @param array $formValues
   * @param array $names
   *
   * @throws \CRM_Core_Exception
   */
  public function testAll(array $formValues, array $names): void {
    $this->setupSampleData();
    $obj = new CRM_Contact_Form_Search_Custom_Sample($formValues);
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
    $full = [];
    foreach ($names as $name) {
      $full[] = [
        'contact_type' => 'Household',
        'household_name' => $name,
        'contact_id' => Contact::get()
          ->addWhere('household_name', '=', $name)
          ->execute()
          ->first()['id'],
      ];
    }
    asort($all);
    $this->assertEquals($full, $all);
  }

  /**
   * Test CRM_Contact_Form_Search_Custom_Sample::contactIDs().
   *
   * @dataProvider dataProvider
   *
   * @param array $formValues
   * @param array $names
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactIDs(array $formValues, array $names): void {
    $this->setupSampleData();
    $obj = new CRM_Contact_Form_Search_Custom_Sample($formValues);
    $sql = $obj->contactIDs();
    $this->assertIsString($sql);
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = [];
    while ($dao->fetch()) {
      $contacts[$dao->contact_id] = 1;
    }
    $contacts = array_keys($contacts);
    sort($contacts, SORT_NUMERIC);
    $this->assertEquals($this->getContactIDs($names), $contacts);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::columns().
   *
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
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample with saved_search_id
   *  With true argument it returns list of contact IDs
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSavedSearch(): void {
    $this->setupSampleData();
    $this->setupSavedSearches();
    $dataset[0] = ['id' => $this->getContactIDs(['Household - NY'])];
    $dataset[1] = [
      'id' => $this->getContactIDs([
        'Household - CA',
        'Household - CA - 2',
      ]),
    ];
    $searches = SavedSearch::get()->addSelect('*')->addWhere('has_base', '=', FALSE)->execute();
    foreach ($searches as $index => $search) {
      $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($search['id']);
      $obj = new CRM_Contact_Form_Search_Custom_Sample($formValues);
      $sql = $obj->contactIDs();
      $this->assertIsString($sql);
      $dao = CRM_Core_DAO::executeQuery($sql);
      $contacts = [];
      while ($dao->fetch()) {
        $contacts[] = $dao->contact_id;
      }
      sort($contacts, SORT_NUMERIC);
      $this->assertEquals($dataset[$index]['id'], $contacts, 'Failed on search ' . $search['id']);
    }
  }

  /**
   * Set up our sample data.
   *
   * @throws \CRM_Core_Exception
   */
  public function setupSampleData(): void {
    $households = [
      'Household - No state' => '',
      'Household - CA' => 1004,
      'Household - CA - 2' => 1004,
      'Household - NY' => 1031,
    ];
    foreach ($households as $household => $state) {
      $create = Contact::create(FALSE)->setValues([
        'contact_type' => 'Household',
        'household_name' => $household,
      ]);
      if ($state) {
        $create->addChain(
          'address',
          Address::create()->setValues([
            'contact_id' => '$id',
            'location_type_id' => 1,
            'state_province_id' => $state,
          ]));
      }
      $create->execute();
    }
  }

  /**
   * Get the ids for the relevant contacts.@
   *
   * @return array
   *   IDs of the contacts.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContactIDs($names): array {
    return array_keys((array) Contact::get()->addWhere(
      'display_name', 'IN', $names
    )->addOrderBy('id')->execute()->indexBy('id'));
  }

  /**
   * Set up saved searches.
   */
  protected function setupSavedSearches(): void {
    SavedSearch::create()->setValues([
      'form_values' => [
        [
          0 => 'csid',
          1 => '=',
          2 => '1',
          3 => 0,
          4 => 0,
        ],
        [
          0 => 'household_name',
          1 => '=',
          2 => 'Household - NY',
          3 => 0,
          4 => 0,
        ],
        [
          0 => 'state_province_id',
          1 => '=',
          2 => '1031',
          3 => 0,
          4 => 0,
        ],
        6 =>
          [
            0 => 'customSearchID',
            1 => '=',
            2 => '1',
            3 => 0,
            4 => 0,
          ],
        7 =>
          [
            0 => 'customSearchClass',
            1 => '=',
            2 => 'CRM_Contact_Form_Search_Custom_Sample',
            3 => 0,
            4 => 0,
          ],
      ],
    ])->execute();

    SavedSearch::create()->setValues([
      'form_values' => [
        'csid' => '1',
        'household_name' => '',
        'state_province_id' => '1004',
      ],
    ])->execute();
  }

}
