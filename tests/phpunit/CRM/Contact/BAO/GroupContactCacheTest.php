<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

require_once 'CiviTest/Contact.php';

/**
 * Test class for CRM_Contact_BAO_GroupContact BAO
 *
 * @package   CiviCRM
 */
class CRM_Contact_BAO_GroupContactCacheTest extends CiviUnitTestCase {

  /**
   * Manually add and remove contacts from a smart group.
   */
  public function testManualAddRemove() {
    // Create smart group $g
    $params = array(
      'name' => 'Deceased Contacts',
      'title' => 'Deceased Contacts',
      'is_active' => 1,
      'formValues' => array('is_deceased' => 1),
    );
    $group = CRM_Contact_BAO_Group::createSmartGroup($params);
    $this->registerTestObjects(array($group));

    // Create contacs $y1, $y2, $y3 which do match $g; create $n1, $n2, $n3 which do not match $g
    $living = $this->createTestObject('CRM_Contact_DAO_Contact', array('is_deceased' => 0), 3);
    $deceased = $this->createTestObject('CRM_Contact_DAO_Contact', array('is_deceased' => 1), 3);
    $this->assertEquals(3, count($deceased));
    $this->assertEquals(3, count($living));

    // Assert: $g cache has exactly $y1, $y2, $y3
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    $this->assertCacheMatches(
      array($deceased[0]->id, $deceased[1]->id, $deceased[2]->id),
      $group->id
    );

    // Add $n1 to $g
    $result = civicrm_api('group_contact', 'create', array(
      'contact_id' => $living[0]->id,
      'group_id' => $group->id,
      'version' => '3',
    ));
    $this->assertAPISuccess($result);
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    $this->assertCacheMatches(
      array($deceased[0]->id, $deceased[1]->id, $deceased[2]->id, $living[0]->id),
      $group->id
    );

    // Remove $y1 from $g
    $result = civicrm_api('group_contact', 'create', array(
      'contact_id' => $deceased[0]->id,
      'group_id' => $group->id,
      'status' => 'Removed',
      'version' => '3',
    ));
    $this->assertAPISuccess($result);
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    $this->assertCacheMatches(
      array(/* deceased[0], */
        $deceased[1]->id,
        $deceased[2]->id,
        $living[0]->id,
      ),
      $group->id
    );
  }

  /**
   * Allow removing contact from a parent group even if contact is in
   * a child group. (CRM-8858)
   */
  public function testRemoveFromParentSmartGroup() {
    // Create smart group $parent
    $params = array(
      'name' => 'Deceased Contacts',
      'title' => 'Deceased Contacts',
      'is_active' => 1,
      'formValues' => array('is_deceased' => 1),
    );
    $parent = CRM_Contact_BAO_Group::createSmartGroup($params);
    $this->registerTestObjects(array($parent));

    // Create group $child in $parent
    $params = array(
      'name' => 'Child Group',
      'title' => 'Child Group',
      'is_active' => 1,
      'parents' => array($parent->id => 1),
    );
    $child = CRM_Contact_BAO_Group::create($params);
    $this->registerTestObjects(array($child));

    // Create $c1, $c2, $c3
    $deceased = $this->createTestObject('CRM_Contact_DAO_Contact', array('is_deceased' => 1), 3);

    // Add $c1, $c2, $c3 to $child
    foreach ($deceased as $contact) {
      $result = $this->callAPISuccess('group_contact', 'create', array(
        'contact_id' => $contact->id,
        'group_id' => $child->id,
      ));
    }

    // GroupContactCache::load()
    CRM_Contact_BAO_GroupContactCache::load($parent, TRUE);
    $this->assertCacheMatches(
      array($deceased[0]->id, $deceased[1]->id, $deceased[2]->id),
      $parent->id
    );

    // Remove $c1 from $parent
    $result = civicrm_api('group_contact', 'create', array(
      'contact_id' => $deceased[0]->id,
      'group_id' => $parent->id,
      'status' => 'Removed',
      'version' => '3',
    ));
    $this->assertAPISuccess($result);

    // Assert $c1 not in $parent
    CRM_Contact_BAO_GroupContactCache::load($parent, TRUE);
    $this->assertCacheMatches(
      array(/* deceased[0], */
        $deceased[1]->id,
        $deceased[2]->id,
      ),
      $parent->id
    );

    // Assert $c1 still in $child
    $this->assertDBQuery(1,
      'select count(*) from civicrm_group_contact where group_id=%1 and contact_id=%2 and status=%3',
      array(
        1 => array($child->id, 'Integer'),
        2 => array($deceased[0]->id, 'Integer'),
        3 => array('Added', 'String'),
      )
    );
  }

  /**
   * Assert that the cache for a group contains exactly the listed contacts.
   *
   * @param array $expectedContactIds
   *   Array(int).
   * @param int $groupId
   */
  public function assertCacheMatches($expectedContactIds, $groupId) {
    $sql = 'SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id = %1';
    $params = array(1 => array($groupId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $actualContactIds = array();
    while ($dao->fetch()) {
      $actualContactIds[] = $dao->contact_id;
    }

    sort($expectedContactIds);
    sort($actualContactIds);
    $this->assertEquals($expectedContactIds, $actualContactIds);
  }

  // *** Everything below this should be moved to parent class ****

  /**
   * @var array(DAO_Name => array(int)) List of items to garbage-collect during tearDown
   */
  private $_testObjects;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->_testObjects = array();
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    parent::tearDown();
    $this->deleteTestObjects();
  }

  /**
   * This is a wrapper for CRM_Core_DAO::createTestObject which tracks
   * created entities and provides for brainless clenaup.
   *
   * @see CRM_Core_DAO::createTestObject
   * @param $daoName
   * @param array $params
   * @param int $numObjects
   * @param bool $createOnly
   */
  public function createTestObject($daoName, $params = array(), $numObjects = 1, $createOnly = FALSE) {
    $objects = CRM_Core_DAO::createTestObject($daoName, $params, $numObjects, $createOnly);
    if (is_array($objects)) {
      $this->registerTestObjects($objects);
    }
    else {
      $this->registerTestObjects(array($objects));
    }
    return $objects;
  }

  /**
   * @param array $objects
   *   DAO or BAO objects.
   */
  public function registerTestObjects($objects) {
    //if (is_object($objects)) {
    //  $objects = array($objects);
    //}
    foreach ($objects as $object) {
      $daoName = preg_replace('/_BAO_/', '_DAO_', get_class($object));
      $this->_testObjects[$daoName][] = $object->id;
    }
  }

  public function deleteTestObjects() {
    // Note: You might argue that the FK relations between test
    // objects could make this problematic; however, it should
    // behave intuitively as long as we mentally split our
    // test-objects between the "manual/primary records"
    // and the "automatic/secondary records"
    foreach ($this->_testObjects as $daoName => $daoIds) {
      foreach ($daoIds as $daoId) {
        CRM_Core_DAO::deleteTestObjects($daoName, array('id' => $daoId));
      }
    }
    $this->_testObjects = array();
  }

}
