<?php
/**
 * @file
 *  File for the ActivitySearchTest class
 *
 *  (PHP 5)
 *
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package   CiviCRM
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
 *  Include class definitions
 */

use Civi\Api4\OptionValue;

/**
 * Test APIv3 civicrm_activity_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Activity
 * @group headless
 */
class CRM_Contact_BAO_ActivitySearchTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  protected $contactID;

  /**
   * @var array
   */
  protected $params;

  /**
   * Test setup for every test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->contactID = $this->individualCreate();
    $activityTypes = $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 2,
      'name' => 'Test activity type',
      'label' => 'Test activity type',
      'sequential' => 1,
    ]);

    $this->params = [
      'source_contact_id' => $this->contactID,
      'activity_type_id' => $activityTypes['values'][0]['value'],
      'subject' => 'test activity type id',
      'activity_date_time' => '2011-06-02 14:36:13',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
    ];
    // Create a logged in USER since the code references it for source_contact_id.
    $this->createLoggedInUser();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_uf_match',
      'civicrm_entity_tag',
    ];
    $this->quickCleanup($tablesToTruncate, TRUE);
    OptionValue::delete()->addWhere('name', '=', 'Test activity type')->execute();
    parent::tearDown();
  }

  /**
   * Test that activity.get api works when filtering on subject.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchBySubjectOnly(): void {
    $subject = 'test activity ' . __FUNCTION__;
    $params = $this->params;
    $params['subject'] = $subject;
    $this->callAPISuccess('Activity', 'Create', $params);

    $case = [
      'form_value' => [
        'activity_text' => $subject,
        'activity_option' => 3,
      ],
      'expected_count' => 1,
      'expected_contact' => [$this->contactID],
    ];
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    [, $from, $where] = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertCount($case['expected_count'], $groupContacts);
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on subject.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchBySubjectBoth(): void {
    $subject = 'test activity ' . __FUNCTION__;
    $params = $this->params;
    $params['subject'] = $subject;
    $this->callAPISuccess('Activity', 'Create', $params);

    $case = [
      'form_value' => [
        'activity_text' => $subject,
        'activity_option' => 6,
      ],
      'expected_count' => 1,
      'expected_contact' => [$this->contactID],
    ];
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    [, $from, $where] = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertCount($case['expected_count'], $groupContacts);
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on subject.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchByDetailsOnly(): void {
    $details = 'test activity ' . __FUNCTION__;
    $params = $this->params;
    $params['details'] = $details;
    $this->callAPISuccess('Activity', 'Create', $params);

    $case = [
      'form_value' => [
        'activity_text' => $details,
        'activity_option' => 2,
      ],
      'expected_count' => 1,
      'expected_contact' => [$this->contactID],
    ];
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    [, $from, $where] = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertCount($case['expected_count'], $groupContacts);
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on details.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchByDetailsBoth(): void {
    $details = 'test activity ' . __FUNCTION__;
    $params = $this->params;
    $params['details'] = $details;
    $this->callAPISuccess('Activity', 'Create', $params);

    $case = [
      'form_value' => [
        'activity_text' => $details,
        'activity_option' => 6,
      ],
      'expected_count' => 1,
      'expected_contact' => [$this->contactID],
    ];
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    [, $from, $where] = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertCount($case['expected_count'], $groupContacts);
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on bare tags (i.e. tags
   * not part of a tagset).
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchByBareTags(): void {
    $tag = $this->callAPISuccess('Tag', 'create', [
      'name' => 'a1',
      'used_for' => 'Activities',
    ]);
    $subject = 'test activity ' . __FUNCTION__;
    $params = $this->params;
    $params['subject'] = $subject;
    $activity = $this->callAPISuccess('Activity', 'Create', $params);
    $this->callAPISuccess('EntityTag', 'create', [
      'entity_id' => $activity['id'],
      'entity_table' => 'civicrm_activity',
      'tag_id' => 'a1',
    ]);
    $case = [
      'form_value' => [
        // It looks like it can be an array or comma-separated string.
        // The qill will be slightly different ("IN" vs. "OR").
        // The search form seems to use commas.
        'activity_tags' => (string) $tag['id'],
      ],
      'expected_count' => 1,
      'expected_contact' => [$this->contactID],
    ];
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    [, $from, $where] = $query->query();

    $expectedQill = [
      0 => [
        0 => 'Activity Tag = a1',
      ],
    ];
    $this->assertEquals($expectedQill, $query->_qill);

    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertCount($case['expected_count'], $groupContacts);
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);

    // Clean up. Don't want to use teardown to wipe the table since then
    // the stock tags get wiped.
    $this->callAPISuccess('Tag', 'delete', ['id' => $tag['id']]);
  }

  /**
   * Test that activity.get api works when filtering on a tag in a tagset.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchByTagset(): void {
    $tagset = $this->callAPISuccess('Tag', 'create', [
      'name' => 'activity tagset',
      'is_tagset' => 1,
      'used_for' => 'Activities',
    ]);
    $tag = $this->callAPISuccess('Tag', 'create', [
      'name' => 'aa1',
      'used_for' => 'Activities',
      'parent_id' => 'activity tagset',
    ]);
    $subject = 'test activity ' . __FUNCTION__;
    $params = $this->params;
    $params['subject'] = $subject;
    $activity = $this->callAPISuccess('Activity', 'Create', $params);
    $this->callAPISuccess('EntityTag', 'create', [
      'entity_id' => $activity['id'],
      'entity_table' => 'civicrm_activity',
      'tag_id' => 'aa1',
    ]);
    $case = [
      'form_value' => [
        // If multiple tags the array element value is a comma-separated string
        // and then the qill looks like "IN a OR b".
        'activity_taglist' => [$tagset['id'] => (string) $tag['id']],
      ],
      'expected_count' => 1,
      'expected_contact' => [$this->contactID],
    ];
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    [, $from, $where] = $query->query();

    $expectedQill = [
      0 => [
        0 => 'Activity Tag IN aa1',
      ],
    ];
    $this->assertEquals($expectedQill, $query->_qill);

    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertCount($case['expected_count'], $groupContacts);
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);

    // Clean up. Don't want to use teardown to wipe the table since then
    // the stock tags get wiped.
    $this->callAPISuccess('Tag', 'delete', ['id' => $tag['id']]);
  }

}
