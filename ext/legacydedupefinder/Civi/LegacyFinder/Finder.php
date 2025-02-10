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
    // make sure we've got a fetched db record, not sure if this is enforced
    $ruleGroup->find(TRUE);
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
    \CRM_Core_DAO::executeQuery($ruleGroup->tableDropQuery());
  }

  public static function findDuplicates(GenericHookEvent $event): void {
    $event->stopPropagation();

    if (!empty($event->dedupeResults['handled'])) {
      // @todo - in time we can deprecate this & expect them to use stopPropagation().
      return;
    }
    $rgBao = new \CRM_Dedupe_BAO_DedupeRuleGroup();
    $rgBao->params = $event->dedupeParams['match_params'];
    // make sure we've got a fetched dbrecord, not sure if this is enforced
    $rgBao->find(TRUE);
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
    \CRM_Core_DAO::executeQuery($rgBao->tableDropQuery());
    $event->dedupeResults['ids'] = array_diff($dupes, $event->dedupeParams['excluded_contact_ids']);
  }

  /**
   * Fill the dedupe finder table.
   *
   * @internal do not access from outside core
   *
   * @param \CRM_Dedupe_BAO_DedupeRuleGroup $ruleGroup
   * @param int $id
   * @param array $contactIDs
   * @param array $params
   *
   * @return false|string
   * @throws \CRM_Core_Exception
   */
  private static function fillTable($ruleGroup, int $id, array $contactIDs, array $params) {
    $optimizer = new FinderQueryOptimizer($id, $contactIDs, $params);
    // Reserved Rule Groups can optionally get special treatment by
    // implementing an optimization class and returning a query array.
    if ($optimizer->isUseReservedQuery()) {
      $tableQueries = $optimizer->getReservedQuery();
    }
    else {
      $tableQueries = $optimizer->getRuleQueries();
    }
    // if there are no rules in this rule group
    // add an empty query fulfilling the pattern
    if (!$tableQueries) {
      // Just for the hook.... (which is deprecated).
      $ruleGroup->noRules = TRUE;
    }
    \CRM_Utils_Hook::dupeQuery($ruleGroup, 'table', $tableQueries);

    if (empty($tableQueries)) {
      return FALSE;
    }
    $threshold = $ruleGroup->threshold;

    return $ruleGroup->runTablesQuery($params, $tableQueries, $threshold);
  }

}
