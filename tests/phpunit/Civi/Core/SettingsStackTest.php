<?php
namespace Civi\Core;

class SettingsStackTest extends \CiviUnitTestCase {

  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Temporarily modify -- then restore -- settings.
   */
  public function testStack() {
    $origVal = \Civi::settings()->get('show_events');

    $settingsStack = new \Civi\Core\SettingsStack();

    $settingsStack->push('show_events', 9);
    $this->assertEquals(9, \Civi::settings()->get('show_events'));

    $settingsStack->push('show_events', 8);
    $this->assertEquals(8, \Civi::settings()->get('show_events'));

    $settingsStack->popAll();
    $this->assertEquals($origVal, \Civi::settings()->get('show_events'));
  }

}
