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
 * Form helper class for custom data section.
 */
class CRM_Contact_Form_Inline_CustomData extends CRM_Contact_Form_Inline {

  /**
   * Custom group id.
   *
   * @int
   */
  public $_groupID;

  /**
   * Entity type of the table id.
   *
   * @var string
   */
  protected $_entityType;

  /**
   * Call preprocess.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE, NULL, $_REQUEST);
    $this->assign('customGroupId', $this->_groupID);
    $customRecId = CRM_Utils_Request::retrieve('customRecId', 'Positive', $this, FALSE, 1, $_REQUEST);
    $cgcount = CRM_Utils_Request::retrieve('cgcount', 'Positive', $this, FALSE, 1, $_REQUEST);
    $subType = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId, ',');
    CRM_Custom_Form_CustomData::preProcess($this, NULL, $subType, $cgcount,
      $this->_contactType, $this->_contactId);
  }

  /**
   * Build the form object elements for custom data.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Custom_Form_CustomData::buildQuickForm($this);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    return CRM_Custom_Form_CustomData::setDefaultValues($this);
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // Process / save custom data
    // Get the form values and groupTree
    $params = $this->controller->exportValues($this->_name);
    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_contact',
      $this->_contactId,
      $this->_entityType
    );

    $this->log();

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();

    $this->response();
  }

}
