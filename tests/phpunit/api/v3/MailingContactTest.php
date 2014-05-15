<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MailingContactTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_entity = 'mailing';
  
  function setUp() {
    parent::setUp();
    $params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );
    $this->_contact = $this->callAPISuccess("contact", "create", $params);
  }

  function tearDown() {
    $this->callAPISuccess("contact", "delete", array('id' => $this->_contact['id']));
    parent::tearDown();
  }

  /*
   * Test that the api responds correctly to null params
   * Note to copy & pasters - tests like this that test the wrapper belong in the SyntaxConformance class
   * (which already has a 'not array test)
   * I have left this here in case 'null' isn't covered in that class
   * but don't copy it only any other classes
   */
    public function testMailingNullParams() {
        $result = $this->callAPIFailure('MailingContact', 'get', null);
    }

    public function testMailingContactGetFields() {
      $result = $this->callAPISuccess('MailingContact', 'getfields', array(
         'action' => 'get',
        )
      );
      $this->assertEquals('Delivered', $result['values']['type']['api.default']);
    }

  /**
   * Test that the api will return the proper error when you do not
   * supply the contact_id
   * Note to copy & pasters - test is of marginal if any value & testing of wrapper level functionaliy
   * belongs in the SyntaxConformance class
   */

  public function testMailingNoContactID() {
    $params = array(
      'something' => 'This is not a real field',
    );
    $result = $this->callAPIFailure('MailingContact', 'get', $params);
  }

  /**
   * Test that invalid contact_id return with proper error messages
   * Note to copy & pasters - test is of marginal if any value & testing of wrapper level functionaliy
   * belongs in the SyntaxConformance class
   */
    public function testMailingContactInvalidContactID() {
      $params = array('contact_id' => 'This is not a number',);
      $result = $this->callAPIFailure('MailingContact', 'get', $params);
   }

   /**
    * Test that invalid types are returned with appropriate errors
    */
    public function testMailingContactInvalidType() {
      $params = array(
        'contact_id' => 23,
        'type' => 'invalid',
      );
      $result = $this->callAPIFailure('MailingContact', 'get', $params);
    }

    /**
    * Test that the API returns properly when there are no mailings
    * for a the given contact
    */
    public function testMailingContactNoMailings() {
        $params = array(
            'contact_id' => $this->_contact['id'],
        );
        $result = $this->callAPISuccess('MailingContact', 'get', $params);
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
        );

        $result = $this->callAPISuccess('MailingContact', 'get', $params);
        $count = $this->callAPISuccess('MailingContact', 'getcount', $params);
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
        );

        $result = $this->callAPISuccess('MailingContact', 'get', $params);
        $this->assertEquals($result['count'], 1, "In line " . __LINE__);
        $this->assertFalse(empty($result['values']), "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['mailing_id'], 2, "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['subject'], "Some Subject", "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['creator_id'], 1, "In line " . __LINE__);
        $this->assertEquals($result['values'][2]['creator_name'], "xyz1, abc1", "In line " . __LINE__);
    }
}
