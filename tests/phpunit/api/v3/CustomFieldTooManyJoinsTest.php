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
 *  Test excess custom fields (causing api3 to produce too many joins in the
 *  constructed SQL)
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_CustomFieldTooManyJoinsTest extends CiviUnitTestCase {

  protected $createdCustomGroups = [];

  public function testTooManyJoins() {
    $activityTypes = civicrm_api3('Activity', 'getoptions', [
      'sequential' => 1,
      'field' => "activity_type_id",
    ]);
    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => 1,
      'activity_type_id' => $activityTypes['values'][0]['key'],
    ]);
    for ($i = 0; $i < 130; $i++) {
      $customGroup = $this->customGroupCreate([
        'extends' => 'Activity',
        'title' => "Test join limit $i",
      ]);
      $this->createdCustomGroups[] = $customGroup;
      $customField = $this->customFieldCreate([
        'custom_group_id' => $customGroup['id'],
        'label' => "Activity Custom Field $i",
      ]);
    }
    $this->callAPISuccess('Activity', 'get', ['id' => $activity['id']]);
  }

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    foreach ($this->createdCustomGroups as $customGroup) {
      $this->customGroupDelete($customGroup['id']);
    }
    parent::tearDown();
  }

  // Suggestion from Totten in developer chat
  // function testTooManyJoins() {
  //   $act = callApiSuccess('Activity', 'create', array(...));
  //   for ($i = 0; $i < 70; $i++) {
  //     $cg = callApiSuccess('CustomGroup', 'create', array(...'label' => "My custom group $i"...));
  //     $cf = callApiSuccess('CustomField', 'create', array(...'label' => "My custom field $i", 'custom_group_id' => $cg['id']...));
  //   }
  //   callApiSuccess('Activity', 'get', array('id' => $act['id']));
  // }
  // function tearDown() {
  //   // destroy the ~70 CustomGroups
  // }

}
