<?php
namespace Civi\Core;

/**
 * Class CiviEventInspectorTest
 * @group headless
 */
class CiviEventInspectorTest extends \CiviUnitTestCase {

  public function testGet() {
    $inspector = new CiviEventInspector();
    $hook = $inspector->get('hook_civicrm_alterSettingsMetaData');
    $this->assertEquals('hook_civicrm_alterSettingsMetaData', $hook['name']);
    $this->assertEquals(array('settingsMetaData', 'domainID', 'profile'), array_keys($hook['fields']));
    $this->assertTrue($hook['fields']['settingsMetaData']['ref']);
    $this->assertFalse($hook['fields']['domainID']['ref']);
    $this->assertEquals('&$settingsMetaData, $domainID, $profile', $hook['signature']);
    $this->assertTrue($inspector->validate($hook));
  }

  public function testGetAll() {
    $inspector = new CiviEventInspector();
    $all = $inspector->getAll();
    $this->assertTrue(count($all) > 1);
    $this->assertTrue(isset($all['hook_civicrm_alterSettingsMetaData']));
    foreach ($all as $name => $hook) {
      $this->assertEquals($name, $hook['name']);
      $this->assertNotEmpty($hook['description_html']);
      $this->assertTrue($inspector->validate($hook));
    }
  }

}
