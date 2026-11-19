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
 * @group headless
 */
class CRM_Utils_PagerTest extends CiviUnitTestCase {

  /**
   * Test basic pager on first page of multi-page dataset.
   */
  public function testPagerBasicFirstPage() {
    $params = [
      'total' => 100,
      'rowCount' => 25,
      'pageID' => 1,
    ];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(100, $pager->numItems());
    $this->assertEquals(4, $pager->numPages());
    $this->assertEquals(1, $pager->getCurrentPageID());
    $this->assertFalse($pager->getPreviousPageID());
    $this->assertEquals(2, $pager->getNextPageID());
    $this->assertTrue($pager->isFirstPage());
    $this->assertFalse($pager->isLastPage());
    $this->assertEquals([0, 25], $pager->getOffsetAndRowCount());

    $response = $pager->_response;
    $this->assertEquals(1, $response['currentPage']);
    $this->assertEquals(4, $response['numPages']);
    $this->assertStringContainsString('1 - 25 of 100', $response['status']);
    $this->assertEquals('', $response['first']);
    $this->assertEquals('', $response['back']);
    $this->assertStringContainsString('href="2"', $response['next']);
    $this->assertStringContainsString('href="4"', $response['last']);
    $this->assertStringContainsString('name="crmPID"', $response['titleTop']);
    $this->assertStringContainsString('value="1"', $response['titleTop']);
    $this->assertStringContainsString('name="crmPID_B"', $response['titleBottom']);
    $this->assertStringContainsString('value="1"', $response['titleBottom']);
  }

  /**
   * Test pager on middle page of multi-page dataset.
   */
  public function testPagerMiddlePage() {
    $params = [
      'total' => 100,
      'rowCount' => 25,
      'pageID' => 2,
    ];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(100, $pager->numItems());
    $this->assertEquals(4, $pager->numPages());
    $this->assertEquals(2, $pager->getCurrentPageID());
    $this->assertEquals(1, $pager->getPreviousPageID());
    $this->assertEquals(3, $pager->getNextPageID());
    $this->assertFalse($pager->isFirstPage());
    $this->assertFalse($pager->isLastPage());
    $this->assertEquals([25, 25], $pager->getOffsetAndRowCount());

    $response = $pager->_response;
    $this->assertEquals(2, $response['currentPage']);
    $this->assertEquals(4, $response['numPages']);
    $this->assertStringContainsString('26 - 50 of 100', $response['status']);
    $this->assertStringContainsString('href="1"', $response['first']);
    $this->assertStringContainsString('href="1"', $response['back']);
    $this->assertStringContainsString('href="3"', $response['next']);
    $this->assertStringContainsString('href="4"', $response['last']);
  }

  /**
   * Test pager on last page of multi-page dataset.
   */
  public function testPagerLastPage() {
    $params = [
      'total' => 100,
      'rowCount' => 25,
      'pageID' => 4,
    ];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(100, $pager->numItems());
    $this->assertEquals(4, $pager->numPages());
    $this->assertEquals(4, $pager->getCurrentPageID());
    $this->assertEquals(3, $pager->getPreviousPageID());
    $this->assertFalse($pager->getNextPageID());
    $this->assertFalse($pager->isFirstPage());
    $this->assertTrue($pager->isLastPage());
    $this->assertEquals([75, 25], $pager->getOffsetAndRowCount());

    $response = $pager->_response;
    $this->assertEquals(4, $response['currentPage']);
    $this->assertEquals(4, $response['numPages']);
    $this->assertStringContainsString('76 - 100 of 100', $response['status']);
    $this->assertStringContainsString('href="1"', $response['first']);
    $this->assertStringContainsString('href="3"', $response['back']);
    $this->assertEquals('', $response['next']);
    $this->assertEquals('', $response['last']);
  }

  /**
   * Test pager when total items is zero.
   */
  public function testPagerZeroTotal() {
    $params = [
      'total' => 0,
      'rowCount' => 25,
    ];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(0, $pager->numItems());
    $this->assertEquals(0, $pager->numPages());
    $this->assertEquals([0, 25], $pager->getOffsetAndRowCount());

    $response = $pager->_response;
    $this->assertEquals('Contacts ', $response['status']);
    $this->assertEquals('', $response['first']);
    $this->assertEquals('', $response['back']);
    $this->assertEquals('', $response['next']);
    $this->assertEquals('', $response['last']);
  }

  /**
   * Test pager when dataset fits in a single page.
   */
  public function testPagerSinglePage() {
    $params = [
      'total' => 10,
      'rowCount' => 25,
    ];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(10, $pager->numItems());
    $this->assertEquals(1, $pager->numPages());
    $this->assertEquals(1, $pager->getCurrentPageID());
    $this->assertFalse($pager->getPreviousPageID());
    $this->assertFalse($pager->getNextPageID());
    $this->assertTrue($pager->isFirstPage());
    $this->assertTrue($pager->isLastPage());
    $this->assertEquals([0, 25], $pager->getOffsetAndRowCount());

    $response = $pager->_response;
    $this->assertStringContainsString('1 - 10 of 10', $response['status']);
    $this->assertEquals('', $response['first']);
    $this->assertEquals('', $response['back']);
    $this->assertEquals('', $response['next']);
    $this->assertEquals('', $response['last']);
  }

  /**
   * Test getPageID and getPageRowCount with GET / POST globals.
   */
  public function testGetAndPostOverrides() {
    $_GET['crmPID'] = 3;
    $_GET['crmRowCount'] = 50;

    $params = ['total' => 200, 'pageID' => 1];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(3, $pager->getCurrentPageID());
    $this->assertEquals(50, $pager->_perPage);
    $this->assertEquals([100, 50], $pager->getOffsetAndRowCount());

    unset($_GET['crmPID'], $_GET['crmRowCount']);

    $_POST['crmPID_B'] = 2;
    $_POST['buttonBottom'] = 'Submit';
    $_POST['crmRowCount'] = 10;

    $params = ['total' => 200, 'pageID' => 1, 'buttonBottom' => 'Submit'];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(2, $pager->getCurrentPageID());
    $this->assertEquals(10, $pager->_perPage);
    $this->assertEquals([10, 10], $pager->getOffsetAndRowCount());

    unset($_POST['crmPID_B'], $_POST['buttonBottom'], $_POST['crmRowCount']);
  }

  /**
   * Test initializing pager when $params does not contain 'pageID' key.
   */
  public function testPagerWithoutPageIDParamInConstructor() {
    $_GET['crmPID'] = 2;
    $params = ['total' => 104, 'rowCount' => 50];
    $pager = new CRM_Utils_Pager($params);

    $this->assertEquals(2, $pager->getCurrentPageID());
    $this->assertEquals([50, 50], $pager->getOffsetAndRowCount());
    $this->assertStringContainsString('51 - 100 of 104', $pager->_response['status']);

    unset($_GET['crmPID']);

    $pager = new CRM_Utils_Pager($params);
    $this->assertEquals(1, $pager->getCurrentPageID());
    $this->assertEquals([0, 50], $pager->getOffsetAndRowCount());
  }

}
