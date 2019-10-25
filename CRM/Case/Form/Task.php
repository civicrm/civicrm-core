<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form task actions for CiviCase.
 */
class CRM_Case_Form_Task extends CRM_Core_Form_Task {

  /**
   * Must be set to entity table name (eg. civicrm_participant) by child class
   * @var string
   */
  public static $tableName = 'civicrm_case';
  /**
   * Must be set to entity shortname (eg. event)
   * @var string
   */
  public static $entityShortname = 'case';

  /**
   * @inheritDoc
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_Form_Task::getContactIDsFromComponent($this->_entityIds,
      'civicrm_case_contact', 'case_id'
    );
  }
  // order by case id return to getContactIDsFromComponent
  public function orderBy() {
    $order_array = 'ORDER BY ';
    foreach ($this->_entityIds as $item) {
       $order_array .= case_id . ' = ' . $item . ' DESC,';
     }
    return $order_array = trim($order_array, ',');
  }
  /**
   * Get the query mode (eg. CRM_Core_BAO_Query::MODE_CASE)
   *
   * @return int
   */
  public function getQueryMode() {
    return CRM_Contact_BAO_Query::MODE_CASE;
  }

}
