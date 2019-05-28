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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components generic to Contribution admin.
 */
class CRM_Contribute_Form extends CRM_Admin_Form {

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      if (!empty($this->_BAOName)) {
        $baoName = $this->_BAOName;
        $baoName::retrieve($params, $defaults);
      }
    }
    if ($this->_action == CRM_Core_Action::DELETE && !empty($defaults['name'])) {
      $this->assign('delName', $defaults['name']);
    }
    elseif ($this->_action == CRM_Core_Action::ADD) {
      $condition = " AND is_default = 1";
      $values = CRM_Core_OptionGroup::values('financial_account_type', FALSE, FALSE, FALSE, $condition);
      $defaults['financial_account_type_id'] = array_keys($values);
      $defaults['is_active'] = 1;

    }
    elseif ($this->_action & CRM_Core_Action::UPDATE) {
      if (!empty($defaults['contact_id']) || !empty($defaults['created_id'])) {
        $contactID = !empty($defaults['created_id']) ? $defaults['created_id'] : $defaults['contact_id'];
        $this->assign('created_id', $contactID);
        $this->assign('organisationId', $contactID);
      }

      if ($parentId = CRM_Utils_Array::value('parent_id', $defaults)) {
        $this->assign('parentId', $parentId);
      }
    }
    return $defaults;
  }

}
