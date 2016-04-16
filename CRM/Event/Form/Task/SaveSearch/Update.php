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
 * This class provides the functionality to update a saved search
 *
 */
class CRM_Event_Form_Task_SaveSearch_Update extends CRM_Event_Form_Task_SaveSearch {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();

    $this->_id = $this->get('ssID');
    if (!$this->_id) {
      // fetch the value from the group id gid
      $gid = $this->get('gid');
      $this->_id = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $gid, 'saved_search_id');
    }
  }

  /**
   * Set default values for the form.
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {

    $defaults = array();
    $params = array();

    $params = array('saved_search_id' => $this->_id);
    CRM_Contact_BAO_Group::retrieve($params, $defaults);

    return $defaults;
  }

}
