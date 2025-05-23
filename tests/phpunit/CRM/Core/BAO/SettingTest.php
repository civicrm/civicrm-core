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
 * Class CRM_Core_BAO_SettingTest
 * @group headless
 */
class CRM_Core_BAO_SettingTest extends CiviUnitTestCase {

  /**
   * Original value of civicrm_setting global.
   * @var array
   */
  private $origSetting;

  public function setUp(): void {
    parent::setUp();
    global $civicrm_setting;
    $this->origSetting = $civicrm_setting;
    CRM_Utils_Cache::singleton()->clear();
  }

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    global $civicrm_setting;
    $civicrm_setting = $this->origSetting;
    $this->quickCleanup(['civicrm_contribution']);
    CRM_Utils_Cache::singleton()->clear();
    parent::tearDown();
  }

  /**
   * Test that enabling a valid component works.
   */
  public function testEnableComponentValid(): void {
    CRM_Core_Config::singleton(TRUE, TRUE);
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->assertTrue($result);
  }

  /**
   * Test that we get a success result if we try to enable an enabled component.
   */
  public function testEnableComponentAlreadyPresent(): void {
    CRM_Core_Config::singleton(TRUE, TRUE);
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->assertTrue($result);
  }

  /**
   * Test that we get a false result if we try to enable an invalid component.
   */
  public function testEnableComponentInvalid(): void {
    CRM_Core_Config::singleton(TRUE, TRUE);
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviFake');
    $this->assertFalse($result);
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * using getItem($group,$name).
   */
  public function testGetItem_Override(): void {
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME]['imageUploadDir'] = '/test/override';
    Civi::service('settings_manager')->useMandatory();
    $value = Civi::settings()->get('imageUploadDir');
    $this->assertEquals('/test/override', $value);

    $civicrm_setting['domain']['customCSSURL'] = 'http://test.test/overridedomain';
    Civi::service('settings_manager')->useMandatory();
    $value = Civi::settings()->get('customCSSURL');
    $this->assertEquals('http://test.test/overridedomain', $value);

    // CRM-14974 test suite
    $civicrm_setting['Test Preferences']['overrideSetting'] = '/test/override';
    Civi::service('settings_manager')->useMandatory();
    $value = Civi::settings()->get('overrideSetting');
    $this->assertEquals('/test/override', $value);
    // CRM-14974 tear down
    unset($civicrm_setting['Test Preferences']);
    $query = "DELETE FROM civicrm_setting WHERE name = 'overrideSetting'";
    CRM_Core_DAO::executeQuery($query);
  }

  public function testDefaults(): void {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_setting WHERE name = "max_attachments"');
    Civi::service('settings_manager')->flush();
    $this->assertEquals(3, Civi::settings()->get('max_attachments'));
    $this->assertEquals(3, CRM_Core_Config::singleton()->maxAttachments);
  }

  /**
   * Ensure that on_change callbacks fire.
   *
   * Note: api_v3_SettingTest::testOnChange and CRM_Core_BAO_SettingTest::testOnChange
   * are very similar, but they exercise different codepaths. The first uses the API
   * and setItems [plural]; the second uses setItem [singular].
   */
  public function testOnChange(): void {
    global $_testOnChange_hookCalls;
    $this->setMockSettingsMetaData([
      'onChangeExample' => [
        'group_name' => 'CiviCRM Preferences',
        'group' => 'core',
        'name' => 'onChangeExample',
        'type' => 'Array',
        'quick_form_type' => 'Element',
        'html_type' => 'advmultiselect',
        'default' => ['CiviEvent', 'CiviContribute'],
        'add' => '4.4',
        'title' => 'List of Components',
        'is_domain' => '1',
        'is_contact' => 0,
        'description' => NULL,
        'help_text' => NULL,
        // list of callbacks
        'on_change' => [
          [__CLASS__, '_testOnChange_onChangeExample'],
        ],
      ],
    ]);

    // set initial value
    $_testOnChange_hookCalls = ['count' => 0];
    Civi::settings()->set('onChangeExample', ['First', 'Value']);
    $this->assertEquals(1, $_testOnChange_hookCalls['count']);
    $this->assertEquals(['First', 'Value'], $_testOnChange_hookCalls['newValue']);
    $this->assertEquals('List of Components', $_testOnChange_hookCalls['metadata']['title']);

    // change value
    $_testOnChange_hookCalls = ['count' => 0];
    Civi::settings()->set('onChangeExample', ['Second', 'Value']);
    $this->assertEquals(1, $_testOnChange_hookCalls['count']);
    $this->assertEquals(['First', 'Value'], $_testOnChange_hookCalls['oldValue']);
    $this->assertEquals(['Second', 'Value'], $_testOnChange_hookCalls['newValue']);
    $this->assertEquals('List of Components', $_testOnChange_hookCalls['metadata']['title']);
  }

  /**
   * Mock callback for a setting's on_change handler
   *
   * @param $oldValue
   * @param $newValue
   * @param $metadata
   */
  public static function _testOnChange_onChangeExample($oldValue, $newValue, $metadata) {
    global $_testOnChange_hookCalls;
    $_testOnChange_hookCalls['count']++;
    $_testOnChange_hookCalls['oldValue'] = $oldValue;
    $_testOnChange_hookCalls['newValue'] = $newValue;
    $_testOnChange_hookCalls['metadata'] = $metadata;
  }

  /**
   * Test to set isProductionEnvironment
   *
   */
  public function testSetCivicrmEnvironment(): void {
    Civi::settings()->set('environment', 'Staging');
    $values = Civi::settings()->get('environment');
    $this->assertEquals('Staging', $values);
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME]['environment'] = 'Development';
    Civi::service('settings_manager')->useMandatory();
    $environment = CRM_Core_Config::environment();
    $this->assertEquals('Development', $environment);
  }

  /**
   * Test that options defined as a pseudoconstant can be converted to options.
   */
  public function testPseudoConstants(): void {
    $this->contributionPageCreate();
    $metadata = \Civi\Core\SettingsMetadata::getMetadata(['name' => ['default_invoice_page']], NULL, TRUE);
    $this->assertEquals('Test Contribution Page', $metadata['default_invoice_page']['options'][1]);
  }

}
