<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *  Test APIv3 civicrm_setting_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Core
 */

/**
 * Class contains api test cases for civicrm settings
 *
 */
class api_v3_SettingTest extends CiviUnitTestCase {

  protected $_apiversion = 3;
  protected $_contactID;
  protected $_params;
  protected $_currentDomain;
  protected $_domainID2;
  protected $_domainID3;
  public $_eNoticeCompliant = TRUE;
  function __construct() {
    parent::__construct();

  }

  function get_info() {
    return array(
      'name' => 'Settings Tests',
      'description' => 'Settings API',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $params = array(
      'name' => 'Default Domain Name',
      'version' => 3,
    );
    $result = civicrm_api( 'domain','get',$params);
    if(empty($result['id'])){
      $result = civicrm_api( 'domain','create',$params );
      $this->assertAPISuccess($result);
    }

    $params['name'] = 'Second Domain';
    $result = civicrm_api( 'domain','get',$params);
    if(empty($result['id'])){
      $result = civicrm_api( 'domain','create',$params );
      $this->assertAPISuccess($result);
    }
    $this->_domainID2 = $result['id'];
    $params['name'] = 'A-team domain';
    $result = civicrm_api( 'domain','get',$params);
    if(empty($result['id'])){
      $result = civicrm_api( 'domain','create',$params );
      $this->assertAPISuccess($result);
    }
    $this->_domainID3 = $result['id'];
    $this->_currentDomain = CRM_Core_Config::domainID();
  }

  function tearDown() {
    parent::tearDown();
    $this->quickCleanup(array('civicrm_domain'));
    civicrm_api('system','flush', array('version' => $this->_apiversion));
  }

  /**
   * check getfields works
   */
  function testGetFields() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('setting', 'getfields', $params);
    $description = 'Demonstrate return from getfields - see subfolder for variants';
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description,'', 'getfields');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayHasKey('customCSSURL', $result['values']);

