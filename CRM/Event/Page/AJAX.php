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
 * This class contains all the function that are called using AJAX
 */
class CRM_Event_Page_AJAX {

  /**
   * Building EventFee combo box.
   * FIXME: This ajax callback could be eliminated in favor of an entityRef field but the priceFieldValue api doesn't currently support filtering on entity_table
   */
  public function eventFee() {
    $name = trim(CRM_Utils_Type::escape($_GET['term'], 'String'));

    if (!$name) {
      $name = '%';
    }

    $whereClause = "cv.label LIKE '$name%' ";

    $query = "SELECT DISTINCT (
cv.label
), cv.id
FROM civicrm_price_field_value cv
LEFT JOIN civicrm_price_field cf ON cv.price_field_id = cf.id
LEFT JOIN civicrm_price_set_entity ce ON ce.price_set_id = cf.price_set_id
WHERE ce.entity_table = 'civicrm_event' AND {$whereClause}";

    $dao = CRM_Core_DAO::executeQuery($query);
    $results = [];
    while ($dao->fetch()) {
      $results[] = ['id' => $dao->id, 'text' => $dao->label];
    }
    CRM_Utils_JSON::output($results);
  }

}
