<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 *  Test APIv3 civicrm_setting_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 */

/**
 * Class contains api test cases for civicrm settings
 *
 * @group headless
 */
class api_v3_SettingTest extends CiviUnitTestCase {

  protected $_contactID;
  protected $_params;
  protected $_currentDomain;
  protected $_domainID2;
  protected $_domainID3;

  public function setUp() {
    parent::setUp();
    $params = [
      'name' => 'Default Domain Name',
      'domain_version' => '4.7',
    ];
    $result = $this->callAPISuccess('domain', 'get', $params);
    if (empty($result['id'])) {
      $result = $this->callAPISuccess('domain', 'create', $params);
    }

    $params['name'] = 'Second Domain';
    $result = $this->callAPISuccess('domain', 'get', $params);
    if (empty($result['id'])) {
      $result = $this->callAPISuccess('domain', 'create', $params);
    }
    $this->_domainID2 = $result['id'];
    $params['name'] = 'A-team domain';
    $result = $this->callAPISuccess('domain', 'get', $params);
    if (empty($result['id'])) {
      $result = $this->callAPISuccess('domain', 'create', $params);
    }
    $this->_domainID3 = $result['id'];
    $this->_currentDomain = CRM_Core_Config::domainID();
    $this->hookClass = CRM_Utils_Hook::singleton();
  }

