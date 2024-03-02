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
    // I have no idea why this is necessary. Hopefully can remove later.
    $this->expectNotToPerformAssertions();
    $session = $this->mink->getSession();
    $page = $session->getPage();

    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $gidBasic = $this->createTestRecord('Group');
    $gidMailing = $this->createTestRecord('Group', ['group_type:name' => ['Mailing List']]);

    $this->visit(Civi::url('backend://civicrm/group'));
    $session->wait(5000, 'document.querySelectorAll("tr[data-entity-id]").length > 0');
    $this->createScreenshot('/tmp/manage-groups-1.png');
    $afformTable = $page->find('xpath', '//afsearch-manage-groups//table');
    $this->assertSession()->elementExists('xpath', "//tr[@data-entity-id = '$gidBasic']", $afformTable);
    // $this->assertSession()->elementExists('xpath', "//tr[@data-entity-id = '99999']", $afformTable);
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
