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
class CRM_Activity_Form_Task_SearchTaskHookSample extends CRM_Activity_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $rows = [];
    // display name and activity details of all selected contacts
    $activityIDs = implode(',', $this->_activityHolderIds);

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $query = "
    SELECT at.subject      as subject,
           ov.label        as activity_type,
           at.activity_date_time as activity_date,
           ct.display_name as display_name
      FROM civicrm_activity at
LEFT JOIN  civicrm_activity_contact ac ON ( ac.activity_id = at.id AND ac.record_type_id = {$sourceID} )
INNER JOIN civicrm_contact ct ON ( ac.contact_id = ct.id )
 LEFT JOIN civicrm_option_group og ON ( og.name = 'activity_type' )
 LEFT JOIN civicrm_option_value ov ON (at.activity_type_id = ov.value AND og.id = ov.option_group_id )
     WHERE at.id IN ( $activityIDs )";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $rows[] = [
        'subject' => $dao->subject,
        'activity_type' => $dao->activity_type,
        'activity_date' => $dao->activity_date,
        'display_name' => $dao->display_name,
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
