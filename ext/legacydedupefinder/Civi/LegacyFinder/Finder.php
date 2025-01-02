<?php

namespace Civi\LegacyFinder;

use Civi\Core\Event\GenericHookEvent;

class Finder {

  /**
   * This function exists to provide legacy hook support for finding duplicates.
   *
   * @return void
   */
  public static function findExistingDuplicates(GenericHookEvent $event) {
    $event->stopPropagation();
    $ruleGroupIDs = $event->ruleGroupIDs;
    $ruleGroup = new \CRM_Dedupe_BAO_DedupeRuleGroup();
    $ruleGroup->id = reset($ruleGroupIDs);
    $contactIDs = [];
    $whereClauses = $event->whereClauses;
    if (!empty($whereClauses)) {
      foreach ($whereClauses as $whereClause) {
        if ($whereClause[0] === 'id' && $whereClause[1] === 'IN') {
          $contactIDs = $whereClause[2];
        }
      }
    }
    if (!$ruleGroup->fillTable($ruleGroup->id, $contactIDs, [])) {
      return;
    }
    $dao = \CRM_Core_DAO::executeQuery($ruleGroup->thresholdQuery($event->checkPermissions));
    $duplicates = [];
    while ($dao->fetch()) {
      $duplicates[] = ['entity_id1' => $dao->id1, 'entity_id2' => $dao->id2, 'weight' => $dao->weight];
    }
    $event->duplicates = $duplicates;
    \CRM_Core_DAO::executeQuery($ruleGroup->tableDropQuery());
  }

}
