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
 * Test class for CRM_Core_Form_Tag.
 * @group headless
 */
class CRM_Core_Form_TagTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_entity_tag', 'civicrm_tag']);
    parent::tearDown();
  }

  /**
   * The tags submitted for a tagset replace that record's tags and nothing else.
   */
  public function testPostProcessReplacesTagsOfOneRecord(): void {
    [$tagsetID, $firstTagID, $secondTagID] = $this->createTagsetWithTwoTags();
    $contactID = $this->individualCreate([], 'tagged');
    $otherContactID = $this->individualCreate([], 'other');
    $this->createTestEntity('EntityTag', ['entity_table' => 'civicrm_contact', 'entity_id' => $contactID, 'tag_id' => $firstTagID], 'tagged');
    $this->createTestEntity('EntityTag', ['entity_table' => 'civicrm_contact', 'entity_id' => $otherContactID, 'tag_id' => $firstTagID], 'other');

    $params = [$tagsetID => (string) $secondTagID];
    CRM_Core_Form_Tag::postProcess($params, $contactID);

    $this->assertEquals([$secondTagID], $this->getTagIDs($contactID));
    $this->assertEquals([$firstTagID], $this->getTagIDs($otherContactID));
  }

  /**
   * A tagset value that is not a list of tag ids is rejected and changes nothing.
   */
  public function testPostProcessRejectsNonIntegerTag(): void {
    [$tagsetID, $firstTagID] = $this->createTagsetWithTwoTags();
    $contactID = $this->individualCreate([], 'tagged');
    $otherContactID = $this->individualCreate([], 'other');
    $this->createTestEntity('EntityTag', ['entity_table' => 'civicrm_contact', 'entity_id' => $otherContactID, 'tag_id' => $firstTagID]);

    $params = [$tagsetID => '0) OR (1=1'];
    try {
      CRM_Core_Form_Tag::postProcess($params, $contactID);
      $this->fail('The tagset value was accepted.');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEquals([$firstTagID], $this->getTagIDs($otherContactID));
    }
  }

  private function createTagsetWithTwoTags(): array {
    $tagsetID = $this->createTestEntity('Tag', ['name' => 'Tagset', 'is_tagset' => TRUE, 'used_for' => ['civicrm_contact']], 'tagset')['id'];
    $firstTagID = $this->createTestEntity('Tag', ['name' => 'First', 'parent_id' => $tagsetID, 'used_for' => ['civicrm_contact']], 'first')['id'];
    $secondTagID = $this->createTestEntity('Tag', ['name' => 'Second', 'parent_id' => $tagsetID, 'used_for' => ['civicrm_contact']], 'second')['id'];
    return [$tagsetID, $firstTagID, $secondTagID];
  }

  private function getTagIDs(int $contactID): array {
    return (array) \Civi\Api4\EntityTag::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $contactID)
      ->execute()->column('tag_id');
  }

}