  public function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    parent::tearDown();
    $this->callAPISuccess('system', 'flush', []);
    $this->quickCleanup(['civicrm_domain']);
  }

  /**
   * Set additional settings into metadata (implements hook)
   * @param array $metaDataFolders
   */
  public function setExtensionMetadata(&$metaDataFolders) {
    global $civicrm_root;
    $metaDataFolders[] = $civicrm_root . '/tests/phpunit/api/v3/settings';
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetFields($version) {
    $this->_apiversion = $version;
    $description = 'Demonstrate return from getfields - see subfolder for variants';
    $result = $this->callAPIAndDocument('setting', 'getfields', [], __FUNCTION__, __FILE__, $description);
    $this->assertArrayHasKey('customCSSURL', $result['values']);

    $description = 'Demonstrate return from getfields';
    $result = $this->callAPISuccess('setting', 'getfields', []);
    $this->assertArrayHasKey('customCSSURL', $result['values']);
    $this->callAPISuccess('system', 'flush', []);
  }

  /**
   * Let's check it's loading from cache by meddling with the cache
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetFieldsCaching($version) {
    $this->_apiversion = $version;
    $settingsMetadata = [];
    Civi::cache('settings')->set('settingsMetadata_' . \CRM_Core_Config::domainID() . '_', $settingsMetadata);
    $result = $this->callAPISuccess('setting', 'getfields', []);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->quickCleanup(['civicrm_cache']);
    Civi::cache('settings')->flush();
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetFieldsFilters($version) {
    $this->_apiversion = $version;
    $params = ['name' => 'advanced_search_options'];
    $result = $this->callAPISuccess('setting', 'getfields', $params);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->assertArrayHasKey('advanced_search_options', $result['values']);
  }

  /**
   * Test that getfields will filter on group.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetFieldsGroupFilters($version) {
    $this->_apiversion = $version;
    $params = ['filters' => ['group' => 'multisite']];
    $result = $this->callAPISuccess('setting', 'getfields', $params);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->assertArrayHasKey('domain_group_id', $result['values']);
  }

  /**
   * Ensure that on_change callbacks fire.
   *
   * Note: api_v3_SettingTest::testOnChange and CRM_Core_BAO_SettingTest::testOnChange
   * are very similar, but they exercise different codepaths. The first uses the API
   * and setItems [plural]; the second uses setItem [singular].
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testOnChange($version) {
    $this->_apiversion = $version;
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
    $this->callAPISuccess('setting', 'create', [
      'onChangeExample' => ['First', 'Value'],
    ]);
    $this->assertEquals(1, $_testOnChange_hookCalls['count']);
    $this->assertEquals(['First', 'Value'], $_testOnChange_hookCalls['newValue']);
    $this->assertEquals('List of Components', $_testOnChange_hookCalls['metadata']['title']);

    // change value
    $_testOnChange_hookCalls = ['count' => 0];
    $this->callAPISuccess('setting', 'create', [
      'onChangeExample' => ['Second', 'Value'],
    ]);
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
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSetting($version) {
    $this->_apiversion = $version;
    $description = "Shows setting a variable for a given domain - if no domain is set current is assumed.";

    $params = [
      'domain_id' => $this->_domainID2,
      'uniq_email_per_site' => 1,
    ];
    $result = $this->callAPIAndDocument('setting', 'create', $params, __FUNCTION__, __FILE__);

    $params = ['uniq_email_per_site' => 1];
    $description = "Shows setting a variable for a current domain.";
    $result = $this->callAPIAndDocument('setting', 'create', $params, __FUNCTION__, __FILE__, $description, 'CreateSettingCurrentDomain');
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidSettings($version) {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->_domainID2,
      'invalid_key' => 1,
    ];
    $result = $this->callAPIFailure('setting', 'create', $params);
  }

  /**
   * Check invalid settings rejected -
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidURLSettings($version) {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->_domainID2,
      'userFrameworkResourceURL' => 'dfhkd hfd',
    ];
    $result = $this->callAPIFailure('setting', 'create', $params);
    $params = [
      'domain_id' => $this->_domainID2,
      'userFrameworkResourceURL' => 'http://blah.com',
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidBooleanSettings($version) {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->_domainID2,
      'track_civimail_replies' => 'dfhkdhfd',
    ];
    $result = $this->callAPIFailure('setting', 'create', $params);

    $params = ['track_civimail_replies' => '0'];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $getResult = $this->callAPISuccess('setting', 'get');
    $this->assertEquals(0, $getResult['values'][$this->_currentDomain]['track_civimail_replies']);

    $getResult = $this->callAPISuccess('setting', 'get');
    $this->assertEquals(0, $getResult['values'][$this->_currentDomain]['track_civimail_replies']);
    $params = [
      'domain_id' => $this->_domainID2,
      'track_civimail_replies' => '1',
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $getResult = $this->callAPISuccess('setting', 'get', ['domain_id' => $this->_domainID2]);
    $this->assertEquals(1, $getResult['values'][$this->_domainID2]['track_civimail_replies']);

    $params = [
      'domain_id' => $this->_domainID2,
      'track_civimail_replies' => 'TRUE',
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $getResult = $this->callAPISuccess('setting', 'get', ['domain_id' => $this->_domainID2]);

    $this->assertEquals(1, $getResult['values'][$this->_domainID2]['track_civimail_replies'], "check TRUE is converted to 1");
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSettingMultipleDomains($version) {
    $this->_apiversion = $version;
    $description = "Shows setting a variable for all domains.";

    $params = [
      'domain_id' => 'all',
      'uniq_email_per_site' => 1,
    ];
    $result = $this->callAPIAndDocument('setting', 'create', $params, __FUNCTION__, __FILE__, $description, 'CreateAllDomains');

    $this->assertEquals(1, $result['values'][2]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][1]['uniq_email_per_site']);
    $this->assertArrayHasKey(3, $result['values'], 'Domain create probably failed Debug this IF domain test is passing');
    $this->assertEquals(1, $result['values'][3]['uniq_email_per_site'], 'failed to set setting for domain 3.');

    $params = [
      'domain_id' => 'all',
      'return' => 'uniq_email_per_site',
    ];
    // we'll check it with a 'get'
    $description = "Shows getting a variable for all domains.";
    $result = $this->callAPIAndDocument('setting', 'get', $params, __FUNCTION__, __FILE__, $description, 'GetAllDomains');

    $this->assertEquals(1, $result['values'][2]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][1]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][3]['uniq_email_per_site']);

    $params = [
      'domain_id' => [1, 3],
      'uniq_email_per_site' => 0,
    ];
    $description = "Shows setting a variable for specified domains.";
    $result = $this->callAPIAndDocument('setting', 'create', $params, __FUNCTION__, __FILE__, $description, 'CreateSpecifiedDomains');

    $this->assertEquals(0, $result['values'][3]['uniq_email_per_site']);
    $this->assertEquals(0, $result['values'][1]['uniq_email_per_site']);
    $params = [
      'domain_id' => [1, 2],
      'return' => ['uniq_email_per_site'],
    ];
    $description = "Shows getting a variable for specified domains.";
    $result = $this->callAPIAndDocument('setting', 'get', $params, __FUNCTION__, __FILE__, $description, 'GetSpecifiedDomains');
    $this->assertEquals(1, $result['values'][2]['uniq_email_per_site']);
    $this->assertEquals(0, $result['values'][1]['uniq_email_per_site']);

  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetSetting($version) {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->_domainID2,
      'return' => 'uniq_email_per_site',
    ];
    $description = "Shows get setting a variable for a given domain - if no domain is set current is assumed.";

    $result = $this->callAPIAndDocument('setting', 'get', $params, __FUNCTION__, __FILE__);

    $params = [
      'return' => 'uniq_email_per_site',
    ];
    $description = "Shows getting a variable for a current domain.";
    $result = $this->callAPIAndDocument('setting', 'get', $params, __FUNCTION__, __FILE__, $description, 'GetSettingCurrentDomain');
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * Check that setting defined in extension can be retrieved.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetExtensionSetting($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_alterSettingsFolders', [$this, 'setExtensionMetadata']);
    $data = NULL;
    Civi::cache('settings')->flush();
    $fields = $this->callAPISuccess('setting', 'getfields', ['filters' => ['group_name' => 'Test Settings']]);
    $this->assertArrayHasKey('test_key', $fields['values']);
    $this->callAPISuccess('setting', 'create', ['test_key' => 'keyset']);
    $this->assertEquals('keyset', Civi::settings()->get('test_key'));
    $result = $this->callAPISuccess('setting', 'getvalue', ['name' => 'test_key', 'group' => 'Test Settings']);
    $this->assertEquals('keyset', $result);
  }

  /**
   * Setting api should set & fetch settings stored in config as well as those in settings table
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetConfigSetting($version) {
    $this->_apiversion = $version;
    $settings = $this->callAPISuccess('setting', 'get', [
      'name' => 'defaultCurrency',
      'sequential' => 1,
    ]);
    $this->assertEquals('USD', $settings['values'][0]['defaultCurrency']);
  }

  /**
   * Setting api should set & fetch settings stored in config as well as those in settings table
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetSetConfigSettingMultipleDomains($version) {
    $this->_apiversion = $version;
    $settings = $this->callAPISuccess('setting', 'create', [
      'defaultCurrency' => 'USD',
      'domain_id' => $this->_currentDomain,
    ]);
    $settings = $this->callAPISuccess('setting', 'create', [
      'defaultCurrency' => 'CAD',
      'domain_id' => $this->_domainID2,
    ]);
    $settings = $this->callAPISuccess('setting', 'get', [
      'return' => 'defaultCurrency',
      'domain_id' => 'all',
    ]);
    $this->assertEquals('USD', $settings['values'][$this->_currentDomain]['defaultCurrency']);
    $this->assertEquals('CAD', $settings['values'][$this->_domainID2]['defaultCurrency'],
      "second domain (id {$this->_domainID2} ) should be set to CAD. First dom was {$this->_currentDomain} & was USD");

  }

  /**
   * Use getValue against a config setting.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetValueConfigSetting($version) {
    $this->_apiversion = $version;
    $params = [
      'name' => 'monetaryThousandSeparator',
      'group' => 'Localization Setting',
    ];
    $result = $this->callAPISuccess('setting', 'getvalue', $params);
    $this->assertEquals(',', $result);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetValue($version) {
    $this->_apiversion = $version;
    $params = [
      'name' => 'petition_contacts',
      'group' => 'Campaign Preferences',
    ];
    $description = "Demonstrates getvalue action - intended for runtime use as better caching than get.";

    $result = $this->callAPIAndDocument('setting', 'getvalue', $params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals('Petition Contacts', $result);
  }

  /**
   * V3 only - no api4 equivalent.
   */
  public function testGetDefaults() {
    $description = "Gets defaults setting a variable for a given domain - if no domain is set current is assumed.";

    $params = [
      'name' => 'address_format',
    ];
    $result = $this->callAPIAndDocument('setting', 'getdefaults', $params, __FUNCTION__, __FILE__, $description, 'GetDefaults');
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $params = ['name' => 'mailing_format'];
    $result = $this->callAPISuccess('setting', 'getdefaults', $params);
    $this->assertEquals("{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * Function tests reverting a specific parameter.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testRevert($version) {
    $this->_apiversion = $version;
    $params = [
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $revertParams = [
      'name' => 'address_format',
    ];
    $result = $this->callAPISuccess('setting', 'get');
    //make sure it's set
    $this->assertEquals('xyz', $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $description = "Demonstrates reverting a parameter to default value.";
    $result = $this->callAPIAndDocument('setting', 'revert', $revertParams, __FUNCTION__, __FILE__, $description, '');
    //make sure it's reverted
    $result = $this->callAPISuccess('setting', 'get');
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $params = [
      'return' => ['mailing_format'],
    ];
    $result = $this->callAPISuccess('setting', 'get', $params);
    //make sure it's unchanged
    $this->assertEquals('bcs', $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
  }

  /**
   * Tests reverting ALL parameters (specific domain)
   * Api3 only.
   */
  public function testRevertAll() {
    $params = [
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $revertParams = [];
    $result = $this->callAPISuccess('setting', 'get', $params);
    //make sure it's set
    $this->assertEquals('xyz', $result['values'][CRM_Core_Config::domainID()]['address_format']);

    $this->callAPISuccess('setting', 'revert', $revertParams);
    //make sure it's reverted
    $result = $this->callAPISuccess('setting', 'get', ['group' => 'core']);
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $this->assertEquals("{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
  }

  /**
   * Settings should respect their defaults
   * V3 only - no fill action in v4
   */
  public function testDefaults() {
    $domparams = [
      'name' => 'B Team Domain',
      'domain_version' => '4.7',
    ];
    $dom = $this->callAPISuccess('domain', 'create', $domparams);
    $params = [
      'domain_id' => 'all',
    ];
    $result = $this->callAPISuccess('setting', 'get', $params);
    $params = [
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
      'domain_id' => $this->_domainID2,
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $params = [
      'domain_id' => $dom['id'],
    ];
    $result = $this->callAPISuccess('setting', 'get', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals('Unconfirmed', $result['values'][$dom['id']]['tag_unconfirmed']);

    // The 'fill' operation is no longer necessary, but third parties might still use it, so let's
    // make sure it doesn't do anything weird (crashing or breaking values).
    $result = $this->callAPISuccess('setting', 'fill', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $result = $this->callAPISuccess('setting', 'get', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayHasKey('tag_unconfirmed', $result['values'][$dom['id']]);

    // Setting has NULL default. Not returned.
    //$this->assertArrayHasKey('extensionsDir', $result['values'][$dom['id']]);

    $this->assertEquals('Unconfirmed', $result['values'][$dom['id']]['tag_unconfirmed']);
  }

  /**
   * Test to set isProductionEnvironment
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSetCivicrmEnvironment($version) {
    $this->_apiversion = $version;
    global $civicrm_setting;
    unset($civicrm_setting[CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME]['environment']);
    Civi::service('settings_manager')->useMandatory();
    $params = [
      'environment' => 'Staging',
    ];
    $result = $this->callAPISuccess('Setting', 'create', $params);
    $params = [
      'name' => 'environment',
      'group' => 'Developer Preferences',
    ];
    $result = $this->callAPISuccess('Setting', 'getvalue', $params);
    $this->assertEquals('Staging', $result);

    $civicrm_setting[CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME]['environment'] = 'Production';
    Civi::service('settings_manager')->useMandatory();
    $result = $this->callAPISuccess('Setting', 'getvalue', $params);
    $this->assertEquals('Production', $result);
  }

}
