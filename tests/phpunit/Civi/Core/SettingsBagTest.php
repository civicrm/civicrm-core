<?php
namespace Civi\Core;

class SettingsBagTest extends \CiviUnitTestCase {

  protected $origSetting;

  protected function setUp(): void {
    $this->origSetting = $GLOBALS['civicrm_setting'];

    parent::setUp();
    $this->useTransaction(TRUE);

    $this->mandates = [];
  }

  public function tearDown(): void {
    $GLOBALS['civicrm_setting'] = $this->origSetting;
    parent::tearDown();
  }

  /**
   * CRM-19610 - Ensure InnoDb FTS doesn't break search preferenes when disabled.
   */
  public function testInnoDbFTS() {

    $settingsBag = \Civi::settings();

    $settingsBag->set("enable_innodb_fts", "0");
    $this->assertEquals(0, $settingsBag->get('enable_innodb_fts'));
  }

  /**
   * The setting "contribution_invoice_settings" is actually a virtual value built on other settings.
   * Check that various updates work as expected.
   */
  public function testVirtualContributionSetting_explicit() {
    $s = \Civi::settings();

    $this->assertEquals(10, $s->get('contribution_invoice_settings')['due_date']);
    $this->assertEquals(10, $s->get('invoice_due_date'));
    $this->assertEquals(NULL, $s->getExplicit('invoice_due_date'));

    $s->set('invoice_due_date', 20);
    $this->assertEquals(20, $s->get('contribution_invoice_settings')['due_date']);
    $this->assertEquals(20, $s->get('invoice_due_date'));
    $this->assertEquals(20, $s->getExplicit('invoice_due_date'));

    $s->set('contribution_invoice_settings', array_merge($s->get('contribution_invoice_settings'), [
      'due_date' => 30,
    ]));
    $this->assertEquals(30, $s->get('contribution_invoice_settings')['due_date']);
    $this->assertEquals(30, $s->get('invoice_due_date'));
    $this->assertEquals(30, $s->getExplicit('invoice_due_date'));

    $s->revert('invoice_due_date');
    $this->assertEquals(10, $s->get('contribution_invoice_settings')['due_date']);
    $this->assertEquals(10, $s->get('invoice_due_date'));
    $this->assertEquals(NULL, $s->getExplicit('invoice_due_date'));
  }

  /**
   * The setting "contribution_invoice_settings" is actually a virtual value built on other settings.
   * Check that mandatory values ($civicrm_settings) are respected.
   */
  public function testVirtualContributionSetting_mandatory() {
    $s = \Civi::settings();
    $this->assertEquals(10, $s->get('contribution_invoice_settings')['due_date']);
    $this->assertEquals(10, $s->get('invoice_due_date'));
    $this->assertEquals(NULL, $s->getExplicit('invoice_due_date'));

    $s->loadMandatory(['invoice_due_date' => 30]);

    $this->assertEquals(30, $s->get('contribution_invoice_settings')['due_date']);
    $this->assertEquals(30, $s->get('invoice_due_date'));
    $this->assertEquals(NULL, $s->getExplicit('invoice_due_date'));
  }

}
