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

use Civi\Api4\EntityTag;
use Civi\Api4\Tag;

/**
 * @group headless
 */
class CRM_Tag_Form_MergeTest extends CiviUnitTestCase {

  public function tearDown(): void {
    if (!empty($this->ids['Tag'])) {
      EntityTag::delete(FALSE)->addWhere('tag_id', 'IN', $this->ids['Tag'])->execute();
      Tag::delete(FALSE)->addWhere('id', 'IN', $this->ids['Tag'])->execute();
    }
    parent::tearDown();
  }

  public function testMergeTags(): void {
    $primaryTagID = $this->createTestEntity('Tag', ['name' => 'primary_tag', 'label' => 'Primary tag', 'used_for' => ['civicrm_contact']], 'primary')['id'];
    $otherTagID = $this->createTestEntity('Tag', ['name' => 'other_tag', 'label' => 'Other tag', 'used_for' => ['civicrm_contact']], 'other')['id'];
    $taggedWithOtherID = $this->individualCreate([], 'tagged_with_other');
    $taggedWithBothID = $this->individualCreate([], 'tagged_with_both');
    $this->tagContact($taggedWithOtherID, $otherTagID);
    $this->tagContact($taggedWithBothID, $primaryTagID);
    $this->tagContact($taggedWithBothID, $otherTagID);

    $form = $this->getTestForm('CRM_Tag_Form_Merge', ['label' => 'Merged tag'], ['id' => "$otherTagID,$primaryTagID"]);
    $form->processForm();

    $this->assertSame([$primaryTagID => 'Primary tag', $otherTagID => 'Other tag'], $form->getTemplateVariable('tags'));
    $this->assertSame(0, Tag::get(FALSE)->addWhere('id', '=', $otherTagID)->execute()->count());
    $this->assertSame('Merged tag', Tag::get(FALSE)->addWhere('id', '=', $primaryTagID)->execute()->single()['label']);
    foreach ([$taggedWithOtherID, $taggedWithBothID] as $contactID) {
      $tagIDs = EntityTag::get(FALSE)
        ->addWhere('entity_table', '=', 'civicrm_contact')
        ->addWhere('entity_id', '=', $contactID)
        ->execute()
        ->column('tag_id');
      $this->assertSame([$primaryTagID], $tagIDs);
    }
  }

  public function testMergeReservedTagWithoutPermission(): void {
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'manage tags'];
    $reservedTagID = $this->createTestEntity('Tag', ['name' => 'reserved_tag', 'label' => 'Reserved tag', 'is_reserved' => TRUE], 'reserved')['id'];
    $otherTagID = $this->createTestEntity('Tag', ['name' => 'other_tag', 'label' => 'Other tag'], 'other')['id'];

    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->getTestForm('CRM_Tag_Form_Merge', ['label' => 'Merged tag'], ['id' => "$reservedTagID,$otherTagID"])
      ->processForm();
  }

  private function tagContact(int $contactID, int $tagID): void {
    EntityTag::create(FALSE)
      ->setValues(['entity_table' => 'civicrm_contact', 'entity_id' => $contactID, 'tag_id' => $tagID])
      ->execute();
  }

}
