<?php
// $Id$

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

/**
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_MailingContact
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MailingContactTest extends CiviUnitTestCase {
  protected $_apiversion;

  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_entity     = 'mailing';
    $this->_contact_params     = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
      'version' => $this->_apiversion,
    );
    $this->_contact = civicrm_api("contact", "create", $this->_contact_params);

    /*$this->quickCleanup(
      array(
        'civicrm_mailing',
        'civicrm_job',
        'civicrm_mailing_event_queue',
        'civicrm_mailing_event_delivered',
        'civicrm_mailing_event_bounced',
      )
    );*/
  }

  function tearDown() {
    parent::tearDown();
    civicrm_api("contact", "delete", $this->_contact_id);

  }

  /*
   * Test that the api responds correctly to null params
   */

    public function testMailingNullParams() {
        $result = civicrm_api('MailingContact', 'get', null);
        $this->assertAPIFailure($result, "In line " . __LINE__);
    }
    public function testMailingContactGetFields() {
      $result = civicrm_api('MailingContact', 'getfields', array(
        'version' => 3,
        'action' => 'get',
        )
      );
      $this->assertAPISuccess($result);
      $this->assertEquals('Delivered', $result['values']['type']['api.default']);
    }

  /*
   * Test that the api will return the proper error when you do not
   * supply the contact_id
   */

  public function testMailingNoContactID() {
    $params = array(
      'something' => 'This is not a real field',
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('MailingContact', 'get', $params);
    $this->assertAPIFailure($result, "In line " . __LINE__);
  }

  /*
   * Test that invalid contact_id return with proper error messages
   */
    public function testMailingContactInvalidContactID() {
        $params = array(
            'contact_id' => 'This is not a number',
            'version' => $this->_apiversion,
        );

        $result = civicrm_api('MailingContact', 'get', $params);
        $this->assertAPIFailure($result, "In line " . __LINE__);
   }

   /*
    * Test that invalid types are returned with appropriate errors
    */
    public function testMailingContactInvalidType() {
        $params = array(
            'contact_id' => 23,
            'type' => 'invalid',
            'version' => $this->_apiversion,
        );

        $result = civicrm_api('MailingContact', 'get', $params);
        $this->assertAPIFailure($result, "In line " . __LINE__);
    }


    /*
    * Test that the API returns properly when there are no mailings
    * for a the given contact
    */
    public function testMailingContactNoMailings() {
        $params = array(
            'contact_id' => $this->_contact['id'],
            'version' => $this->_apiversion,
        );

        $result = civicrm_api('MailingContact', 'get', $params);
        $this->assertAPISuccess($result, "In line " . __LINE__);

        $this->assertEquals($result['count'], 0, "In line " . __LINE__);
        $this->assertTrue(empty($result['values']), "In line " . __LINE__);
    }

  /*
   * Test that the API returns a mailing properly when there is only one
   */
    public function testMailingContactDelivered() {
        $op = new PHPUnit_Extensions_Database_Operation_Insert();
        //Create the User
        $op->execute($this->_dbconn,
          new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/dataset/mailing_contact.xml'
          )
        );
        //~ Create the Mailing and connections to the user
        $op->execute($this->_dbconn,
          new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/dataset/mailing_delivered.xml'
          )
        );

        $params = array(
            'contact_id' => 23,
            'type' => 'Delivered',
            'version' => $this->_apiversion,
        );

        $result = civicrm_api('MailingContact', 'get', $params);
        $count = civicrm_api('MailingContact', 'getcount', $params);
        $this->assertAPISuccess($result, "In line " . __LINE__);
        $this->assertEquals($result['count'], 1, "In line " . __LINE__);
        $this->assertEquals($count, 1, "In line " . __LINE__);
        $this->assertFalse(empty($result['values']), "In line " . __LINE__);
        $this->assertEquals($result['values'][1]['mailing_id'], 1, "In line " . __LINE__);
        $this->assertEquals($result['values'][1]['subject'], "Some Subject", "In line " . __LINE__);
        $this->assertEquals($result['values'][1]['creator_id'], 1, "In line " . __LINE__);
        $this->assertEquals($result['values'][1]['creator_name'], "xyz1, abc1", "In line " . __LINE__);
    }


    /*
     * Test that the API returns only the "Bounced" mailings when instructed to do so
     */
    function testMailingContactBounced( ) {
        $op = new PHPUnit_Extensions_Database_Operation_Insert();
        //Create the User
        $op->execute($this->_dbconn,
          new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/dataset/mailing_contact.xml'
          )
        );
        //~ Create the Mailing and connections to the user
        $op->execute($this->_dbconn,
          new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
            dirname(__FILE__) . '/dataset/mailing_bounced.xml'
          )
        );

        $params = array(
            'contact_id' => 23,
            'type' => 'Bounced',
            'version' => $this->_apiversion,
        );

        $result = civicrm_api('MailingContact', 'get', $params);
        $this->assertAPISuccess($result, "In line " . __LINE__);
        $this->assertEquals($result['count'], 1, "In line " . __LINE__);
        $this->assertFalse(empty($result['values']), "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['mailing_id'], 2, "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['subject'], "Some Subject", "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['creator_id'], 1, "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['creator_name'], "xyz1, abc1", "In line " . __LINE__);
    }



}
