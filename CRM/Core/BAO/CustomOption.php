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
 * Business objects for managing custom data options.
 *
 */
class CRM_Core_BAO_CustomOption {

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @deprecated
   * @param array $params
   * @param array $defaults
   *
   * @return CRM_Core_DAO_OptionValue|NULL
   */
  public static function retrieve($params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_OptionValue', $params, $defaults);
  }

  /**
   * Returns all active options ordered by weight for a given field.
   *
   * @param int $fieldID
   *   Field whose options are needed.
   * @param bool $inactiveNeeded Do we need inactive options ?.
   *   Do we need inactive options ?.
   *
   * @return array
   *   all active options for fieldId
   */
  public static function getCustomOption(
    $fieldID,
    $inactiveNeeded = FALSE
  ) {
    $options = [];
    if (!$fieldID) {
      return $options;
    }

    $optionValues = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', 'custom_' . $fieldID, [], $inactiveNeeded ? 'get' : 'create');

    foreach ((array) $optionValues as $value => $label) {
      $options[] = [
        'label' => $label,
        'value' => $value,
      ];
    }

    return $options;
  }

  /**
   * Wrapper for ajax option selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of option list
   *   -rp = rowcount
   *   -page= offset
   */
  public static function getOptionListSelector(&$params) {
    $options = [];

    $field = CRM_Core_BAO_CustomField::getFieldObject($params['fid']);
    $defVal = (array) CRM_Utils_Array::explodePadded($field->default_value);

    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];

    if (!$field->option_group_id) {
      return $options;
    }
    $queryParams = [1 => [$field->option_group_id, 'Integer']];
    $total = "SELECT COUNT(*) FROM civicrm_option_value WHERE option_group_id = %1";
    $params['total'] = CRM_Core_DAO::singleValueQuery($total, $queryParams);

    $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    $orderBy = ' ORDER BY options.weight asc';

    $query = "SELECT * FROM civicrm_option_value as options WHERE option_group_id = %1 {$orderBy} {$limit}";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $links = CRM_Custom_Page_Option::actionLinks();

    $fields = ['id', 'label', 'value'];
    $config = CRM_Core_Config::singleton();
    while ($dao->fetch()) {
      $options[$dao->id] = [];
      foreach ($fields as $k) {
        $options[$dao->id][$k] = $dao->$k;
      }
      $action = array_sum(array_keys($links));
      $class = 'crm-entity';
      // update enable/disable links depending on custom_field properties.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $class .= ' disabled';
        $action -= CRM_Core_Action::DISABLE;
      }

      $isGroupLocked = (bool) CRM_Core_DAO::getFieldValue(
        CRM_Core_DAO_OptionGroup::class,
        $field->option_group_id,
        'is_locked'
      );

      // disable deletion of option values for locked option groups
      if (($action & CRM_Core_Action::DELETE) && $isGroupLocked) {
        $action -= CRM_Core_Action::DELETE;
      }

      if (CRM_Core_BAO_CustomField::isSerialized($field)) {
        $options[$dao->id]['is_default'] = in_array($dao->value, $defVal);
      }
      else {
        $options[$dao->id]['is_default'] = ($field->default_value == $dao->value);
      }
      $options[$dao->id]['description'] = $dao->description;
      $options[$dao->id]['class'] = $dao->id . ',' . $class;
      $options[$dao->id]['is_active'] = empty($dao->is_active) ? ts('No') : ts('Yes');
      $options[$dao->id]['links'] = CRM_Core_Action::formLink($links,
          $action,
          [
            'id' => $dao->id,
            'fid' => $params['fid'],
            'gid' => $params['gid'],
          ],
          ts('more'),
          FALSE,
          'customOption.row.actions',
          'customOption',
          $dao->id
        );
    }

    return $options;
  }

  /**
   * Delete Option.
   *
   * @param int $optionId
   *   option id
   *
   */
  public static function del($optionId) {
    // get the customFieldID
    $query = "
SELECT f.id as id, f.data_type as dataType
FROM   civicrm_option_value v,
       civicrm_option_group g,
       civicrm_custom_field f
WHERE  v.id    = %1
AND    g.id    = f.option_group_id
AND    g.id    = v.option_group_id";
    $params = [1 => [$optionId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      if (in_array($dao->dataType,
        ['Int', 'Float', 'Money', 'Boolean']
      )) {
        $value = 0;
      }
      else {
        $value = '';
      }
      $params = [
        'optionId' => $optionId,
        'fieldId' => $dao->id,
        'value' => $value,
      ];
      // delete this value from the tables
      self::updateValue($optionId, $value);

      // also delete this option value
      CRM_Core_BAO_OptionValue::deleteRecord(['id' => $optionId]);
    }
  }

  /**
   * When changing the value of an option this is called to update all corresponding custom data
   *
   * @param int $optionId
   * @param string $newValue
   */
  public static function updateValue($optionId, $newValue) {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->id = $optionId;
    $optionValue->find(TRUE);
    $oldValue = $optionValue->value;
    if ($oldValue == $newValue) {
      return;
    }

    $customField = new CRM_Core_DAO_CustomField();
    $customField->option_group_id = $optionValue->option_group_id;
    $customField->find();
    while ($customField->fetch()) {
      $customGroup = new CRM_Core_DAO_CustomGroup();
      $customGroup->id = $customField->custom_group_id;
      $customGroup->find(TRUE);
      if (CRM_Core_BAO_CustomField::isSerialized($customField)) {
        $params = [
          1 => [CRM_Utils_Array::implodePadded($oldValue), 'String'],
          2 => [CRM_Utils_Array::implodePadded($newValue), 'String'],
          3 => ['%' . CRM_Utils_Array::implodePadded($oldValue) . '%', 'String'],
        ];
      }
      else {
        $params = [
          1 => [$oldValue, 'String'],
          2 => [$newValue, 'String'],
          3 => [$oldValue, 'String'],
        ];
      }
      $sql = "UPDATE `{$customGroup->table_name}` SET `{$customField->column_name}` = REPLACE(`{$customField->column_name}`, %1, %2) WHERE `{$customField->column_name}` LIKE %3";
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }

}
