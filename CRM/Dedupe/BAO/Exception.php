<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
