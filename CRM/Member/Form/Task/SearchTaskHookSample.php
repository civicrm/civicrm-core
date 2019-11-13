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
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Member_Form_Task_SearchTaskHookSample extends CRM_Member_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and membership details of all selected contacts
    $memberIDs = implode(',', $this->_memberIds);

    $query = "
    SELECT mem.start_date  as start_date,
           mem.end_date    as end_date,
           mem.source      as source,
           ct.display_name as display_name
FROM       civicrm_membership mem
INNER JOIN civicrm_contact ct ON ( mem.contact_id = ct.id )
WHERE      mem.id IN ( $memberIDs )";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $rows[] = [
        'display_name' => $dao->display_name,
        'start_date' => CRM_Utils_Date::customFormat($dao->start_date),
        'end_date' => CRM_Utils_Date::customFormat($dao->end_date),
        'source' => $dao->source,
      ];
    }
    $this->assign('rows', $rows);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'done',
        'name' => ts('Done'),
        'isDefault' => TRUE,
      ],
    ]);
  }

}
