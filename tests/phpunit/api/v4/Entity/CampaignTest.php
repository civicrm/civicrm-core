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

namespace api\v4\Entity;

use api\v4\Api4TestBase;

/**
 * @group headless
 */
class CampaignTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
  }

  public function testCampaignAndSurveyUpdate(): void {
    $cid1 = $this->createLoggedInUser();

    foreach (['Campaign', 'Survey'] as $entityName) {
      $entity = $this->createTestRecord($entityName);
      $created[$entityName] = $this->getTestRecord($entityName, $entity['id']);
      $this->assertEquals($cid1, $created[$entityName]['created_id']);
      $this->assertEquals($cid1, $created[$entityName]['last_modified_id']);
      $this->assertNotNull($created[$entityName]['created_date']);
      $this->assertEquals($created[$entityName]['created_date'], $created[$entityName]['last_modified_date']);
    }

    // Switch user, update time
    $cid2 = $this->createLoggedInUser();
    sleep(1);

    // Ensure updated record reflects new user id and modified_date
    foreach (['Campaign', 'Survey'] as $entityName) {
      $updated[$entityName] = civicrm_api4($entityName, 'update', [
        'checkPermissions' => FALSE,
        'values' => ['title' => 'new', 'id' => $created[$entityName]['id']],
        'reload' => TRUE,
      ])->single();

      $this->assertEquals('new', $updated[$entityName]['title']);
      $this->assertEquals($cid1, $updated[$entityName]['created_id']);
      $this->assertEquals($cid2, $updated[$entityName]['last_modified_id']);
      $this->assertEquals($created[$entityName]['created_date'], $updated[$entityName]['created_date']);
      $this->assertGreaterThan($updated[$entityName]['created_date'], $updated[$entityName]['last_modified_date']);
    }
  }

  public function testCampaignTreeAndDescendantIds(): void {
    $p = $this->createTestRecord('Campaign', ['title' => 'Tree Parent']);
    $c1 = $this->createTestRecord('Campaign', ['title' => 'Tree Child 1', 'parent_id' => $p['id']]);
    $c2 = $this->createTestRecord('Campaign', ['title' => 'Tree Child 2', 'parent_id' => $p['id']]);
    $g1 = $this->createTestRecord('Campaign', ['title' => 'Tree Grandchild 1', 'parent_id' => $c1['id']]);

    $tree = \CRM_Campaign_BAO_Campaign::getCampaignTree();
    $this->assertArrayHasKey($p['id'], $tree);
    $this->assertEqualsCanonicalizing([$c1['id'], $c2['id']], array_map('intval', explode(',', $tree[$p['id']])));
    $this->assertArrayHasKey($c1['id'], $tree);
    $this->assertEquals((string) $g1['id'], $tree[$c1['id']]);
    // Leaf campaign should not be a key in the tree to maximize memory efficiency
    $this->assertArrayNotHasKey($g1['id'], $tree);

    $descendantsWithSelf = \CRM_Campaign_BAO_Campaign::getDescendantIds($p['id']);
    $this->assertEqualsCanonicalizing([$p['id'], $c1['id'], $c2['id'], $g1['id']], $descendantsWithSelf);

    $descendantsWithoutSelf = \CRM_Campaign_BAO_Campaign::getDescendantIds($p['id'], FALSE);
    $this->assertEqualsCanonicalizing([$c1['id'], $c2['id'], $g1['id']], $descendantsWithoutSelf);

    $leafDescendants = \CRM_Campaign_BAO_Campaign::getDescendantIds($g1['id']);
    $this->assertEquals([$g1['id']], $leafDescendants);

    $multiDescendants = \CRM_Campaign_BAO_Campaign::getDescendantIds([$c1['id'], $c2['id']]);
    $this->assertEqualsCanonicalizing([$c1['id'], $c2['id'], $g1['id']], $multiDescendants);

    // Test cycle detection
    $cycleA = $this->createTestRecord('Campaign', ['title' => 'Cycle A']);
    $cycleB = $this->createTestRecord('Campaign', ['title' => 'Cycle B', 'parent_id' => $cycleA['id']]);
    // Create cycle directly in DB
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_campaign SET parent_id = %1 WHERE id = %2', [
      1 => [$cycleB['id'], 'Positive'],
      2 => [$cycleA['id'], 'Positive'],
    ]);
    \Civi::cache('metadata')->delete('campaign_hierarchy_tree');

    $cycleDescendants = \CRM_Campaign_BAO_Campaign::getDescendantIds($cycleA['id']);
    $this->assertEqualsCanonicalizing([$cycleA['id'], $cycleB['id']], $cycleDescendants);
  }

  public function testCampaignHierarchyFilterRollup(): void {
    $parent = $this->createTestRecord('Campaign', ['title' => 'Parent Campaign']);
    $child = $this->createTestRecord('Campaign', ['title' => 'Child Campaign', 'parent_id' => $parent['id']]);
    $grandchild = $this->createTestRecord('Campaign', ['title' => 'Grandchild Campaign', 'parent_id' => $child['id']]);
    $other = $this->createTestRecord('Campaign', ['title' => 'Other Independent Campaign']);

    $contact = $this->createTestRecord('Contact');

    $actParent = $this->createTestRecord('Activity', [
      'activity_type_id:name' => 'Meeting',
      'source_contact_id' => $contact['id'],
      'subject' => 'Act in Parent Campaign',
      'campaign_id' => $parent['id'],
    ]);
    $actChild = $this->createTestRecord('Activity', [
      'activity_type_id:name' => 'Meeting',
      'source_contact_id' => $contact['id'],
      'subject' => 'Act in Child Campaign',
      'campaign_id' => $child['id'],
    ]);
    $actGrandchild = $this->createTestRecord('Activity', [
      'activity_type_id:name' => 'Meeting',
      'source_contact_id' => $contact['id'],
      'subject' => 'Act in Grandchild Campaign',
      'campaign_id' => $grandchild['id'],
    ]);
    $actOther = $this->createTestRecord('Activity', [
      'activity_type_id:name' => 'Meeting',
      'source_contact_id' => $contact['id'],
      'subject' => 'Act in Other Campaign',
      'campaign_id' => $other['id'],
    ]);

    $allActIds = [$actParent['id'], $actChild['id'], $actGrandchild['id'], $actOther['id']];

    // Filter by Parent: should return Parent, Child, and Grandchild
    $parentActs = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', '=', $parent['id'])
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$actParent['id'], $actChild['id'], $actGrandchild['id']], $parentActs);

    // Filter by Child: should return Child and Grandchild
    $childActs = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', '=', $child['id'])
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$actChild['id'], $actGrandchild['id']], $childActs);

    // Filter by Grandchild: should return only Grandchild
    $grandchildActs = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', '=', $grandchild['id'])
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$actGrandchild['id']], $grandchildActs);

    // Filter with IN operator
    $multiActs = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', 'IN', [$child['id'], $other['id']])
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$actChild['id'], $actGrandchild['id'], $actOther['id']], $multiActs);

    // Filter with negative operator (!=)
    $notChildActs = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', '!=', $child['id'])
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$actParent['id'], $actOther['id']], $notChildActs);

    // Test dynamic hierarchy change: unparent child
    \Civi\Api4\Campaign::update(FALSE)
      ->addWhere('id', '=', $child['id'])
      ->addValue('parent_id', NULL)
      ->execute();

    $parentActsAfterUnparent = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', '=', $parent['id'])
      ->execute()
      ->column('id');
    $this->assertEquals([$actParent['id']], $parentActsAfterUnparent);

    // Child should still include grandchild
    $childActsAfterUnparent = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', 'IN', $allActIds)
      ->addWhere('campaign_id', '=', $child['id'])
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$actChild['id'], $actGrandchild['id']], $childActsAfterUnparent);
  }

}
