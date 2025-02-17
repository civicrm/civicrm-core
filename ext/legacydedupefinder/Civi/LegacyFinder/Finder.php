<?php

namespace Civi\LegacyFinder;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

class Finder extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_findExistingDuplicates' => ['findExistingDuplicates', -150],
      'hook_civicrm_findDuplicates' => ['findDuplicates', -150],
    ];
  }

  /**
   * This function exists to provide legacy hook support for finding duplicates.
   *
   * @return void
   */
  public static function findExistingDuplicates(GenericHookEvent $event): void {
    $event->stopPropagation();
    $ruleGroupIDs = $event->ruleGroupIDs;
    $ruleGroup = new \CRM_Dedupe_BAO_DedupeRuleGroup();
    $ruleGroup->id = reset($ruleGroupIDs);
    $contactIDs = [];
    if ($event->tableName) {
      $contactIDs = explode(',', \CRM_Core_DAO::singleValueQuery('SELECT GROUP_CONCAT(id) FROM ' . $event->tableName));
    }
    $ruleGroup->contactIds = $contactIDs;
    $tempTable = self::fillTable($ruleGroup, $ruleGroup->id, $contactIDs, []);
    if (!$tempTable) {
      return;
    }

    $aclWhere = $aclFrom = '';
    $dedupeTable = $tempTable;
    $contactType = $ruleGroup->contact_type;
    $threshold = $ruleGroup->threshold;

    if ($event->checkPermissions) {
      [$aclFrom, $aclWhere] = \CRM_Contact_BAO_Contact_Permission::cacheClause(['c1', 'c2']);
      $aclWhere = $aclWhere ? "AND {$aclWhere}" : '';
    }
    $query = \CRM_Core_DAO::composeQuery("SELECT IF(dedupe.id1 < dedupe.id2, dedupe.id1, dedupe.id2) as id1,
              IF(dedupe.id1 < dedupe.id2, dedupe.id2, dedupe.id1) as id2, dedupe.weight
              FROM $dedupeTable dedupe JOIN civicrm_contact c1 ON dedupe.id1 = c1.id
                JOIN civicrm_contact c2 ON dedupe.id2 = c2.id {$aclFrom}
                LEFT JOIN civicrm_dedupe_exception exc
                  ON dedupe.id1 = exc.contact_id1 AND dedupe.id2 = exc.contact_id2
              WHERE c1.contact_type = %1 AND
                    c2.contact_type = %1
                     AND c1.is_deleted = 0 AND c2.is_deleted = 0
                    {$aclWhere}
                    AND weight >= %2 AND exc.contact_id1 IS NULL",
      [
        1 => [$contactType, 'String'],
        2 => [$threshold, 'Integer'],
      ]
    );

    \CRM_Utils_Hook::dupeQuery($ruleGroup, 'threshold', $query);
    $dao = \CRM_Core_DAO::executeQuery($query);
    $duplicates = [];
    while ($dao->fetch()) {
      $duplicates[] = ['entity_id_1' => $dao->id1, 'entity_id_2' => $dao->id2, 'weight' => $dao->weight];
    }
    $event->duplicates = $duplicates;
    // Does it ever exist?
    \CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS dedupe');
  }

  public static function findDuplicates(GenericHookEvent $event): void {
    $event->stopPropagation();

    if (!empty($event->dedupeResults['handled'])) {
      // @todo - in time we can deprecate this & expect them to use stopPropagation().
      return;
    }
    $rgBao = new \CRM_Dedupe_BAO_DedupeRuleGroup();
    $rgBao->params = $event->dedupeParams['match_params'];
    $dedupeTable = self::fillTable($rgBao, $event->dedupeParams['rule_group_id'], [], $event->dedupeParams['match_params'], TRUE);
    if (!$dedupeTable) {
      $event->dedupeResults['ids'] = [];
      return;
    }
    $aclWhere = $aclFrom = '';
    if ($event->dedupeParams['check_permission']) {
      [$aclFrom, $aclWhere] = \CRM_Contact_BAO_Contact_Permission::cacheClause('civicrm_contact');
      $aclWhere = $aclWhere ? "AND {$aclWhere}" : '';
    }
    $query = \CRM_Core_DAO::composeQuery("
      SELECT dedupe.id1 as id
        FROM $dedupeTable dedupe JOIN civicrm_contact
          ON dedupe.id1 = civicrm_contact.id {$aclFrom}
        WHERE contact_type = %1 AND is_deleted = 0 $aclWhere
        AND weight >= %2
      ", [
        1 => [$rgBao->contact_type, 'String'],
        2 => [$rgBao->threshold, 'Integer'],
      ]
    );
    \CRM_Utils_Hook::dupeQuery($rgBao, 'threshold', $query);
    $dao = \CRM_Core_DAO::executeQuery($query);
    $dupes = [];
    while ($dao->fetch()) {
      if (isset($dao->id) && $dao->id) {
        $dupes[] = $dao->id;
      }
    }
    // Does it ever exist?
    \CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS dedupe');
    $event->dedupeResults['ids'] = array_diff($dupes, $event->dedupeParams['excluded_contact_ids']);
  }

  /**
   * Fill the dedupe finder table.
   *
   * @param \CRM_Dedupe_BAO_DedupeRuleGroup $ruleGroup
   * @param int $id
   * @param array $contactIDs
   * @param array $params
   * @param bool $legacyMode
   *   Legacy mode is called to give backward hook compatibility, in the legacydedupefinder
   *   extension. It is intended to be transitional, with the non-legacy mode being
   *   separated out and optimized once it no longer has to comply with the legacy
   *   hook and reserved query methodology.
   *
   * @return false|string
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   * @internal do not access from outside core.
   *
   */
  private static function fillTable(\CRM_Dedupe_BAO_DedupeRuleGroup $ruleGroup, int $id, array $contactIDs, array $params, $legacyMode = TRUE) {
    $ruleGroup->id = $id;
    // make sure we've got a fetched dbrecord, not sure if this is enforced
    $ruleGroup->find(TRUE);
    $optimizer = new FinderQueryBuilder($id, $contactIDs, $params);
    // Reserved Rule Groups can optionally get special treatment by
    // implementing an optimization class and returning a query array.
    if ($legacyMode && $optimizer->isUseReservedQuery()) {
      $tableQueries = $optimizer->getReservedQuery();
    }
    else {
      $tableQueries = $optimizer->getRuleQueries();
    }
    // if there are no rules in this rule group
    // add an empty query fulfilling the pattern
    if ($legacyMode) {
      if (!$tableQueries) {
        // Just for the hook.... (which is deprecated).
        $ruleGroup->noRules = TRUE;
      }
      \CRM_Utils_Hook::dupeQuery($ruleGroup, 'table', $tableQueries);
    }
    if (empty($tableQueries)) {
      return FALSE;
    }
    $threshold = $ruleGroup->threshold;

    return self::runTablesQuery($params, $tableQueries, $threshold);
  }

  /**
   * @internal this query is part of a refactoring process.
   *
   * @param array $params
   * @param array $tableQueries
   * @param int $threshold
   *
   * @return string
   * @throws \Civi\Core\Exception\DBQueryException
   */
  private static function runTablesQuery(array $params, array $tableQueries, int $threshold): string {
    if ($params) {
      $dedupeTable = \CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe')
        ->createWithColumns("id1 int, weight int, UNIQUE UI_id1 (id1)")->getName();
      $dedupeCopyTemporaryTableObject = \CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe');
      $dedupeTableCopy = $dedupeCopyTemporaryTableObject->getName();
      $insertClause = "INSERT INTO $dedupeTable  (id1, weight)";
      $groupByClause = "GROUP BY id1, weight";
      $dupeCopyJoin = " JOIN $dedupeTableCopy dedupe_copy ON dedupe_copy.id1 = t1.column WHERE ";
    }
    else {
      $dedupeTable = \CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe')
        ->createWithColumns("id1 int, id2 int, weight int, UNIQUE UI_id1_id2 (id1, id2)")->getName();
      $dedupeCopyTemporaryTableObject = \CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe');
      $dedupeTableCopy = $dedupeCopyTemporaryTableObject->getName();
      $insertClause = "INSERT INTO $dedupeTable  (id1, id2, weight)";
      $groupByClause = "GROUP BY id1, id2, weight";
      $dupeCopyJoin = " JOIN $dedupeTableCopy dedupe_copy ON dedupe_copy.id1 = t1.column AND dedupe_copy.id2 = t2.column WHERE ";
    }
    $patternColumn = '/t1.(\w+)/';
    $exclWeightSum = [];

    while (!empty($tableQueries)) {
      [$isInclusive, $isDie] = self::isQuerySetInclusive($tableQueries, $threshold, $exclWeightSum);

      if ($isInclusive) {
        // order queries by table count
        self::orderByTableCount($tableQueries);

        $weightSum = array_sum($exclWeightSum);
        $searchWithinDupes = !empty($exclWeightSum) ? 1 : 0;

        while (!empty($tableQueries)) {
          // extract the next query ( and weight ) to be executed
          $fieldWeight = array_keys($tableQueries);
          $fieldWeight = $fieldWeight[0];
          $query = array_shift($tableQueries);

          if ($searchWithinDupes) {
            // drop dedupe_copy table just in case if its already there.
            $dedupeCopyTemporaryTableObject->drop();
            // get prepared to search within already found dupes if $searchWithinDupes flag is set
            $dedupeCopyTemporaryTableObject->createWithQuery("SELECT * FROM $dedupeTable WHERE weight >= {$weightSum}");

            preg_match($patternColumn, $query, $matches);
            $query = str_replace(' WHERE ', str_replace('column', $matches[1], $dupeCopyJoin), $query);

            // CRM-19612: If there's a union, there will be two WHEREs, and you
            // can't use the temp table twice.
            if (preg_match('/' . $dedupeTableCopy . '[\S\s]*(union)[\S\s]*' . $dedupeTableCopy . '/i', $query, $matches, PREG_OFFSET_CAPTURE)) {
              // Make a second temp table:
              $dedupeTableCopy2 = \CRM_Utils_SQL_TempTable::build()
                ->setCategory('dedupe')
                ->createWithQuery("SELECT * FROM $dedupeTable WHERE weight >= {$weightSum}")
                ->getName();
              // After the union, use that new temp table:
              $part1 = substr($query, 0, $matches[1][1]);
              $query = $part1 . str_replace($dedupeTableCopy, $dedupeTableCopy2, substr($query, $matches[1][1]));
            }
          }
          $searchWithinDupes = 1;

          // construct and execute the intermediate query
          $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
          $dao = \CRM_Core_DAO::executeQuery($query);

          // FIXME: we need to be more accurate with affected rows, especially for insert vs duplicate insert.
          // And that will help optimize further.
          $affectedRows = $dao->affectedRows();

          // In an inclusive situation, failure of any query means no further processing -
          if ($affectedRows == 0) {
            // reset to make sure no further execution is done.
            $tableQueries = [];
            break;
          }
          $weightSum = substr($fieldWeight, strrpos($fieldWeight, '.') + 1) + $weightSum;
        }
        // An exclusive situation -
      }
      elseif (!$isDie) {
        // since queries are already sorted by weights, we can continue as is
        $fieldWeight = array_keys($tableQueries);
        $fieldWeight = $fieldWeight[0];
        $query = array_shift($tableQueries);
        $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
        $dao = \CRM_Core_DAO::executeQuery($query);
        if ($dao->affectedRows() >= 1) {
          $exclWeightSum[] = substr($fieldWeight, strrpos($fieldWeight, '.') + 1);
        }
      }
      else {
        // its a die situation
        break;
      }
    }
    return $dedupeTable;
  }

  /**
   * Function to determine if a given query set contains inclusive or exclusive set of weights.
   * The function assumes that the query set is already ordered by weight in desc order.
   * @param $tableQueries
   * @param $threshold
   * @param array $exclWeightSum
   *
   * @return array
   */
  private static function isQuerySetInclusive($tableQueries, $threshold, $exclWeightSum = []) {
    $input = [];
    foreach ($tableQueries as $key => $query) {
      $input[] = substr($key, strrpos($key, '.') + 1);
    }

    if (!empty($exclWeightSum)) {
      $input = array_merge($input, $exclWeightSum);
      rsort($input);
    }

    if (count($input) == 1) {
      return [FALSE, $input[0] < $threshold];
    }

    $totalCombinations = 0;
    for ($i = 0; $i < count($input); $i++) {
      $combination = [$input[$i]];
      if (array_sum($combination) >= $threshold) {
        $totalCombinations++;
        continue;
      }
      for ($j = $i + 1; $j < count($input); $j++) {
        $combination[] = $input[$j];
        if (array_sum($combination) >= $threshold) {
          $totalCombinations++;
        }
      }
    }
    return [$totalCombinations == 1, $totalCombinations <= 0];
  }

  /**
   * Sort queries by number of records for the table associated with them.
   *
   * @param array $tableQueries
   */
  public static function orderByTableCount(array &$tableQueries): void {
    uksort($tableQueries, [__CLASS__, 'isTableBigger']);
  }

}
