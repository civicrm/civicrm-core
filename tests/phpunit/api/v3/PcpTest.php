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

  public function setUp(): void {
    $this->params = [
      'title' => 'Pcp title',
      'contact_id' => 1,
      'page_id' => 1,
      'pcp_block_id' => 1,
      'status_id:name' => 'Approved',
    ];
    parent::setUp();
  }

  /**
   * Test create function succeeds.
   */
  public function testCreatePcp(): void {
    $result = $this->callAPISuccess('Pcp', 'create', $this->params);
    $this->getAndCheck($this->params, $result['id'], $this->entity);
  }

  /**
   * Test disable a PCP succeeds.
   */
  public function testDisablePcp(): void {
    $result = $this->callAPISuccess('Pcp', 'create', $this->params);
    $this->callAPISuccess('Pcp', 'create', ['id' => $result['id'], 'is_active' => 0]);
    $this->getAndCheck($this->params + ['is_active' => 0], $result['id'], $this->entity);
  }

  /**
   * Test get function succeeds.
   *
   * This is actually largely tested in the get
   * action on create. Add extra checks for any 'special' return values or
   * behaviours
   */
  public function testGetPcp(): void {
    $this->createTestEntity('PCP', $this->params);
    $result = $this->callAPISuccess('Pcp', 'get', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDeletePcp(): void {
    $entity = $this->createTestEntity('PCP', $this->params);
    $checkCreated = $this->callAPISuccess($this->entity, 'get',
      ['id' => $entity['id']]);
    $this->assertEquals(1, $checkCreated['count']);
    $this->callAPISuccess('Pcp', 'delete',
        ['id' => $entity['id']]);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get',
        ['id' => $entity['id']]);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test chained delete pattern.
   */
  public function testGetPcpChainDelete(): void {
    $params = ['title' => 'Pcp title', 'api.Pcp.delete' => 1];
    $this->callAPISuccess('Pcp', 'create', $this->params);
    $this->callAPISuccess('Pcp', 'get', $params);
    $this->assertEquals(0, $this->callAPISuccess('Pcp', 'getcount', []));
  }

}
