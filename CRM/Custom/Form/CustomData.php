<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * this class builds custom data
 */
class CRM_Custom_Form_CustomData {

  /**
   * Generic wrapper to add custom data to a form via a single line in preProcess.
   *
   * $this->getDefaultEntity() must be defined for the form class for this to work.
   *
   * If the postProcess form cannot use the api & instead uses a BAO function it will need.
   *   $params['custom'] = CRM_Core_BAO_CustomField::postProcess($submitted, $this->_id, $this->getDefaultEntity());
   *
   * @param CRM_Core_Form $form
   * @param null|string $subType values stored in civicrm_custom_group.extends_entity_column_value
   *   e.g Student for contact type
   * @param null|string $subName value in civicrm_custom_group.extends_entity_column_id
   * @param null|int $groupCount number of entities that could have custom data
   *
   * @throws \CRM_Core_Exception
   */
  public static function addToForm(&$form, $subType = NULL, $subName = NULL, $groupCount = 1) {
    $entityName = $form->getDefaultEntity();
    $entityID = $form->getEntityId();
    // FIXME: If the form has been converted to use entityFormTrait then getEntitySubTypeId() will exist.
    // However, if it is only partially converted (ie. we've switched customdata to use CRM_Custom_Form_CustomData)
    // it won't, so we check if we have a subtype before calling the function.
    $entitySubType = NULL;
    if ($subType) {
      $entitySubType = $form->getEntitySubTypeId($subType);
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
    // need to assign custom data type and subtype to the template
    $form->assign('customDataType', $entityName);
    $form->assign('customDataSubType', $entitySubType);
    $form->assign('entityID', $entityID);
  }

  /**
   * @param CRM_Core_Form $form
   * @param null|string $subName
   * @param null|string $subType
   * @param null|int $groupCount
   * @param string $type
   * @param null|int $entityID
   * @param null $onlySubType
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcess(
    &$form, $subName = NULL, $subType = NULL,
    $groupCount = NULL, $type = NULL, $entityID = NULL, $onlySubType = NULL
  ) {
    if ($type) {
      $form->_type = $type;
    }
    else {
      $form->_type = CRM_Utils_Request::retrieve('type', 'String', $form);
    }

    if (isset($subType)) {
      $form->_subType = $subType;
    }
    else {
      $form->_subType = CRM_Utils_Request::retrieve('subType', 'String', $form);
    }

    if ($form->_subType == 'null') {
      $form->_subType = NULL;
    }

    if (isset($subName)) {
      $form->_subName = $subName;
    }
    else {
      $form->_subName = CRM_Utils_Request::retrieve('subName', 'String', $form);
    }

    if ($form->_subName == 'null') {
      $form->_subName = NULL;
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
    $getCachedTree = isset($form->_getCachedTree) ? $form->_getCachedTree : TRUE;

    $subType = $form->_subType;
    if (!is_array($subType) && strstr($subType, CRM_Core_DAO::VALUE_SEPARATOR)) {
      $subType = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',', trim($subType, CRM_Core_DAO::VALUE_SEPARATOR));
    }

    self::setGroupTree($form, $subType, $gid, $onlySubType, $getCachedTree);
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
      TRUE,
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
