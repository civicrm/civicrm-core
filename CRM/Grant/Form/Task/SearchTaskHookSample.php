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
class CRM_Grant_Form_Task_SearchTaskHookSample extends CRM_Grant_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and grant details of all selectced contacts
    $grantIDs = implode(',', $this->_grantIds);

    $query = "
    SELECT grt.decision_date  as decision_date,
           grt.amount_total   as amount_total,
           grt.amount_granted as amount_granted,
           ct.display_name    as display_name
      FROM civicrm_grant grt
INNER JOIN civicrm_contact ct ON ( grt.contact_id = ct.id )
     WHERE grt.id IN ( $grantIDs )";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $rows[] = [
        'display_name' => $dao->display_name,
        'decision_date' => $dao->decision_date,
        'amount_requested' => $dao->amount_total,
        'amount_granted' => $dao->amount_granted,
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
