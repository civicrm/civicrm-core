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
class CRM_Contact_Form_Search_Custom_FullTextTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  protected $_tablesToTruncate = array(
    'civicrm_acl_contact_cache',
  );

  /**
   * Test ACL contacts are filtered properly.
   */
  public function testfilterACLContacts() {
    $this->quickCleanup($this->_tablesToTruncate);

    $userId = $this->createLoggedInUser();
    // remove all permissions
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();

    for ($i = 1; $i <= 10; $i++) {
      $contactId = $this->individualCreate(array(), $i);
      if ($i <= 5) {
        $queryParams = array(
          1 => array($userId, 'Integer'),
          2 => array($contactId, 'Integer'),
        );
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_contact_cache ( user_id, contact_id, operation ) VALUES(%1, %2, 'View')", $queryParams);
      }
      $contactIDs[$i] = $contactId;
    }

    $formValues = array('component_mode' => 1, 'operator' => 1, 'is_unit_test' => 1);
    $fullText = new CRM_Contact_Form_Search_Custom_FullText($formValues);
    $fullText->initialize();

    //Assert that ACL contacts are filtered.
    $queryParams = array(1 => array($userId, 'Integer'));
    $whereClause = "WHERE NOT EXISTS (SELECT c.contact_id
      FROM civicrm_acl_contact_cache c
      WHERE c.user_id = %1 AND t.contact_id = c.contact_id )";

    $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM {$fullText->_tableNameForTest} t {$whereClause}", $queryParams);
    $this->assertEmpty($count, 'ACL contacts are not removed.');
  }

}
