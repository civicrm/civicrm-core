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
 * This form is intended to replace the overloading of many forms to generate a snippet for custom data.
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
    $this->_subType = CRM_Utils_Request::retrieve('subType', 'String');
    $this->_subName = CRM_Utils_Request::retrieve('subName', 'String');
    $this->_groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive');
    $this->_entityId = CRM_Utils_Request::retrieve('entityID', 'Positive');
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive');
    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive');
    $this->_onlySubtype = CRM_Utils_Request::retrieve('onlySubtype', 'Boolean');
    $this->_action = CRM_Utils_Request::retrieve('action', 'Alphanumeric');
    $this->assign('cdType', FALSE);
    $this->assign('cgCount', $this->_groupCount);

    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo();
    if (array_key_exists($this->_type, $contactTypes)) {
      $this->assign('contactId', $this->_entityId);
    }
    if (!is_array($this->_subType) && strstr($this->_subType, CRM_Core_DAO::VALUE_SEPARATOR)) {
      $this->_subType = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',', trim($this->_subType, CRM_Core_DAO::VALUE_SEPARATOR));
    }
    CRM_Custom_Form_CustomData::setGroupTree($this, $this->_subType, $this->_groupID, $this->_onlySubtype);

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
