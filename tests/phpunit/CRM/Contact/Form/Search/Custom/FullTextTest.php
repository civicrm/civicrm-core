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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_FullTextTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  protected $_tablesToTruncate = [
    'civicrm_acl_contact_cache',
  ];

  /**
   * Test ACL contacts are filtered properly.
   */
  public function testfilterACLContacts() {
    $this->quickCleanup($this->_tablesToTruncate);

    $userId = $this->createLoggedInUser();
    // remove all permissions
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];

    for ($i = 1; $i <= 10; $i++) {
      $contactId = $this->individualCreate([], $i);
      if ($i <= 5) {
        $queryParams = [
          1 => [$userId, 'Integer'],
          2 => [$contactId, 'Integer'],
        ];
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_contact_cache ( user_id, contact_id, operation ) VALUES(%1, %2, 'View')", $queryParams);
      }
      $contactIDs[$i] = $contactId;
    }

    $formValues = ['component_mode' => 1, 'operator' => 1, 'is_unit_test' => 1];
    $fullText = new CRM_Contact_Form_Search_Custom_FullText($formValues);
    $fullText->initialize();

    //Assert that ACL contacts are filtered.
    $queryParams = [1 => [$userId, 'Integer']];
    $whereClause = "WHERE NOT EXISTS (SELECT c.contact_id
      FROM civicrm_acl_contact_cache c
      WHERE c.user_id = %1 AND t.contact_id = c.contact_id )";

    $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM {$fullText->_tableNameForTest} t {$whereClause}", $queryParams);
    $this->assertEmpty($count, 'ACL contacts are not removed.');
  }

}
