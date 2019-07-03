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
 * Test class for GroupNesting API - civicrm_group_nesting_*
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_GroupNestingTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();

    $this->ids['Group'] = [];
    $this->ids['Group']['parent'] = $this->callAPISuccess('Group', 'create', [
      'name' => 'Administrators',
      'title' => 'Administrators',
    ])['id'];
    $this->ids['Group']['child'] = $this->callAPISuccess('Group', 'create', [
      'name' => 'Newsletter Subscribers',
      'title' => 'Newsletter Subscribers',
      'parents' => $this->ids['Group']['parent'],
    ])['id'];
    $this->ids['Group']['child2'] = $this->callAPISuccess('Group', 'create', [
      'name' => 'Another Newsletter Subscribers',
      'title' => 'Another Newsletter Subscribers',
      'parents' => $this->ids['Group']['parent'],
    ])['id'];
    $this->ids['Group']['child3'] = $this->callAPISuccess('Group', 'create', [
      'name' => 'Super Special Newsletter Subscribers',
      'title' => 'Super Special Newsletter Subscribers',
      'parents' => [$this->ids['Group']['parent'], $this->ids['Group']['child']],
    ])['id'];

  }

  /**
   * Tears down the fixture.
   *
   * This method is called after a test is executed.
   *
   * @throws \Exception
   */
  protected function tearDown() {
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_group_nesting',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );
    parent::tearDown();
  }

  /**
   * Test civicrm_group_nesting_get.
   */
  public function testGet() {
    $params = [
      'parent_group_id' => $this->ids['Group']['parent'],
      'child_group_id' => $this->ids['Group']['child'],
    ];

    $result = $this->callAPIAndDocument('group_nesting', 'get', $params, __FUNCTION__, __FILE__);
    $expected = [
      1 => [
        'id' => 1,
        'child_group_id' => $this->ids['Group']['child'],
        'parent_group_id' => $this->ids['Group']['parent'],
      ],
    ];

    $this->assertEquals($expected, $result['values']);
  }

  /**
   * Test civicrm_group_nesting_get with just one param (child_group_id).
   */
  public function testGetWithChildGroupId() {
    $params = [
      'child_group_id' => $this->ids['Group']['child3'],
    ];

    $result = $this->callAPISuccess('group_nesting', 'get', $params);

    // expected data loaded in setUp
    $expected = [
      3 => [
        'id' => 3,
        'child_group_id' => $this->ids['Group']['child3'],
        'parent_group_id' => $this->ids['Group']['parent'],
      ],
      4 => [
        'id' => 4,
        'child_group_id' => $this->ids['Group']['child3'],
        'parent_group_id' => $this->ids['Group']['child'],
      ],
    ];

    $this->assertEquals($expected, $result['values']);
  }

  /**
   * Test civicrm_group_nesting_get with just one param (parent_group_id).
   */
  public function testGetWithParentGroupId() {
    $params = [
      'parent_group_id' => $this->ids['Group']['parent'],
    ];

    $result = $this->callAPISuccess('group_nesting', 'get', $params);

    // expected data loaded in setUp
    $expected = [
      1 => [
        'id' => 1,
        'child_group_id' => $this->ids['Group']['child'],
        'parent_group_id' => $this->ids['Group']['parent'],
      ],
      2 => [
        'id' => 2,
        'child_group_id' => $this->ids['Group']['child2'],
        'parent_group_id' => $this->ids['Group']['parent'],
      ],
      3 => [
        'id' => 3,
        'child_group_id' => $this->ids['Group']['child3'],
        'parent_group_id' => $this->ids['Group']['parent'],
      ],
    ];

    $this->assertEquals($expected, $result['values']);
  }

  /**
   * Test civicrm_group_nesting_get for no records results.
   *
   * Success expected. (these tests are of marginal value as are in syntax conformance,
   * don't copy & paste
   */
  public function testGetEmptyResults() {
    $params = [
      'parent_group_id' => $this->ids['Group']['parent'],
      'child_group_id' => 700,
    ];
    $this->callAPISuccess('group_nesting', 'get', $params);
  }

  /**
   * Test civicrm_group_nesting_create.
   *
   * @throws \Exception
   */
  public function testCreate() {
    $params = [
      'parent_group_id' => $this->ids['Group']['parent'],
      'child_group_id' => $this->ids['Group']['child2'],
    ];

    $this->callAPIAndDocument('group_nesting', 'create', $params, __FUNCTION__, __FILE__);
    $this->callAPISuccessGetCount('GroupNesting', $params, 1);
  }

  /**
   * Test civicrm_group_nesting_remove.
   */
  public function testDelete() {
    $params = [
      'parent_group_id' => $this->ids['Group']['parent'],
      'child_group_id' => $this->ids['Group']['child'],
    ];

    $result = $this->callAPISuccess('group_nesting', 'get', $params);
    $params = ['id' => $result['id']];
    $this->callAPIAndDocument('group_nesting', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $this->callAPISuccess('group_nesting', 'getcount', $params));
  }

  /**
   * Test civicrm_group_nesting_remove with empty parameter array.
   *
   * Error expected.
   */
  public function testDeleteWithEmptyParams() {
    $this->callAPIFailure('group_nesting', 'delete', []);
  }

}
