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
 * Deprecated class no longer used in core.
 *
 * @deprecated since 5.72 will be removed around 5.92
 */
class CRM_Custom_Form_CustomData {

  /**
   * Generic wrapper to add custom data to a form via a single line in preProcess.
   *
   * @param CRM_Core_Form $form
   * @param null|string $entitySubType values stored in civicrm_custom_group.extends_entity_column_value
   *   e.g Student for contact type
   * @param null|string $subName value in civicrm_custom_group.extends_entity_column_id
   * @param null|int $groupCount number of entities that could have custom data
   * @param null|int $contact_id contact ID associated with the custom data.
   *
   * @throws \CRM_Core_Exception
   * @deprecated - preferred code now is to add to ensure the tpl loads custom
   * data using the ajax template & add code to `buildForm()` like this
   *
   * ```
   * if ($this->isSubmitted()) {
   *   $this->addCustomDataFieldsToForm('Membership', array_filter([
   *   'id' => $this->getMembershipID(),
   *   'membership_type_id' => $this->getSubmittedValue('membership_type_id')
   * ]));
   * }
   * ```
   *
   * $this->getDefaultEntity() must be defined for the form class for this to work.
   *
   * If the postProcess form cannot use the api & instead uses a BAO function it will need.
   *   $params['custom'] = CRM_Core_BAO_CustomField::postProcess($submitted, $this->_id, $this->getDefaultEntity());
   *
   */
  public static function addToForm(&$form, $entitySubType = NULL, $subName = NULL, $groupCount = 1, $contact_id = NULL) {
    $entityName = $form->getDefaultEntity();
    $entityID = $form->getEntityId();
    // If the form has been converted to use entityFormTrait then getEntitySubTypeId() will exist.
    if (method_exists($form, 'getEntitySubTypeId') && empty($entitySubType)) {
      $entitySubType = $form->getEntitySubTypeId();
    }

    if ($form->getAction() == CRM_Core_Action::VIEW) {
      // Viewing custom data (Use with {include file="CRM/Custom/Page/CustomDataView.tpl"} in template)
      $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityName, NULL, $entityID, 0, $entitySubType);
      CRM_Core_BAO_CustomGroup::buildCustomDataView($form, $groupTree, FALSE, NULL, NULL, NULL, $entityID);
    }
    else {
      // Editing custom data (Use with {include file="CRM/common/customDataBlock.tpl"} in template)
      if (!empty($_POST['hidden_custom'])) {
        self::preProcess($form, $subName, $entitySubType, $groupCount, $entityName, $entityID);
        self::buildQuickForm($form);
        self::setDefaultValues($form);
      }
    }
  }

  /**
   * @param CRM_Core_Form $form
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
  public static function preProcess(
    &$form, $extendsEntityColumn = NULL, $subType = NULL,
    $groupCount = NULL, $type = NULL, $entityID = NULL, $onlySubType = NULL, $isLoadFromCache = TRUE
  ) {
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
      $subType,
      $extendsEntityColumn,
      $isLoadFromCache,
      $onlySubType,
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
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function setDefaultValues(&$form) {
    $defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($form->_groupTree, $defaults, FALSE, FALSE, $form->get('action'));
    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $form->addElement('hidden', 'hidden_custom', 1);
    $form->addElement('hidden', "hidden_custom_group_count[{$form->_groupID}]", $form->_groupCount);
    CRM_Core_BAO_CustomGroup::buildQuickForm($form, $form->_groupTree);
  }

  /**
   * Add the group data as a formatted array to the form.
   *
   * Note this is only called from this class in core but it is called
   * from the gdpr extension so rather than clean it up we will deprecate in place
   * and stop calling from core. (Calling functions like this from extensions)
   * is not supported but since we are aware of it we can deprecate rather than
   * remove it).
   *
   * @deprecated since 5.65 will be removed around 5.80.
   *
   * @param CRM_Core_Form $form
   * @param string $subType
   * @param int $gid
   * @param bool $onlySubType
   * @param bool $getCachedTree
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function setGroupTree(&$form, $subType, $gid, $onlySubType = NULL, $getCachedTree = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative - maybe copy & paste to your extension');
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

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($form->_type,
      NULL,
      $form->_entityId,
      $gid,
      $subType,
      $form->_subName,
      $getCachedTree,
      $onlySubType,
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
      return [$form, $groupTree];
    }
    else {
      $form->_groupTree = $groupTree;
      return [$form, $groupTree];
    }
  }

}
