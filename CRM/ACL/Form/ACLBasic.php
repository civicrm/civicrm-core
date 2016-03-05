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
class CRM_ACL_Form_ACLBasic extends CRM_Admin_Form {

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = array();

    if ($this->_id ||
      $this->_id === '0'
    ) {
      $defaults['entity_id'] = $this->_id;

      $query = "
SELECT object_table
  FROM civicrm_acl
 WHERE entity_id = %1
   AND ( object_table NOT IN ( 'civicrm_saved_search', 'civicrm_uf_group', 'civicrm_custom_group' ) )
";
      $params = array(1 => array($this->_id, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $defaults['object_table'] = array();
      while ($dao->fetch()) {
        $defaults['object_table'][$dao->object_table] = 1;
      }
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $permissions = array_flip(CRM_Core_Permission::basicPermissions());
    $this->addCheckBox('object_table',
      ts('ACL Type'),
      $permissions,
      NULL, NULL, TRUE, NULL,
      array('</td><td>', '</td></tr><tr><td>')
    );

    $label = ts('Role');
    $role = array(
      '-1' => ts('- select role -'),
      '0' => ts('Everyone'),
    ) + CRM_Core_OptionGroup::values('acl_role');
    $entityID = &$this->add('select', 'entity_id', $label, $role, TRUE);

    if ($this->_id) {
      $entityID->freeze();
    }
    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(array('CRM_ACL_Form_ACLBasic', 'formRule'));
  }

  /**
   * @param array $params
   *
   * @return array|bool
   */
  public static function formRule($params) {
    if ($params['entity_id'] == -1) {
      $errors = array('entity_id' => ts('Role is a required field'));
      return $errors;
    }

    return TRUE;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_ACL_BAO_Cache::resetCache();

    $params = $this->controller->exportValues($this->_name);
    if ($this->_id ||
      $this->_id === '0'
    ) {
      $query = "
DELETE
  FROM civicrm_acl
 WHERE entity_id = %1
   AND ( object_table NOT IN ( 'civicrm_saved_search', 'civicrm_uf_group', 'civicrm_custom_group' ) )
";
      $deleteParams = array(1 => array($this->_id, 'Integer'));
      CRM_Core_DAO::executeQuery($query, $deleteParams);

      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Core_Session::setStatus(ts('Selected ACL has been deleted.'), ts('Record Deleted'), 'success');
        return;
      }
    }

    $params['operation'] = 'All';
    $params['deny'] = 0;
    $params['is_active'] = 1;
    $params['entity_table'] = 'civicrm_acl_role';
    $params['name'] = 'Core ACL';

    foreach ($params['object_table'] as $object_table => $value) {
      if ($value) {
        $newParams = $params;
        unset($newParams['object_table']);
        $newParams['object_table'] = $object_table;
        CRM_ACL_BAO_ACL::create($newParams);
      }
    }
  }

}
