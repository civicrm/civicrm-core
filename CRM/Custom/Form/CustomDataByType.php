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
 * This form is loaded when custom data is loaded by ajax.
 *
 * The forms ALSO need to call enough functions from Form_CustomData
 * to ensure the fields they need are added to the form or the values will be
 * ignored in post process (ie. quick form will filter them out).
 *
 * This form never submits & hence has no post process.
 */
class CRM_Custom_Form_CustomDataByType extends CRM_Core_Form {

  /**
   * Contact ID associated with the Custom Data
   *
   * @var int
   */
  public $_contactID = NULL;

  /**
   * Preprocess function.
   */
  public function preProcess() {

    $this->_type = $this->_cdType = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    $subType = CRM_Utils_Request::retrieve('subType', 'String');
    $this->_groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive');
    $this->_entityId = CRM_Utils_Request::retrieve('entityID', 'Positive');
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive');
    $this->_groupID = $groupID = CRM_Utils_Request::retrieve('groupID', 'Positive');
    $onlySubType = CRM_Utils_Request::retrieve('onlySubtype', 'Boolean');
    $this->_action = CRM_Utils_Request::retrieve('action', 'Alphanumeric');
    $this->assign('cdType', FALSE);
    $this->assign('cgCount', $this->_groupCount);

    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo();
    if (array_key_exists($this->_type, $contactTypes)) {
      $this->assign('contactId', $this->_entityId);
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree(CRM_Utils_Request::retrieve('type', 'String'),
      NULL,
      CRM_Utils_Request::retrieve('entityID', 'Positive'),
      $groupID,
      $subType,
      CRM_Utils_Request::retrieve('subName', 'String'),
      TRUE,
      $onlySubType,
      FALSE,
      CRM_Core_Permission::EDIT,
      NULL
    );

    // we should use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $this->_groupCount, $this);

    if (isset($this->_groupTree) && is_array($this->_groupTree)) {
      $keys = array_keys($groupTree);
      foreach ($keys as $key) {
        $this->_groupTree[$key] = $groupTree[$key];
      }
    }
    else {
      $this->_groupTree = $groupTree;
    }

    $this->assign('suppressForm', TRUE);
    $this->controller->_generateQFKey = FALSE;
  }

  /**
   * Set defaults.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($this->_groupTree, $defaults, FALSE, FALSE, $this->get('action'));
    return $defaults;
  }

  /**
   * Build quick form.
   */
  public function buildQuickForm() {
    $this->addElement('hidden', 'hidden_custom', 1);
    $this->addElement('hidden', "hidden_custom_group_count[{$this->_groupID}]", $this->_groupCount);
    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->_groupTree);
  }

}
