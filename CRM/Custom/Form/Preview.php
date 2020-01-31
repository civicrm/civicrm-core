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
      elseif (empty($defaults['is_active'])) {
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
