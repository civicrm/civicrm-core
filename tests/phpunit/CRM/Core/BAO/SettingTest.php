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

  public function setUp() {
    parent::setUp();
    global $civicrm_setting;
    $this->origSetting = $civicrm_setting;
    CRM_Utils_Cache::singleton()->flush();
  }

  public function tearDown() {
    global $civicrm_setting;
    $civicrm_setting = $this->origSetting;
    CRM_Utils_Cache::singleton()->flush();
    parent::tearDown();
  }

  /**
   * Test that enabling a valid component works.
   */
  public function testEnableComponentValid() {
    CRM_Core_Config::singleton(TRUE, TRUE);
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->assertTrue($result);
  }

  /**
   * Test that we get a success result if we try to enable an enabled component.
   */
  public function testEnableComponentAlreadyPresent() {
    CRM_Core_Config::singleton(TRUE, TRUE);
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->assertTrue($result);
  }

  /**
   * Test that we get a false result if we try to enable an invalid component.
   */
  public function testEnableComponentInvalid() {
    CRM_Core_Config::singleton(TRUE, TRUE);
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviFake');
    $this->assertFalse($result);
  }

  /**
   * Test temporary retrieval & setting of converted settings.
   *
   * As a transitional measure we allow the settings that were munged into
   * contribution_invoice_setting. This tests that the current method of getting via the 'old' key
   * works. This will be deprecated & removed over the next few versions but
   * 1) some extensions use these settings &
   * 2) there is a lot of work to fix this mess in core so a transitional method makes sense.
   *
   * https://lab.civicrm.org/dev/core/issues/1558
   *
   * @throws \CRM_Core_Exception
   */
  public function testHandlingOfContributionInvoiceSetting() {
    $contributionSettings = [
      'invoice_prefix' => 'G_',
      'credit_notes_prefix' => 'XX_',
      'due_date' => '20',
      'due_date_period' => 'weeks',
      'notes' => '<p>Give me money</p>',
      'tax_term' => 'Extortion',
      'tax_display_settings' => 'Exclusive',
      'invoicing' => 1,
      'is_email_pdf' => '1',
    ];
    Civi::settings()->set('contribution_invoice_settings', $contributionSettings);
    $settingsFromGet = Civi::settings()->get('contribution_invoice_settings');
    $settingsFromAPI = $this->callAPISuccess('Setting', 'get', ['return' => 'contribution_invoice_settings'])['values'][CRM_Core_Config::domainID()]['contribution_invoice_settings'];
    $getVersion = $this->callAPISuccessGetValue('Setting', ['name' => 'contribution_invoice_settings']);
    $this->assertEquals($settingsFromAPI, $settingsFromGet);
    $this->assertAPIArrayComparison($getVersion, $settingsFromGet);
    $this->assertEquals($contributionSettings, $settingsFromGet);

    // These are the preferred retrieval methods.
    $this->assertEquals('G_', Civi::settings()->get('invoice_prefix'));
    $this->assertEquals('XX_', Civi::settings()->get('credit_notes_prefix'));
    $this->assertEquals('20', Civi::settings()->get('invoice_due_date'));
    $this->assertEquals('weeks', Civi::settings()->get('invoice_due_date_period'));
    $this->assertEquals('<p>Give me money</p>', Civi::settings()->get('invoice_notes'));
    $this->assertEquals('Extortion', Civi::settings()->get('tax_term'));
    $this->assertEquals('Exclusive', Civi::settings()->get('tax_display_settings'));
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * using getItem($group,$name).
   */
  public function testGetItem_Override() {
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME]['imageUploadDir'] = '/test/override';
    Civi::service('settings_manager')->useMandatory();
    $value = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME, 'imageUploadDir');
    $this->assertEquals('/test/override', $value);

    // CRM-14974 test suite
    $civicrm_setting['Test Preferences']['overrideSetting'] = '/test/override';
    Civi::service('settings_manager')->useMandatory();
    $values = CRM_Core_BAO_Setting::getItem('Test Preferences');
    $this->assertEquals('/test/override', $values['overrideSetting']);
    Civi::settings()->set('databaseSetting', '/test/database');
    $values = CRM_Core_BAO_Setting::getItem('Test Preferences');
    $this->assertEquals('/test/override', $values['overrideSetting']);
    $this->assertEquals('/test/database', $values['databaseSetting']);
    $civicrm_setting['Test Preferences']['databaseSetting'] = '/test/dataride';
    Civi::service('settings_manager')->useMandatory();
    $values = CRM_Core_BAO_Setting::getItem('Test Preferences');
    $this->assertEquals('/test/override', $values['overrideSetting']);
    $this->assertEquals('/test/dataride', $values['databaseSetting']);
    // CRM-14974 tear down
    unset($civicrm_setting['Test Preferences']);
    $query = "DELETE FROM civicrm_setting WHERE name IN ('overrideSetting', 'databaseSetting');";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * using getItem($group).
   */
  public function testGetItemGroup_Override() {
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME]['imageUploadDir'] = '/test/override';
    Civi::service('settings_manager')->useMandatory();
    $values = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME);
    $this->assertEquals('/test/override', $values['imageUploadDir']);
  }

  public function testDefaults() {
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
  public function testOnChange() {
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
  public function testSetCivicrmEnvironment() {
    Civi::settings()->set('environment', 'Staging');
    $values = Civi::settings()->get('environment');
    $this->assertEquals('Staging', $values);
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME]['environment'] = 'Development';
    Civi::service('settings_manager')->useMandatory();
    $environment = CRM_Core_Config::environment();
    $this->assertEquals('Development', $environment);
  }

}
