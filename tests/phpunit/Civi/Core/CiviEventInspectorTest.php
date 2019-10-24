<?php
namespace Civi\Core;

/**
 * Class CiviEventInspectorTest
 * @group headless
 */
class CiviEventInspectorTest extends \CiviUnitTestCase {

  public function testGet() {
    $inspector = new CiviEventInspector();
    $eventDef = $inspector->get('hook_civicrm_alterSettingsMetaData');
    $this->assertEquals('hook_civicrm_alterSettingsMetaData', $eventDef['name']);
    $this->assertEquals(['settingsMetaData', 'domainID', 'profile'], array_keys($eventDef['fields']));
    $this->assertEquals('hook', $eventDef['type']);
    $this->assertNotEmpty($eventDef['description_html']);
    $this->assertTrue($eventDef['fields']['settingsMetaData']['ref']);
    $this->assertFalse($eventDef['fields']['domainID']['ref']);
    $this->assertEquals('&$settingsMetaData, $domainID, $profile', $eventDef['signature']);
    $this->assertTrue($inspector->validate($eventDef));
    $this->assertTrue($eventDef['stub'] instanceof \ReflectionMethod);
    $this->assertTrue($eventDef['stub']->isStatic());
  }

  public function testGetAll() {
    $inspector = new CiviEventInspector();
    $all = $inspector->getAll();
    $this->assertTrue(count($all) > 1);
    $this->assertTrue(isset($all['hook_civicrm_alterSettingsMetaData']));
    foreach ($all as $name => $eventDef) {
      $this->assertEquals($name, $eventDef['name']);
      $this->assertTrue($inspector->validate($eventDef));
      if (isset($eventDef['stub'])) {
        $this->assertTrue($eventDef['stub'] instanceof \ReflectionMethod);
        $this->assertTrue($eventDef['stub']->isStatic());
      }
    }
  }

  public function testFind() {
    $inspector = new CiviEventInspector();

    $result_a = $inspector->find('/^hook_civicrm_post/');
    $this->assertTrue(is_array($result_a['hook_civicrm_post']));
    $this->assertFalse(isset($result_a['hook_civicrm_pre']));

    $result_b = $inspector->find('/^hook_civicrm_pre/');
    $this->assertTrue(is_array($result_b['hook_civicrm_pre']));
    $this->assertFalse(isset($result_b['hook_civicrm_post']));
  }

}