    $description = 'Demonstrate return from getfields';
    $result = civicrm_api('setting', 'getfields', array('version' => $this->_apiversion));
    //  $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, 'GetFieldsGroup');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayHasKey('customCSSURL', $result['values']);
    civicrm_api('system','flush', array('version' => $this->_apiversion));
  }

  /**
   * let's check it's loading from cache by meddling with the cache
   */
  function testGetFieldsCaching() {
    $settingsMetadata = array();
    CRM_Core_BAO_Cache::setItem($settingsMetadata,'CiviCRM setting Specs', 'settingsMetadata__');
    CRM_Core_BAO_Cache::setItem($settingsMetadata,'CiviCRM setting Spec', 'All');
    $result = civicrm_api('setting', 'getfields', array('version' => $this->_apiversion));
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->quickCleanup(array('civicrm_cache'));
  }

  function testGetFieldsFilters() {
    $params = array('version' => $this->_apiversion);
    $filters = array('name' => 'advanced_search_options');
    $result = civicrm_api('setting', 'getfields', $params + $filters);
    $this->assertAPISuccess($result, ' in LINE ' . __LINE__);

    $this->assertArrayNotHasKey('customCSSURL', $result['values']);
    $this->assertArrayHasKey('advanced_search_options',$result['values']);
  }
  /**
   * check getfields works
   */
  function testCreateSetting() {

    $params = array('version' => $this->_apiversion,
        'domain_id' => $this->_domainID2,
        'uniq_email_per_site' => 1,
    );
    $result = civicrm_api('setting', 'create', $params);
    $description = "shows setting a variable for a given domain - if no domain is set current is assumed";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, "in line " . __LINE__);

    $params = array('version' => $this->_apiversion,
        'uniq_email_per_site' => 1,
    );
    $result = civicrm_api('setting', 'create', $params);
    $description = "shows setting a variable for a current domain";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, 'CreateSettingCurrentDomain');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }

  /**
   * check getfields works
   */
  function testCreateInvalidSettings() {

    $params = array('version' => $this->_apiversion,
        'domain_id' => $this->_domainID2,
        'invalid_key' => 1,
    );
    $result = $this->callAPIFailure('setting', 'create', $params);
   }

   /**
    * check invalid settings rejected -
    */

   function testCreateInvalidURLSettings() {

     $params = array('version' => $this->_apiversion,
         'domain_id' => $this->_domainID2,
         'userFrameworkResourceURL' => 'dfhkdhfd',
     );
     $result = $this->callAPIFailure('setting', 'create', $params);
     $params = array('version' => $this->_apiversion,
         'domain_id' => $this->_domainID2,
         'userFrameworkResourceURL' => 'http://blah.com',
     );
     $result = civicrm_api('setting', 'create', $params);
     $this->assertAPISuccess($result);

   }

   /**
    * check getfields works
    */
   function testCreateInvalidBooleanSettings() {

     $params = array('version' => $this->_apiversion,
         'domain_id' => $this->_domainID2,
         'track_civimail_replies' => 'dfhkdhfd',
     );
     $result = $this->callAPIFailure('setting', 'create', $params);

     $params = array('version' => $this->_apiversion,
         'track_civimail_replies' => '0',
     );
     $result = civicrm_api('setting', 'create', $params);
     $getResult = civicrm_api('setting','get',$params);
     $this->assertEquals(0, $getResult['values'][$this->_currentDomain]['track_civimail_replies']);

     $this->assertAPISuccess($result);
     $getResult = civicrm_api('setting','get',$params);
     $this->assertEquals(0, $getResult['values'][$this->_currentDomain]['track_civimail_replies']);
     $params = array(
       'version' => $this->_apiversion,
       'domain_id' => $this->_domainID2,
       'track_civimail_replies' => '1',
     );
     $result = civicrm_api('setting', 'create', $params);
     $this->assertAPISuccess($result);
     $getResult = civicrm_api('setting','get',$params);
     $this->assertEquals(1, $getResult['values'][$this->_domainID2]['track_civimail_replies']);

     $params = array('version' => $this->_apiversion,
         'domain_id' => $this->_domainID2,
         'track_civimail_replies' => 'TRUE',
     );
     $result = civicrm_api('setting', 'create', $params);
     $this->assertAPISuccess($result);
     $getResult = civicrm_api('setting','get',$params);

     $this->assertEquals(1, $getResult['values'][$this->_domainID2]['track_civimail_replies'], "check TRUE is converted to 1");


   }

  /**
   * check getfields works
   */
  function testCreateSettingMultipleDomains() {

    $params = array('version' => $this->_apiversion,
        'domain_id' => 'all',
        'uniq_email_per_site' => 1,
    );
    $result = civicrm_api('setting', 'create', $params);
    $description = "shows setting a variable for all domains";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__,$description, 'CreateAllDomains');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals(1, $result['values'][2]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][1]['uniq_email_per_site']);
    $this->assertArrayHasKey(3, $result['values'], 'Domain create probably failed Debug this IF domain test is passing');
    $this->assertEquals(1, $result['values'][3]['uniq_email_per_site'], 'failed to set setting for domain 3.');

    $params = array('version' => $this->_apiversion,
        'domain_id' => 'all',
        'return' => 'uniq_email_per_site'
    );
    // we'll check it with a 'get'
    $result = civicrm_api('setting', 'get', $params);
    $description = "shows getting a variable for all domains";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__,$description, 'GetAllDomains', 'Get');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals(1, $result['values'][2]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][1]['uniq_email_per_site']);
    $this->assertEquals(1, $result['values'][3]['uniq_email_per_site']);

    $params = array('version' => $this->_apiversion,
        'domain_id' => array(1,3),
        'uniq_email_per_site' => 0,
    );
    $result = civicrm_api('setting', 'create', $params);
    $description = "shows setting a variable for specified domains";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__,$description, 'CreateSpecifiedDomains');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals(0, $result['values'][3]['uniq_email_per_site']);
    $this->assertEquals(0, $result['values'][1]['uniq_email_per_site']);
    $params = array('version' => $this->_apiversion,
        'domain_id' => array(1,2),
        'return' => array('uniq_email_per_site'),
    );
    $result = civicrm_api('setting', 'get', $params);
    $description = "shows getting a variable for specified domains";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__,$description, 'GetSpecifiedDomains', 'Get');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals(1, $result['values'][2]['uniq_email_per_site']);
    $this->assertEquals(0, $result['values'][1]['uniq_email_per_site']);

  }

  function testGetSetting() {

    $params = array('version' => $this->_apiversion,
        'domain_id' => $this->_domainID2,
        'return' => 'uniq_email_per_site',
    );
    $result = civicrm_api('setting', 'get', $params);
    $description = "shows get setting a variable for a given domain - if no domain is set current is assumed";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, "in line " . __LINE__);

    $params = array(
      'version' => $this->_apiversion,
      'return' => 'uniq_email_per_site',
    );
    $result = civicrm_api('setting', 'get', $params);
    $description = "shows getting a variable for a current domain";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, 'GetSettingCurrentDomain');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }
