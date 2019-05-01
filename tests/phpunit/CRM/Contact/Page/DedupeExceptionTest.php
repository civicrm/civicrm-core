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
 * Test class for CRM_Contact_Page_DedupeException BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_Page_DedupeExceptionTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    parent::tearDown();
  }

  public function testGetDedupeExceptions() {
    $contact1      = $this->individualCreate();
    $contact2      = $this->individualCreate();
    $exception     = $this->callAPISuccess('Exception', 'create', [
      'contact_id1' => $contact1,
      'contact_id2' => $contact2,
    ]);
    $page          = new CRM_Contact_Page_DedupeException();
    $totalitems    = civicrm_api3('Exception', "getcount", []);
    $params        = array(
      'total' => $totalitems,
      'rowCount' => CRM_Utils_Pager::ROWCOUNT,
      'status' => ts('Dedupe Exceptions %%StatusMessage%%'),
      'buttonBottom' => 'PagerBottomButton',
      'buttonTop' => 'PagerTopButton',
      'pageID' => $page->get(CRM_Utils_Pager::PAGE_ID),
    );
    $page->_pager  = new CRM_Utils_Pager($params);
    $exceptions    = $page->getExceptions();
    $expectedArray = [
      $exception['id'] => [
        'id' => $exception['id'],
        'contact_id1.display_name' => 'Mr. Anthony Anderson II',
        'contact_id2.display_name' => 'Mr. Anthony Anderson II',
        'contact_id1' => $contact1,
        'contact_id2' => $contact2,
      ],
    ];
    $this->assertEquals($expectedArray, $exceptions);
  }

}
