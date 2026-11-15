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

/**
 * Test the Tags tab on the contact summary.
 *
 * @group headless
 */
class CRM_Contact_Page_View_TagTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    // The Smarty singleton keeps tagsetInfo from earlier tests in the same
    // process, and CRM_Core_Form_Tag adds its own tagsets to it.
    CRM_Core_Smarty::singleton()->clearAssign('tagsetInfo');
  }

  public function tearDown(): void {
    if (!empty($this->ids['Tag'])) {
      Tag::delete(FALSE)->addWhere('id', 'IN', $this->ids['Tag'])->execute();
    }
    parent::tearDown();
  }

  /**
   * The Tags tab renders without warnings for tags the contact does not have.
   *
   * $tagged only holds the tags of the contact, and $tagsetInfo is null
   * when there are no tagsets.
   */
  public function testTagTabWithUnassignedTagAndNoTagset(): void {
    $contact = $this->createTestEntity('Contact', ['contact_type' => 'Individual', 'first_name' => 'Tagged'], 'tagged');
    $assigned = $this->createTestEntity('Tag', ['name' => 'test_assigned', 'label' => 'Assigned tag', 'used_for' => ['civicrm_contact']], 'assigned');
    $unassigned = $this->createTestEntity('Tag', ['name' => 'test_unassigned', 'label' => 'Unassigned tag', 'used_for' => ['civicrm_contact']], 'unassigned');
    $this->createTestEntity('EntityTag', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $contact['id'],
      'tag_id' => $assigned['id'],
    ], 'entity_tag');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts', 'edit all contacts']);

    $this->getTestPage('CRM_Contact_Page_View_Tag', ['cid' => $contact['id']])->run();

    $this->assertOutputContainsString('Unassigned tag');
    $this->assertOutputContainsString('id="tag_' . $assigned['id'] . '" class=" jstree-clicked"');
    $this->assertOutputNotContainsString('id="tag_' . $unassigned['id'] . '" class=" jstree-clicked"');
    $this->assertOutputNotContainsString('Tag Sets');
  }

  /**
   * The Tags tab shows the Tag Sets column when a contact tagset exists.
   */
  public function testTagTabWithContactTagset(): void {
    $contact = $this->createTestEntity('Contact', ['contact_type' => 'Individual', 'first_name' => 'Tagset'], 'tagset_contact');
    $this->createTestEntity('Tag', ['name' => 'test_contact_tagset', 'label' => 'Contact tagset', 'is_tagset' => TRUE, 'used_for' => ['civicrm_contact']], 'tagset');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts', 'edit all contacts']);

    $this->getTestPage('CRM_Contact_Page_View_Tag', ['cid' => $contact['id']])->run();

    $this->assertOutputContainsString('Tag Sets');
    $this->assertOutputContainsString('Contact tagset');
  }

}
