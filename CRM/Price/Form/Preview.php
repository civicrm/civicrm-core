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
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Price_Form_Preview extends CRM_Core_Form {

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
    $groupId = $this->get('groupId');
    $fieldId = $this->get('fieldId');

    if ($fieldId) {
      $groupTree = CRM_Price_BAO_PriceSet::getSetDetail($groupId);
      $this->_groupTree[$groupId]['fields'][$fieldId] = $groupTree[$groupId]['fields'][$fieldId];
      $this->assign('preview_type', 'field');
      $url = CRM_Utils_System::url('civicrm/admin/price/field', "reset=1&action=browse&sid={$groupId}");
      $breadCrumb = [
        [
          'title' => ts('Price Set Fields'),
          'url' => $url,
        ],
      ];
    }
    else {
      // group preview
      $this->_groupTree = CRM_Price_BAO_PriceSet::getSetDetail($groupId);
      $this->assign('preview_type', 'group');
      $this->assign('setTitle', CRM_Price_BAO_PriceSet::getTitle($groupId));
      $url = CRM_Utils_System::url('civicrm/admin/price', 'reset=1');
      $breadCrumb = [
        [
          'title' => ts('Price Sets'),
          'url' => $url,
        ],
      ];
    }
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  /**
   * Set the default form values.
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = [];
    $groupId = $this->get('groupId');
    $fieldId = $this->get('fieldId');
    if (!empty($this->_groupTree[$groupId]['fields'])) {
      foreach ($this->_groupTree[$groupId]['fields'] as $key => $val) {
        foreach ($val['options'] as $keys => $values) {
          if ($values['is_default']) {
            if ($val['html_type'] == 'CheckBox') {
              $defaults["price_{$key}"][$keys] = 1;
            }
            else {
              $defaults["price_{$key}"] = $keys;
            }
          }
        }
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->assign('groupTree', $this->_groupTree);

    // add the form elements

    foreach ($this->_groupTree as $group) {
      if (is_array($group['fields']) && !empty($group['fields'])) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'price_' . $fieldId;
          if (!empty($field['options'])) {
            CRM_Price_BAO_PriceField::addQuickFormElement($this, $elementName, $fieldId, FALSE, $field['is_required']);
          }
        }
      }
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
