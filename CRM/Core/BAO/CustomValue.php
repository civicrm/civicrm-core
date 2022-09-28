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
 * Business objects for managing custom data values.
 */
class CRM_Core_BAO_CustomValue extends CRM_Core_DAO {

  /**
   * Validate a value against a CustomField type.
   *
   * @param string $type
   *   The type of the data.
   * @param string $value
   *   The data to be validated.
   *
   * @return bool
   *   True if the value is of the specified type
   */
  public static function typecheck($type, $value) {
    switch ($type) {
      case 'Memo':
        return TRUE;

      case 'String':
        return CRM_Utils_Rule::string($value);

      case 'Int':
        return CRM_Utils_Rule::integer($value);

      case 'Float':
      case 'Money':
        return CRM_Utils_Rule::numeric($value);

      case 'Date':
        if (is_numeric($value)) {
          return CRM_Utils_Rule::dateTime($value);
        }
        else {
          return CRM_Utils_Rule::date($value);
        }
      case 'Boolean':
        return CRM_Utils_Rule::boolean($value);

      case 'ContactReference':
        return CRM_Utils_Rule::validContact($value);

      case 'StateProvince':

        //fix for multi select state, CRM-3437
        $valid = FALSE;
        $mulValues = explode(',', $value);
        foreach ($mulValues as $key => $state) {
          $valid = array_key_exists(strtolower(trim($state)),
              array_change_key_case(array_flip(CRM_Core_PseudoConstant::stateProvinceAbbreviation()), CASE_LOWER)
            ) || array_key_exists(strtolower(trim($state)),
              array_change_key_case(array_flip(CRM_Core_PseudoConstant::stateProvince()), CASE_LOWER)
            );
          if (!$valid) {
            break;
          }
        }
        return $valid;

      case 'Country':

        //fix multi select country, CRM-3437
        $valid = FALSE;
        $mulValues = explode(',', $value);
        foreach ($mulValues as $key => $country) {
          $valid = array_key_exists(strtolower(trim($country)),
              array_change_key_case(array_flip(CRM_Core_PseudoConstant::countryIsoCode()), CASE_LOWER)
            ) || array_key_exists(strtolower(trim($country)),
              array_change_key_case(array_flip(CRM_Core_PseudoConstant::country()), CASE_LOWER)
            );
          if (!$valid) {
            break;
          }
        }
        return $valid;

      case 'Link':
        return CRM_Utils_Rule::url($value);
    }
    return FALSE;
  }

  /**
   * Given a 'civicrm' type string, return the mysql data store area
   *
   * @param string $type
   *   The civicrm type string.
   *
   * @return string|null
   *   the mysql data store placeholder
   */
  public static function typeToField($type) {
    switch ($type) {
      case 'String':
      case 'File':
        return 'char_data';

      case 'Boolean':
      case 'Int':
      case 'StateProvince':
      case 'Country':
      case 'Auto-complete':
        return 'int_data';

      case 'Float':
        return 'float_data';

      case 'Money':
        return 'decimal_data';

      case 'Memo':
        return 'memo_data';

      case 'Date':
        return 'date_data';

      case 'Link':
        return 'char_data';

      default:
        return NULL;
    }
  }

  /**
   * @param array $formValues
   * @return null
   */
  public static function fixCustomFieldValue(&$formValues) {
    if (empty($formValues)) {
      return NULL;
    }
    foreach (array_keys($formValues) as $key) {
      if (substr($key, 0, 7) != 'custom_') {
        continue;
      }
      elseif (empty($formValues[$key])) {
        continue;
      }

      $htmlType = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomField',
        substr($key, 7), 'html_type'
      );
      $dataType = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomField',
        substr($key, 7), 'data_type'
      );

