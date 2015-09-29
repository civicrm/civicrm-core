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
 * This class generates form components for custom data.
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 */
class CRM_Contact_Form_CustomData extends CRM_Core_Form {

  /**
   * The table id, used when editing/creating custom data
   *
   * @var int
   */
  protected $_tableId;

  /**
   * Entity type of the table id
   *
   * @var string
   */
  protected $_entityType;

  /**
   * Entity sub type of the table id
   *
   * @var string
   */
  protected $_entitySubType;

  /**
   * The group tree data
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
   * Custom group id
   *
   * @int
   */
  public $_groupID;

  public $_multiRecordDisplay;

  public $_copyValueId;

  /**
   * Pre processing work done here.
   *
   * Gets session variables for table name, id of entity in table, type of entity and stores them.
   */
  public function preProcess() {
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    $this->_multiRecordDisplay = CRM_Utils_Request::retrieve('multiRecordDisplay', 'String', $this);
    if ($this->_cdType || $this->_multiRecordDisplay == 'single') {
      if ($this->_cdType) {
        $this->assign('cdType', TRUE);
      }
      // NOTE : group id is not stored in session from within CRM_Custom_Form_CustomData::preProcess func
      // this is due to some condition inside it which restricts it from saving in session
      // so doing this for multi record edit action
      $entityId = CRM_Utils_Request::retrieve('entityID', 'Positive', $this);
      if (!empty($entityId)) {
        $subType = CRM_Contact_BAO_Contact::getContactSubType($entityId, ',');
      }
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $subType, NULL, NULL, $entityId);
      if ($this->_multiRecordDisplay) {
        $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this);
        $this->_tableID = $this->_entityId;
        $this->_contactType = CRM_Contact_BAO_Contact::getContactType($this->_tableID);
        $mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
        $hasReachedMax = CRM_Core_BAO_CustomGroup::hasReachedMaxLimit($this->_groupID, $this->_tableID);
        if ($hasReachedMax && $mode == 'add') {
          CRM_Core_Error::statusBounce(ts('The maximum record limit is reached'));
        }
        $this->_copyValueId = CRM_Utils_Request::retrieve('copyValueId', 'Positive', $this);

        $groupTitle = CRM_Core_BAO_CustomGroup::getTitle($this->_groupID);
        $mode = CRM_Utils_Request::retrieve('mode', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET');
        $mode = ucfirst($mode);
        CRM_Utils_System::setTitle(ts('%1 %2 Record', array(1 => $mode, 2 => $groupTitle)));

        if (!empty($_POST['hidden_custom'])) {
          $this->assign('postedInfo', TRUE);
        }
      }
      return;
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
    if (!empty($_POST['hidden_custom'])) {
      for ($i = 0; $i <= $_POST['hidden_custom_group_count'][$this->_groupID]; $i++) {
        CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_contactSubType, $i);
        CRM_Custom_Form_CustomData::buildQuickForm($this);
        CRM_Custom_Form_CustomData::setDefaultValues($this);
      }
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_cdType || $this->_multiRecordDisplay == 'single') {
      // buttons display for multi-valued fields to perform independednt actions
      if ($this->_multiRecordDisplay) {
        $isMultiple = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
          $this->_groupID,
          'is_multiple'
        );
        if ($isMultiple) {
          $this->assign('multiRecordDisplay', $this->_multiRecordDisplay);
          $saveButtonName = $this->_copyValueId ? ts('Save a Copy') : ts('Save');
          $this->addButtons(array(
              array(
                'type' => 'upload',
                'name' => $saveButtonName,
                'isDefault' => TRUE,
              ),
              array(
                'type' => 'upload',
                'name' => ts('Save and New'),
                'subName' => 'new',
              ),
              array(
                'type' => 'cancel',
                'name' => ts('Cancel'),
              ),
            )
          );
        }
      }
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
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    if ($this->_cdType || $this->_multiRecordDisplay == 'single') {
      if ($this->_copyValueId) {
        // cached tree is fetched
        $groupTree = &CRM_Core_BAO_CustomGroup::getTree($this->_type,
          $this,
          $this->_entityId,
          $this->_groupID
        );
        $valueIdDefaults = array();
        $groupTreeValueId = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $this->_copyValueId, $this);
        CRM_Core_BAO_CustomGroup::setDefaults($groupTreeValueId, $valueIdDefaults, FALSE, FALSE, $this->get('action'));
        $tableId = $groupTreeValueId[$this->_groupID]['table_id'];
        foreach ($valueIdDefaults as $valueIdElementName => $value) {
          // build defaults for COPY action for new record saving
          $valueIdElementNamePieces = explode('_', $valueIdElementName);
          $valueIdElementNamePieces[2] = "-{$this->_groupCount}";
          $elementName = implode('_', $valueIdElementNamePieces);
          $customDefaultValue[$elementName] = $value;
        }
      }
      else {
        $customDefaultValue = CRM_Custom_Form_CustomData::setDefaultValues($this);
      }
      return $customDefaultValue;
    }

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree($this->_contactType,
      $this,
      $this->_tableID,
      $this->_groupID,
      $this->_contactSubType
    );

    if (empty($_POST['hidden_custom_group_count'])) {
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
   */
  public function postProcess() {
    // Get the form values and groupTree
    $params = $this->controller->exportValues($this->_name);

    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_contact',
      $this->_tableID,
      $this->_entityType
    );
    $table = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $this->_groupID, 'table_name');
    $cgcount = CRM_Core_BAO_CustomGroup::customGroupDataExistsForEntity($this->_tableID, $table, TRUE);
    $cgcount += 1;
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('upload', 'new')) {
      CRM_Core_Session::singleton()
        ->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/cd/edit', "reset=1&type={$this->_contactType}&groupID={$this->_groupID}&entityID={$this->_tableID}&cgcount={$cgcount}&multiRecordDisplay=single&mode=add"));
    }

    // Add entry in the log table
    CRM_Core_BAO_Log::register($this->_tableID,
      'civicrm_contact',
      $this->_tableID
    );

    if (CRM_Core_Resources::isAjaxMode()) {
      $this->ajaxResponse += CRM_Contact_Form_Inline::renderFooter($this->_tableID);
    }

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();
  }

}
