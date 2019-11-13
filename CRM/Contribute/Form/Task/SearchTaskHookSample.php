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
class CRM_Contribute_Form_Task_SearchTaskHookSample extends CRM_Contribute_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and contribution details of all selected contacts
    $contribIDs = implode(',', $this->_contributionIds);

    $query = "
    SELECT co.total_amount as amount,
           co.receive_date as receive_date,
           co.source       as source,
           ct.display_name as display_name
      FROM civicrm_contribution co
INNER JOIN civicrm_contact ct ON ( co.contact_id = ct.id )
     WHERE co.id IN ( $contribIDs )";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $rows[] = [
        'display_name' => $dao->display_name,
        'amount' => $dao->amount,
        'source' => $dao->source,
        'receive_date' => $dao->receive_date,
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
