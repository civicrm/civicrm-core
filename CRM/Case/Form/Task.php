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
    // @todo Parameters shouldn't be needed and should be class member
    // variables instead, set appropriately by each subclass.
    $this->_contactIds = $this->getContactIDsFromComponent($this->_entityIds,
      'civicrm_case_contact', 'case_id'
    );
  }

  /**
   * Get the query mode (eg. CRM_Core_BAO_Query::MODE_CASE)
   *
   * @return int
   */
  public function getQueryMode() {
    return CRM_Contact_BAO_Query::MODE_CASE;
  }

  /**
   * Override of CRM_Core_Form_Task::orderBy()
   *
   * @return string
   */
  public function orderBy() {
    if (empty($this->_entityIds)) {
      return '';
    }
    $order_array = [];
    foreach ($this->_entityIds as $item) {
      // Ordering by conditional in mysql. This evaluates to 0 or 1, so we
      // need to order DESC to get the '1'.
      $order_array[] = 'case_id = ' . CRM_Core_DAO::escapeString($item) . ' DESC';
    }
    return 'ORDER BY ' . implode(',', $order_array);
  }

}
