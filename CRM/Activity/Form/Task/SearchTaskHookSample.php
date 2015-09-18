<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
    $rows = array();
    // display name and activity details of all selected contacts
    $activityIDs = implode(',', $this->_activityHolderIds);

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
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

    $dao = CRM_Core_DAO::executeQuery($query,
      CRM_Core_DAO::$_nullArray
    );

    while ($dao->fetch()) {
      $rows[] = array(
        'subject' => $dao->subject,
        'activity_type' => $dao->activity_type,
        'activity_date' => $dao->activity_date,
        'display_name' => $dao->display_name,
      );
    }
    $this->assign('rows', $rows);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'done',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

}
