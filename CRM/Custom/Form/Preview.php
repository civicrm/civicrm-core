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
 * This class generates form components for previewing custom data
 *
 */
class CRM_Custom_Form_Preview extends CRM_Core_Form {

  /**
   * @var int
   */
  protected $_groupId;

  /**
   * @var int
   */
  protected $_fieldId;

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
    // Get field id if previewing a single field
    $this->_fieldId = CRM_Utils_Request::retrieve('fid', 'Positive', $this);

    // Single field preview
    if ($this->_fieldId) {
      $defaults = [];
      $params = ['id' => $this->_fieldId];
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $defaults);
      $this->_groupId = $defaults['custom_group_id'];

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
    // Group preview
    else {
      $this->_groupId = CRM_Utils_Request::retrieve('gid', 'Positive', $this, TRUE);
      $groupTree = CRM_Core_BAO_CustomGroup::getCustomGroupDetail($this->_groupId);
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
      foreach ($this->_groupTree[$this->_groupId]['fields'] as $field) {
        //add the form elements
        CRM_Core_BAO_CustomField::addQuickFormElement($this, $field['element_name'], $field['id'], !empty($field['is_required']));
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
