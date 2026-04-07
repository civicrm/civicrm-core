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
 */

namespace api\v4\Query {

  use api\v4\Api4TestBase;
  use Civi\Api4\PhoneNumberJoin;
  use Civi\Core\Event\GenericHookEvent;
  use Civi\Core\HookInterface;
  use Civi\Schema\EntityRepository;

  /**
   * @package api\v4\Query
   * @group headless
   */
  class ArbitraryForeignKeyJoinTest extends Api4TestBase implements HookInterface {

    public function setUp(): void {
      // Create `civicrm_phone_number_join` table
      $entities = [];
      self::hook_entityTypes($entities);
      $createTableSql = \Civi::schemaHelper()->arrayToSql($entities['PhoneNumberJoin']);
      \CRM_Core_DAO::executeQuery($createTableSql, [], TRUE, NULL, FALSE, FALSE);

      // hook_civicrm_entityTypes has special significance in system boot. This seems to be more reliable way to register it.
      \CRM_Utils_Hook::singleton()->setHook('civicrm_entityTypes', [$this, 'hook_entityTypes']);
      EntityRepository::flush();
      \Civi::cache('metadata')->flush();
      parent::setUp();
    }

    public function tearDown(): void {
      \CRM_Utils_Hook::singleton()->reset();
      EntityRepository::flush();
      // Drop table
      \CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_phone_number_join`', [], TRUE, NULL, FALSE, FALSE);
      parent::tearDown();
    }

    /**
     * Ensure join conditions correctly handle FKs to arbitrary fields, not just primary keys
     *
     * @see \Civi\Api4\Query\Api4SelectQuery::getJoinConditions
     */
    public function testArbitraryForeignKeyJoin(): void {
      // With an explicit join condition to the FK, the api should NOT auto-insert any other clauses
      $apiCall = PhoneNumberJoin::get(FALSE)
        ->addJoin('Phone AS Phone', 'LEFT',
          ['Phone.phone_numeric', '=', 'phone_fk'])
        ->setDebug(TRUE)
        ->execute();
      $sql = $apiCall->debug['sql'];
      [$select, $join] = explode('LEFT JOIN', $sql[0]);
      $this->assertLike('(`civicrm_phone` `Phone`) ON `Phone`.`phone_numeric` = `a`.`phone_fk`', $join);

      // Make sure it works both ways
      $apiCall = PhoneNumberJoin::get(FALSE)
        ->addJoin('Phone AS Phone', 'LEFT',
          ['phone_fk', '=', 'Phone.phone_numeric'])
        ->setDebug(TRUE)
        ->execute();
      $sql = $apiCall->debug['sql'];
      [$select, $join] = explode('LEFT JOIN', $sql[0]);
      $this->assertLike('(`civicrm_phone` `Phone`) ON `a`.`phone_fk` = `Phone`.`phone_numeric`', $join);

      // Joining without any ON conditions specified, ensure the API automatically adds the correct FK.
      $apiCall = PhoneNumberJoin::get(FALSE)
        ->addJoin('Phone AS Phone', 'LEFT')
        ->setDebug(TRUE)
        ->execute();
      $sql = $apiCall->debug['sql'];
      [$select, $join] = explode('LEFT JOIN', $sql[0]);
      $this->assertLike('(`civicrm_phone` `Phone`) ON `a`.`phone_fk` = `Phone`.`phone_numeric`', $join);
    }

    /**
     * Listens for civi.api4.entityTypes event to manually add this nonstandard entity
     *
     * @param \Civi\Core\Event\GenericHookEvent $e
     */
    public function on_civi_api4_entityTypes(GenericHookEvent $e): void {
      $e->entities['PhoneNumberJoin'] = [
        'name' => 'PhoneNumberJoin',
        'title' => 'PhoneNumberJoin',
        'title_plural' => 'PhoneNumberJoin',
        'table_name' => 'civicrm_phone_number_join',
        'type' => ['DAOEntity'],
        'paths' => [],
        'class' => 'Civi\Api4\PhoneNumberJoin',
        'dao' => 'Civi\DAO\PhoneNumberJoin',
        'primary_key' => ['id'],
      ];
    }

    /**
     * @implements CRM_Utils_Hook::entityTypes()
     */
    public function hook_entityTypes(array &$entityTypes): void {
      $entityTypes['PhoneNumberJoin'] = [
        'name' => 'PhoneNumberJoin',
        'table' => 'civicrm_phone_number_join',
        'class' => 'Civi\DAO\PhoneNumberJoin',
        'getInfo' => fn() => [
          'title' => 'PhoneNumberJoin',
          'title_plural' => 'PhoneNumberJoin',
          'description' => 'PhoneNumberJoin',
          'log' => FALSE,
        ],
        'getFields' => fn() => [
          'id' => [
            'title' => 'ID',
            'sql_type' => 'int unsigned',
            'input_type' => 'Number',
            'required' => TRUE,
            'primary_key' => TRUE,
            'auto_increment' => TRUE,
          ],
          'phone_fk' => [
            'title' => 'FK to phone_numeric',
            'sql_type' => 'int unsigned',
            'input_type' => 'EntityRef',
            'entity_reference' => [
              'entity' => 'Phone',
              // This is what's being tested: the key is to a field other than ID.
              'key' => 'phone_numeric',
              'fk' => FALSE,
            ],
          ],
        ],
      ];
    }

  }

}

namespace Civi\Api4 {

  class PhoneNumberJoin extends Generic\DAOEntity {
  }

}

namespace Civi\DAO {

  class PhoneNumberJoin extends \CRM_Core_DAO_Base {
  }

}
