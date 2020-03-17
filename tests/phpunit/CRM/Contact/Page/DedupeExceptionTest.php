<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
    $params        = [
      'total' => $totalitems,
      'rowCount' => CRM_Utils_Pager::ROWCOUNT,
      'status' => ts('Dedupe Exceptions %%StatusMessage%%'),
      'buttonBottom' => 'PagerBottomButton',
      'buttonTop' => 'PagerTopButton',
      'pageID' => $page->get(CRM_Utils_Pager::PAGE_ID),
    ];
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
