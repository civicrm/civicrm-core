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
class CRM_Event_Form_Task_SearchTaskHookSample extends CRM_Event_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and participation details of participants
    $participantIDs = implode(',', $this->_participantIds);

    $query = "
     SELECT p.fee_amount as amount,
            p.register_date as register_date,
            p.source as source,
            ct.display_name as display_name
       FROM civicrm_participant p
 INNER JOIN civicrm_contact ct ON ( p.contact_id = ct.id )
      WHERE p.id IN ( $participantIDs )";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $rows[] = [
        'display_name' => $dao->display_name,
        'amount' => $dao->amount,
        'register_date' => CRM_Utils_Date::customFormat($dao->register_date),
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
