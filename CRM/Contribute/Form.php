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

      $parentId = $defaults['parent_id'] ?? NULL;
      if ($parentId) {
        $this->assign('parentId', $parentId);
      }
    }
    return $defaults;
  }

}
