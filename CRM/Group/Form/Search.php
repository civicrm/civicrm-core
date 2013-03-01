<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Group_Form_Search extends CRM_Core_Form {

  public function preProcess() {
    parent::preProcess();
  }

  function setDefaultValues() {
    $defaults = array();
    $defaults['group_status[1]'] = 1;
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
    );

    $this->add('text', 'created_by', ts('Created By'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
    );

    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Joomla') {
      unset($groupTypes['Access Control']);
    }

    $this->addCheckBox('group_type',
      ts('Type'),
      $groupTypes,
      NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
    );

    $this->add('select', 'visibility', ts('Visibility'),
      array('' => ts('- any visibility -')) + CRM_Core_SelectValues::ufVisibility(TRUE)
    );

    $groupStatuses = array(ts('Enabled') => 1, ts('Disabled') => 2);
    $this->addCheckBox('group_status',
      ts('Status'),
      $groupStatuses,
      NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
    );

    $this->addButtons(array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      ));

    parent::buildQuickForm();
    $this->assign('suppressForm', TRUE);
  }

  function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = array('title', 'created_by', 'group_type', 'visibility', 'active_status', 'inactive_status');
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

