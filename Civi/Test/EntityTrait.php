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

namespace Civi\Test;

use Civi\Core\Exception\DBQueryException;

/**
 * Helper for tracking entities created in tests.
 */
trait EntityTrait {

  /**
   * Array of IDs created to support the test.
   *
   * e.g
   * $this->ids = ['Event' => ['descriptive_key' => $eventID], 'Group' => [$groupID]];
   *
   * @var array
   */
  protected $ids = [];

  /**
   * Records created which will be deleted during tearDown
   *
   * @var array
   */
  protected $testRecords = [];

  /**
   * Track tables we have modified during a test.
   *
   * Set up functions that add entities can register the relevant tables here for
   * the cleanup process.
   *
   * @var array
   */
  protected $tablesToCleanUp = [];

  /**
   * Create an entity, recording it's details for tearDown.
   *
   * @param string $entity
   * @param array $values
   * @param string $identifier
   *
   * @return array
   */
  protected function createTestEntity(string $entity, array $values, string $identifier = 'default'): array {
    $result = NULL;
    try {
      $result = \civicrm_api4($entity, 'create', ['values' => $values, 'checkPermissions' => FALSE])->single();
      $this->setTestEntityID($entity, $result['id'], $identifier);
    }
    catch (DBQueryException $e) {
      $this->fail('sql error when trying to create ' . $entity . ' : ' . "\n" . $e->getUserInfo());
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('Failed to create ' . $entity . ' : ' . $e->getMessage());
    }
    return $result;
  }

  /**
   * Set the test entity on the class for access.
   *
   * This follows the ids patter and also the api4TestTrait pattern.
   *
   * @param string $entity
   * @param array $values
   * @param string $identifier
   */
  protected function setTestEntity(string $entity, array $values, string $identifier): void {
    $this->ids[$entity][$identifier] = $values['id'];
    $this->testRecords[] = [$entity, [[$values['id'] => $values]]];
    $tableName = \CRM_Core_DAO_AllCoreTables::getTableForEntityName($entity);
    $this->tablesToCleanUp[$tableName] = $tableName;
  }

  /**
   * @param string $entity
   * @param int $id
   * @param string $identifier
   */
  protected function setTestEntityID(string $entity, int $id, string $identifier): void {
    $this->setTestEntity($entity, ['id' => $id], $identifier);
  }

}
