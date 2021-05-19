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

/**
 * Tests for manipulating bundles
 * @group headless
 * @group resources
 */
class CRM_Core_Resources_BundleTest extends CiviUnitTestCase {

  use CRM_Core_Resources_CollectionTestTrait;

  /**
   * @return \CRM_Core_Resources_CollectionInterface
   */
  public function createEmptyCollection() {
    return new CRM_Core_Resources_Bundle();
  }

  /**
   * Create two bundles (parent, child) - and merge the child into the parent.
   */
  public function testMergeIntoRegion() {
    $bundle = $this->createEmptyCollection();
    $region = CRM_Core_Region::instance(__FUNCTION__);

    $bundle->addScriptUrl('http://example.com/bundle.js');
    $bundle->addStyleUrl('http://example.com/bundle.css');
    $bundle->addSetting(['child' => ['schoolbooks']]);
    $this->assertCount(3, $bundle->getAll());

    $region->addScriptUrl('http://example.com/region.js');
    $region->addStyleUrl('http://example.com/region.css');
    $region->addSetting(['region' => ['groceries']]);
    $this->assertCount(3 + 1 /* default */, $region->getAll());

    $region->merge($bundle->getAll());
    $this->assertCount(5 + 1 /* default */, $region->getAll());

    $expectSettings = [
      'child' => ['schoolbooks'],
      'region' => ['groceries'],
    ];
    $this->assertEquals($expectSettings, $region->getSettings());
    $this->assertEquals('http://example.com/bundle.js', $region->get('http://example.com/bundle.js')['scriptUrl']);
    $this->assertEquals('http://example.com/bundle.css', $region->get('http://example.com/bundle.css')['styleUrl']);
    $this->assertEquals('http://example.com/region.js', $region->get('http://example.com/region.js')['scriptUrl']);
    $this->assertEquals('http://example.com/region.css', $region->get('http://example.com/region.css')['styleUrl']);
  }

  /**
   * Add some resources - sometimes forgetting to set a 'region'. Fill in missing regions.
   */
  public function testFillDefaults() {
    $bundle = new CRM_Core_Resources_Bundle(__FUNCTION__, ['scriptUrl', 'styleUrl', 'markup']);
    $bundle->addScriptUrl('http://example.com/myscript.js');
    $bundle->addStyleUrl('http://example.com/yonder-style.css', ['region' => 'yonder']);
    $bundle->addMarkup('<b>Cheese</b>', ['name' => 'cheese']);

    $bundle->fillDefaults();

    $this->assertEquals('html-header', $bundle->get('http://example.com/myscript.js')['region']);
    $this->assertEquals('yonder', $bundle->get('http://example.com/yonder-style.css')['region']);
    $this->assertEquals('page-header', $bundle->get('cheese')['region']);
  }

  /**
   * Test creation of coreStyles bundle
   */
  public function testCoreStylesBundle() {
    $config = CRM_Core_Config::singleton();
    $config->customCSSURL = "http://example.com/css/custom.css";
    $bundle = CRM_Core_Resources_Common::createStyleBundle('coreStyles');
    $this->assertEquals('civicrm:css/civicrm.css', $bundle->get('civicrm:css/civicrm.css')['name']);
    $this->assertEquals('civicrm:css/crm-i.css', $bundle->get('civicrm:css/crm-i.css')['name']);
    $this->assertEquals('civicrm:css/custom.css', $bundle->get('civicrm:css/custom.css')['name']);
  }

}
