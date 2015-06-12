<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * $Id$
 *
 */

/**
 * Business objects for managing custom data options.
 *
 */
class CRM_Core_BAO_CustomOption {

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_CustomOption
   */
  public static function retrieve(&$params, &$defaults) {
    $customOption = new CRM_Core_DAO_OptionValue();
    $customOption->copyValues($params);
    if ($customOption->find(TRUE)) {
      CRM_Core_DAO::storeValues($customOption, $defaults);
      return $customOption;
    }
    return NULL;
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
    $options = array();
    if (!$fieldID) {
      return $options;
    }

    $field = CRM_Core_BAO_CustomField::getFieldObject($fieldID);

    // get the option group id
    $optionGroupID = $field->option_group_id;
    if (!$optionGroupID) {
      return $options;
    }

    $optionValues = CRM_Core_BAO_OptionValue::getOptionValuesArray($optionGroupID);

    foreach ($optionValues as $id => $value) {
      if (!$inactiveNeeded && empty($value['is_active'])) {
        continue;
      }

      $options[$id] = array();
      $options[$id]['id'] = $id;
      $options[$id]['label'] = $value['label'];
      $options[$id]['value'] = $value['value'];
    }

    CRM_Utils_Hook::customFieldOptions($fieldID, $options, TRUE);

    return $options;
  }

  /**
   * wrapper for ajax option selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of option list
   *   -rp = rowcount
   *   -page= offset
   */
  static public function getOptionListSelector(&$params) {

    $options = array();

    //get the default value from custom fields
    $customFieldBAO = new CRM_Core_BAO_CustomField();
    $customFieldBAO->id = $params['fid'];
    if ($customFieldBAO->find(TRUE)) {
      $defaultValue = $customFieldBAO->default_value;
      $fieldHtmlType = $customFieldBAO->html_type;
    }
    else {
      CRM_Core_Error::fatal();
    }
    $defVal = explode(CRM_Core_DAO::VALUE_SEPARATOR,
      substr($defaultValue, 1, -1)
    );

    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];

    $field = CRM_Core_BAO_CustomField::getFieldObject($params['fid']);

    // get the option group id
    $optionGroupID = $field->option_group_id;
    if (!$optionGroupID) {
      return $options;
    }
    $queryParams = array(1 => array($optionGroupID, 'Integer'));
    $total = "SELECT COUNT(*) FROM civicrm_option_value WHERE option_group_id = %1";
    $params['total'] = CRM_Core_DAO::singleValueQuery($total, $queryParams);

    $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    $orderBy = ' ORDER BY options.weight asc';

    $query = "SELECT * FROM civicrm_option_value as options WHERE option_group_id = %1 {$orderBy} {$limit}";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $links = CRM_Custom_Page_Option::actionLinks();

