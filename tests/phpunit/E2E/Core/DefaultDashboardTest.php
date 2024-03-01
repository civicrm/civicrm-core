<?php

namespace E2E\Core;

use Civi;
use Civi\Api4\Group;

/**
 * Class DefaultDashboardTest
 *
 * @package E2E\Core
 * @group e2e
 */
class DefaultDashboardTest extends \MinkBase {

  public function testDashboard() {
    $session = $this->mink->getSession();
    $page = $session->getPage();

    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->createScreenshot('/tmp/test-login.png');
    $this->visit(Civi::url('backend://civicrm/dashboard'));
    $session->wait(5000, "document.getElementsByClassName('crm-hover-button').length");
    $page->find('xpath', '//a[contains(@class, "crm-hover-button")]')->click();
    $this->createScreenshot('/tmp/test-dashboard.png');
    $this->assertSession()->pageTextContains('Event Income Summary');
  }

  public function testManageGroups() {
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
    $session->wait(5000, 'document.querySelector("afsearch-manage-groups") != null');
    $this->createScreenshot('/tmp/manage-groups-1.png');
    $afformTable = $page->find('xpath', '//afsearch-manage-groups//table');
    // $group1Row = $afformTable->find('xpath', '//tr[contains(text(), "Group 1")]');
    $group1Row = $afformTable->find('xpath', '//*[contains(text(), "Group 1")]/ancestor::tr');
    // $rows->find('named', ['content', 'Group 1']);
    // Delete all groups.
    Group::delete(FALSE)->addWhere('id', 'IN', [$gid1, $gid2])->execute();
  }
    $session = $this->mink->getSession();
    $page = $session->getPage();

    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    file_put_contents('/tmp/test-login.png', $this->mink->getSession()->getDriver()->getScreenshot());

    $this->visit(Civi::url('backend://civicrm/dashboard'));
    $session->wait(5000, "document.getElementsByClassName('crm-hover-button').length");
    $inactiveDashletLink = $page->find('xpath', '//a[contains(@class, "crm-hover-button")]')->click();
    
    file_put_contents('/tmp/test-dashboard.png', $this->mink->getSession()->getDriver()->getScreenshot());
    $this->assertSession()->pageTextContains('Event Income Summary');
  }

}
