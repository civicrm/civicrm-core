<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This form is intended to replace the overloading of many forms to generate a snippet for custom data.
 */
class CRM_Custom_Form_CustomDataByType extends CRM_Core_Form {

  /**
   * Preprocess function.
   */
  public function preProcess() {

    $this->_type = $this->_cdType = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    $this->_subType = CRM_Utils_Request::retrieve('subType', 'String');
    $this->_subName = CRM_Utils_Request::retrieve('subName', 'String');
    $this->_groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive');
    $this->_entityId = CRM_Utils_Request::retrieve('entityID', 'Positive');
    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive');
    $this->_onlySubtype = CRM_Utils_Request::retrieve('onlySubtype', 'Boolean');
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
    $defaults = array();
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
