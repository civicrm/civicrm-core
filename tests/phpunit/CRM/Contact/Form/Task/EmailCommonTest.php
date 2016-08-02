<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |                                    |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  * Test class for CRM_Contact_Form_Task_EmailCommon.
  * @group headless
  */
class CRM_Contact_Form_Task_EmailCommonTest extends CiviUnitTestCase {

  protected function setUp() {
    parent::setUp();
    $this->_contactIds = array(
      $this->individualCreate(array('first_name' => 'Antonia', 'last_name' => 'D`souza')),
      $this->individualCreate(array('first_name' => 'Anthony', 'last_name' => 'Collins')),
    );
    $this->_optionValue = $this->callApiSuccess('optionValue', 'create', array(
      'label' => '"Seamus Lee" <seamus@example.com>',
      'option_group_id' => 'from_email_address',
    ));
  }

  /**
   * Test generating domain emails
   */
  public function testDomainEmailGeneation() {
    $emails = CRM_Contact_Form_Task_EmailCommon::domainEmails();
    $this->assertNotEmpty($emails);
    $optionValue = $this->callAPISuccess('OptionValue', 'Get', array(
      'id' => $this->_optionValue['id'],
    ));
    $this->assertTrue(array_key_exists('"Seamus Lee" <seamus@example.com>', $emails));
    $this->assertEquals('"Seamus Lee" <seamus@example.com>', $optionValue['values'][$this->_optionValue['id']]['label']);
  }

}