    $fields = array('id', 'label', 'value');
    $config = CRM_Core_Config::singleton();
    while ($dao->fetch()) {
      $options[$dao->id] = array();
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
      if ($fieldHtmlType == 'CheckBox' ||
        $fieldHtmlType == 'AdvMulti-Select' ||
        $fieldHtmlType == 'Multi-Select'
      ) {
        if (in_array($dao->value, $defVal)) {
          $options[$dao->id]['is_default'] = '<img src="' . $config->resourceBase . 'i/check.gif" />';
        }
        else {
          $options[$dao->id]['is_default'] = '';
        }
      }
      else {
        if ($defaultValue == $dao->value) {
          $options[$dao->id]['is_default'] = '<img src="' . $config->resourceBase . 'i/check.gif" />';
        }
        else {
          $options[$dao->id]['is_default'] = '';
        }
      }

      $options[$dao->id]['class'] = $dao->id . ',' . $class;
      $options[$dao->id]['is_active'] = !empty($dao->is_active) ? 'Yes' : 'No';
      $options[$dao->id]['links'] = CRM_Core_Action::formLink($links,
          $action,
          array(
            'id' => $dao->id,
            'fid' => $params['fid'],
            'gid' => $params['gid'],
          ),
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
   * Returns the option label for a custom field with a specific value. Handles all
   * custom field data and html types
   *
   * @param int $fieldId
   *   the custom field ID.
   * @pram  $value    string the value (typically from the DB) of this custom field
   * @param $value
   * @param string $htmlType
   *   the html type of the field (optional).
   * @param string $dataType
   *   the data type of the field (optional).
   *
   * @return string
   *   the label to display for this custom field
   */
  public static function getOptionLabel($fieldId, $value, $htmlType = NULL, $dataType = NULL) {
    if (!$fieldId) {
      return NULL;
    }

    if (!$htmlType || !$dataType) {
      $sql = "
SELECT html_type, data_type
FROM   civicrm_custom_field
WHERE  id = %1
";
      $params = array(1 => array($fieldId, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $htmlType = $dao->html_type;
        $dataType = $dao->data_type;
      }
      else {
        CRM_Core_Error::fatal();
      }
    }

    $options = NULL;
    switch ($htmlType) {
      case 'CheckBox':
      case 'Multi-Select':
      case 'AdvMulti-Select':
      case 'Select':
      case 'Radio':
      case 'Autocomplete-Select':
        if (!in_array($dataType, array(
          'Boolean',
          'ContactReference',
        ))
        ) {
          $options = self::valuesByID($fieldId);
        }
    }

    return CRM_Core_BAO_CustomField::getDisplayValueCommon($value,
      $options,
      $htmlType,
      $dataType
    );
  }

  /**
   * Delete Option.
   *
   * @param $optionId integer
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
    $params = array(1 => array($optionId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      if (in_array($dao->dataType,
        array('Int', 'Float', 'Money', 'Boolean')
      )) {
        $value = 0;
      }
      else {
        $value = '';
      }
      $params = array(
        'optionId' => $optionId,
        'fieldId' => $dao->id,
        'value' => $value,
      );
      // delete this value from the tables
      self::updateCustomValues($params);

      // also delete this option value
      $query = "
DELETE
FROM   civicrm_option_value
WHERE  id = %1";
      $params = array(1 => array($optionId, 'Integer'));
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * @param array $params
   *
   * @throws Exception
   */
  public static function updateCustomValues($params) {
    $optionDAO = new CRM_Core_DAO_OptionValue();
    $optionDAO->id = $params['optionId'];
    $optionDAO->find(TRUE);
    $oldValue = $optionDAO->value;

    // get the table, column, html_type and data type for this field
    $query = "
SELECT g.table_name  as tableName ,
       f.column_name as columnName,
       f.data_type   as dataType,
       f.html_type   as htmlType
FROM   civicrm_custom_group g,
       civicrm_custom_field f
WHERE  f.custom_group_id = g.id
  AND  f.id = %1";
    $queryParams = array(1 => array($params['fieldId'], 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    if ($dao->fetch()) {
      if ($dao->dataType == 'Money') {
        $params['value'] = CRM_Utils_Rule::cleanMoney($params['value']);
      }
      switch ($dao->htmlType) {
        case 'Autocomplete-Select':
        case 'Select':
        case 'Radio':
          $query = "
UPDATE {$dao->tableName}
SET    {$dao->columnName} = %1
WHERE  id = %2";
          if ($dao->dataType == 'Auto-complete') {
            $dataType = "String";
          }
          else {
            $dataType = $dao->dataType;
          }
          $queryParams = array(
            1 => array(
              $params['value'],
              $dataType,
            ),
            2 => array(
              $params['optionId'],
              'Integer',
            ),
          );
          break;

        case 'AdvMulti-Select':
        case 'Multi-Select':
        case 'CheckBox':
          $oldString = CRM_Core_DAO::VALUE_SEPARATOR . $oldValue . CRM_Core_DAO::VALUE_SEPARATOR;
          $newString = CRM_Core_DAO::VALUE_SEPARATOR . $params['value'] . CRM_Core_DAO::VALUE_SEPARATOR;
          $query = "
UPDATE {$dao->tableName}
SET    {$dao->columnName} = REPLACE( {$dao->columnName}, %1, %2 )";
          $queryParams = array(
            1 => array($oldString, 'String'),
            2 => array($newString, 'String'),
          );
          break;

        default:
          CRM_Core_Error::fatal();
      }
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    }
  }

  /**
   * @param int $customFieldID
   * @param int $optionGroupID
   *
   * @return array
   */
  public static function valuesByID($customFieldID, $optionGroupID = NULL) {
    if (!$optionGroupID) {
      $optionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
        $customFieldID,
        'option_group_id'
      );
    }

    $options = $optionGroupID ? CRM_Core_OptionGroup::valuesByID($optionGroupID) : array();

    CRM_Utils_Hook::customFieldOptions($customFieldID, $options, FALSE);

    return $options;
  }

}
