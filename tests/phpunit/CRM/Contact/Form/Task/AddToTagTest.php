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
use Civi\Test\FormTrait;

/**
 * @group headless
 */
class CRM_Contact_Form_Task_AddToTagTest extends CiviUnitTestCase {
  use FormTrait;

  /**
   * Selecting a tag from a tagset's taglist widget only (no plain "tag"
   * value) must validate and apply the tag.
   *
   * https://lab.civicrm.org/dev/core/-/work_items/6660
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddToTagFromTaglistOnly(): void {
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);

    $tagset = Tag::create(FALSE)
      ->setValues(['name' => 'Test Tagset', 'label' => 'Test Tagset', 'used_for' => 'civicrm_contact', 'is_tagset' => TRUE])
      ->execute()->single();
    $childTag = Tag::create(FALSE)
      ->setValues(['name' => 'Child Tag', 'label' => 'Child Tag', 'used_for' => 'civicrm_contact', 'parent_id' => $tagset['id']])
      ->execute()->single();

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_AddToTag', [
        'tag' => '',
        'entity_taglist' => [$tagset['id'] => (string) $childTag['id']],
      ]);
    $form->processForm();

    $this->assertValidationError(['AddToTag' => []]);

    $tagCount = EntityTag::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $contact['id'])
      ->addWhere('tag_id', '=', $childTag['id'])
      ->execute()
      ->count();
    $this->assertEquals(1, $tagCount);
  }

  /**
   * Submitting the form with no tag selected anywhere must still fail
   * validation.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddToTagWithNoSelectionFails(): void {
    $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
    ]);

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_AddToTag', [
        'tag' => '',
      ]);
    $form->processForm();

    $this->assertValidationError([
      'AddToTag' => ['_qf_default' => ts('Please select at least one tag.')],
    ]);
  }

}
