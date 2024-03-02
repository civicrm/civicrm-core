<?php

// use CRM_CivicrmAdminUi_ExtensionUtil as E;
// use Civi\Test\EndToEndInterface;
use Civi;
use Civi\Api4\Group;

/**
 * E2E Mink tests for Manage Groups screen.
 * 
 * @group e2e
 * @see cv
 */
class CRM_CivicrmAdminUi_ManageGroupsTest extends \Civi\Test\MinkBase {

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
    $gid1 = Group::create(FALSE)
      ->addValue('name', 'group1')
      ->addValue('title', 'Group 1')
      ->addValue('description', 'This is a basic group.')
      ->execute()[0]['id'];
    $gid2 = Group::create(FALSE)
      ->addValue('name', 'mailing_group')
      ->addValue('title', 'A Mailing Group')
      ->addValue('description', 'This is a mailing group.')
      ->addValue('group_type:name', ['Mailing List'])
      ->execute()[0]['id'];

    $this->visit(Civi::url('backend://civicrm/group'));
    $session->wait(5000, 'document.querySelectorAll("tr[data-entity-id]").length > 0');
    $this->createScreenshot('/tmp/manage-groups-1.png');
    $afformTable = $page->find('xpath', '//afsearch-manage-groups//table');
    $this->assertSession()->elementExists('xpath', "//tr[@data-entity-id = '$gid1']", $afformTable);
    // $this->assertSession()->elementExists('xpath', "//tr[@data-entity-id = '99999']", $afformTable);
  }

  public function tearDown(): void {
    Group::delete(FALSE)->addWhere('id', '>=', 1)->execute();
    parent::tearDown();
  }

}
