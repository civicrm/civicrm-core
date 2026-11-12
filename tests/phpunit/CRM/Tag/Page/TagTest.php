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
 * @group headless
 */
class CRM_Tag_Page_TagTest extends CiviUnitTestCase {

  public function tearDown(): void {
    if (!empty($this->ids['Tag'])) {
      Tag::delete(FALSE)->addWhere('id', 'IN', $this->ids['Tag'])->execute();
    }
    parent::tearDown();
  }

  public function testTagsetsAreAssigned(): void {
    $this->createLoggedInUser();
    $this->createTestEntity('Tag', [
      'name' => 'test_tagset',
      'label' => 'Test Tagset',
      'description' => 'A tagset for testing',
      'is_tagset' => TRUE,
      'used_for' => ['civicrm_contact', 'civicrm_activity'],
      'created_id' => CRM_Core_Session::getLoggedInContactID(),
    ], 'tagset');

    $this->getTestPage('CRM_Tag_Page_Tag')->run();

    $this->assertOutputContainsString('Test Tagset');
    $this->assertSame('Contact', $this->getPageTemplateVariable('usedFor')['civicrm_contact']);

    $tagset = $this->getPageTemplateVariable('tagsets')[$this->ids['Tag']['tagset']];
    $this->assertSame('Test Tagset', $tagset['label']);
    $this->assertSame('A tagset for testing', $tagset['description']);
    $this->assertSame('civicrm_contact,civicrm_activity', $tagset['used_for']);
    $this->assertSame(['Contact', 'Activity'], $tagset['used_for_label']);
    $this->assertSame('Contact, Activity', $tagset['used_for_label_str']);
    $this->assertNotEmpty($this->getPageTemplateVariable('userDisplayName'));
    $this->assertSame($this->getPageTemplateVariable('userDisplayName'), $tagset['display_name']);
  }

}
