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
 * form helper class for custom data section
 */
class CRM_Contact_Form_Inline_CustomData extends CRM_Contact_Form_Inline {

  /**
   * custom group id
   *
   * @int
   * @access public
   */
  public $_groupID;

  /**
   * entity type of the table id
   *
   * @var string
   */
  protected $_entityType;

  /**
   * call preprocess
   */
  public function preProcess() {
    parent::preProcess();

    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE, NULL, $_REQUEST);
    $this->assign('customGroupId', $this->_groupID);
    $customRecId = CRM_Utils_Request::retrieve('customRecId', 'Positive', $this, FALSE, 1, $_REQUEST);
    $cgcount = CRM_Utils_Request::retrieve('cgcount', 'Positive', $this, FALSE, 1, $_REQUEST);
    $subType = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId, ',');
    CRM_Custom_Form_CustomData::preProcess($this, null, $subType, $cgcount,
      $this->_contactType, $this->_contactId);
  }

  /**
   * build the form elements for custom data
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Custom_Form_CustomData::buildQuickForm($this);
  }

  /**
   * set defaults for the form
   *
   * @return array
   * @access public
   */
  public function setDefaultValues() {
    return CRM_Custom_Form_CustomData::setDefaultValues($this);
  }

  /**
   * process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // Process / save custom data
    // Get the form values and groupTree
    $params = $this->controller->exportValues($this->_name);
    CRM_Core_BAO_CustomValueTable::postProcess($params,
      $this->_groupTree[$this->_groupID]['fields'],
      'civicrm_contact',
      $this->_contactId,
      $this->_entityType
    );

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();

    $this->response();
  }
}
