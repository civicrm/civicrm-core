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

/**
 *
 */
class CRM_Core_BAO_UFJoin extends CRM_Core_DAO_UFJoin {

  /**
   * This deprecated "create" function alarmingly will DELETE records if you don't pass them in just right!
   *
   * @param array $params
   *
   * @deprecated
   * @return CRM_Core_DAO_UFJoin
   */
  public static function &create($params) {
    // see if a record exists with the same weight
    $id = self::findJoinEntryId($params);
    if ($id) {
      $params['id'] = $id;
    }

    $dao = new CRM_Core_DAO_UFJoin();
    $dao->copyValues($params);
    if ($params['uf_group_id']) {
      $dao->save();
    }
    else {
      $dao->delete();
    }

    return $dao;
  }

  /**
   * @param array $params
   */
  public static function deleteAll(&$params) {
    $module = $params['module'] ?? NULL;
    $entityTable = $params['entity_table'] ?? NULL;
    $entityID = $params['entity_id'] ?? NULL;

    if (empty($entityTable) ||
      empty($entityID) ||
      empty($module)
    ) {
      return;
    }

    $dao = new CRM_Core_DAO_UFJoin();
    $dao->module = $module;
    $dao->entity_table = $entityTable;
    $dao->entity_id = $entityID;
    $dao->delete();
  }

  /**
   * Given an assoc list of params, find if there is a record
   * for this set of params
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return int
   *   or null
   */
  public static function findJoinEntryId(&$params) {
    if (!empty($params['id'])) {
      return $params['id'];
    }

    $dao = new CRM_Core_DAO_UFJoin();

    // CRM-4377 (ab)uses the module column
    if (isset($params['module'])) {
      $dao->module = $params['module'];
    }
    $dao->entity_table = $params['entity_table'] ?? NULL;
    $dao->entity_id = $params['entity_id'] ?? NULL;
    // user reg / my account can have multiple entries, so we return if thats
    // the case. (since entity_table/id is empty in those cases
    if (!$dao->entity_table ||
      !$dao->entity_id
    ) {
      return NULL;
    }
    $dao->weight = $params['weight'] ?? NULL;
    if ($dao->find(TRUE)) {
      return $dao->id;
    }
    return NULL;
  }

  /**
   * Given an assoc list of params, find if there is a record
   * for this set of params and return the group id
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return int
   *   or null
   */
  public static function findUFGroupId(&$params) {

    $dao = new CRM_Core_DAO_UFJoin();

    $dao->entity_table = $params['entity_table'] ?? NULL;
    $dao->entity_id = $params['entity_id'] ?? NULL;
    $dao->weight = $params['weight'] ?? NULL;
    $dao->module = $params['module'] ?? NULL;
    if ($dao->find(TRUE)) {
      return $dao->uf_group_id;
    }
    return NULL;
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function getUFGroupIds(&$params) {

    $dao = new CRM_Core_DAO_UFJoin();

    // CRM-4377 (ab)uses the module column
    if (isset($params['module'])) {
      $dao->module = $params['module'];
    }
    $dao->entity_table = $params['entity_table'] ?? NULL;
    $dao->entity_id = $params['entity_id'] ?? NULL;
    $dao->orderBy('weight asc');
    $dao->find();
    $first = $firstActive = NULL;
    $second = $secondActive = [];

    while ($dao->fetch()) {
      if ($dao->weight == 1) {
        $first = $dao->uf_group_id;
        $firstActive = $dao->is_active;
      }
      else {
        $second[] = $dao->uf_group_id;
        $secondActive[] = $dao->is_active;
      }
    }
    return [$first, $second, $firstActive, $secondActive];
  }

  /**
   * Whitelist of possible values for the entity_table field
   * @return array
   */
  public static function entityTables() {
    return [
      'civicrm_event' => 'Event',
      'civicrm_contribution_page' => 'ContributionPage',
      'civicrm_survey' => 'Survey',
    ];
  }

  /**
   * Override base method which assumes permissions should be based on entity_table.
   *
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @return array
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

}
