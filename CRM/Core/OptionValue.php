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
class CRM_Core_OptionValue {

  /**
   * Static field for all the option value information that we can potentially export.
   *
   * @var array
   */
  static $_exportableFields = NULL;

  /**
   * Static field for all the option value information that we can potentially export.
   *
   * @var array
   */
  static $_importableFields = NULL;

  /**
   * Static field for all the option value information that we can potentially export.
   *
   * @var array
   */
  static $_fields = NULL;

  /**
   * Return option-values of a particular group
   *
   * @param array $groupParams
   *   Array containing group fields whose option-values is to retrieved.
   * @param array $links
   *   Has links like edit, delete, disable ..etc.
   * @param string $orderBy
   *   For orderBy clause.
   * @param bool $skipEmptyComponents
   *   Whether to skip OptionValue rows with empty Component name
   *   (i.e. when Extension providing the Component is disabled)
   *
   * @return array
   *   Array of option-values
   *
   */
  public static function getRows($groupParams, $links, $orderBy = 'weight', $skipEmptyComponents = TRUE) {
    $optionValue = array();
    $optionGroupID = NULL;
    $isGroupLocked = FALSE;

    if (!isset($groupParams['id']) || !$groupParams['id']) {
      if ($groupParams['name']) {
        $optionGroup = CRM_Core_BAO_OptionGroup::retrieve($groupParams, $dnc);
        $optionGroupID = $optionGroup->id;
        $isGroupLocked = (bool) $optionGroup->is_locked;
      }
    }
    else {
      $optionGroupID = $groupParams['id'];
    }

    $groupName = CRM_Utils_Array::value('name', $groupParams);
    if (!$groupName && $optionGroupID) {
      $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
        $optionGroupID, 'name', 'id'
      );
    }

    $dao = new CRM_Core_DAO_OptionValue();

    if ($optionGroupID) {
      $dao->option_group_id = $optionGroupID;

      if (in_array($groupName, CRM_Core_OptionGroup::$_domainIDGroups)) {
        $dao->domain_id = CRM_Core_Config::domainID();
      }

      $dao->orderBy($orderBy);
      $dao->find();
    }

    if ($groupName == 'case_type') {
      $caseTypeIds = CRM_Case_BAO_Case::getUsedCaseType();
    }
    elseif ($groupName == 'case_status') {
      $caseStatusIds = CRM_Case_BAO_Case::getUsedCaseStatuses();
    }

    $componentNames = CRM_Core_Component::getNames();
    $visibilityLabels = CRM_Core_PseudoConstant::visibility();
    while ($dao->fetch()) {
      $optionValue[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $optionValue[$dao->id]);
      if (!empty($optionValue[$dao->id]['component_id']) &&
        empty($componentNames[$optionValue[$dao->id]['component_id']]) &&
        $skipEmptyComponents
      ) {
        unset($optionValue[$dao->id]);
        continue;
      }
      // form all action links
      $action = array_sum(array_keys($links));

      // update enable/disable links depending on if it is is_reserved or is_active
      if ($dao->is_reserved) {
        $action = CRM_Core_Action::UPDATE;
      }
      else {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
        if ((($groupName == 'case_type') && in_array($dao->value, $caseTypeIds)) ||
          (($groupName == 'case_status') && in_array($dao->value, $caseStatusIds))
        ) {
          $action -= CRM_Core_Action::DELETE;
        }
      }

      // disallow deletion of option values for locked groups
      if (($action & CRM_Core_Action::DELETE) && $isGroupLocked) {
        $action -= CRM_Core_Action::DELETE;
      }

      $optionValue[$dao->id]['label'] = htmlspecialchars($optionValue[$dao->id]['label']);
      $optionValue[$dao->id]['order'] = $optionValue[$dao->id]['weight'];
      $optionValue[$dao->id]['icon'] = CRM_Utils_Array::value('icon', $optionValue[$dao->id], '');
      $optionValue[$dao->id]['action'] = CRM_Core_Action::formLink($links, $action,
        array(
          'id' => $dao->id,
          'gid' => $optionGroupID,
          'value' => $dao->value,
        ),
        ts('more'),
        FALSE,
        'optionValue.row.actions',
        'optionValue',
        $dao->id
      );

      if (!empty($optionValue[$dao->id]['component_id'])) {
        $optionValue[$dao->id]['component_name'] = $componentNames[$optionValue[$dao->id]['component_id']];
      }
      else {
        $optionValue[$dao->id]['component_name'] = 'Contact';
      }

      if (!empty($optionValue[$dao->id]['visibility_id'])) {
        $optionValue[$dao->id]['visibility_label'] = $visibilityLabels[$optionValue[$dao->id]['visibility_id']];
      }
    }

