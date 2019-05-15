<?php
// vim: set si ai expandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 *  File for the CRM_Contact_Form_Search_Custom_GroupTest class
 *
 *  (PHP 5)
 *
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include parent class definition
 */

require_once 'api/api.php';

/**
 *  Test contact custom search functions
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Bridge_OG_DrupalTest extends CiviUnitTestCase {

  /**
   * Test that one (ane only one) role (option value) is deleted by the updateCiviACLRole function
   */
  public function testACLRoleDeleteFunctionality() {
    $optionGroup = civicrm_api('OptionGroup', 'Get', array(
      'version' => 3,
      'name' => 'acl_role',
      'api.OptionValue.Create' =>
        array(
          array(
            'label' => 'OG',
            'value' => 5,
            'description' => 'OG Sync Group ACL :1967:',
          ),
          array(
            'label' => 'OG2',
            'value' => 6,
            'description' => 'OG Sync Group ACL :1969:',
          ),
        ),
    ));
    $getOptionGroupParams = array('version' => 3, 'option_group_id' => $optionGroup['id']);
    $originalCount = civicrm_api('OptionValue', 'GetCount', $getOptionGroupParams);
    $params = array('source' => 'OG Sync Group ACL :1969:');

    // this is the function we are testing
    CRM_Bridge_OG_Drupal::updateCiviACLRole($params, 'delete');
    $newCount = civicrm_api('OptionValue', 'GetCount', $getOptionGroupParams);

    //one option value (role) should have been deleted
    $this->assertEquals(1, $originalCount - $newCount);

    //clean up
    civicrm_api('OptionValue', 'Get', array('version' => 3, 'label' => 'OG', 'api.option_value.delete'));
    civicrm_api('OptionValue', 'Get', array('version' => 3, 'label' => 'OG2', 'api.option_value.delete'));
  }

}
