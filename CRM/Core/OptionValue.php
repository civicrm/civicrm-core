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
class CRM_Core_OptionValue {

  /**
   * Static field for all the option value information that we can potentially export.
   *
   * @var array
   */
  public static $_exportableFields = NULL;

  /**
   * Static field for all the option value information that we can potentially export.
   *
   * @var array
   */
  public static $_importableFields = NULL;

  /**
   * Static field for all the option value information that we can potentially export.
   *
   * @var array
   */
  public static $_fields = NULL;

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
    $optionValue = [];
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

    $groupName = $groupParams['name'] ?? NULL;
    if (!$groupName && $optionGroupID) {
      $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
        $optionGroupID, 'name', 'id'
      );
    }

    $dao = new CRM_Core_DAO_OptionValue();

    if ($optionGroupID) {
      $dao->option_group_id = $optionGroupID;

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
      $optionValue[$dao->id] = [];
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
      $optionValue[$dao->id]['icon'] = $optionValue[$dao->id]['icon'] ?? '';
      $optionValue[$dao->id]['action'] = CRM_Core_Action::formLink($links, $action,
        [
          'id' => $dao->id,
          'gid' => $optionGroupID,
          'value' => $dao->value,
        ],
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
    $params['is_active'] ??= FALSE;
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
      $fieldValues = ['option_group_id' => $optionGroupID];
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, $params['weight'] ?? NULL, $fieldValues);
    }
    $params['option_group_id'] = $optionGroupID;

    if (($action & CRM_Core_Action::ADD) && !isset($params['value'])) {
      $fieldValues = ['option_group_id' => $optionGroupID];
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
   *
   * @return bool
   *   true if object exists
   */
  public static function optionExists($value, $daoName, $daoID, $optionGroupID, $fieldName) {
    $object = new $daoName();
    $object->$fieldName = $value;
    $object->option_group_id = $optionGroupID;

    if ($object->find(TRUE)) {
      return $daoID && $object->id == $daoID;
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
    if (empty(self::$_fields[$key])) {
      self::$_fields[$key] = [];

      $nameTitle = [];
      if ($mode == 'contribute') {
        // @todo - remove this - the only code place that calls
        // this function in a way that would hit this is commented 'remove this'
        // This is part of a move towards standardising option values but we
        // should derive them from the fields array so am deprecating it again...
        // note that the reason this was needed was that payment_instrument_id was
        // not set to exportable.
        $nameTitle = [
          'payment_instrument' => [
            'name' => 'payment_instrument',
            'title' => ts('Payment Method'),
            'headerPattern' => '/^payment|(p(ayment\s)?instrument)$/i',
          ],
        ];
      }
      elseif ($mode == '') {
        $nameTitle = [
          'addressee' => [
            'name' => 'addressee',
            'title' => ts('Addressee'),
            'headerPattern' => '/^addressee$/i',
          ],
          'email_greeting' => [
            'name' => 'email_greeting',
            'title' => ts('Email Greeting'),
            'headerPattern' => '/^email_greeting$/i',
          ],
          'postal_greeting' => [
            'name' => 'postal_greeting',
            'title' => ts('Postal Greeting'),
            'headerPattern' => '/^postal_greeting$/i',
          ],
        ];
      }

      $optionName = CRM_Core_DAO_OptionValue::import()['name'];

      foreach ($nameTitle as $name => $attribs) {
        self::$_fields[$key][$name] = $optionName;
        self::$_fields[$key][$name]['where'] = "{$name}.label";
        foreach ($attribs as $k => $val) {
          self::$_fields[$key][$name][$k] = $val;
        }
      }
    }

    return self::$_fields[$key];
  }

  /**
   * Build select query in case of option-values
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function select(&$query) {
    if (!empty($query->_params) || !empty($query->_returnProperties)) {
      $field = self::getFields();
      foreach ($field as $name => $values) {
        if (!empty($values['pseudoconstant'])) {
          continue;
        }
        [$tableName, $fieldName] = explode('.', $values['where']);
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
  public static function getValues($groupParams, &$values = [], $orderBy = 'weight', $isActive = FALSE) {
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
   option_value.icon        as icon,
   option_value.color       as color,
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

    $groupId = $groupParams['id'] ?? NULL;
    $groupName = $groupParams['name'] ?? NULL;

    if ($groupId) {
      $where .= " AND option_group.id = %1";
      $params[1] = [$groupId, 'Integer'];
      if (!$groupName) {
        $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
          $groupId, 'name', 'id'
        );
      }
    }

    if ($groupName) {
      $where .= " AND option_group.name = %2";
      $params[2] = [$groupName, 'String'];
    }

    $query = $select . $from . $where . $order;

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      $values[$dao->id] = [
        'id' => $dao->id,
        'label' => $dao->label,
        'value' => $dao->value,
        'name' => $dao->name,
        'description' => $dao->description,
        'weight' => $dao->weight,
        'is_active' => $dao->is_active,
        'is_default' => $dao->is_default,
        'icon' => $dao->icon,
        'color' => $dao->color,
      ];
    }
    return $values;
  }

}
