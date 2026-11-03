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
 * Test class for CRM_Contact_BAO_GroupNesting BAO.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_GroupNestingTest extends CiviUnitTestCase {

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown(): void {
    $this->quickCleanup(['civicrm_group', 'civicrm_group_nesting']);
    parent::tearDown();
  }

  /**
   * getDescendentGroupIds() is called with group ids keyed by group name
   * when the caller has fetched them via an api call indexed on 'name'
   * (e.g. CRM_Contactlayout_BAO_ContactLayout::checkGroupFilter() calls
   * Group::get with ['name' => 'id']). The group ids array passed in is
   * therefore not necessarily sequentially indexed from 0, but the
   * cyclical-reference check assumes it is by reading $groupIds[0].
   */
  public function testGetDescendentGroupIdsWithNonSequentialArray(): void {
    $parentId = $this->createTestEntity('Group', [
      'title' => 'Parent Group',
      'name' => 'parent_group',
    ])['id'];
    $childId = $this->createTestEntity('Group', [
      'title' => 'Child Group',
      'name' => 'child_group',
      'parents' => $parentId,
    ], 'child')['id'];

    $groupIds = ['parent_group' => $parentId];

    $result = NULL;
    $warnings = self::captureErrors(E_WARNING, function() use ($groupIds, &$result) {
      $result = CRM_Contact_BAO_GroupNesting::getDescendentGroupIds($groupIds);
    });

    $this->assertEquals([], $warnings);
    $this->assertEquals([$parentId, $childId], array_values($result));
  }

}
