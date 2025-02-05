<?php

use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CRM_Dedupe_BAO_Finder implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_findExistingDuplicates' => ['findExistingDuplicates', -5],
    ];
  }

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

}
