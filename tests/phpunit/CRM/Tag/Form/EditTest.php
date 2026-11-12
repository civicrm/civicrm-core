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

use Civi\Api4\Tag;
use Civi\Test\FormWrapper;

/**
 * @group headless
 */
class CRM_Tag_Form_EditTest extends CiviUnitTestCase {

  public function tearDown(): void {
    if (!empty($this->ids['Tag'])) {
      Tag::delete(FALSE)->addWhere('id', 'IN', $this->ids['Tag'])->execute();
    }
    parent::tearDown();
  }

  public function testDeleteTag(): void {
    $tagID = $this->createTestEntity('Tag', ['name' => 'doomed_tag', 'label' => 'Doomed tag'])['id'];

    $this->getTestForm('CRM_Tag_Form_Edit', [], ['action' => 'delete', 'id' => $tagID])->processForm();

    $this->assertSame(0, Tag::get(FALSE)->addWhere('id', '=', $tagID)->execute()->count());
    $this->assertSame("The tag 'Doomed tag' has been deleted.", $this->getLastStatusText());
  }

  public function testDeleteTagset(): void {
    $tagsetID = $this->createTestEntity('Tag', ['name' => 'doomed_tagset', 'label' => 'Doomed tagset', 'is_tagset' => TRUE])['id'];

    $this->getTestForm('CRM_Tag_Form_Edit', [], ['action' => 'delete', 'id' => $tagsetID])->processForm();

    $this->assertSame(0, Tag::get(FALSE)->addWhere('id', '=', $tagsetID)->execute()->count());
    $this->assertSame("The tag set 'Doomed tagset' has been deleted.", $this->getLastStatusText());
  }

  public function testCloneDefaults(): void {
    $tagID = $this->createTestEntity('Tag', [
      'name' => 'original_tag',
      'label' => 'Original tag',
      'description' => 'Tag to clone',
    ])['id'];

    $form = $this->getTestForm('CRM_Tag_Form_Edit', [], ['action' => 'add', 'clone_from' => $tagID])
      ->processForm(FormWrapper::BUILT);

    $defaults = $form->getDefaultValues();
    $this->assertSame('Original tag (copy)', $defaults['label']);
    $this->assertSame('Tag to clone', $defaults['description']);
  }

  private function getLastStatusText(): string {
    $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
    return end($statuses)['text'];
  }

}
