<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for Navigation
 *
 */
class CRM_Admin_Form_Navigation extends CRM_Admin_Form {

  /**
   * The parent id of the navigation menu
   */
  protected $_currentParentID = NULL;

  /**
   * Default values
   */
  protected $_defaults = array();

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

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
    $permissions = CRM_Core_Permission::basicPermissions(TRUE);
    $include = &$this->addElement('advmultiselect', 'permission',
      ts('Permission') . ' ', $permissions,
      array(
        'size' => 5,
        'style' => 'width:auto',
        'class' => 'advmultiselect',
      )
    );

    $include->setButtonAttributes('add', array('value' => ts('Add >>')));
    $include->setButtonAttributes('remove', array('value' => ts('<< Remove')));

    $operators = array('AND' => 'AND', 'OR' => 'OR');
    $this->add('select', 'permission_operator', ts('Operator'), $operators);

    //make separator location configurable
    $separator = array(0 => 'None', 1 => 'After Menu Element', 2 => 'Before Menu Element');
    $this->add('select', 'has_separator', ts('Separator?'), $separator);

    $active = $this->add('checkbox', 'is_active', ts('Enabled?'));

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

      $parent = $this->add('select', 'parent_id', ts('Parent'), array('' => ts('-- select --')) + $parentMenu);
    }
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->_defaults;
    if (isset($this->_id)) {
      if (!empty($this->_defaults['permission'])) {
        foreach (explode(',', $this->_defaults['permission']) as $value) {
          $components[$value] = $value;
        }
        $defaults['permission'] = $components;
      }
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
   * Function to process the form
   *
   * @access public
   *
   * @return void
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
