<?php
/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 4.7                                                |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 */
class api_v3_NavigationTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;

  protected $_entity = 'Navigation';

  /**
   * Test get function.
   */
  public function testGet() {
    $this->callAPISuccess($this->_entity, 'getsingle', array('label' => 'Manage Groups', 'domain_id' => 1));
  }

  /**
   * Test create function.
   */
  public function testCreate() {
    $params = array('label' => 'Feed the Goats', 'domain_id' => 1);
    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $this->getAndCheck($params, $result['id'], $this->_entity, TRUE);
  }

  /**
   * Test delete function.
   */
  public function testDelete() {
    $getParams = array(
      'return' => 'id',
      'options' => array('limit' => 1),
    );
    $result = $this->callAPISuccess('Navigation', 'getvalue', $getParams);
    $this->callAPISuccess('Navigation', 'delete', array('id' => $result));
    $this->callAPIFailure('Navigation', 'getvalue', array('id' => $result));
  }

}