      if (is_array($formValues[$key])) {
        if (!in_array(key($formValues[$key]), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $formValues[$key] = ['IN' => $formValues[$key]];
        }
      }
      elseif (($htmlType == 'TextArea' ||
          ($htmlType == 'Text' && $dataType == 'String')
        ) && strstr($formValues[$key], '%')
      ) {
        $formValues[$key] = ['LIKE' => $formValues[$key]];
      }
      elseif ($htmlType == 'Autocomplete-Select' && !empty($formValues[$key]) && is_string($formValues[$key]) && (strpos($formValues[$key], ',') != FALSE)) {
        $formValues[$key] = ['IN' => explode(',', $formValues[$key])];
      }
    }
  }

  /**
   * Delete option value give an option value and custom group id.
   *
   * @param int $customValueID
   *   Custom value ID.
   * @param int $customGroupID
   *   Custom group ID.
   */
  public static function deleteCustomValue($customValueID, $customGroupID) {
    // first we need to find custom value table, from custom group ID
    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupID, 'table_name');

    // Retrieve the $entityId so we can pass that to the hook.
    $entityID = (int) CRM_Core_DAO::singleValueQuery("SELECT entity_id FROM {$tableName} WHERE id = %1", [
      1 => [$customValueID, 'Integer'],
    ]);

    // delete custom value from corresponding custom value table
    $sql = "DELETE FROM {$tableName} WHERE id = {$customValueID}";
    CRM_Core_DAO::executeQuery($sql);

    CRM_Utils_Hook::custom('delete',
      (int) $customGroupID,
      $entityID,
      $customValueID
    );
  }

  /**
   * ACL clause for an APIv4 custom pseudo-entity (aka multi-record custom group extending Contact).
   * @return array
   */
  public function addSelectWhereClause() {
    $clauses = [
      'entity_id' => CRM_Utils_SQL::mergeSubquery('Contact'),
    ];
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
    return $clauses;
  }

  /**
   * Special checkAccess function for multi-record custom pseudo-entities
   *
   * @param string $entityName
   *   Ex: 'Contact' or 'Custom_Foobar'
   * @param string $action
   * @param array $record
   * @param int $userID
   *   Contact ID of the active user (whose access we must check). 0 for anonymous.
   * @return bool
   *   TRUE if granted. FALSE if prohibited. NULL if indeterminate.
   */
  public static function _checkAccess(string $entityName, string $action, array $record, int $userID): ?bool {
    // This check implements two rules: you must have access to the specific custom-data-group - and to the underlying record (e.g. Contact).

    $groupName = substr($entityName, 0, 7) === 'Custom_' ? substr($entityName, 7) : NULL;
    $extends = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $groupName, 'extends', 'name');
    $id = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $groupName, 'id', 'name');
    if (!$groupName) {
      // $groupName is required but the function signature has to match the parent.
      throw new CRM_Core_Exception('Missing required group-name in CustomValue::checkAccess');
    }

    if (empty($extends) || empty($id)) {
      throw new CRM_Core_Exception('Received invalid group-name in CustomValue::checkAccess');
    }

    $actionType = $action === 'get' ? CRM_Core_Permission::VIEW : CRM_Core_Permission::EDIT;
    if (!\CRM_Core_BAO_CustomGroup::checkGroupAccess($id, $actionType, $userID)) {
      return FALSE;
    }

    $eid = $record['entity_id'] ?? NULL;
    if (!$eid) {
      $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $groupName, 'table_name', 'name');
      $eid = CRM_Core_DAO::singleValueQuery("SELECT entity_id FROM `$tableName` WHERE id = " . (int) $record['id']);
    }

    // Do we have access to the target record?
    if (in_array($extends, ['Contact', 'Individual', 'Organization', 'Household'])) {
      return \Civi\Api4\Utils\CoreUtil::checkAccessDelegated('Contact', 'update', ['id' => $eid], $userID);
    }
    elseif (\Civi\Api4\Utils\CoreUtil::getApiClass($extends)) {
      // For most entities (Activity, Relationship, Contribution, ad nauseum), we acn just use an eponymous API.
      return \Civi\Api4\Utils\CoreUtil::checkAccessDelegated($extends, 'update', ['id' => $eid], $userID);
    }
    else {
      // Do you need to add a special case for some oddball custom-group type?
      throw new CRM_Core_Exception("Cannot assess delegated permissions for group {$groupName}.");
    }
  }

}
