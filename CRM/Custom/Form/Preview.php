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
      $field = CRM_Core_BAO_CustomField::getField($this->_fieldId);
      $this->_groupId = $field['custom_group_id'];

      if (!empty($field['is_view'])) {
        CRM_Core_Error::statusBounce(ts('This field is view only so it will not display on edit form.'));
      }
      elseif (empty($field['is_active'])) {
        CRM_Core_Error::statusBounce(ts('This field is inactive so it will not display on edit form.'));
      }

      $customGroup = $field['custom_group'];
      // Don't show group-level help on single field display
      unset($customGroup['help_pre'], $customGroup['help_post']);
      unset($field['custom_group']);
      $customGroup['fields'] = [$field['id'] => $field];
      $this->assign('preview_type', 'field');
    }
    // Group preview
    else {
      $this->_groupId = CRM_Utils_Request::retrieve('gid', 'Positive', $this, TRUE);
      $customGroup = CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->_groupId]);
      $this->assign('preview_type', 'group');
    }
    $this->_groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree([$customGroup['id'] => $customGroup], 1, $this);
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
