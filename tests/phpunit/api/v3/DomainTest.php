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
 * Test class for Domain API - civicrm_domain_*
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Domain
 */
class api_v3_DomainTest extends CiviUnitTestCase {

  /* This test case doesn't require DB reset - apart from
       where cleanDB() is called. */



  public $DBResetRequired = FALSE;

  protected $_apiversion = 3;
  protected $params;
  public $_eNoticeCompliant = TRUE;

  /**
   *  Constructor
   *
   *  Initialize configuration
   */ function __construct() {
    parent::__construct();
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();

    // taken from form code - couldn't find good method to use
    $params['entity_id'] = 1;
    $params['entity_table'] = CRM_Core_BAO_Domain::getTableName();
    $domain = 1;
    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
    $location = array();
    $domContact = civicrm_api('contact', 'create', array(
      'version' => $this->_apiversion,
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
      )
      )
    );

    civicrm_api('domain','create',array(
      'id' => 1,
      'contact_id' => $domContact['id'],
      'version' => $this->_apiversion
      )
    );
    $this->_apiversion = 3;
    $this->params = array(
      'name' => 'A-team domain',
      'description' => 'domain of chaos',
      'version' => $this->_apiversion,
      'domain_version' => '4.2',
      'contact_id' => $domContact['id'],
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {

  }

  ///////////////// civicrm_domain_get methods

  /**
   * Test civicrm_domain_get. Takes no params.
   * Testing mainly for format.
   */
  public function testGet() {


    $params = array('version' => 3, 'sequential' => 1,);
    $result = civicrm_api('domain', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);

    $this->assertType('array', $result, 'In line' . __LINE__);

    $domain = $result['values'][0];
    $this->assertEquals("info@EXAMPLE.ORG", $domain['from_email'], 'In line ' . __LINE__);
    $this->assertEquals("FIXME", $domain['from_name'], 'In line' . __LINE__);
    // checking other important parts of domain information
    // test will fail if backward incompatible changes happen
    $this->assertArrayHasKey('id', $domain, 'In line' . __LINE__);
    $this->assertArrayHasKey('name', $domain, 'In line' . __LINE__);
    $this->assertArrayHasKey('domain_email', $domain, 'In line' . __LINE__);
    $this->assertArrayHasKey('domain_phone', $domain, 'In line' . __LINE__);
    $this->assertArrayHasKey('domain_address', $domain, 'In line' . __LINE__);
  }

  public function testGetCurrentDomain() {
    $params = array('version' => 3, 'current_domain' => 1);
    $result = civicrm_api('domain', 'get', $params);

    $this->assertType('array', $result, 'In line' . __LINE__);

    foreach ($result['values'] as $key => $domain) {
      if ($key == 'version') {
        continue;
      }

      $this->assertEquals("info@EXAMPLE.ORG", $domain['from_email'], 'In line ' . __LINE__);
      $this->assertEquals("FIXME", $domain['from_name'], 'In line' . __LINE__);

      // checking other important parts of domain information
      // test will fail if backward incompatible changes happen
      $this->assertArrayHasKey('id', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('name', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_email', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_phone', $domain, 'In line' . __LINE__);
      $this->assertArrayHasKey('domain_address', $domain, 'In line' . __LINE__);
      $this->assertEquals("my@email.com",$domain['domain_email']);
      $this->assertEquals("456-456",$domain['domain_phone']['phone']);
      $this->assertEquals("45 Penny Lane",$domain['domain_address']['street_address']);
    }
  }

  ///////////////// civicrm_domain_create methods
  /*
    * This test checks for a memory leak observed when doing 2 gets on current domain
    */



  public function testGetCurrentDomainTwice() {
    $domain = civicrm_api('domain', 'getvalue', array(
        'version' => 3,
        'current_domain' => 1,
        'return' => 'name',
      ));
    $this->assertEquals('Default Domain Name', $domain, print_r($domain, TRUE) . 'in line ' . __LINE__);
    $domain = civicrm_api('domain', 'getvalue', array(
        'version' => 3,
        'current_domain' => 1,
        'return' => 'name',
      ));
    $this->assertEquals('Default Domain Name', $domain, print_r($domain, TRUE) . 'in line ' . __LINE__);
  }

  /**
   * Test civicrm_domain_create.
   */
  public function testCreate() {
    $result = civicrm_api('domain', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['count'], 1);
    $this->assertNotNull($result['id']);
    $this->assertEquals($result['values'][$result['id']]['name'], $this->params['name']);
    $this->assertEquals($result['values'][$result['id']]['version'], $this->params['domain_version']);
  }

  /**
   * Test civicrm_domain_create with empty params.
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('domain', 'create', $params);
    $this->assertAPIFailure($result,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_domain_create with wrong parameter type.
   */
  public function testCreateWithWrongParams() {
    $params = 1;
    $result = civicrm_api('domain', 'create', $params);
    $this->assertAPIFailure($result,
      "In line " . __LINE__
    );
  }
}

