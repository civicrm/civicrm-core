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
   * @var int
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
    $this->_cdType = CRM_Utils_Request::retrieve('type', 'String', $this, FALSE, NULL);
    $this->_multiRecordDisplay = CRM_Utils_Request::retrieve('multiRecordDisplay', 'String', $this);
    $isBuildForm = $this->_cdType && $this->_multiRecordDisplay;
    $this->assign('cdType', (bool) $this->_cdType);
    // This will be false if display type is tab and it's not multivalued.
    if ($isBuildForm) {
      // NOTE : group id is not stored in session from within CRM_Custom_Form_CustomData::preProcess func
      // this is due to some condition inside it which restricts it from saving in session
      // so doing this for multi record edit action
      $entityId = CRM_Utils_Request::retrieve('entityID', 'Positive', $this);
      $this->preProcessCustomData(NULL, CRM_Utils_Request::retrieve('type', 'String', $this), $entityId);
      if ($this->_multiRecordDisplay) {
        $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this);
        $this->_tableID = $entityId;
        $this->_contactType = CRM_Contact_BAO_Contact::getContactType($this->_tableID);
        $mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
        $hasReachedMax = CRM_Core_BAO_CustomGroup::hasReachedMaxLimit($this->_groupID, $this->_tableID);
        if ($hasReachedMax && $mode === 'add') {
          CRM_Core_Error::statusBounce(ts('The maximum record limit is reached'));
        }
        $this->_copyValueId = CRM_Utils_Request::retrieve('copyValueId', 'Positive', $this);

        $groupTitle = CRM_Core_BAO_CustomGroup::getTitle($this->_groupID);
        switch ($mode) {
          case 'add':
            $this->setTitle(ts('Add %1', [1 => $groupTitle]));
            break;

          case 'edit':
            $this->setTitle(ts('Edit %1', [1 => $groupTitle]));
            break;

          case 'copy':
            $this->setTitle(ts('Copy %1', [1 => $groupTitle]));
            break;
        }

        if (!empty($_POST['hidden_custom'])) {
          $this->assign('postedInfo', TRUE);
          CRM_Core_Error::deprecatedWarning("I'm kinda confused - how did we get here?");
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
    [$displayName, $contactImage] = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_tableID);
    $this->setTitle($displayName, $contactImage . ' ' . $displayName);

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      for ($i = 1; $i <= $_POST['hidden_custom_group_count'][$this->_groupID]; $i++) {
        $this->preProcessCustomData($i, $this->_contactType, $this->_tableID);
        $this->addElement('hidden', 'hidden_custom', 1);
        $this->addElement('hidden', "hidden_custom_group_count[{$this->_groupID}]", $this->_groupCount);
        CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->_groupTree);
      }
    }
  }

  /**
   * Previously shared function
   *
   * @param null|int $groupCount
   * @param null $type
   * @param null|int $entityID
   *
   * @throws \CRM_Core_Exception
   * @deprecated see https://github.com/civicrm/civicrm-core/pull/29241 for preferred approach - basically
   * 1) at the tpl layer use CRM/common/customDataBlock.tpl
   * 2) to make the fields available for postProcess
   * if ($this->isSubmitted()) {
   *   $this->addCustomDataFieldsToForm('FinancialAccount');
   * }
   * 3) pass getSubmittedValues() to CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(), $this->_id, 'FinancialAccount');
   *  to ensure any money or number fields are handled for localisation
   */
  private function preProcessCustomData($groupCount = NULL, $type = NULL, $entityID = NULL) {
    $form = $this;

    $extendsEntityColumn = CRM_Utils_Request::retrieve('subName', 'String', $form);
    if ($extendsEntityColumn === 'null') {
      // Is this reachable?
      $extendsEntityColumn = NULL;
    }

    if ($groupCount) {
      $form->_groupCount = $groupCount;
    }
    else {
      $form->_groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive', $form);
    }

    $form->assign('cgCount', $form->_groupCount);

    //carry qf key, since this form is not inhereting core form.
    if ($qfKey = CRM_Utils_Request::retrieve('qfKey', 'String')) {
      $form->assign('qfKey', $qfKey);
    }

    if ($entityID) {
      $form->_entityId = $entityID;
    }
    else {
      $form->_entityId = CRM_Utils_Request::retrieve('entityID', 'Positive', $form);
    }

    $typeCheck = CRM_Utils_Request::retrieve('type', 'String');
    $urlGroupId = CRM_Utils_Request::retrieve('groupID', 'Positive');
    if (isset($typeCheck) && $urlGroupId) {
      $form->_groupID = $urlGroupId;
    }
    else {
      $form->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $form);
    }

    $gid = (isset($form->_groupID)) ? $form->_groupID : NULL;

    $singleRecord = NULL;
    if (!empty($form->_groupCount) && !empty($form->_multiRecordDisplay) && $form->_multiRecordDisplay == 'single') {
      $singleRecord = $form->_groupCount;
    }
    $mode = CRM_Utils_Request::retrieve('mode', 'String', $form);
    // when a new record is being added for multivalued custom fields.
    if (isset($form->_groupCount) && $form->_groupCount == 0 && $mode == 'add' &&
      !empty($form->_multiRecordDisplay) && $form->_multiRecordDisplay == 'single') {
      $singleRecord = 'new';
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($type,
      NULL,
      $form->_entityId,
      $gid,
      CRM_Contact_BAO_Contact::getContactSubType($entityID),
      $extendsEntityColumn,
      TRUE,
      NULL,
      FALSE,
      CRM_Core_Permission::EDIT,
      $singleRecord
    );

    if (property_exists($form, '_customValueCount') && !empty($groupTree)) {
      $form->_customValueCount = CRM_Core_BAO_CustomGroup::buildCustomDataView($form, $groupTree, TRUE, NULL, NULL, NULL, $form->_entityId);
    }
    // we should use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $form->_groupCount, $form);

    if (isset($form->_groupTree) && is_array($form->_groupTree)) {
      $keys = array_keys($groupTree);
      foreach ($keys as $key) {
        $form->_groupTree[$key] = $groupTree[$key];
      }
    }
    else {
      $form->_groupTree = $groupTree;
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
          $this->addButtons([
            [
              'type' => 'upload',
              'name' => $saveButtonName,
              'isDefault' => TRUE,
            ],
            [
              'type' => 'upload',
              'name' => ts('Save and New'),
              'subName' => 'new',
            ],
            [
              'type' => 'cancel',
              'name' => ts('Cancel'),
            ],
          ]);
        }
      }
      $this->addElement('hidden', 'hidden_custom', 1);
      $this->addElement('hidden', "hidden_custom_group_count[{$this->_groupID}]", $this->_groupCount);
      CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->_groupTree);
      return;
    }

    //need to assign custom data type and subtype to the template
    $this->assign('entityID', $this->_tableID);
    $this->assign('groupID', $this->_groupID);

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
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
        $groupTree = CRM_Core_BAO_CustomGroup::getTree('Contact',
          NULL,
          $this->_entityId,
          $this->_groupID,
          [],
          NULL,
          TRUE,
          NULL,
          FALSE,
          CRM_Core_Permission::EDIT,
          $this->_copyValueId
        );
        $valueIdDefaults = [];
        $groupTreeValueId = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $this->_copyValueId, $this);
        CRM_Core_BAO_CustomGroup::setDefaults($groupTreeValueId, $valueIdDefaults, FALSE, FALSE, $this->get('action'));
        foreach ($valueIdDefaults as $valueIdElementName => $value) {
          // build defaults for COPY action for new record saving
          $valueIdElementNamePieces = explode('_', $valueIdElementName);
          $valueIdElementNamePieces[2] = "-{$this->_groupCount}";
          $elementName = implode('_', $valueIdElementNamePieces);
          $customDefaultValue[$elementName] = $value;
        }
      }
      else {
        $customDefaultValue = [];
        CRM_Core_BAO_CustomGroup::setDefaults($this->_groupTree, $customDefaultValue, FALSE, FALSE, $this->get('action'));
      }
      return $customDefaultValue;
    }

    if (empty($_POST['hidden_custom_group_count'])) {
      // custom data building in edit mode (required to handle multi-value)
      $groupTree = CRM_Core_BAO_CustomGroup::getTree($this->_contactType, NULL, $this->_tableID,
        $this->_groupID, $this->_contactSubType
      );
      $customValueCount = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, TRUE, $this->_groupID, NULL, NULL, $this->_tableID);
    }
    else {
      $customValueCount = $_POST['hidden_custom_group_count'][$this->_groupID];
    }

    $this->assign('customValueCount', $customValueCount);

    $defaults = [];
    return $defaults;
  }

  /**
   * Process the user submitted custom data values.
   */
  public function postProcess() {
    // Get the form values and groupTree
    $params = $this->getSubmittedValues();

    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_contact',
      $this->_tableID,
      $this->_entityType
    );
    $table = CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->_groupID])['table_name'];
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

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();
  }

}
