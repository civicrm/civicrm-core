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
class CRM_ACL_Form_EntityRole extends CRM_Admin_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $aclRoles = ['' => ts('- select -')] + CRM_Core_OptionGroup::values('acl_role');
    $this->add('select', 'acl_role_id', ts('ACL Role'),
      $aclRoles, TRUE
    );

    $label = ts('Assigned to');
    $group = ['' => ts('- select group -')] + CRM_Core_PseudoConstant::staticGroup(FALSE, 'Access');
    $this->add('select', 'entity_id', $label, $group, TRUE, ['class' => 'crm-select2 huge']);

    $this->add('checkbox', 'is_active', ts('Enabled?'));
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_ACL_BAO_Cache::resetCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_ACL_BAO_EntityRole::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Entity Role has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = $this->controller->exportValues($this->_name);
      if ($this->_id) {
        $params['id'] = $this->_id;
      }

      $params['entity_table'] = 'civicrm_group';
      CRM_ACL_BAO_EntityRole::create($params);
    }
  }

}
