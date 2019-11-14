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
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Pledge_Form_Task_SearchTaskHookSample extends CRM_Pledge_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and pledge details of all selected contacts
    $pledgeIDs = implode(',', $this->_pledgeIds);

    $query = "
    SELECT plg.amount      as amount,
           plg.create_date as create_date,
           ct.display_name as display_name
      FROM civicrm_pledge plg
INNER JOIN civicrm_contact ct ON ( plg.contact_id = ct.id )
     WHERE plg.id IN ( $pledgeIDs )";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $rows[] = [
        'display_name' => $dao->display_name,
        'amount' => $dao->amount,
        'create_date' => CRM_Utils_Date::customFormat($dao->create_date),
      ];
    }
    $this->assign('rows', $rows);
  }

  /**
   * Build the form object.
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
