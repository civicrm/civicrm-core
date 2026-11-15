<?php

namespace Civi\Test;

/**
 * Removes custom groups and fields that a transactional test leaves behind.
 *
 * Creating a custom group or field runs DDL (CREATE/ALTER TABLE), which
 * implicitly commits the open transaction, so those records survive the
 * test's rollback. Record the highest ids before the test and delete anything
 * newer afterwards.
 *
 * Other rows written before the DDL are committed too; they are not removed here.
 *
 * @see TransactionalInterface
 */
class CustomDataCleanup {

  /**
   * @return int[]
   *   Highest existing custom group and custom field ids.
   */
  public static function snapshot(): array {
    return [
      'CustomGroup' => (int) \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_custom_group'),
      'CustomField' => (int) \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_custom_field'),
    ];
  }

  /**
   * Delete custom groups and fields created since the snapshot.
   *
   * Groups go first, which also deletes their fields; then any new fields in older groups.
   *
   * @param int[] $snapshot
   *   Return value of snapshot().
   */
  public static function cleanup(array $snapshot): void {
    if (self::snapshot() === $snapshot) {
      return;
    }
    foreach ($snapshot as $entity => $maxId) {
      civicrm_api4($entity, 'delete', [
        'checkPermissions' => FALSE,
        'where' => [['id', '>', $maxId]],
      ]);
    }
  }

}
