<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Activity_Form_Task_SearchTaskHookSample extends CRM_Activity_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    $rows = array();
    // display name and activity details of all selected contacts
    $activityIDs = implode(',', $this->_activityHolderIds);
    $query = "
    SELECT at.subject      as subject,
           ov.label        as activity_type,
           at.activity_date_time as activity_date,  
           ct.display_name as display_name
      FROM civicrm_activity at
INNER JOIN civicrm_contact ct ON ( at.source_contact_id = ct.id )   
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
   * Function to actually build the form
   *
   * @return None
   * @access public
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

