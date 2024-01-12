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

namespace Civi\Searches;

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
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
class GroupTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use Test\ContactTestTrait;
  use Test\EntityTrait;
  use Test\Api3TestTrait;

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
   * Set up tags and groups for test.
   */
  protected function setup(): void {
    $i = 9;
    while ($i < 29) {
      // Weird historical data set issue.
      if ($i !== 25) {
        $this->individualCreate([
          'first_name' => 'Test',
          'last_name' => 'Contact ' . $i,
        ], $i);
      }
      $i++;
    }

    foreach ([7, 9] as $tagNumber) {
      $this->createTestEntity('Tag', [
        'name' => 'Tag' . $tagNumber,
        'description' => 'Test Tag ' . $tagNumber,
      ], $tagNumber);
    }
    $entityTags = [
      ['contact' => 10, 'tag' => 9],
      ['contact' => 12, 'tag' => 9],
      ['contact' => 14, 'tag' => 9],
      ['contact' => 16, 'tag' => 9],
      ['contact' => 18, 'tag' => 9],
      ['contact' => 20, 'tag' => 9],
      ['contact' => 22, 'tag' => 9],
      ['contact' => 24, 'tag' => 9],
      ['contact' => 11, 'tag' => 7],
      ['contact' => 12, 'tag' => 7],
      ['contact' => 15, 'tag' => 7],
      ['contact' => 16, 'tag' => 7],
      ['contact' => 19, 'tag' => 7],
      ['contact' => 20, 'tag' => 7],
      ['contact' => 23, 'tag' => 7],
      ['contact' => 24, 'tag' => 7],
      ['contact' => 26, 'tag' => 7],
      ['contact' => 28, 'tag' => 7],
    ];
    foreach ($entityTags as $entityTag) {
      $this->createTestEntity('EntityTag', [
        'tag_id:name' => 'Tag' . $entityTag['tag'],
        'entity_table' => 'civicrm_contact',
        'entity_id' => $this->ids['Contact'][$entityTag['contact']],
      ], $entityTag['contact']);
    }

    $this->createTestEntity('Group', [
      'name' => 'Group3',
      'title' => 'Test Group 3',
    ], 3);

    $this->createTestEntity('SavedSearch', [
      'search_custom_id' => 4,
      'customSearchClass' => 'CRM_Contact_Form_Search_Custom_Group',
      'form_values' => [
        'includeGroups' => [$this->ids['Group'][3]],
        'excludeGroups' => [],
        'customSearchID' => 4,
        'customSearchClass' => 'CRM_Contact_Form_Search_Custom_Group',
      ],
    ], 1);
    $this->createTestEntity('SavedSearch', [
      'search_custom_id' => 4,
      'customSearchClass' => 'CRM_Contact_Form_Search_Custom_Group',
      'form_values' => [
        'excludeGroups' => [$this->ids['Group'][3]],
        'includeGroups' => [],
        'includeTags' => [],
        'excludeTags' => [],
        'customSearchID' => 4,
        'customSearchClass' => 'CRM_Contact_Form_Search_Custom_Group',
      ],
    ], 2);

    $this->createTestEntity('Group', [
      'name' => 'Group4',
      'title' => 'Test Smart Group 4',
      'saved_search_id' => $this->ids['SavedSearch'][1],
    ], 4);

    $this->createTestEntity('Group', [
      'name' => 'Group5',
      'title' => 'Test Group 5',
    ], 5);

    $this->createTestEntity('Group', [
      'name' => 'Group6',
      'title' => 'Test Smart Group 6',
      'saved_search_id' => $this->ids['SavedSearch'][2],
    ], 6);

    $groupContacts = [
      ['contact_id' => $this->ids['Contact'][13], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][14], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][15], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][16], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][21], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][22], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][23], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][24], 'group_id' => $this->ids['Group'][5], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][17], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][18], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][19], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][20], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][21], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][22], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][23], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][24], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][27], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
      ['contact_id' => $this->ids['Contact'][28], 'group_id' => $this->ids['Group'][3], 'status' => 'Added'],
    ];
    foreach ($groupContacts as $groupContact) {
      $this->createTestEntity('GroupContact', $groupContact);
    }
  }

  /**
   * @return array
   */
  public function dataProvider(): array {
    return [
      'Exclude static group 3' => [
        'form_values' => ['excludeGroups' => [3]],
        'contact_numbers' => [1, 2, 9, 10, 11, 12, 13, 14, 15, 16, 26],
      ],
      'Include static group 3' => [
        'form_values' => ['includeGroups' => [3]],
        'contact_numbers' => [17, 18, 19, 20, 21, 22, 23, 24, 27, 28],
      ],
      'Include static group 5' => [
        'form_values' => ['includeGroups' => [5]],
        'contact_numbers' => [13, 14, 15, 16, 21, 22, 23, 24],
      ],
      ' Include static groups 3 and 5' => [
        'form_values' => ['includeGroups' => [3, 5]],
        'contact_numbers' => [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 27, 28],
      ],
      'Include static group 3, exclude static group 5' => [
        'form_values' => ['includeGroups' => [3], 'excludeGroups' => [5]],
        'contact_numbers' => [17, 18, 19, 20, 27, 28],
      ],
      'Exclude tag 7' => [
        'form_values' => ['excludeTags' => [7]],
        'contact_numbers' => [1, 2, 9, 10, 13, 14, 17, 18, 21, 22, 27],
      ],
      'Include tag 7' => [
        'form_values' => ['includeTags' => [7]],
        'contact_numbers' => [11, 12, 15, 16, 19, 20, 23, 24, 26, 28],
      ],
      'Include tag 9' => [
        'form_values' => ['includeTags' => [9]],
        'contact_numbers' => [10, 12, 14, 16, 18, 20, 22, 24],
      ],
      'Include tags 7 and 9' => [
        'form_values' => ['includeTags' => [7, 9]],
        'contact_numbers' => [10, 11, 12, 14, 15, 16, 18, 19, 20, 22, 23, 24, 26, 28],
      ],
      'Include tag 7, exclude tag 9' => [
        'form_values' => ['includeTags' => [7], 'excludeTags' => [9]],
        'contact_numbers' => [11, 15, 19, 23, 26, 28],
      ],
      'Include static group 3, include tag 7 (either)' => [
        'form_values' => ['includeGroups' => [3], 'includeTags' => [7], 'andOr' => 0],
        'contact_numbers' => [11, 12, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 26, 27, 28],
      ],
      'Include static group 3, include tag 7 (both)' => [
        'form_values' => ['includeGroups' => [3], 'includeTags' => [7], 'andOr' => 1],
        'contact_numbers' => [19, 20, 23, 24, 28],
      ],
      'Include static group 3, exclude tag 7' => [
        'form_values' => ['includeGroups' => [3], 'excludeTags' => [7]],
        'contact_numbers' => [17, 18, 21, 22, 27],
      ],
      'Include tag 9, exclude static group 5' => [
        'form_values' => ['includeTags' => [9], 'excludeGroups' => ['5']],
        'contact_numbers' => [10, 12, 18, 20],
      ],
      'Exclude tag 9, exclude static group 5' => [
        'form_values' => ['excludeTags' => [9], 'excludeGroups' => [5]],
        'contact_numbers' => [1, 2, 9, 11, 17, 19, 26, 27, 28],
      ],
      'Include smart group 6' => [
        'form_values' => ['includeGroups' => [6]],
        'contact_numbers' => [1, 2, 9, 10, 11, 12, 13, 14, 15, 16, 26],
      ],
      'Include smart group 4' => [
        'form_values' => ['includeGroups' => [4]],
        'contact_numbers' => [17, 18, 19, 20, 21, 22, 23, 24, 27, 28],
      ],
      'Include smart group 4 and static group 5' => [
        'form_values' => ['includeGroups' => [4, 5]],
        'contact_numbers' => [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 27, 28],
      ],
    ];
  }

  /**
   * Test CRM_Contact_Form_Search_Custom_Group::all().
   *
   * @dataProvider dataProvider
   *
   * @param array $formValues
   * @param array $contactIDs
   *
   * @throws \CRM_Core_Exception
   */
  public function testAll(array $formValues, array $contactIDs): void {
    $formValues = $this->replaceFormValuesPlaceholders($formValues);
    $full = [];
    foreach ($contactIDs as $id) {
      if ($id === 1) {
        $full[] = [
          'contact_id' => 1,
          'contact_type' => 'Organization',
          'sort_name' => 'Default Organization',
        ];
      }
      elseif ($id === 2) {
        $full[] = [
          'contact_id' => 2,
          'contact_type' => 'Organization',
          'sort_name' => 'Second Domain',
        ];
      }
      else {
        $full[] = [
          'contact_id' => $this->ids['Contact'][$id],
          'contact_type' => 'Individual',
          'sort_name' => 'Contact ' . $id . ', Test II',
        ];
      }
    }
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
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
    $this->assertEquals(count($contactIDs), $obj->count());
  }

  /**
   * Test CRM_Contact_Form_Search_Custom_Group::contactIDs().
   *
   * @dataProvider dataProvider
   *
   * @param $formValues
   * @param $contactIDs
   *
   * @throws \Exception
   */
  public function testContactIDs($formValues, $contactIDs): void {
    $formValues = $this->replaceFormValuesPlaceholders($formValues);
    $contactIDs = $this->replaceIDSPlaceholders($contactIDs);
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
    $sql = $obj->contactIDs();
    $this->assertIsString($sql);
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = [];
    while ($dao->fetch()) {
      $contacts[$dao->contact_id] = 1;
    }
    $contacts = array_keys($contacts);
    sort($contacts, SORT_NUMERIC);
    $this->assertEquals($contactIDs, $contacts);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::columns()
   *  It returns an array of translated name => keys
   */
  public function testColumns(): void {
    $formValues = [];
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
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
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertNull($obj->summary());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::templateFile()
   *  Returns the path to the file as a string
   */
  public function testTemplateFile(): void {
    $formValues = [];
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
    $fileName = $obj->templateFile();
    $this->assertIsString($fileName);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With no arguments it returns '(1)'
   */
  public function testWhereNoArgs(): void {
    $formValues = [
      \CRM_Core_Form::CB_PREFIX . 17 => TRUE,
      \CRM_Core_Form::CB_PREFIX . 23 => TRUE,
    ];
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1) ', $obj->where());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With false argument it returns '(1)'
   */
  public function testWhereFalse(): void {
    $formValues = [
      \CRM_Core_Form::CB_PREFIX . 17 => TRUE,
      \CRM_Core_Form::CB_PREFIX . 23 => TRUE,
    ];
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1) ', $obj->where(FALSE));
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With true argument it returns list of contact IDs
   */
  public function testWhereTrue(): void {
    $formValues = [
      \CRM_Core_Form::CB_PREFIX . 17 => TRUE,
      \CRM_Core_Form::CB_PREFIX . 23 => TRUE,
    ];
    $obj = new \CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1)  AND contact_a.id IN ( 17, 23 )', $obj->where(TRUE));
  }

  /**
   * Replace placeholder form values with created IDS.
   *
   * @param array $formValues
   *
   * @return array
   */
  private function replaceFormValuesPlaceholders(array $formValues): array {
    if (!empty($formValues['excludeGroups'])) {
      foreach ($formValues['excludeGroups'] as $index => $number) {
        $formValues['excludeGroups'][$index] = $this->ids['Group'][$number];
      }
    }
    if (!empty($formValues['includeGroups'])) {
      foreach ($formValues['includeGroups'] as $index => $number) {
        $formValues['includeGroups'][$index] = $this->ids['Group'][$number];
      }
    }
    if (!empty($formValues['excludeTags'])) {
      foreach ($formValues['excludeTags'] as $index => $number) {
        $formValues['excludeTags'][$index] = $this->ids['Tag'][$number];
      }
    }
    if (!empty($formValues['includeTags'])) {
      foreach ($formValues['includeTags'] as $index => $number) {
        $formValues['includeTags'][$index] = $this->ids['Tag'][$number];
      }
    }
    return $formValues;
  }

  /**
   * @param array $contactIDs
   *
   * @return array
   */
  private function replaceIDSPlaceholders(array $contactIDs): array {
    foreach ($contactIDs as $index => $id) {
      if ($id > 2) {
        $contactIDs[$index] = $this->ids['Contact'][$id];
      }
    }
    return $contactIDs;
  }

}
