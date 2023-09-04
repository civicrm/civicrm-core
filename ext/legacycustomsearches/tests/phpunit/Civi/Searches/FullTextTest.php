<?php

namespace Civi\Searches;

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Contact_Form_Search_Custom_FullText;
use CRM_Core_Config;
use CRM_Core_DAO;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test
 * class. Simply create corresponding functions (e.g. "hook_civicrm_post(...)"
 * or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or
 * test****() functions will rollback automatically -- as long as you don't
 * manipulate schema or truncate tables. If this test needs to manipulate
 * schema or truncate tables, then either: a. Do all that using setupHeadless()
 * and Civi\Test. b. Disable TransactionalInterface, and handle all
 * setup/teardown yourself.
 *
 * @group headless
 */
class FullTextTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Test\ContactTestTrait;
  use Test\Api3TestTrait;

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and
   * sqlFile(). See:
   * https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['legacycustomsearches'])
      ->apply();
  }

  /**
   * Test ACL contacts are filtered properly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFilterACLContacts(): void {
    $userId = $this->createLoggedInUser();
    // remove all permissions
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

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
    $whereClause = 'WHERE NOT EXISTS (SELECT c.contact_id
      FROM civicrm_acl_contact_cache c
      WHERE c.user_id = %1 AND t.contact_id = c.contact_id )';

    $count = CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM ' . $fullText->getTableName() . " t $whereClause", $queryParams);
    $this->assertEmpty($count, 'ACL contacts are not removed.');
  }

}
