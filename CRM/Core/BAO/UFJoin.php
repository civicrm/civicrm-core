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
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_BAO_UFJoin extends CRM_Core_DAO_UFJoin {

  /**
   * Takes an associative array and creates a uf join object.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
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
    $module = CRM_Utils_Array::value('module', $params);
    $entityTable = CRM_Utils_Array::value('entity_table', $params);
    $entityID = CRM_Utils_Array::value('entity_id', $params);

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
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    // user reg / my account can have multiple entries, so we return if thats
    // the case. (since entity_table/id is empty in those cases
    if (!$dao->entity_table ||
      !$dao->entity_id
    ) {
      return NULL;
    }
    $dao->weight = CRM_Utils_Array::value('weight', $params);
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

    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    $dao->weight = CRM_Utils_Array::value('weight', $params);
    $dao->module = CRM_Utils_Array::value('module', $params);
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
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
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

}
