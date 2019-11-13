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
class CRM_Case_Form_Task_SearchTaskHookSample extends CRM_Case_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and email of all contact ids
    $caseIDs = implode(',', $this->_entityIds);
    $statusId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'case_status', 'id', 'name');
    $query = "
SELECT ct.display_name as display_name,
       cs.start_date   as start_date,
       ov.label as status

FROM  civicrm_case cs
INNER JOIN civicrm_case_contact cc ON ( cs.id = cc.case_id)
INNER JOIN civicrm_contact ct ON ( cc.contact_id = ct.id)
LEFT  JOIN civicrm_option_value ov ON (cs.status_id = ov.value AND ov.option_group_id = {$statusId} )
WHERE cs.id IN ( {$caseIDs} )";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $rows[] = [
        'display_name' => $dao->display_name,
        'start_date' => CRM_Utils_Date::customFormat($dao->start_date),
        'status' => $dao->status,
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