/**
 * setting api should set & fetch settings stored in config as well as those in settings table
 */
  function testSetConfigSetting() {
    $config = CRM_Core_Config::singleton();
    $this->assertFalse($config->debug == 1);
    $params = array('version' => $this->_apiversion,
      'domain_id' => $this->_domainID2,
      'debug_enabled' => 1,
    );
    $result = civicrm_api('setting', 'create', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    CRM_Core_BAO_Domain::setDomain($this->_domainID2);
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    CRM_Core_BAO_Domain::resetDomain();
    $this->assertTrue($config->debug == 1);
    // this should NOT be stored in the settings table now - only in config
    $sql = " SELECT count(*) as c FROM civicrm_setting WHERE name LIKE '%debug%'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $this->assertEquals($dao->c, 0);
  }
  /**
   * setting api should set & fetch settings stored in config as well as those in settings table
   */
  function testGetConfigSetting() {
    $settings = civicrm_api('setting', 'get', array(
      'name' => 'defaultCurrency',
      'version' => $this->_apiversion,
      'sequential' => 1,)
    );
    $this->assertAPISuccess($settings);
    $this->assertEquals('USD', $settings['values'][0]['defaultCurrency']);
  }

  /**
   * setting api should set & fetch settings stored in config as well as those in settings table
   */
  function testGetSetConfigSettingMultipleDomains() {
    $settings = civicrm_api('setting', 'create', array(
      'defaultCurrency' => 'USD',
      'version' => $this->_apiversion,
      'domain_id' => $this->_currentDomain)
    );
    $settings = civicrm_api('setting', 'create', array(
      'defaultCurrency' => 'CAD',
      'version' => $this->_apiversion,
      'domain_id' => $this->_domainID2)
    );
    $this->assertAPISuccess($settings);
    $settings = civicrm_api('setting', 'get', array(
      'return' => 'defaultCurrency',
      'version' => $this->_apiversion,
      'domain_id' => 'all',
      )
    );
    $this->assertEquals('USD', $settings['values'][$this->_currentDomain]['defaultCurrency']);
    $this->assertEquals('CAD', $settings['values'][$this->_domainID2]['defaultCurrency'],
      "second domain (id {$this->_domainID2} ) should be set to CAD. First dom was {$this->_currentDomain} & was USD");

  }

/*
 * Use getValue against a config setting
 */
  function testGetValueConfigSetting() {
    $params = array(
      'version' => $this->_apiversion,
      'name' => 'monetaryThousandSeparator',
      'group' => 'Localization Setting',
    );
    $result = civicrm_api('setting', 'getvalue', $params);
    $this->assertEquals(',', $result);
  }

  function testGetValue() {
    $params = array(
      'version' => $this->_apiversion,
      'name' => 'petition_contacts',
      'group' => 'Campaign Preferences'
    );
    $result = civicrm_api('setting', 'getvalue', $params);
    $this->assertEquals('Petition Contacts', $result);
    $description = "Demonstrates getvalue action - intended for runtime use as better caching than get";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description);
  }

  function testGetDefaults() {

    $params = array('version' => $this->_apiversion,
      'name' => 'address_format',
    );
    $result = civicrm_api('setting', 'getdefaults', $params);
    $description = "gets defaults setting a variable for a given domain - if no domain is set current is assumed";
    $this->documentMe($params, $result, __FUNCTION__, __FILE__,$description,'GetDefaults','getdefaults');
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $params = array('version' => $this->_apiversion,
      'name' => 'mailing_format',
    );
    $result = civicrm_api('setting', 'getdefaults', $params);
    $this->assertEquals("{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
    $this->assertArrayHasKey(CRM_Core_Config::domainID(), $result['values']);
  }
  /*
   * Function tests reverting a specific parameter
   */
  function testRevert() {

    $params = array(
      'version' => $this->_apiversion,
      'address_format' => 'xyz',
      'mailing_format' => 'bcs',
    );
    $result = civicrm_api('setting', 'create', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $revertParams = array(
      'version' => $this->_apiversion,
      'name' => 'address_format'
    );
    $result = civicrm_api('setting', 'get', $params);
    //make sure it's set
    $this->assertEquals('xyz', $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $description = "Demonstrates reverting a parameter to default value";
    $result = civicrm_api('setting', 'revert', $revertParams);
    $this->documentMe($revertParams, $result, __FUNCTION__, __FILE__,$description,'','revert');
    //make sure it's reverted
    $result = civicrm_api('setting', 'get', $params);
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $params = array(
      'version' => $this->_apiversion,
      'return' => array('mailing_format'),
    );
    $result = civicrm_api('setting', 'get', $params);
    //make sure it's unchanged
    $this->assertEquals('bcs', $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
  }
  /*
   * Tests reverting ALL parameters (specific domain)
   */
  function testRevertAll() {

    $params = array(
        'version' => $this->_apiversion,
        'address_format' => 'xyz',
        'mailing_format' => 'bcs',
    );
    $result = civicrm_api('setting', 'create', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $revertParams = array(
        'version' => $this->_apiversion,
    );
    $result = civicrm_api('setting', 'get', $params);
    //make sure it's set
    $this->assertEquals('xyz', $result['values'][CRM_Core_Config::domainID()]['address_format']);

    civicrm_api('setting', 'revert', $revertParams);
    //make sure it's reverted
    $result = civicrm_api('setting', 'get', array('version' => 3, 'group' => 'core'));
    $this->assertEquals("{contact.address_name}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['address_format']);
    $this->assertEquals("{contact.addressee}\n{contact.street_address}\n{contact.supplemental_address_1}\n{contact.supplemental_address_2}\n{contact.city}{, }{contact.state_province}{ }{contact.postal_code}\n{contact.country}", $result['values'][CRM_Core_Config::domainID()]['mailing_format']);
  }

  /*
   * Tests filling missing params
  */
  function testFill() {
    $domparams = array(
      'name' => 'B Team Domain',
      'version' => 3,
    );
    $dom = civicrm_api('domain', 'create', $domparams);
    $params = array(
      'version' => $this->_apiversion,
      'domain_id' => 'all',
    );
    $result = civicrm_api('setting', 'get', $params);
    $params = array(
        'version' => $this->_apiversion,
        'address_format' => 'xyz',
        'mailing_format' => 'bcs',
        'domain_id' => $this->_domainID2,
    );
    $result = civicrm_api('setting', 'create', $params);
    $params = array(
      'version' => $this->_apiversion,
      'domain_id' => $dom['id'],
    );
    $result = civicrm_api('setting', 'get', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayNotHasKey('tag_unconfirmed', $result['values'][$dom['id']],'setting for domain 3 should not be set. Debug this IF domain test is passing');
    $result = civicrm_api('setting', 'fill', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $result = civicrm_api('setting', 'get', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertArrayHasKey('tag_unconfirmed', $result['values'][$dom['id']]);
    $this->assertArrayHasKey('extensionsDir', $result['values'][$dom['id']]);
    $this->assertEquals('Unconfirmed', $result['values'][$dom['id']]['tag_unconfirmed']);
  }
}

