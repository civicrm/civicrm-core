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

namespace Civi\Search {

  use api\v4\Api4TestBase;
  use Civi\Api4\Contact;
  use Civi\Core\Event\GenericHookEvent;
  use Civi\Core\HookInterface;
  use Civi\Schema\EntityRepository;
  use Civi\Test\CiviEnvBuilder;

  require_once __DIR__ . '/../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

  /**
   * Ensure SearchKit offers joins for entities whose DAO class is shared with other
   * entities (as with the entities supplied by the ECK extension) and therefore
   * declares its foreign keys as field metadata rather than as reference columns.
   *
   * @group headless
   */
  class SharedDaoJoinsTest extends Api4TestBase implements HookInterface {

    public function setUpHeadless(): CiviEnvBuilder {
      return \Civi\Test::headless()->installMe(__DIR__)->apply();
    }

    public function setUp(): void {
      $entities = [];
      self::hook_entityTypes($entities);
      $createTableSql = \Civi::schemaHelper()->arrayToSql($entities['MockSharedDaoEntity']);
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
      \CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_mock_shared_dao_entity`', [], TRUE, NULL, FALSE, FALSE);
      parent::tearDown();
    }

    public static function tearDownAfterClass(): void {
      // The api4 entity list is persistently cached, and this hook is still registered during
      // tearDown(), so the mock entity has to be flushed once it has been deregistered.
      EntityRepository::flush();
      \Civi::cache('metadata')->flush();
      parent::tearDownAfterClass();
    }

    public function testSharedDaoEntityGetJoins(): void {
      $allowedEntities = Admin::getSchema();
      $this->assertArrayHasKey('MockSharedDaoEntity', $allowedEntities);
      $joins = Admin::getJoins($allowedEntities);
      $this->assertArrayHasKey('MockSharedDaoEntity', $joins);

      $forwardJoins = \CRM_Utils_Array::filter($joins['MockSharedDaoEntity'], [
        'entity' => 'Contact',
        'alias' => 'MockSharedDaoEntity_Contact_created_id',
        'multi' => FALSE,
      ]);
      $this->assertCount(1, $forwardJoins);
      $forwardJoin = reset($forwardJoins);
      $this->assertEquals(
        [['created_id', '=', 'MockSharedDaoEntity_Contact_created_id.id']],
        $forwardJoin['conditions']
      );

      $reverseJoins = \CRM_Utils_Array::filter($joins['Contact'], [
        'entity' => 'MockSharedDaoEntity',
        'alias' => 'Contact_MockSharedDaoEntity_created_id',
        'multi' => TRUE,
      ]);
      $this->assertCount(1, $reverseJoins);
      $reverseJoin = reset($reverseJoins);
      $this->assertEquals(
        [['id', '=', 'Contact_MockSharedDaoEntity_created_id.created_id']],
        $reverseJoin['conditions']
      );

      // Serialized fields hold a list of values so are not joinable
      $this->assertCount(0, \CRM_Utils_Array::filter($joins['MockSharedDaoEntity'], ['entity' => 'Tag']));
      $this->assertCount(0, \CRM_Utils_Array::filter($joins['Tag'], ['entity' => 'MockSharedDaoEntity']));
    }

    /**
     * The join conditions SearchKit generates must be runnable.
     */
    public function testSharedDaoEntityJoinRuns(): void {
      $contact = $this->createTestRecord('Contact');
      // Inserted directly because the mock DAO class can't write (see MockSharedDao)
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO `civicrm_mock_shared_dao_entity` (`title`, `created_id`) VALUES (%1, %2)',
        [1 => ['Mock thing', 'String'], 2 => [$contact['id'], 'Integer']]
      );

      $result = Contact::get(FALSE)
        ->addSelect('id', 'Contact_MockSharedDaoEntity_created_id.title')
        ->addJoin('MockSharedDaoEntity AS Contact_MockSharedDaoEntity_created_id', 'INNER',
          ['id', '=', 'Contact_MockSharedDaoEntity_created_id.created_id'])
        ->execute();
      $this->assertCount(1, $result);
      $this->assertEquals($contact['id'], $result[0]['id']);
      $this->assertEquals('Mock thing', $result[0]['Contact_MockSharedDaoEntity_created_id.title']);
    }

    /**
     * @implements CRM_Utils_Hook::entityTypes()
     */
    public function hook_entityTypes(array &$entityTypes): void {
      $entityTypes['MockSharedDaoEntity'] = [
        'name' => 'MockSharedDaoEntity',
        'table' => 'civicrm_mock_shared_dao_entity',
        'class' => 'Civi\DAO\MockSharedDao',
        'getInfo' => fn() => [
          'title' => 'Mock Shared Dao Entity',
          'title_plural' => 'Mock Shared Dao Entities',
          'description' => 'Mock entity whose DAO class is shared with other entities',
          'label_field' => 'title',
          'searchable' => 'secondary',
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
          'title' => [
            'title' => 'Title',
            'sql_type' => 'varchar(255)',
            'input_type' => 'Text',
          ],
          'created_id' => [
            'title' => 'Created By Contact ID',
            'sql_type' => 'int unsigned',
            'input_type' => 'EntityRef',
            'entity_reference' => [
              'entity' => 'Contact',
              'key' => 'id',
            ],
          ],
          'tag_list' => [
            'title' => 'Tags',
            'sql_type' => 'varchar(255)',
            'input_type' => 'EntityRef',
            'serialize' => \CRM_Core_DAO::SERIALIZE_COMMA,
            'entity_reference' => [
              'entity' => 'Tag',
              'key' => 'id',
              // A list of ids in a text column, so not a real constraint
              'fk' => FALSE,
            ],
          ],
        ],
      ];
    }

    /**
     * Listens for civi.api4.entityTypes event to manually add this nonstandard entity
     */
    public function on_civi_api4_entityTypes(GenericHookEvent $e): void {
      $e->entities['MockSharedDaoEntity'] = [
        'name' => 'MockSharedDaoEntity',
        'title' => 'Mock Shared Dao Entity',
        'title_plural' => 'Mock Shared Dao Entities',
        'table_name' => 'civicrm_mock_shared_dao_entity',
        'type' => ['DAOEntity'],
        'paths' => [],
        'class' => 'Civi\Api4\MockSharedDaoEntity',
        'dao' => 'Civi\DAO\MockSharedDao',
        'primary_key' => ['id'],
        'searchable' => 'secondary',
      ];
    }

  }

}

namespace Civi\Api4 {

  class MockSharedDaoEntity extends Generic\DAOEntity {
  }

}

namespace Civi\DAO {

  /**
   * Emulates a DAO class shared by several entities, e.g. `CRM_Eck_DAO_Entity`, which
   * serves every ECK entity type and so can declare neither reference columns nor a
   * table name of its own.
   */
  class MockSharedDao extends \CRM_Core_DAO_Base {

    public static function getReferenceColumns() {
      return [];
    }

    public static function getTableName() {
      return NULL;
    }

  }

}
