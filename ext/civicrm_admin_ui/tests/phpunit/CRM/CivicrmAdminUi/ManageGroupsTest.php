<?php

use Civi\Api4\Group;

/**
 * E2E Mink tests for Manage Groups screen.
 * 
 * @group e2e
 * @see cv
 */
class CRM_CivicrmAdminUi_ManageGroupsTest extends \Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    // Example: Install this extension. Don't care about anything else.
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  public function testManageGroups() {
    $session = $this->mink->getSession();
    $page = $session->getPage();

    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $gidBasic = $this->createTestRecord('Group')['id'];
    $gidMailing = $this->createTestRecord('Group', ['group_type:name' => ['Mailing List']])['id'];
    $gidInactive = $this->createTestRecord('Group', ['is_active' => FALSE])['id'];
    $this->visit(Civi::url('backend://civicrm/group'));
    $session->wait(5000, 'document.querySelectorAll("tr[data-entity-id]").length > 0');
    $this->createScreenshot('/tmp/manage-groups-1.png');
    $afformTable = $page->find('xpath', '//afsearch-manage-groups//table');
    $this->assertSession()->elementExists('css', "tr[data-entity-id='this-is-a-failing-test']", $afformTable);
    $basicGroupRow = $this->assertSession()->elementExists('css', "tr[data-entity-id='$gidBasic']", $afformTable);
    $this->assertSession()->elementTextNotContains('css', "tr[data-entity-id='$gidBasic']", 'Mailing List');
    $this->assertSession()->elementExists('css', "tr[data-entity-id='$gidMailing']", $afformTable);
    $this->assertSession()->elementTextContains('css', "tr[data-entity-id='$gidMailing']", 'Mailing List');
    $this->assertSession()->elementNotExists('css', "tr[data-entity-id='$gidInactive']", $afformTable);
    $this->createScreenshot('/tmp/test-manage-groups.png');
    // Test some in-line editing.
    // Equivalent JS: document.querySelector('[data-field-name="is_active"]').querySelector('span').click(); 
    $isActiveCell = $basicGroupRow->find('css', '[data-field-name="is_active"]');
    $isActiveField = $isActiveCell->find('css', 'span');
    $isActiveField->click();
    $isActiveCell->find('css','input[value="false"]')->click();
    $isActiveCell->find('css','button.btn-success')->click();
    // Confirm the group is now inactive.  But wait until "Saved" appears because of race conditions.
    $session->wait(5000, 'document.querySelectorAll("div.crm-status-box-outer.status-success").length > 0');
    $basicGroupStatus = Group::get(FALSE)->addWhere('id', '=', $gidBasic)->execute()->single()['is_active'];
    $this->assertEquals(FALSE, $basicGroupStatus);
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
