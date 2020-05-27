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
 * Class api_v3_NavigationTest
 * @group headless
 */
class api_v3_NavigationTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;

  protected $_entity = 'Navigation';

  /**
   * Test get function.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGet($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess($this->_entity, 'getsingle', ['label' => 'Manage Groups', 'domain_id' => 1]);
  }

  /**
   * Test get specifying parent
   * FIXME: Api4
   */
  public function testGetByParent() {
    // get by name
    $this->callAPISuccess($this->_entity, 'get', ['parentID' => 'Administer', 'domain_id' => 1]);

    $params = [
      'name' => 'Administer',
      'domain_id' => 1,
      'return' => 'id',
    ];
    $adminId = $this->callAPISuccess($this->_entity, 'getvalue', $params);

    $this->callAPISuccess($this->_entity, 'get', ['parentID' => $adminId, 'domain_id' => 1]);
  }

  /**
   * Test create function.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreate($version) {
    $this->_apiversion = $version;
    $params = ['label' => 'Feed the Goats', 'domain_id' => 1];
    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $this->getAndCheck($params, $result['id'], $this->_entity, TRUE);
  }

  /**
   * Test create function.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDefaultDomain($version) {
    $this->_apiversion = $version;
    $params = ['label' => 'Herd the Cats'];
    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    // Check domain_id has been set per default
    $params['domain_id'] = CRM_Core_Config::domainID();
    $this->getAndCheck($params, $result['id'], $this->_entity, TRUE);
  }

  /**
   * Test delete function.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDelete($version) {
    $this->_apiversion = $version;
    $getParams = [
      'return' => 'id',
      'options' => ['limit' => 1],
    ];
    $result = $this->callAPISuccess('Navigation', 'getvalue', $getParams);
    $this->callAPISuccess('Navigation', 'delete', ['id' => $result]);
    $this->callAPIFailure('Navigation', 'getvalue', ['id' => $result]);
  }

}
