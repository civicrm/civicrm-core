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
 * Test class for Domain API - civicrm_domain_*
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Domain
 * @group headless
 */
class api_v3_DomainTest extends CiviUnitTestCase {

  /**
   * This test case doesn't require DB reset - apart from
   * where cleanDB() is called.
   * @var bool
   */
  public $DBResetRequired = FALSE;

  protected $_apiversion = 3;
  protected $params;

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    // taken from form code - couldn't find good method to use
    $params['entity_id'] = 1;
    $params['entity_table'] = CRM_Core_BAO_Domain::getTableName();
    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
    $domContact = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Organization',
      'organization_name' => 'new org',
      'api.phone.create' => array(
        'location_type_id' => $defaultLocationType->id,
        'phone_type_id' => 1,
        'phone' => '456-456',
      ),
      'api.address.create' => array(
        'location_type_id' => $defaultLocationType->id,
        'street_address' => '45 Penny Lane',
      ),
      'api.email.create' => array(
        'location_type_id' => $defaultLocationType->id,
        'email' => 'my@email.com',
      ),
    ));

    $this->callAPISuccess('domain', 'create', array(
      'id' => 1,
      'contact_id' => $domContact['id'],
    ));
    $this->params = array(
      'name' => 'A-team domain',
      'description' => 'domain of chaos',
      'domain_version' => '4.2',
      'contact_id' => $domContact['id'],
    );
  }

  /**
   * Test civicrm_domain_get.
   *
   * Takes no params.
   * Testing mainly for format.
   */
  public function testGet() {

    $params = array('sequential' => 1);
    $result = $this->callAPIAndDocument('domain', 'get', $params, __FUNCTION__, __FILE__);

    $this->assertType('array', $result);

    $domain = $result['values'][0];
    $this->assertEquals("info@EXAMPLE.ORG", $domain['from_email']);
    $this->assertEquals("FIXME", $domain['from_name']);
    // checking other important parts of domain information
    // test will fail if backward incompatible changes happen
    $this->assertArrayHasKey('id', $domain);
    $this->assertArrayHasKey('name', $domain);
    $this->assertArrayHasKey('domain_email', $domain);
    $this->assertEquals(array(
      'phone_type' => 'Phone',
      'phone' => '456-456',
    ), $domain['domain_phone']);
    $this->assertArrayHasKey('domain_address', $domain);
  }

  /**
   * Test get function with current domain.
   */
  public function testGetCurrentDomain() {
    $params = array('current_domain' => 1);
    $result = $this->callAPISuccess('domain', 'get', $params);

    $this->assertType('array', $result);

    foreach ($result['values'] as $key => $domain) {
      if ($key == 'version') {
        continue;
      }

      $this->assertEquals("info@EXAMPLE.ORG", $domain['from_email']);
      $this->assertEquals("FIXME", $domain['from_name']);

      // checking other important parts of domain information
      // test will fail if backward incompatible changes happen
      $this->assertArrayHasKey('id', $domain);
      $this->assertArrayHasKey('name', $domain);
      $this->assertArrayHasKey('domain_email', $domain);
      $this->assertArrayHasKey('domain_phone', $domain);
      $this->assertArrayHasKey('domain_address', $domain);
      $this->assertEquals("my@email.com", $domain['domain_email']);
      $this->assertEquals("456-456", $domain['domain_phone']['phone']);
      $this->assertEquals("45 Penny Lane", $domain['domain_address']['street_address']);
    }
  }

  /**
   * This test checks for a memory leak.
   *
   * The leak was observed when doing 2 gets on current domain.
   */
  public function testGetCurrentDomainTwice() {
    $domain = $this->callAPISuccess('domain', 'getvalue', array(
      'current_domain' => 1,
      'return' => 'name',
    ));
    $this->assertEquals('Default Domain Name', $domain, print_r($domain, TRUE));
    $domain = $this->callAPISuccess('domain', 'getvalue', array(
      'current_domain' => 1,
      'return' => 'name',
    ));
    $this->assertEquals('Default Domain Name', $domain, print_r($domain, TRUE));
  }

  /**
   * Test civicrm_domain_create.
   */
  public function testCreate() {
    $result = $this->callAPIAndDocument('domain', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['count'], 1);
    $this->assertNotNull($result['id']);
    $this->assertEquals($result['values'][$result['id']]['name'], $this->params['name']);
    $this->assertEquals($result['values'][$result['id']]['domain_version'], $this->params['domain_version']);
  }

  /**
   * Test if Domain.create does not touch the version of the domain.
   *
   * See CRM-17430.
   */
  public function testUpdateDomainName() {
    // First create a domain.
    $domain_result = $this->callAPISuccess('domain', 'create', $this->params);
    $domain_before = $this->callAPISuccess('Domain', 'getsingle', array('id' => $domain_result['id']));

    // Change domain name.
    $this->callAPISuccess('Domain', 'create', array(
      'id' => $domain_result['id'],
      'name' => 'B-Team domain',
    ));

    // Get domain again.
    $domain_after = $this->callAPISuccess('Domain', 'getsingle', array('id' => $domain_result['id']));

    // Version should still be the same.
    $this->assertEquals($domain_before['version'], $domain_after['version']);
  }

  /**
   * Test whether Domain.create returns a correct value for domain_version.
   *
   * See CRM-17430.
   */
  public function testCreateDomainResult() {
    // First create a domain.
    $domain_result = $this->callAPISuccess('Domain', 'create', $this->params);
    $result_value = CRM_Utils_Array::first($domain_result['values']);

    // Check for domain_version in create result.
    $this->assertEquals($this->params['domain_version'], $result_value['domain_version']);
  }

  /**
   * Test civicrm_domain_create with empty params.
   *
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $this->callAPIFailure('domain', 'create', array());
  }

}
