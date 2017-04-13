<?php

/**
 * Class CRM_Utils_Hook_InspectorTest
 * @group headless
 */
class CRM_Utils_Hook_InspectorTest extends CiviUnitTestCase {

  public function testGet() {
    $inspector = new CRM_Utils_Hook_Inspector();
    $hook = $inspector->get('hook_civicrm_alterSettingsMetaData');
    $this->assertEquals('hook_civicrm_alterSettingsMetaData', $hook['name']);
    $this->assertEquals(array('settingsMetaData', 'domainID', 'profile'), array_keys($hook['fields']));
    $this->assertTrue($hook['fields']['settingsMetaData']['ref']);
    $this->assertFalse($hook['fields']['domainID']['ref']);
    $this->assertEquals('&$settingsMetaData, $domainID, $profile', $hook['signature']);
    $this->assertTrue($inspector->validate($hook));
  }

  public function testGetAll() {
    $inspector = new CRM_Utils_Hook_Inspector();
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
