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
class CRM_Dedupe_BAO_Exception extends CRM_Dedupe_DAO_Exception {

  /**
   * Create a dedupe exception record.
   *
   * @param array $params
   *
   * @return \CRM_Dedupe_BAO_Exception
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Exception', CRM_Utils_Array::value('id', $params), $params);
    $contact1 = CRM_Utils_Array::value('contact_id1', $params);
    $contact2 = CRM_Utils_Array::value('contact_id2', $params);
    $dao = new CRM_Dedupe_BAO_Exception();
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

    CRM_Utils_Hook::post($hook, 'Exception', $dao->id, $dao);
    return $dao;
  }

}
