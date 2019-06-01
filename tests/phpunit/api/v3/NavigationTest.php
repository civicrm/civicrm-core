<?php
/**
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
    $this->callAPISuccess($this->_entity, 'getsingle', array('label' => 'Manage Groups', 'domain_id' => 1));
  }

  /**
   * Test get specifying parent
   * FIXME: Api4
   */
  public function testGetByParent() {
    // get by name
    $this->callAPISuccess($this->_entity, 'get', array('parentID' => 'Administer', 'domain_id' => 1));

    $params = array(
      'name' => 'Administer',
      'domain_id' => 1,
      'return' => 'id',
    );
    $adminId = $this->callAPISuccess($this->_entity, 'getvalue', $params);

    $this->callAPISuccess($this->_entity, 'get', array('parentID' => $adminId, 'domain_id' => 1));
  }

  /**
   * Test create function.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreate($version) {
    $this->_apiversion = $version;
    $params = array('label' => 'Feed the Goats', 'domain_id' => 1);
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
    $params = array('label' => 'Herd the Cats');
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
    $getParams = array(
      'return' => 'id',
      'options' => array('limit' => 1),
    );
    $result = $this->callAPISuccess('Navigation', 'getvalue', $getParams);
    $this->callAPISuccess('Navigation', 'delete', array('id' => $result));
    $this->callAPIFailure('Navigation', 'getvalue', array('id' => $result));
  }

}
