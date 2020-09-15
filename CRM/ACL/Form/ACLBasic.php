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
class CRM_ACL_Form_ACLBasic extends CRM_Admin_Form {

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];

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
      $params = [1 => [$this->_id, 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $defaults['object_table'] = [];
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
      ['</td><td>', '</td></tr><tr><td>']
    );

    $label = ts('Role');
    $role = [
      '-1' => ts('- select role -'),
      '0' => ts('Everyone'),
    ] + CRM_Core_OptionGroup::values('acl_role');
    $entityID = &$this->add('select', 'entity_id', $label, $role, TRUE);

    if ($this->_id) {
      $entityID->freeze();
    }
    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_ACL_Form_ACLBasic', 'formRule']);
  }

  /**
   * @param array $params
   *
   * @return array|bool
   */
  public static function formRule($params) {
    if ($params['entity_id'] == -1) {
      $errors = ['entity_id' => ts('Role is a required field')];
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
      $deleteParams = [1 => [$this->_id, 'Integer']];
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
