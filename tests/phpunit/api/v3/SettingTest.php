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

  protected $currentDomain;
  protected $domainID2;
  protected $domainID3;

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $params = [
      'name' => __CLASS__ . 'Second Domain',
      'domain_version' => CRM_Utils_System::version(),
    ];
    $result = $this->callAPISuccess('domain', 'get', $params);
    if (empty($result['id'])) {
      $result = $this->callAPISuccess('domain', 'create', $params);
    }
    $this->domainID2 = $result['id'];
    $params['name'] = __CLASS__ . 'Third domain';
    $result = $this->callAPISuccess('domain', 'get', $params);
    if (empty($result['id'])) {
      $result = $this->callAPISuccess('domain', 'create', $params);
    }
    $this->domainID3 = $result['id'];
    $this->currentDomain = CRM_Core_Config::domainID();
    $this->hookClass = CRM_Utils_Hook::singleton();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    parent::tearDown();
    try {
      CRM_Core_DAO::executeQuery('
        DELETE d, s, n, dc, m
        FROM civicrm_domain d
          INNER JOIN civicrm_setting s ON s.domain_id = d.id
          INNER JOIN civicrm_navigation n ON n.domain_id = d.id
          INNER JOIN civicrm_menu m ON m.domain_id = d.id
          INNER JOIN civicrm_dashboard dc ON dc.domain_id = d.id
        WHERE d.name LIKE "' . __CLASS__ . '%"
     ');
    }
    catch (CRM_Core_Exception $e) {
      $result = $this->getTablesWithData();
      throw new CRM_Core_Exception($e->getMessage() . 'look to one of these tables to have the data ...' . print_r($result, TRUE));
    }
  }

  /**
   * Set additional settings into metadata (implements hook)
   *
   * @param array $metaDataFolders
   */
  public function setExtensionMetadata(array &$metaDataFolders): void {
    global $civicrm_root;
    $metaDataFolders[] = $civicrm_root . '/tests/phpunit/api/v3/settings';
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetFields(int $version): void {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('setting', 'getfields', []);
    $this->assertArrayHasKey('customCSSURL', $result['values']);

    $result = $this->callAPISuccess('setting', 'getfields', []);
    $this->assertArrayHasKey('customCSSURL', $result['values']);
    $this->callAPISuccess('system', 'flush', []);
  }

  /**
   * Let's check it's loading from cache by meddling with the cache
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetFieldsCaching(int $version): void {
    $this->_apiversion = $version;
    $settingsMetadata = [];
    Civi::cache('settings')->set('settingsMetadata_' . CRM_Core_Config::domainID() . '_', $settingsMetadata);
    $result = $this->callAPISuccess('setting', 'getfields', []);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->quickCleanup(['civicrm_cache']);
    Civi::cache('settings')->flush();
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetFieldsFilters(int $version): void {
    $this->_apiversion = $version;
    $params = ['name' => 'advanced_search_options'];
    $result = $this->callAPISuccess('setting', 'getfields', $params);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->assertArrayHasKey('advanced_search_options', $result['values']);
  }

  /**
   * Test that getfields will filter on group.
   */
  public function testGetFieldsGroupFilters(): void {
    $this->_apiversion = 3;
    $params = ['filters' => ['group' => 'multisite']];
    $result = $this->callAPISuccess('setting', 'getfields', $params);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->assertArrayHasKey('domain_group_id', $result['values']);
  }

  /**
   * Ensure that on_change callbacks fire.
   *
   * Note: api_v3_SettingTest::testOnChange and CRM_Core_BAO_SettingTest::testOnChange
   * are very similar, but they exercise different code paths. The first uses the API
   * and setItems [plural]; the second uses setItem [singular].
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testOnChange(int $version): void {
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
  public static function _testOnChange_onChangeExample($oldValue, $newValue, $metadata): void {
    global $_testOnChange_hookCalls;
    $_testOnChange_hookCalls['count']++;
    $_testOnChange_hookCalls['oldValue'] = $oldValue;
    $_testOnChange_hookCalls['newValue'] = $newValue;
    $_testOnChange_hookCalls['metadata'] = $metadata;
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSetting(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->domainID2,
      'uniq_email_per_site' => 1,
    ];
    $this->callAPISuccess('setting', 'create', $params);

    $params = ['uniq_email_per_site' => 1];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidSettings(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->domainID2,
      'invalid_key' => 1,
    ];
    $this->callAPIFailure('Setting', 'create', $params);
  }

  /**
   * Check invalid settings rejected -
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidURLSettings(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->domainID2,
      'userFrameworkResourceURL' => 'blah blah',
    ];
    $this->callAPIFailure('Setting', 'create', $params);
    $params = [
      'domain_id' => $this->domainID2,
      'userFrameworkResourceURL' => 'https://blah.com',
    ];
    $this->callAPISuccess('Setting', 'create', $params);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateInvalidBooleanSettings(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->domainID2,
      'track_civimail_replies' => 'blah',
    ];
    $this->callAPIFailure('Setting', 'create', $params);

    $params = ['track_civimail_replies' => '0'];
    $this->callAPISuccess('Setting', 'create', $params);
    $getResult = $this->callAPISuccess('Setting', 'get');
    $this->assertEquals(0, $getResult['values'][$this->currentDomain]['track_civimail_replies']);

    $getResult = $this->callAPISuccess('Setting', 'get');
    $this->assertEquals(0, $getResult['values'][$this->currentDomain]['track_civimail_replies']);
    $params = [
      'domain_id' => $this->domainID2,
      'track_civimail_replies' => '1',
    ];
    $this->callAPISuccess('Setting', 'create', $params);
    $getResult = $this->callAPISuccess('Setting', 'get', ['domain_id' => $this->domainID2]);
    $this->assertEquals(1, $getResult['values'][$this->domainID2]['track_civimail_replies']);

    $params = [
      'domain_id' => $this->domainID2,
      'track_civimail_replies' => 'TRUE',
    ];
    $this->callAPISuccess('Setting', 'create', $params);
    $getResult = $this->callAPISuccess('Setting', 'get', ['domain_id' => $this->domainID2]);

    $this->assertEquals(1, $getResult['values'][$this->domainID2]['track_civimail_replies'], 'check TRUE is converted to 1');
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSettingMultipleDomains(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => 'all',
      'uniq_email_per_site' => 1,
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);

    $this->assertEquals(1, $result['values'][$this->domainID2]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][$this->currentDomain]['uniq_email_per_site']);
    $this->assertArrayHasKey($this->domainID3, $result['values'], 'Domain create probably failed Debug this IF domain test is passing');
    $this->assertEquals(1, $result['values'][$this->domainID3]['uniq_email_per_site'], 'failed to set setting for domain 3.');

    $params = [
      'domain_id' => 'all',
      'return' => 'uniq_email_per_site',
    ];
    // we'll check it with a 'get'
    $result = $this->callAPISuccess('setting', 'get', $params);

    $this->assertEquals(1, $result['values'][$this->domainID2]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][$this->currentDomain]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][$this->domainID3]['uniq_email_per_site']);

    $params = [
      'domain_id' => [$this->currentDomain, $this->domainID3],
      'uniq_email_per_site' => 0,
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);

    $this->assertEquals(0, $result['values'][$this->domainID3]['uniq_email_per_site']);
    $this->assertEquals(0, $result['values'][$this->currentDomain]['uniq_email_per_site']);
    $params = [
      'domain_id' => [$this->currentDomain, $this->domainID2],
      'return' => ['uniq_email_per_site'],
    ];
    $result = $this->callAPISuccess('setting', 'get', $params);
    $this->assertEquals(1, $result['values'][$this->domainID2]['uniq_email_per_site']);
    $this->assertEquals(0, $result['values'][$this->currentDomain]['uniq_email_per_site']);

  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetSetting(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'domain_id' => $this->domainID2,
      'return' => 'uniq_email_per_site',
    ];

    $this->callAPISuccess('Setting', 'get', $params);

    $params = [
      'return' => 'uniq_email_per_site',
    ];
    $result = $this->callAPISuccess('Setting', 'get', $params);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * Check that setting defined in extension can be retrieved.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetExtensionSetting(int $version): void {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_alterSettingsFolders', [$this, 'setExtensionMetadata']);
    Civi::cache('settings')->flush();
    $fields = $this->callAPISuccess('setting', 'getfields');
    $this->assertArrayHasKey('test_key', $fields['values']);
    $this->callAPISuccess('setting', 'create', ['test_key' => 'key_set']);
    $this->assertEquals('key_set', Civi::settings()->get('test_key'));
    $result = $this->callAPISuccess('setting', 'getvalue', ['name' => 'test_key']);
    $this->assertEquals('key_set', $result);
  }

  /**
   * Setting api should set & fetch settings stored in config as well as those in settings table
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetConfigSetting(int $version): void {
    $this->_apiversion = $version;
    $settings = $this->callAPISuccess('setting', 'get', [
      'name' => 'defaultCurrency',
      'sequential' => 1,
    ]);
    $this->assertEquals('USD', $settings['values'][0]['defaultCurrency']);
  }

  /**
   * Setting api should set & fetch settings stored in config as well as those in settings table
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetSetConfigSettingMultipleDomains(int $version): void {
    $this->_apiversion = $version;
    $this->callAPISuccess('setting', 'create', [
      'defaultCurrency' => 'USD',
      'domain_id' => $this->currentDomain,
    ]);
    $this->callAPISuccess('setting', 'create', [
      'defaultCurrency' => 'CAD',
      'domain_id' => $this->domainID2,
    ]);
    $settings = $this->callAPISuccess('setting', 'get', [
      'return' => 'defaultCurrency',
      'domain_id' => 'all',
    ]);
    $this->assertEquals('USD', $settings['values'][$this->currentDomain]['defaultCurrency']);
    $this->assertEquals('CAD', $settings['values'][$this->domainID2]['defaultCurrency'],
      "second domain (id $this->domainID2 ) should be set to CAD. First dom was $this->currentDomain & was USD");

  }

  /**
   * Use getValue against a config setting.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetValueConfigSetting(int $version): void {
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
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetValue(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'name' => 'petition_contacts',
      'group' => 'Campaign Preferences',
    ];

    $result = $this->callAPISuccess('setting', 'getvalue', $params);
    $this->assertEquals('Petition Contacts', $result);
  }

  /**
   * V3 only - no api4 equivalent.
   */
  public function testGetDefaults(): void {
    $params = [
      'name' => 'address_format',
    ];
    $result = $this->callAPISuccess('Setting', 'getdefaults', $params);
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $params = ['name' => 'mailing_format'];
    $result = $this->callAPISuccess('setting', 'getdefaults', $params);
    $this->assertEquals("{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * Function tests reverting a specific parameter.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testRevert(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
    ];
    $result = $this->callAPISuccess('setting', 'create', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $revertParams = [
      'name' => 'address_format',
    ];
    $result = $this->callAPISuccess('setting', 'get');
    //make sure it's set
    $this->assertEquals('xyz', $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $this->callAPISuccess('setting', 'revert', $revertParams);
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
  public function testRevertAll(): void {
    $params = [
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
    ];
    $this->callAPISuccess('Setting', 'create', $params);
    $revertParams = [];
    $result = $this->callAPISuccess('Setting', 'get', $params);
    //make sure it's set
    $this->assertEquals('xyz', $result['values'][CRM_Core_Config::domainID()]['address_format']);

    $this->callAPISuccess('Setting', 'revert', $revertParams);
    //make sure it's reverted
    $result = $this->callAPISuccess('setting', 'get', ['group' => 'core']);
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $this->assertEquals("{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.supplemental_address_3}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
  }

  /**
   * Settings should respect their defaults
   * V3 only - no fill action in v4
   *
   * @throws \CRM_Core_Exception
   */
  public function testDefaults(): void {
    $domain = $this->callAPISuccess('Domain', 'create', [
      'name' => __CLASS__ . 'B Team Domain',
      'domain_version' => CRM_Utils_System::version(),
    ]);

    $this->callAPISuccess('Setting', 'get', ['domain_id' => 'all']);
    $params = [
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
      'domain_id' => $this->domainID2,
    ];
    $this->callAPISuccess('Setting', 'create', $params);
    $params = [
      'domain_id' => $domain['id'],
    ];
    $result = $this->callAPISuccess('setting', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals('Unconfirmed', $result['values'][$domain['id']]['tag_unconfirmed']);

    // The 'fill' operation is no longer necessary, but third parties might still use it, so let's
    // make sure it doesn't do anything weird (crashing or breaking values).
    $result = $this->callAPISuccess('Setting', 'fill', $params);
    $this->assertAPISuccess($result);
    $result = $this->callAPISuccess('Setting', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertArrayHasKey('tag_unconfirmed', $result['values'][$domain['id']]);

    // Setting has NULL default. Not returned.
    //$this->assertArrayHasKey('extensionsDir', $result['values'][$dom['id']]);

    $this->assertEquals('Unconfirmed', $result['values'][$domain['id']]['tag_unconfirmed']);
  }

  /**
   * Test to set isProductionEnvironment
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testSetCivicrmEnvironment(int $version): void {
    $this->_apiversion = $version;
    global $civicrm_setting;
    unset($civicrm_setting[CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME]['environment']);
    Civi::service('settings_manager')->useMandatory();
    $params = [
      'environment' => 'Staging',
    ];
    $this->callAPISuccess('Setting', 'create', $params);
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
