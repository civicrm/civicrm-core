<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */


require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Core_BAO_SettingTest
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

  public function testEnableComponentValid() {
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');

    $this->assertTrue($result);
  }

  public function testEnableComponentAlreadyPresent() {
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');

    $this->assertTrue($result);
  }

  public function testEnableComponentInvalid() {
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    $result = CRM_Core_BAO_ConfigSetting::enableComponent('CiviFake');

    $this->assertFalse($result);
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * using getItem($group,$name).
   */
  public function testGetItem_Override() {
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME]['imageUploadDir'] = '/test/override';
    $value = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME, 'imageUploadDir');
    $this->assertEquals('/test/override', $value);

    // CRM-14974 test suite
    $civicrm_setting['Test Preferences']['overrideSetting'] = '/test/override';
    $values = CRM_Core_BAO_Setting::getItem('Test Preferences');
    $this->assertEquals('/test/override', $values['overrideSetting']);
    CRM_Core_BAO_Setting::setItem('/test/database', 'Test Preferences', 'databaseSetting');
    $values = CRM_Core_BAO_Setting::getItem('Test Preferences');
    $this->assertEquals('/test/override', $values['overrideSetting']);
    $this->assertEquals('/test/database', $values['databaseSetting']);
    $civicrm_setting['Test Preferences']['databaseSetting'] = '/test/dataride';
    $values = CRM_Core_BAO_Setting::getItem('Test Preferences');
    $this->assertEquals('/test/override', $values['overrideSetting']);
    $this->assertEquals('/test/dataride', $values['databaseSetting']);
    // CRM-14974 tear down
    unset($civicrm_setting['Test Preferences']);
    $query = "DELETE FROM civicrm_setting WHERE group_name = 'Test Preferences';";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * using getItem($group).
   */
  public function testGetItemGroup_Override() {
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME]['imageUploadDir'] = '/test/override';
    $values = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME);
    $this->assertEquals('/test/override', $values['imageUploadDir']);
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * when using retrieveDirectoryAndURLPreferences().
   */
  public function testRetrieveDirectoryAndURLPreferences_Override() {
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME]['imageUploadDir'] = '/test/override';

    $params = array();
    CRM_Core_BAO_Setting::retrieveDirectoryAndURLPreferences($params);
    $this->assertEquals('/test/override', $params['imageUploadDir']);
  }

  /**
   * This test checks that CRM_Core_BAO_Setting::updateSettingsFromMetaData();
   * 1) Removes 'maxAttachments' from config (because 'prefetch' is not set in the metadata it should
   * be removed
   *  2) for current domain setting max_attachments is set to the value that $config->maxAttachments
   *    had (6)
   *  3) for other domain (2) max_attachments is set to the configured default (3)
   */
  public function testConvertAndFillSettings() {
    $settings = array('maxAttachments' => 6);
    CRM_Core_BAO_ConfigSetting::add($settings);
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    $this->assertEquals(6, $config->maxAttachments);
    $checkSQL = "SELECT  count(*) FROM civicrm_domain WHERE config_backend LIKE '%\"maxAttachments\";i:6%' AND id = 1
    ";
    $checkresult = CRM_Core_DAO::singleValueQuery($checkSQL);
    $this->assertEquals(1, $checkresult, "Check that maxAttachments has been saved to database not just stored in config");
    $sql = " DELETE FROM civicrm_setting WHERE name = 'max_attachments'";
    CRM_Core_DAO::executeQuery($sql);

    $currentDomain = CRM_Core_Config::domainID();
    // we are setting up an artificial situation here as we are trying to drive out
    // previous memory of this setting so we need to flush it out
    $cachekey = CRM_Core_BAO_Setting::inCache('CiviCRM Preferences', 'max_attachments', NULL, NULL, TRUE, $currentDomain);
    CRM_Core_BAO_Setting::flushCache($cachekey);
    CRM_Core_BAO_Setting::updateSettingsFromMetaData();
    //check current domain
    $value = civicrm_api('setting', 'getvalue', array(
      'version' => 3,
      'name' => 'max_attachments',
      'group' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    ));

    $this->assertEquals(6, $value);
    // check alternate domain
    $value = civicrm_api('setting', 'getvalue', array(
      'version' => 3,
      'name' => 'max_attachments',
      'group' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'domain_id' => 2,
    ));

    $this->assertEquals(3, $value);

    //some caching inconsistency here
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    $maxAttachments = empty($config->maxAttachments) ? NULL : $config->maxAttachments;
    $this->assertEmpty($maxAttachments, "Config item still Set to $maxAttachments
    . This works fine when test run alone");
  }

  /**
   * Ensure that overrides in $civicrm_setting apply when
   * when using retrieveDirectoryAndURLPreferences().
   */
  public function testConvertConfigToSettingNoPrefetch() {
    $settings = array('maxAttachments' => 6);
    CRM_Core_BAO_ConfigSetting::add($settings);
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    $this->assertEquals(6, $config->maxAttachments);

    CRM_Core_BAO_Setting::convertConfigToSetting('max_attachments');
    $value = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');
    $this->assertEquals(6, $value);

    $this->callAPISuccess('system', 'flush', array());
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    $maxAttachments = empty($config->maxAttachments) ? NULL : $config->maxAttachments;
    $this->assertEmpty($maxAttachments);
  }

  /* @codingStandardsIgnoreStart
   * Check that setting is converted without config value being removed
   *
    public function testConvertConfigToSettingPrefetch() {
    $settings = array('debug' => 1);
    CRM_Core_BAO_ConfigSetting::add($settings);
    $config = CRM_Core_Config::singleton(true, true);
    $this->assertEquals(1, $config->debug);
    CRM_Core_BAO_Setting::convertConfigToSetting('debug_is_enabled');
    $value = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::DEBUG_PREFERENCES_NAME, 'debug_is_enabled');
    $this->assertEquals(1, $value);
    civicrm_api('system', 'flush', array('version' => 3));
    $config = CRM_Core_Config::singleton(true, true);
    $this->assertEmpty($config->debug);
  }
  @codingStandardsIgnoreEnd */

  /**
   * Ensure that on_change callbacks fire.
   *
   * Note: api_v3_SettingTest::testOnChange and CRM_Core_BAO_SettingTest::testOnChange
   * are very similar, but they exercise different codepaths. The first uses the API
   * and setItems [plural]; the second uses setItem [singular].
   */
  public function testOnChange() {
    global $_testOnChange_hookCalls;
    $this->setMockSettingsMetaData(array(
      'onChangeExample' => array(
        'group_name' => 'CiviCRM Preferences',
        'group' => 'core',
        'name' => 'onChangeExample',
        'type' => 'Array',
        'quick_form_type' => 'Element',
        'html_type' => 'advmultiselect',
        'default' => array('CiviEvent', 'CiviContribute'),
        'add' => '4.4',
        'title' => 'List of Components',
        'is_domain' => '1',
        'is_contact' => 0,
        'description' => NULL,
        'help_text' => NULL,
        'on_change' => array(// list of callbacks
          array(__CLASS__, '_testOnChange_onChangeExample'),
        ),
      ),
    ));

    // set initial value
    $_testOnChange_hookCalls = array('count' => 0);
    CRM_Core_BAO_Setting::setItem(
      array('First', 'Value'),
      'CiviCRM Preferences',
      'onChangeExample'
    );
    $this->assertEquals(1, $_testOnChange_hookCalls['count']);
    $this->assertEquals(array('First', 'Value'), $_testOnChange_hookCalls['newValue']);
    $this->assertEquals('List of Components', $_testOnChange_hookCalls['metadata']['title']);

    // change value
    $_testOnChange_hookCalls = array('count' => 0);
    CRM_Core_BAO_Setting::setItem(
      array('Second', 'Value'),
      'CiviCRM Preferences',
      'onChangeExample'
    );
    $this->assertEquals(1, $_testOnChange_hookCalls['count']);
    $this->assertEquals(array('First', 'Value'), $_testOnChange_hookCalls['oldValue']);
    $this->assertEquals(array('Second', 'Value'), $_testOnChange_hookCalls['newValue']);
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

}
