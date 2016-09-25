<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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
    $rows = array();
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
      $rows[] = array(
        'display_name' => $dao->display_name,
        'decision_date' => $dao->decision_date,
        'amount_requested' => $dao->amount_total,
        'amount_granted' => $dao->amount_granted,
      );
    }
    $this->assign('rows', $rows);
  }

  /**
   * Build the form object.
   *
   * @return void
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
