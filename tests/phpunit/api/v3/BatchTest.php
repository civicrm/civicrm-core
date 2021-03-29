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
 * Test class for Batch API - civicrm_batch_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_BatchTest extends CiviUnitTestCase {

  protected $_entity = 'batch';

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Test civicrm_batch_get - success expected.
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   */
  public function testGet(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'id' => $this->batchCreate(),
    ];
    $result = $this->callAPIAndDocument('Batch', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($params['id'], $result['id']);
  }

  /**
   * Test civicrm_batch_create - success expected.
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreate(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'name' => 'New_Batch_03',
      'title' => 'New Batch 03',
      'description' => 'This is description for New Batch 03',
      'total' => '300.33',
      'item_count' => 3,
      'status_id' => 1,
    ];

    $result = $this->callAPIAndDocument('batch', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->getAndCheck($params, $result['id'], 'Batch');
  }

  /**
   * Test civicrm_batch_create with id.
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdate(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'name' => 'New_Batch_04',
      'title' => 'New Batch 04',
      'description' => 'This is description for New Batch 04',
      'total' => '400.44',
      'item_count' => 4,
      'id' => $this->batchCreate(),
    ];

    $result = $this->callAPIAndDocument('batch', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->getAndCheck($params, $result['id'], $this->_entity);
  }

  /**
   * Test civicrm_batch_delete using the old $params['batch_id'] syntax.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchDeleteOldSyntax(): void {
    $batchID = $this->batchCreate();
    $params = [
      'batch_id' => $batchID,
    ];
    $this->callAPISuccess('Batch', 'delete', $params);
  }

  /**
   * Test civicrm_batch_delete using the new $params['id'] syntax.
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   */
  public function testBatchDeleteCorrectSyntax(int $version): void {
    $this->_apiversion = $version;
    $batchID = $this->batchCreate();
    $params = [
      'id' => $batchID,
    ];
    $this->callAPIAndDocument('Batch', 'delete', $params, __FUNCTION__, __FILE__);
  }

}
