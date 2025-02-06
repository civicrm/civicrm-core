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
    if (!$ruleGroup->fillTable($ruleGroup->id, $contactIDs, [])) {
      return;
    }
    $dao = \CRM_Core_DAO::executeQuery($ruleGroup->thresholdQuery($event->checkPermissions));
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
    if (!$rgBao->fillTable($event->dedupeParams['rule_group_id'], [], $event->dedupeParams['match_params'], TRUE)) {
      $event->dedupeResults['ids'] = [];
      return;
    }

    $dao = \CRM_Core_DAO::executeQuery($rgBao->thresholdQuery($event->dedupeParams['check_permission']));
    $dupes = [];
    while ($dao->fetch()) {
      if (isset($dao->id) && $dao->id) {
        $dupes[] = $dao->id;
      }
    }
    \CRM_Core_DAO::executeQuery($rgBao->tableDropQuery());
    $event->dedupeResults['ids'] = array_diff($dupes, $event->dedupeParams['excluded_contact_ids']);
  }

}
