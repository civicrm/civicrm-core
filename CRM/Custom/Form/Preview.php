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
 * $Id$
 *
 */

/**
 * This class generates form components for previewing custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Custom_Form_Preview extends CRM_Core_Form {

  /**
   * The group tree data.
   *
   * @var array
   */
  protected $_groupTree;

  /**
   * Pre processing work done here.
   *
   * gets session variables for group or field id
   *
   * @return void
   */
  public function preProcess() {
    // get the controller vars
    $this->_groupId = $this->get('groupId');
    $this->_fieldId = $this->get('fieldId');
    if ($this->_fieldId) {
      // field preview
      $defaults = [];
      $params = ['id' => $this->_fieldId];
      $fieldDAO = new CRM_Core_DAO_CustomField();
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $defaults);

      if (!empty($defaults['is_view'])) {
        CRM_Core_Error::statusBounce(ts('This field is view only so it will not display on edit form.'));
      }
      elseif (CRM_Utils_Array::value('is_active', $defaults) == 0) {
        CRM_Core_Error::statusBounce(ts('This field is inactive so it will not display on edit form.'));
      }

      $groupTree = [];
      $groupTree[$this->_groupId]['id'] = 0;
      $groupTree[$this->_groupId]['fields'] = [];
      $groupTree[$this->_groupId]['fields'][$this->_fieldId] = $defaults;
      $this->_groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
      $this->assign('preview_type', 'field');
    }
    else {
      $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail($this->_groupId);
      $this->_groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, TRUE, $this);
      $this->assign('preview_type', 'group');
    }
  }

  /**
   * Set the default form values.
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = [];

    CRM_Core_BAO_CustomGroup::setDefaults($this->_groupTree, $defaults, FALSE, FALSE);

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if (is_array($this->_groupTree) && !empty($this->_groupTree[$this->_groupId])) {
      foreach ($this->_groupTree[$this->_groupId]['fields'] as & $field) {
        //add the form elements
        CRM_Core_BAO_CustomField::addQuickFormElement($this, $field['element_name'], $field['id'], CRM_Utils_Array::value('is_required', $field));
      }

      $this->assign('groupTree', $this->_groupTree);
    }
    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Done with Preview'),
        'isDefault' => TRUE,
      ],
    ]);
  }

}
