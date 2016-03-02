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

/**
 * This class generates form components for Navigation.
 */
class CRM_Admin_Form_Navigation extends CRM_Admin_Form {

  /**
   * The parent id of the navigation menu.
   */
  protected $_currentParentID = NULL;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->setPageTitle(ts('Menu Item'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Core_BAO_Navigation::retrieve($params, $this->_defaults);
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text',
      'label',
      ts('Title'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Navigation', 'label'),
      TRUE
    );

    $this->add('text', 'url', ts('Url'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Navigation', 'url'));
    $permissions = array();
    foreach (CRM_Core_Permission::basicPermissions(TRUE, TRUE) as $id => $vals) {
      $permissions[] = array('id' => $id, 'label' => $vals[0], 'description' => (array) CRM_Utils_Array::value(1, $vals));
    }
    $this->add('text', 'permission', ts('Permission'),
      array('placeholder' => ts('Unrestricted'), 'class' => 'huge', 'data-select-params' => json_encode(array('data' => $permissions)))
    );

    $operators = array('AND' => ts('AND'), 'OR' => ts('OR'));
    $this->add('select', 'permission_operator', NULL, $operators);

    //make separator location configurable
    $separator = array(ts('None'), ts('After menu element'), ts('Before menu element'));
    $this->add('select', 'has_separator', ts('Separator'), $separator);

    $active = $this->add('advcheckbox', 'is_active', ts('Enabled'));

    if (CRM_Utils_Array::value('name', $this->_defaults) == 'Home') {
      $active->freeze();
    }
    else {
      $parentMenu = CRM_Core_BAO_Navigation::getNavigationList();

      if (isset($this->_id)) {
        unset($parentMenu[$this->_id]);
      }

      // also unset home.
      $homeMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Home', 'id', 'name');
      unset($parentMenu[$homeMenuId]);

      $this->add('select', 'parent_id', ts('Parent'), array('' => ts('Top level')) + $parentMenu, FALSE, array('class' => 'crm-select2'));
    }
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->_defaults;
    if (isset($this->_id)) {
      //Take parent id in object variable to calculate the menu
      //weight if menu parent id changed
      $this->_currentParentID = CRM_Utils_Array::value('parent_id', $this->_defaults);
    }
    else {
      $defaults['permission'] = "access CiviCRM";
    }

    // its ok if there is no element called is_active
    $defaults['is_active'] = ($this->_id) ? $this->_defaults['is_active'] : 1;

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if (isset($this->_id)) {
      $params['id'] = $this->_id;
      $params['current_parent_id'] = $this->_currentParentID;
    }

    $navigation = CRM_Core_BAO_Navigation::add($params);

    // also reset navigation
    CRM_Core_BAO_Navigation::resetNavigation();

    CRM_Core_Session::setStatus(ts('Menu \'%1\' has been saved.',
      array(1 => $navigation->label)
    ), ts('Saved'), 'success');
  }

}
