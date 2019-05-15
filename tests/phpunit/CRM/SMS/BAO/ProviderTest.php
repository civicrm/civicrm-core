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
 *  Test CRM_SMS_BAO_Provider functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_SMS_BAO_ProviderTest extends CiviUnitTestCase {

  /**
   * Set Up Funtion
   */
  public function setUp() {
    parent::setUp();
    $option = $this->callAPISuccess('option_value', 'create', array('option_group_id' => 'sms_provider_name', 'name' => 'test_provider_name', 'label' => 'test_provider_name', 'value' => 1));
    $this->option_value = $option['id'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    parent::tearDown();
    $this->callAPISuccess('option_value', 'delete', array('id' => $this->option_value));
  }

  /**
   * CRM-19961 Check that when saving and updating a SMS provider with domain as NULL that it stays null
   */
  public function testCreateAndUpdateProvider() {
    $values = array(
      'domain_id' => NULL,
      'title' => 'test SMS provider',
      'username' => 'test',
      'password' => 'dummpy password',
      'name' => 1,
      'is_active' => 1,
      'api_type' => 1,
    );
    $this->callAPISuccess('SmsProvider', 'create', $values);
    $provider = $this->callAPISuccess('SmsProvider', 'getsingle', array('title' => 'test SMS provider'));
    $domain_id = CRM_Core_DAO::getFieldValue('CRM_SMS_DAO_Provider', $provider['id'], 'domain_id');
    $this->assertNull($domain_id);
    $values2 = array('title' => 'Test SMS Provider2', 'id' => $provider['id']);
    $this->callAPISuccess('SmsProvider', 'create', $values2);
    $provider = $this->callAPISuccess('SmsProvider', 'getsingle', array('id' => $provider['id']));
    $this->assertEquals('Test SMS Provider2', $provider['title']);
    $domain_id = CRM_Core_DAO::getFieldValue('CRM_SMS_DAO_Provider', $provider['id'], 'domain_id');
    $this->assertNull($domain_id);
    CRM_SMS_BAO_Provider::del($provider['id']);
  }

  /**
   * CRM-20989
   * Add unit test to ensure that filtering by domain works in get Active Providers
   */
  public function testActiveProviderCount() {
    $values = array(
      'domain_id' => NULL,
      'title' => 'test SMS provider',
      'username' => 'test',
      'password' => 'dummpy password',
      'name' => 1,
      'is_active' => 1,
      'api_type' => 1,
    );
    $provider = $this->callAPISuccess('SmsProvider', 'create', $values);
    $provider2 = $this->callAPISuccess('SmsProvider', 'create', array_merge($values, array('domain_id' => 2)));
    $result = CRM_SMS_BAO_Provider::activeProviderCount();
    $this->assertEquals(1, $result);
    $provider3 = $this->callAPISuccess('SmsProvider', 'create', array_merge($values, array('domain_id' => 1)));
    $result = CRM_SMS_BAO_Provider::activeProviderCount();
    $this->assertEquals(2, $result);
    CRM_SMS_BAO_Provider::del($provider['id']);
    CRM_SMS_BAO_Provider::del($provider2['id']);
    CRM_SMS_BAO_Provider::del($provider3['id']);
  }

  /**
   * CRM-19961 Check that when a domain is not passed when saving it defaults to current domain when create
   */
  public function testCreateWithoutDomain() {
    $values = array(
      'title' => 'test SMS provider',
      'username' => 'test',
      'password' => 'dummpy password',
      'name' => 1,
      'is_active' => 1,
      'api_type' => 1,
    );
    $this->callAPISuccess('SmsProvider', 'create', $values);
    $provider = $this->callAPISuccess('SmsProvider', 'getsingle', array('title' => 'test SMS provider'));
    $domain_id = CRM_Core_DAO::getFieldValue('CRM_SMS_DAO_Provider', $provider['id'], 'domain_id');
    $this->assertEquals(CRM_Core_Config::domainID(), $domain_id);
    CRM_SMS_BAO_Provider::del($provider['id']);
  }

}