    return $optionValue;
  }

  /**
   * Add/edit option-value of a particular group
   *
   * @param array $params
   *   Array containing exported values from the invoking form.
   * @param string $optionGroupName
   *   Array containing group fields whose option-values is to retrieved/saved.
   * @param $action
   * @param int $optionValueID Has the id of the optionValue being edited, disabled ..etc.
   *   Has the id of the optionValue being edited, disabled ..etc.
   *
   * @return CRM_Core_DAO_OptionValue
   *
   */
  public static function addOptionValue(&$params, $optionGroupName, $action, $optionValueID) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    // checking if the group name with the given id or name (in $groupParams) exists
    $groupParams = ['name' => $optionGroupName, 'is_active' => 1];
    $optionGroup = CRM_Core_BAO_OptionGroup::retrieve($groupParams, $defaults);

    // if the corresponding group doesn't exist, create one.
    if (!$optionGroup->id) {
      $newOptionGroup = CRM_Core_BAO_OptionGroup::add($groupParams);
      $params['weight'] = 1;
      $optionGroupID = $newOptionGroup->id;
    }
    else {
      $optionGroupID = $optionGroup->id;
      $oldWeight = NULL;
      if ($optionValueID) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $optionValueID, 'weight', 'id');
      }
      $fieldValues = array('option_group_id' => $optionGroupID);
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, CRM_Utils_Array::value('weight', $params), $fieldValues);
    }
    $params['option_group_id'] = $optionGroupID;

    if (($action & CRM_Core_Action::ADD) && !isset($params['value'])) {
      $fieldValues = array('option_group_id' => $optionGroupID);
      // use the next available value
      /* CONVERT(value, DECIMAL) is used to convert varchar
      field 'value' to decimal->integer                    */

      $params['value'] = (int) CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
        $fieldValues,
        'CONVERT(value, DECIMAL)'
      );
    }
    if (!$params['label'] && $params['name']) {
      $params['label'] = $params['name'];
    }

    // set name to label if it's not set - but *only* for ADD action (CRM-3522)
    if (($action & CRM_Core_Action::ADD) && empty($params['name']) && $params['label']) {
      $params['name'] = $params['label'];
    }
    if ($action & CRM_Core_Action::UPDATE) {
      $params['id'] = $optionValueID;
    }
    $optionValue = CRM_Core_BAO_OptionValue::add($params);
    return $optionValue;
  }

  /**
   * Check if there is a record with the same name in the db.
   *
   * @param string $value
   *   The value of the field we are checking.
   * @param string $daoName
   *   The dao object name.
   * @param string $daoID
   *   The id of the object being updated. u can change your name.
   *                          as long as there is no conflict
   * @param int $optionGroupID
   * @param string $fieldName
   *   The name of the field in the DAO.
   * @param bool $domainSpecific
   *   Filter this check to the current domain.
   *   Some optionGroups allow for same labels or same names but
   *   they must be in different domains, so filter the check to
   *   the current domain.
   *
   * @return bool
   *   true if object exists
   */
  public static function optionExists($value, $daoName, $daoID, $optionGroupID, $fieldName = 'name', $domainSpecific) {
    $object = new $daoName();
    $object->$fieldName = $value;
    $object->option_group_id = $optionGroupID;

    if ($domainSpecific) {
      $object->domain_id = CRM_Core_Config::domainID();
    }

    if ($object->find(TRUE)) {
      return ($daoID && $object->id == $daoID) ? TRUE : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Check if there is a record with the same name in the db.
   *
   * @param string $mode
   * @param string $contactType
   *
   * @return array
   */
  public static function getFields($mode = '', $contactType = 'Individual') {
    $key = "$mode $contactType";
    if (empty(self::$_fields[$key]) || !self::$_fields[$key]) {
      self::$_fields[$key] = array();

      $option = CRM_Core_DAO_OptionValue::import();

      foreach (array_keys($option) as $id) {
        $optionName = $option[$id];
      }

      $nameTitle = array();
      if ($mode == 'contribute') {
        // This is part of a move towards standardising option values but we
        // should derive them from the fields array so am deprecating it again...
        // note that the reason this was needed was that payment_instrument_id was
        // not set to exportable.
        $nameTitle = array(
          'payment_instrument' => array(
            'name' => 'payment_instrument',
            'title' => ts('Payment Method'),
            'headerPattern' => '/^payment|(p(ayment\s)?instrument)$/i',
          ),
        );
      }
      elseif ($mode == '') {
        //the fields email greeting and postal greeting are meant only for Individual and Household
        //the field addressee is meant for all contact types, CRM-4575
        if (in_array($contactType, array(
          'Individual',
          'Household',
          'Organization',
          'All',
        ))) {
          $nameTitle = array(
            'addressee' => array(
              'name' => 'addressee',
              'title' => ts('Addressee'),
              'headerPattern' => '/^addressee$/i',
            ),
          );
          $title = array(
            'email_greeting' => array(
              'name' => 'email_greeting',
              'title' => ts('Email Greeting'),
              'headerPattern' => '/^email_greeting$/i',
            ),
            'postal_greeting' => array(
              'name' => 'postal_greeting',
              'title' => ts('Postal Greeting'),
              'headerPattern' => '/^postal_greeting$/i',
            ),
          );
          $nameTitle = array_merge($nameTitle, $title);
        }
      }

      if (is_array($nameTitle)) {
        foreach ($nameTitle as $name => $attribs) {
          self::$_fields[$key][$name] = $optionName;
          list($tableName, $fieldName) = explode('.', $optionName['where']);
          self::$_fields[$key][$name]['where'] = "{$name}.label";
          foreach ($attribs as $k => $val) {
            self::$_fields[$key][$name][$k] = $val;
          }
        }
      }
    }

    return self::$_fields[$key];
  }

  /**
   * Build select query in case of option-values
   *
   * @param $query
   */
  public static function select(&$query) {
    if (!empty($query->_params) || !empty($query->_returnProperties)) {
      $field = self::getFields();
      foreach ($field as $name => $values) {
        if (!empty($values['pseudoconstant'])) {
          continue;
        }
        list($tableName, $fieldName) = explode('.', $values['where']);
        if (!empty($query->_returnProperties[$name])) {
          $query->_select["{$name}_id"] = "{$name}.value as {$name}_id";
          $query->_element["{$name}_id"] = 1;
          $query->_select[$name] = "{$name}.{$fieldName} as $name";
          $query->_tables[$tableName] = 1;
          $query->_element[$name] = 1;
        }
      }
    }
  }

  /**
   * Return option-values of a particular group
   *
   * @param array $groupParams
   *   Array containing group fields.
   *                                  whose option-values is to retrieved.
   * @param array $values
   *   (reference) to the array which.
   *                                  will have the values for the group
   * @param string $orderBy
   *   For orderBy clause.
   *
   * @param bool $isActive Do you want only active option values?
   *
   * @return array
   *   Array of option-values
   *
   */
  public static function getValues($groupParams, &$values, $orderBy = 'weight', $isActive = FALSE) {
    if (empty($groupParams)) {
      return NULL;
    }
    $select = "
SELECT
   option_value.id          as id,
   option_value.label       as label,
   option_value.value       as value,
   option_value.name        as name,
   option_value.description as description,
   option_value.weight      as weight,
   option_value.is_active   as is_active,
   option_value.is_default  as is_default";

    $from = "
FROM
   civicrm_option_value  as option_value,
   civicrm_option_group  as option_group ";

    $where = " WHERE option_group.id = option_value.option_group_id ";

    if ($isActive) {
      $where .= " AND option_value.is_active = " . $isActive;
    }

    $order = " ORDER BY " . $orderBy;

    $groupId = CRM_Utils_Array::value('id', $groupParams);
    $groupName = CRM_Utils_Array::value('name', $groupParams);

    if ($groupId) {
      $where .= " AND option_group.id = %1";
      $params[1] = array($groupId, 'Integer');
      if (!$groupName) {
        $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
          $groupId, 'name', 'id'
        );
      }
    }

    if ($groupName) {
      $where .= " AND option_group.name = %2";
      $params[2] = array($groupName, 'String');
    }

    if (in_array($groupName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $where .= " AND option_value.domain_id = " . CRM_Core_Config::domainID();
    }

    $query = $select . $from . $where . $order;

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      $values[$dao->id] = array(
        'id' => $dao->id,
        'label' => $dao->label,
        'value' => $dao->value,
        'name' => $dao->name,
        'description' => $dao->description,
        'weight' => $dao->weight,
        'is_active' => $dao->is_active,
        'is_default' => $dao->is_default,
      );
    }
  }

}
