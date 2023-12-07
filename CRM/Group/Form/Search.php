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
class CRM_Group_Form_Search extends CRM_Core_Form {

  public function preProcess() {
    // This variable does not appear to be set in core civicrm
    // and is possibly obsolete? It probably relates to the multisite extension.
    $this->expectedSmartyVariables[] = 'showOrgInfo';
    parent::preProcess();

    CRM_Core_Resources::singleton()->addPermissions('edit groups');
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults['group_status[1]'] = 1;
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Group Name'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
    );

    $this->add('text', 'created_by', ts('Created By (Name)'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
    );

    $optionTypes = [
      '1' => ts('Smart Group'),
      '2' => ts('Regular Group'),
    ];
    $this->add('select', 'saved_search', ts('Group Type'),
      ['' => ts('- any -')] + $optionTypes
    );

    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Joomla') {
      unset($groupTypes['Access Control']);
    }

    $this->addCheckBox('group_type_search',
      ts('Type'),
      $groupTypes,
      NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
    );

    $this->add('select', 'visibility', ts('Visibility'),
      ['' => ts('- any visibility -')] + CRM_Core_SelectValues::ufVisibility(TRUE)
    );

    $groupStatuses = [ts('Enabled') => 1, ts('Disabled') => 2];
    $this->addCheckBox('group_status',
      ts('Status'),
      $groupStatuses,
      NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
    );

    $componentModes = CRM_Contact_Form_Search::getModeSelect();
    if (count($componentModes) > 1) {
      $this->add('select',
        'component_mode',
        ts('View Results As'),
        $componentModes,
        FALSE,
        ['class' => 'crm-select2']
      );
    }

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
    $this->assign('suppressForm', TRUE);
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = ['title', 'created_by', 'group_type', 'visibility', 'active_status', 'inactive_status', 'component_mode'];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }

}
