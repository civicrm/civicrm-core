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
 * Manages dedupe exceptions - ie pairs marked as non-duplicates.
 */
class CRM_Dedupe_BAO_DedupeException extends CRM_Dedupe_DAO_DedupeException {

  /**
   * Create a dedupe exception record.
   *
   * @param array $params
   *
   * @return \CRM_Dedupe_DAO_DedupeException
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'DedupeException', $params['id'] ?? NULL, $params);
    // Also call hook with deprecated entity name
    CRM_Utils_Hook::pre($hook, 'Exception', $params['id'] ?? NULL, $params);
    $contact1 = $params['contact_id1'] ?? NULL;
    $contact2 = $params['contact_id2'] ?? NULL;
    $dao = new CRM_Dedupe_BAO_DedupeException();
    $dao->copyValues($params);
    if ($contact1 && $contact2) {
      CRM_Core_DAO::singleValueQuery("
        DELETE FROM civicrm_prevnext_cache
        WHERE (entity_id1 = %1 AND entity_id2 = %2)
        OR (entity_id1 = %2 AND entity_id2 = %2)",
        [1 => [$contact1, 'Integer'], 2 => [$contact2, 'Integer']]
      );
      if ($contact2 < $contact1) {
        // These are expected to be saved lowest first.
        $dao->contact_id1 = $contact2;
        $dao->contact_id2 = $contact1;
      }
    }
    $dao->save();
    CRM_Utils_Hook::post($hook, 'DedupeException', $dao->id, $dao);
    // Also call hook with deprecated entity name
    CRM_Utils_Hook::post($hook, 'Exception', $dao->id, $dao);
    return $dao;
  }

}
