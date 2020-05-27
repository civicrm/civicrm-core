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
 *  Test APIv3 civicrm_entity_tag_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 */

/**
 * Class api_v3_EntityTagTest.
 *
 * This test class was introduced to ensure that the fix for CRM-17350 (reducing the required permission
 * from edit all contacts to has right to edit this contact) would not result in inappropriate permission opening on
 * other entities. Other entities are still too restricted but that is a larger job.
 * @group headless
 */
class api_v3_EntityTagACLTest extends CiviUnitTestCase {

  use CRMTraits_ACL_PermissionTrait;

  /**
   * API Version in use.
   *
   * @var int
   */
  protected $_apiversion = 3;

  /**
   * Entity being tested.
   *
   * @var string
   */
  protected $_entity = 'entity_tag';

  /**
   * Set up permissions for test.
   */
  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $individualID = $this->individualCreate();
    $daoObj = new CRM_Core_DAO();
    $this->callAPISuccess('Attachment', 'create', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $individualID,
      'mime_type' => 'k',
      'name' => 'p',
      'content' => 'l',
    ]);
    $daoObj->createTestObject('CRM_Activity_BAO_Activity', [], 1, 0);
    $daoObj->createTestObject('CRM_Case_BAO_Case', [], 1, 0);
    $entities = $this->getTagOptions();
    foreach ($entities as $key => $entity) {
      $this->callAPISuccess('Tag', 'create', [
        'used_for' => $key,
        'name' => $entity,
        'description' => $entity,
      ]);
    }
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
  }

  /**
   * Get the options for the used_for fields.
   *
   * @return array
   */
  public function getTagOptions() {
    $options = $this->callAPISuccess('Tag', 'getoptions', ['field' => 'used_for']);
    return $options['values'];
  }

  /**
   * Get the entity table for a tag label.
   *
   * @param string $entity
   *
   * @return string
   */
  protected function getTableForTag($entity) {
    $options = $this->getTagOptions();
    return array_search($entity, $options);
  }

  /**
   * Get entities which can be tagged in data provider format.
   */
  public function taggableEntities() {
    $return = [];
    foreach ($this->getTagOptions() as $entity) {
      $return[] = [$entity];
    }
    return $return;
  }

  /**
   * This test checks that users with edit all contacts can edit all tags.
   *
   * @dataProvider taggableEntities
   *
   * We are looking to see that a contact with edit all contacts can still add all tags (for all
   * tag entities since that was how it was historically and we are not fixing non-contact entities).
   *
   * @param string $entity
   *   Entity to test
   */
  public function testThatForEntitiesEditAllContactsCanAddTags($entity) {

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit all contacts', 'access CiviCRM'];
    $this->callAPISuccess('EntityTag', 'create', [
      'entity_id' => 1,
      'tag_id' => $entity,
      'check_permissions' => TRUE,
      'entity_table' => $this->getTableForTag($entity),
    ]);
    $this->callAPISuccessGetCount('EntityTag', [
      'entity_id' => 1,
      'entity_table' => $this->getTableForTag($entity),
    ], 1);
  }

  /**
   * This test checks that an ACL or edit all contacts is required to be able to create a contact.
   *
   * @dataProvider taggableEntities
   */
  public function testThatForEntityWithoutACLOrEditAllThereIsNoAccess($entity) {

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $this->callAPIFailure('EntityTag', 'create', [
      'entity_id' => 1,
      'tag_id' => $entity,
      'check_permissions' => TRUE,
      'entity_table' => $this->getTableForTag($entity),
    ]);
  }

  /**
   * This test checks that permissions are not applied when check_permissions is off.
   *
   * @dataProvider taggableEntities
   *
   * @param string $entity
   *   Entity to test
   */
  public function testCheckPermissionsOffWorks($entity) {

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $result = $this->callAPISuccess('EntityTag', 'create', [
      'entity_id' => 1,
      'tag_id' => $entity,
      'check_permissions' => 0,
      'entity_table' => $this->getTableForTag($entity),
    ]);
    $this->assertEquals(1, $result['added']);
    $this->callAPISuccessGetCount('EntityTag', [
      'entity_id' => 1,
      'entity_table' => $this->getTableForTag($entity),
      'check_permissions' => 0,
    ], 1);
  }

  /**
   * This test checks ACLs can be used to control who can edit a contact.
   *
   * Note that for other entities this hook will not allow them to edit the entity_tag and they still need
   * edit all contacts (pending a more extensive fix).
   *
   * @dataProvider taggableEntities
   *
   * @param string $entity
   *   Entity to test
   */
  public function testThatForEntitiesACLApplies($entity) {

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereHookAllResults']);
    civicrm_api('EntityTag', 'create', [
      'version' => 3,
      'entity_id' => 1,
      'tag_id' => $entity,
      'entity_table' => $this->getTableForTag($entity),
      'check_permissions' => TRUE,
    ]);
    $this->callAPISuccessGetCount('EntityTag', [
      'entity_id' => 1,
      'entity_table' => $this->getTableForTag($entity),
    ], ($entity == 'Contacts' ? 1 : 0));
  }

}
