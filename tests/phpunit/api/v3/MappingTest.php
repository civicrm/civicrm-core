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
 *  Test APIv3 civicrm_mapping_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_MappingTest extends CiviUnitTestCase {

  protected $params;
  protected $id;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();

    $this->params = [
      'name' => 'Mapping name',
      'description' => 'Mapping description',
      // 'Export Contact' mapping.
      'mapping_type_id' => 7,
    ];
  }

  public function testCreateMapping(): void {
    $result = $this->callAPISuccess('Mapping', 'create', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], 'Mapping');
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetMapping(): void {
    $this->callAPISuccess('Mapping', 'create', $this->params);
    $result = $this->callAPISuccess('Mapping', 'get', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('Mapping', 'delete', ['id' => $result['id']]);
  }

  public function testDeleteMapping(): void {
    $result = $this->callAPISuccess('Mapping', 'create', $this->params);
    $deleteParams = ['id' => $result['id']];
    $this->callAPISuccess('Mapping', 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess('Mapping', 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testDeleteMappingInvalid(): void {
    $this->callAPISuccess('Mapping', 'create', $this->params);
    $deleteParams = ['id' => 600];
    $this->callAPIFailure('Mapping', 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess('Mapping', 'get', []);
    $this->assertEquals(1, $checkDeleted['count']);
  }

}
