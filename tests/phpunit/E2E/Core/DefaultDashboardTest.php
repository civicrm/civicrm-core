<?php

namespace E2E\Core;

use Civi;


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

}
