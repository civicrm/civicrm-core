<?php

/*
 * +--------------------------------------------------------------------+
 * | CiviCRM version 5                                                  |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2019                                |
 * +--------------------------------------------------------------------+
 * | This file is a part of CiviCRM.                                    |
 * |                                                                    |
 * | CiviCRM is free software; you can copy, modify, and distribute it  |
 * | under the terms of the GNU Affero General Public License           |
 * | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 * |                                                                    |
 * | CiviCRM is distributed in the hope that it will be useful, but     |
 * | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 * | See the GNU Affero General Public License for more details.        |
 * |                                                                    |
 * | You should have received a copy of the GNU Affero General Public   |
 * | License and the CiviCRM Licensing Exception along                  |
 * | with this program; if not, contact CiviCRM LLC                     |
 * | at info[AT]civicrm[DOT]org. If you have questions about the        |
 * | GNU Affero General Public License or the licensing of CiviCRM,     |
 * | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 * +--------------------------------------------------------------------+
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
    $this->params = array(
      'title' => "Pcp title",
      'contact_id' => 1,
      'page_id' => 1,
      'pcp_block_id' => 1,
    );
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
    civicrm_api3('Pcp', 'create', array('id' => $result['id'], 'is_active' => 0));
    $this->getAndCheck($this->params + array('is_active' => 0), $result['id'], $this->entity);
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
      array('id' => $entity['id']));
    $this->assertEquals(1, $checkCreated['count']);
    $this->callAPIAndDocument('Pcp', 'delete',
        array('id' => $entity['id']), __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get',
        array('id' => $entity['id']));
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
    $params = array('title' => "Pcp title", 'api.Pcp.delete' => 1);
    $this->callAPISuccess('Pcp', 'create', $this->params);
    $this->callAPIAndDocument('Pcp', 'get', $params, __FUNCTION__,
        __FILE__, $description, $subfile);
    $this->assertEquals(0, $this->callAPISuccess('Pcp', 'getcount', array()));
  }

}
