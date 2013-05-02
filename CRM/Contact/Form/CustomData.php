<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contact_Form_CustomData extends CRM_Core_Form {

  /**
   * The table id, used when editing/creating custom data
   *
   * @var int
   */
  protected $_tableId;

  /**
   * entity type of the table id
   *
   * @var string
   */
  protected $_entityType;

  /**
   * entity sub type of the table id
   *
   * @var string
   * @access protected
   */
  protected $_entitySubType;

  /**
   * the group tree data
   *
   * @var array
   */
  //protected $_groupTree;

  /**
   * Which blocks should we show and hide.
   *
   * @var CRM_Core_ShowHideBlocks
   */
  protected $_showHide;

  /**
   * Array group titles.
   *
   * @var array
   */
  protected $_groupTitle;

  /**
   * Array group display status.
   *
   * @var array
   */
  protected $_groupCollapseDisplay;

  /**
   * custom group id 
   *
   * @int
   * @access public
   */
  public $_groupID;

  /**
   * pre processing work done here.
   *
   * gets session variables for table name, id of entity in table, type of entity and stores them.
   *
   * @param
   *
   * @return void
   *
   * @access public
   *
   */ 
  function preProcess() {
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);

    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE);
    $this->_tableID = CRM_Utils_Request::retrieve('tableId', 'Positive', $this, TRUE);

    $this->_contactType = CRM_Contact_BAO_Contact::getContactType($this->_tableID);
    $this->_contactSubType = CRM_Contact_BAO_Contact::getContactSubType($this->_tableID, ',');
    $this->assign('contact_type', $this->_contactType);
    $this->assign('contact_subtype', $this->_contactSubType);
    list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_tableID);
    CRM_Utils_System::setTitle($displayName, $contactImage . ' ' . $displayName);

    // when custom data is included in this page
    if (CRM_Utils_Array::value('hidden_custom', $_POST)) {
      for ($i = 0; $i <= $_POST['hidden_custom_group_count'][$this->_groupID]; $i++) {
        CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, $i);
        CRM_Custom_Form_CustomData::buildQuickForm($this);
        CRM_Custom_Form_CustomData::setDefaultValues($this);
      }
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    //need to assign custom data type and subtype to the template
    $this->assign('entityID', $this->_tableID);
    $this->assign('groupID', $this->_groupID);

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc
    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Set the default form values
   *
   * @access protected
   *
   * @return array the default array reference
   */
  function setDefaultValues() {
    if ($this->_cdType) {
      $customDefaultValue = CRM_Custom_Form_CustomData::setDefaultValues($this);
      return $customDefaultValue;
    }

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree($this->_contactType,
      $this,
      $this->_tableID,
      $this->_groupID,
      $this->_contactSubType
    );

    if (!CRM_Utils_Array::value('hidden_custom_group_count', $_POST)) {
      // custom data building in edit mode (required to handle multi-value)
      $groupTree = &CRM_Core_BAO_CustomGroup::getTree($this->_contactType, $this, $this->_tableID,
        $this->_groupID, $this->_contactSubType
      );
      $customValueCount = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, TRUE, $this->_groupID);
    }
    else {
      $customValueCount = $_POST['hidden_custom_group_count'][$this->_groupID];
    }

    $this->assign('customValueCount', $customValueCount);

    $defaults = array();
    return $defaults;
  }

  /**
   * Process the user submitted custom data values.
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // Get the form values and groupTree
    $params = $this->controller->exportValues($this->_name);
    CRM_Core_BAO_CustomValueTable::postProcess($params,
      $this->_groupTree[$this->_groupID]['fields'],
      'civicrm_contact',
      $this->_tableID,
      $this->_entityType
    );

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();
  }
}

