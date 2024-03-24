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
 * Form helper class for custom data section.
 */
class CRM_Contact_Form_Inline_CustomData extends CRM_Contact_Form_Inline {

  /**
   * Custom group id.
   *
   * @var int
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

    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE, NULL);
    $this->assign('customGroupId', $this->_groupID);
    $customRecId = CRM_Utils_Request::retrieve('customRecId', 'Positive', $this, FALSE, 1);
    $cgcount = CRM_Utils_Request::retrieve('cgcount', 'Positive', $this, FALSE, 1);
    $subType = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId, ',');
    $this->preProcessCustomData(NULL, $subType, $cgcount,
      $this->_contactType, $this->_contactId);
  }

  /**
   *
   * Previously shared function.
   *
   * @param null|string $extendsEntityColumn
   *   Additional filter on the type of custom data to retrieve - e.g for
   *   participant data this could be a value representing role.
   * @param null|string $subType
   * @param null|int $groupCount
   * @param null $type
   * @param null|int $entityID
   * @param null $onlySubType
   * @param bool $isLoadFromCache
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated see https://github.com/civicrm/civicrm-core/pull/29241 for preferred approach - basically
   * 1) at the tpl layer use CRM/common/customDataBlock.tpl
   * 2) to make the fields available for postProcess
   * if ($this->isSubmitted()) {
   *   $this->addCustomDataFieldsToForm('FinancialAccount');
   * }
   * 3) pass getSubmittedValues() to CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(), $this->_id, 'FinancialAccount');
   *  to ensure any money or number fields are handled for localisation
   */
  private function preProcessCustomData($extendsEntityColumn = NULL, $subType = NULL,
    $groupCount = NULL, $type = NULL, $entityID = NULL, $onlySubType = NULL, $isLoadFromCache = TRUE
  ) {
    $form = $this;
    if (!$type) {
      CRM_Core_Error::deprecatedWarning('type should be passed in');
      $type = CRM_Utils_Request::retrieve('type', 'String', $form);
    }

    if (!isset($subType)) {
      $subType = CRM_Utils_Request::retrieve('subType', 'String', $form);
    }
    if ($subType === 'null') {
      // Is this reachable?
      $subType = NULL;
    }
    $extendsEntityColumn = $extendsEntityColumn ?: CRM_Utils_Request::retrieve('subName', 'String', $form);
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
    if (!is_array($subType) && str_contains(($subType ?? ''), CRM_Core_DAO::VALUE_SEPARATOR)) {
      CRM_Core_Error::deprecatedWarning('Using a CRM_Core_DAO::VALUE_SEPARATOR separated subType deprecated, use a comma-separated string instead.');
      $subType = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',', trim($subType, CRM_Core_DAO::VALUE_SEPARATOR));
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($type,
      NULL,
      $form->_entityId,
      $gid,
      $subType,
      $extendsEntityColumn,
      $isLoadFromCache,
      $onlySubType
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
   * Build the form object elements for custom data.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addElement('hidden', 'hidden_custom', 1);
    $this->addElement('hidden', "hidden_custom_group_count[{$this->_groupID}]", $this->_groupCount);
    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->_groupTree);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($this->_groupTree, $defaults, FALSE, FALSE, $this->get('action'));
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // Process / save custom data
    // Get the form values and groupTree
    $params = $this->getSubmittedValues();
    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_contact',
      $this->_contactId,
      $this->_entityType
    );

    $this->log();

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    $this->response();
  }

}
