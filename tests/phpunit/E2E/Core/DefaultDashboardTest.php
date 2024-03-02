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
class DefaultDashboardTest extends \Civi\Test\MinkBase {

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

  public function testDefaultCurrency() {
    $session = $this->mink->getSession();
    $page = $session->getPage();

    // login
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    file_put_contents('/tmp/test-login.png', $this->mink->getSession()->getDriver()->getScreenshot());

    // set the default currenty to USD
    $results = \Civi\Api4\Setting::set(FALSE)
      ->addValue('defaultCurrency', 'USD')
      ->execute();

    // add a product using USD
    $result1 = \Civi\Api4\Product::create(TRUE)
      ->addValue('name', '"Takeya Actives Insulated Water Bottle"')
      ->addValue('description', '"Great spout on water bottle with flip lid."')
      ->addValue('sku', '"WBOT-101"')
      ->addValue('options', '"Black, Green, Red",')
      ->addValue('price', 32)
      ->addValue('currency', 'USD')
      ->addValue('is_active', TRUE)
      ->execute();
    
    $gid1 = $result1[0]['id'];

    // add a product using EUR
    $results2 = \Civi\Api4\Product::create(TRUE)
      ->addValue('name', '"Black Bennie"')
      ->addValue('description', '"Cotton beannie with logo."')
      ->addValue('sku', '"BEAN101"')
      ->addValue('options', '"Navy Blue, Orange, Pink",')
      ->addValue('price', 10)
      ->addValue('currency', 'EUR')
      ->addValue('is_active', TRUE)
      ->execute();

    $gid2 = $results2[0]['id'];

    $this->visit(Civi::url('backend://civicrm/admin/contribute/managePremiums'));
    $session->wait(5000, 'document.querySelectorAll("tr[data-entity-id]").length > 0');
    $this->createScreenshot('/tmp/test-searchProductPage.png');

    $this->assertSession()->elementTextContains('xpath', "//tr[@data-entity-id = '$gid1']", "$");
    $this->assertSession()->elementTextContains('xpath', "//tr[@data-entity-id = '$gid2']", "€");

  }

}
