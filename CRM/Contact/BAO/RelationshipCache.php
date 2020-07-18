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
 * Class CRM_Contact_BAO_RelationshipCache.
 */
class CRM_Contact_BAO_RelationshipCache extends CRM_Contact_DAO_RelationshipCache {

  /**
   * The "mappings" array defines the values to put into `civicrm_relationship_cache`
   * using data from `civicrm_relationship rel` and `civicrm_relationship_type reltype`.
   *
   * @var array
   *   Array(string $intoColumn => string $selectValue)
   */
  private static $mappings = [
    'a_b' => [
      'relationship_id' => 'rel.id',
      'relationship_type_id' => 'rel.relationship_type_id',
      'orientation' => '"a_b"',
      'near_contact_id' => 'rel.contact_id_a',
      'near_relation' => 'reltype.name_a_b',
      'far_contact_id' => 'rel.contact_id_b',
      'far_relation' => 'reltype.name_b_a',
      'start_date' => 'rel.start_date',
      'end_date' => 'rel.end_date',
      'is_active' => 'rel.is_active',
    ],
    'b_a' => [
      'relationship_id' => 'rel.id',
      'relationship_type_id' => 'rel.relationship_type_id',
      'orientation' => '"b_a"',
      'near_contact_id' => 'rel.contact_id_b',
      'near_relation' => 'reltype.name_b_a',
      'far_contact_id' => 'rel.contact_id_a',
      'far_relation' => 'reltype.name_a_b',
      'start_date' => 'rel.start_date',
      'end_date' => 'rel.end_date',
      'is_active' => 'rel.is_active',
    ],
  ];

  /**
   * A list of fields which uniquely identify a row.
   *
   * @var array
   */
  private static $keyFields = ['relationship_id', 'orientation'];

  /**
   * A list of of fields in `civicrm_relationship_type` which (if changed)
   * will necessitate an update to the cache.
   *
   * @var array
   */
  private static $relTypeWatchFields = ['name_a_b', 'name_b_a'];

  /**
   * Add our list of triggers to the global list.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::triggerInfo
   */
  public static function onHookTriggerInfo($e) {
    $relUpdates = self::createInsertUpdateQueries();
    foreach ($relUpdates as $relUpdate) {
      /**
       * This trigger runs whenever a "civicrm_relationship" record is inserted or updated.
       *
       * Goal: Ensure that every relationship record has two corresponding entries in the
       * cache, the forward relationship (A=>B) and reverse relationship (B=>A).
       */
      $triggers[] = [
        'table' => 'civicrm_relationship',
        'when' => 'AFTER',
        'event' => ['INSERT', 'UPDATE'],
        'sql' => $relUpdate->copy()->where('rel.id = NEW.id')->toSQL() . ";\n",
      ];

      $triggers[] = [
        /**
         * This trigger runs whenever a "civicrm_relationship_type" record is updated.
         *
         * Goal: Ensure that the denormalized fields ("name_b_a"/"name_a_b" <=> "relation") remain current.
         */
        'table' => 'civicrm_relationship_type',
        'when' => 'AFTER',
        'event' => ['UPDATE'],
        'sql' => sprintf("\nIF (%s) THEN\n %s;\n END IF;\n",

          // Condition
          implode(' OR ', array_map(function ($col) {
            return "(OLD.$col != NEW.$col COLLATE utf8_bin)";
          }, self::$relTypeWatchFields)),

          // Action
          $relUpdate->copy()->where('rel.relationship_type_id = NEW.id')->toSQL()
        ),
      ];
    }

    // Note: We do not need a DELETE trigger to maintain `civicrm_relationship_cache` because it uses `<onDelete>CASCADE</onDelete>`.

    $st = new \Civi\Core\SqlTrigger\StaticTriggers($triggers);
    $st->onTriggerInfo($e);
  }

  /**
   * Read all records from civicrm_relationship and populate the cache.
   * Each ordinary relationship in `civicrm_relationship` becomes two
   * distinct records in the cache (one for A=>B relations; and one for B=>A).
   *
   * This method is primarily written (a) for manual testing and (b) in case
   * a broken DBMS, screwy import, buggy code, etc causes a corruption.
   *
   * NOTE: This is closely related to FiveTwentyNine::populateRelationshipCache(),
   * except that the upgrader users pagination.
   */
  public static function rebuild() {
    $relUpdates = self::createInsertUpdateQueries();

    CRM_Core_DAO::executeQuery('TRUNCATE civicrm_relationship_cache');
    foreach ($relUpdates as $relUpdate) {
      $relUpdate->execute();
    }
  }

  /**
   * Prepare a list of SQL queries that map data from civicrm_relationship
   * to civicrm_relationship_cache.
   *
   * @return CRM_Utils_SQL_Select[]
   *   A list of SQL queries - one for each mapping.
   */
  public static function createInsertUpdateQueries() {
    $queries = [];
    foreach (self::$mappings as $name => $mapping) {
      $queries[$name] = CRM_Utils_SQL_Select::from('civicrm_relationship rel')
        ->join('reltype', 'INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id')
        ->syncInto('civicrm_relationship_cache', self::$keyFields, $mapping);
    }
    return $queries;
  }

}
