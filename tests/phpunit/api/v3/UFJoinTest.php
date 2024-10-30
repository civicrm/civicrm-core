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
 * Test class for UFGroup API - civicrm_uf_*
 * @todo Split UFGroup and UFJoin tests
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_UFJoinTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_group',
      'civicrm_uf_group',
      'civicrm_uf_join',
      'civicrm_uf_match',
    ]);
    parent::tearDown();
  }

  /**
   * Create/update uf join
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFJoin(int $version): void {
    $this->_apiversion = $version;
    $ufGroupID = $this->createTestEntity('UFGroup', [
      'group_type' => 'Contact',
      'title' => 'Test Profile',
    ])['id'];
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $ufGroupID,
      'is_active' => 1,
      'sequential' => 1,
    ];
    $ufJoin = $this->callAPISuccess('UFJoin', 'create', $params)['values'][0];
    $this->assertEquals('CiviContribute', $ufJoin['module']);
    $this->assertEquals($ufGroupID, $ufJoin['uf_group_id']);
    $this->assertEquals(1, $ufJoin['is_active']);

    $params = [
      'id' => $ufJoin['id'],
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $ufGroupID,
      'is_active' => 0,
      'sequential' => 1,
    ];
    $ufJoinUpdated = $this->callAPISuccess('uf_join', 'create', $params)['values'][0];
    $this->assertEquals('CiviContribute', $ufJoinUpdated['module']);
    $this->assertEquals($ufGroupID, $ufJoinUpdated['uf_group_id']);
    $this->assertEquals(0, $ufJoinUpdated['is_active']);
  }

  /**
   * Ensure we can create a survey join which is less common than event or contribution
   * joins.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSurveyUFJoin(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviCampaign',
      'entity_table' => 'civicrm_survey',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->createTestEntity('UFGroup', [
        'group_type' => 'Contact',
        'title' => 'Test Profile',
      ])['id'],
      'is_active' => 1,
      'sequential' => 1,
    ];
    $ufJoin = $this->callAPISuccess('UFJoin', 'create', $params)['values'][0];
    $this->assertEquals('CiviCampaign', $ufJoin['module']);
    $this->assertEquals($params['uf_group_id'], $ufJoin['uf_group_id']);
    $this->assertEquals(1, $ufJoin['is_active']);
  }

}
