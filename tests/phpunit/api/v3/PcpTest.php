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
 * Test APIv3 civicrm_PCP_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Campaign
 */

/**
 * All API should contain at minimum a success test for each
 * function - in this case - create, get & delete
 * In addition any extra functionality should be tested & documented
 *
 * Failure tests should be added for specific api behaviours but note that
 * many generic patterns are tested in the syntax conformance test
 *
 * @author eileen
 *
 * @group headless
 */
class api_v3_PcpTest extends CiviUnitTestCase {
  protected $params;
  protected $entity = 'Pcp';
  public $DBResetRequired = TRUE;

  public function setUp() {
    $this->params = [
      'title' => "Pcp title",
      'contact_id' => 1,
      'page_id' => 1,
      'pcp_block_id' => 1,
    ];
    parent::setUp();
  }

  /**
   * Test create function succeeds.
   */
  public function testCreatePcp() {
    $result = $this->callAPIAndDocument('Pcp', 'create', $this->params,
        __FUNCTION__, __FILE__);
    $this->getAndCheck($this->params, $result['id'], $this->entity);
  }

  /**
   * Test disable a PCP succeeds.
   */
  public function testDisablePcp() {
    $result = civicrm_api3('Pcp', 'create', $this->params);
    civicrm_api3('Pcp', 'create', ['id' => $result['id'], 'is_active' => 0]);
    $this->getAndCheck($this->params + ['is_active' => 0], $result['id'], $this->entity);
  }

  /**
   * Test get function succeeds.
   *
   * This is actually largely tested in the get
   * action on create. Add extra checks for any 'special' return values or
   * behaviours
   */
  public function testGetPcp() {
    $this->createTestEntity();
    $result = $this->callAPIAndDocument('Pcp', 'get', $this->params,
        __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDeletePcp() {
    $entity = $this->createTestEntity();
    $checkCreated = $this->callAPISuccess($this->entity, 'get',
      ['id' => $entity['id']]);
    $this->assertEquals(1, $checkCreated['count']);
    $this->callAPIAndDocument('Pcp', 'delete',
        ['id' => $entity['id']], __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get',
        ['id' => $entity['id']]);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test & document chained delete pattern.
   *
   * Note that explanation of the pattern
   * is best put in the $description variable as it will then be displayed in the
   * test generated examples. (these are to be found in the api/examples folder).
   */
  public function testGetPcpChainDelete() {
    $description = "Demonstrates get + delete in the same call.";
    $subfile = 'ChainedGetDelete';
    $params = ['title' => "Pcp title", 'api.Pcp.delete' => 1];
    $this->callAPISuccess('Pcp', 'create', $this->params);
    $this->callAPIAndDocument('Pcp', 'get', $params, __FUNCTION__,
        __FILE__, $description, $subfile);
    $this->assertEquals(0, $this->callAPISuccess('Pcp', 'getcount', []));
  }

}
