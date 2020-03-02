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
 * Business object for Saved searches.
 */
class CRM_Contact_BAO_SavedSearch extends CRM_Contact_DAO_SavedSearch {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Query the db for all saved searches.
   *
   * @return array
   *   contains the search name as value and and id as key
   */
  public function getAll() {
    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->selectAdd();
    $savedSearch->selectAdd('id, name');
    $savedSearch->find();
    while ($savedSearch->fetch()) {
      $aSavedSearch[$savedSearch->id] = $savedSearch->name;
    }
    return $aSavedSearch;
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contact_BAO_SavedSearch
   */
  public static function retrieve(&$params, &$defaults) {
    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->copyValues($params);
    if ($savedSearch->find(TRUE)) {
      CRM_Core_DAO::storeValues($savedSearch, $defaults);
      return $savedSearch;
    }
    return NULL;
  }

  /**
   * Given an id, extract the formValues of the saved search.
   *
   * @param int $id
   *   The id of the saved search.
   *
   * @return array
   *   the values of the posted saved search used as default values in various Search Form
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function getFormValues($id) {
    $specialDateFields = [
      'event_start_date_low' => 'event_date_low',
      'event_end_date_high' => 'event_date_high',
    ];

    $fv = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $id, 'form_values');
    $result = [];
    if ($fv) {
      // make sure u CRM_Utils_String::unserialize - since it's stored in serialized form
      $result = CRM_Utils_String::unserialize($fv);
    }

    $specialFields = ['contact_type', 'group', 'contact_tags', 'member_membership_type_id', 'member_status_id'];
    foreach ($result as $element => $value) {
      if (CRM_Contact_BAO_Query::isAlreadyProcessedForQueryFormat($value)) {
        $id = CRM_Utils_Array::value(0, $value);
        $value = CRM_Utils_Array::value(2, $value);
        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $op = key($value);
          $value = CRM_Utils_Array::value($op, $value);
          if (in_array($op, ['BETWEEN', '>=', '<='])) {
            self::decodeRelativeFields($result, $id, $op, $value);
            unset($result[$element]);
            continue;
          }
        }
        // Check for a date range field, which might be a standard date
        // range or a relative date.
        if (strpos($id, '_date_low') !== FALSE || strpos($id, '_date_high') !== FALSE) {
          $entityName = strstr($id, '_date', TRUE);

          // This is the default, for non relative dates. We will overwrite
          // it if we determine this is a relative date.
          $result[$id] = $value;
          $result["{$entityName}_date_relative"] = 0;

          if (!empty($result['relative_dates'])) {
            if (array_key_exists($entityName, $result['relative_dates'])) {
              // We have a match from a regular field.
              $result[$id] = NULL;
              $result["{$entityName}_date_relative"] = $result['relative_dates'][$entityName];
            }
            elseif (!empty($specialDateFields[$id])) {
              // We may have a match on a special date field.
              $entityName = strstr($specialDateFields[$id], '_date', TRUE);
              if (array_key_exists($entityName, $result['relative_dates'])) {
                $result[$id] = NULL;
                $result["{$entityName}_relative"] = $result['relative_dates'][$entityName];
              }
            }
          }
        }
        else {
          $result[$id] = $value;
        }
        unset($result[$element]);
        continue;
      }
      if (!empty($value) && is_array($value)) {
        if (in_array($element, $specialFields)) {
          // Remove the element to minimise support for legacy formats. It is stored in $value
          // so will be re-set with the right name.
          unset($result[$element]);
          $element = str_replace('member_membership_type_id', 'membership_type_id', $element);
          $element = str_replace('member_status_id', 'membership_status_id', $element);
          CRM_Contact_BAO_Query::legacyConvertFormValues($element, $value);
          $result[$element] = $value;
        }
        // As per the OK (Operator as Key) value format, value array may contain key
        // as an operator so to ensure the default is always set actual value
        elseif (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $result[$element] = CRM_Utils_Array::value(key($value), $value);
          if (is_string($result[$element])) {
            $result[$element] = str_replace("%", '', $result[$element]);
          }
        }
      }
      // We should only set the relative key for custom date fields if it is not already set in the array.
      $realField = str_replace(['_relative', '_low', '_high', '_to', '_high'], '', $element);
      if (substr($element, 0, 7) == 'custom_' && CRM_Contact_BAO_Query::isCustomDateField($realField)) {
        if (!isset($result[$realField . '_relative'])) {
          $result[$realField . '_relative'] = 0;
        }
      }
      // check to see if we need to convert the old privacy array
      // CRM-9180
      if (!empty($result['privacy'])) {
        if (is_array($result['privacy'])) {
          $result['privacy_operator'] = 'AND';
          $result['privacy_toggle'] = 1;
          if (isset($result['privacy']['do_not_toggle'])) {
            if ($result['privacy']['do_not_toggle']) {
              $result['privacy_toggle'] = 2;
            }
            unset($result['privacy']['do_not_toggle']);
          }

          $result['privacy_options'] = [];
          foreach ($result['privacy'] as $name => $val) {
            if ($val) {
              $result['privacy_options'][] = $name;
            }
          }
        }
        unset($result['privacy']);
      }
    }

    if ($customSearchClass = CRM_Utils_Array::value('customSearchClass', $result)) {
      // check if there is a special function - formatSavedSearchFields defined in the custom search form
      if (method_exists($customSearchClass, 'formatSavedSearchFields')) {
        $customSearchClass::formatSavedSearchFields($result);
      }
    }

    return $result;
  }

  /**
   * Get search parameters.
   *
   * @param int $id
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function getSearchParams($id) {
    $savedSearch = \Civi\Api4\SavedSearch::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute()
      ->first();
    if ($savedSearch['api_entity']) {
      return $savedSearch;
    }
    $fv = self::getFormValues($id);
    //check if the saved search has mapping id
    if ($savedSearch['mapping_id']) {
      return CRM_Core_BAO_Mapping::formattedFields($fv);
    }
    elseif (!empty($fv['customSearchID'])) {
      return $fv;
    }
    else {
      return CRM_Contact_BAO_Query::convertFormValues($fv);
    }
  }

  /**
   * Get the where clause for a saved search.
   *
   * @param int $id
   *   Saved search id.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the where clause for this saved search
   */
  public static function whereClause($id, &$tables, &$whereTables) {
    $params = self::getSearchParams($id);
    if ($params) {
      if (!empty($params['customSearchID'])) {
        // this has not yet been implemented
      }
      else {
        return CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
      }
    }
    return NULL;
  }

  /**
   * Contact IDS Sql (whatever that means!).
   *
   * @param int $id
   *
   * @return string
   */
  public static function contactIDsSQL($id) {
    $params = self::getSearchParams($id);
    if ($params && !empty($params['customSearchID'])) {
      return CRM_Contact_BAO_SearchCustom::contactIDSQL(NULL, $id);
    }
    else {
      $tables = $whereTables = ['civicrm_contact' => 1];
      $where = CRM_Contact_BAO_SavedSearch::whereClause($id, $tables, $whereTables);
      if (!$where) {
        $where = '( 1 )';
      }
      $from = CRM_Contact_BAO_Query::fromClause($whereTables);
      return "
SELECT contact_a.id
$from
WHERE  $where";
    }
  }

  /**
   * Get from where email (whatever that means!).
   *
   * @param int $id
   *
   * @return array
   */
  public static function fromWhereEmail($id) {
    $params = self::getSearchParams($id);

    if ($params) {
      if (!empty($params['customSearchID'])) {
        return CRM_Contact_BAO_SearchCustom::fromWhereEmail(NULL, $id);
      }
      else {
        $tables = $whereTables = ['civicrm_contact' => 1, 'civicrm_email' => 1];
        $where = CRM_Contact_BAO_SavedSearch::whereClause($id, $tables, $whereTables);
        $from = CRM_Contact_BAO_Query::fromClause($whereTables);
        return [$from, $where];
      }
    }
    else {
      // fix for CRM-7240
      $from = "
FROM      civicrm_contact contact_a
LEFT JOIN civicrm_email ON (contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1)
";
      $where = " ( 1 ) ";
      $tables['civicrm_contact'] = $whereTables['civicrm_contact'] = 1;
      $tables['civicrm_email'] = $whereTables['civicrm_email'] = 1;
      return [$from, $where];
    }
  }

  /**
   * Given an id, get the name of the saved search.
   *
   * @param int $id
   *   The id of the saved search.
   *
   * @param string $value
   *
   * @return string
   *   the name of the saved search
   */
  public static function getName($id, $value = 'name') {
    $group = new CRM_Contact_DAO_Group();
    $group->saved_search_id = $id;
    if ($group->find(TRUE)) {
      return $group->$value;
    }
    return NULL;
  }

  /**
   * Create a smart group from normalised values.
   *
   * @param array $params
   *
   * @return \CRM_Contact_DAO_SavedSearch
   */
  public static function create(&$params) {
    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->copyValues($params);
    $savedSearch->save();

    return $savedSearch;
  }

  /**
   * Assign test value.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName == 'form_values') {
      // A dummy value for form_values.
      $this->{$fieldName} = serialize(
          ['sort_name' => "SortName{$counter}"]);
    }
    else {
      parent::assignTestValues($fieldName, $fieldDef, $counter);
    }
  }

  /**
   * Store search variables in $queryParams which were skipped while processing query params,
   * precisely at CRM_Contact_BAO_Query::fixWhereValues(...). But these variable are required in
   * building smart group criteria otherwise it will cause issues like CRM-18585,CRM-19571
   *
   * @param array $queryParams
   * @param array $formValues
   */
  public static function saveSkippedElement(&$queryParams, $formValues) {
    // these are elements which are skipped in a smart group criteria
    $specialElements = [
      'operator',
      'component_mode',
      'display_relationship_type',
      'uf_group_id',
    ];
    foreach ($specialElements as $element) {
      if (!empty($formValues[$element])) {
        $queryParams[] = [$element, '=', $formValues[$element], 0, 0];
      }
    }
  }

  /**
   * Decode relative custom fields (converted by CRM_Contact_BAO_Query->convertCustomRelativeFields(...))
   *  into desired formValues
   *
   * @param array $formValues
   * @param string $fieldName
   * @param string $op
   * @param array|string|int $value
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function decodeRelativeFields(&$formValues, $fieldName, $op, $value) {
    // check if its a custom date field, if yes then 'searchDate' format the value
    if (CRM_Contact_BAO_Query::isCustomDateField($fieldName)) {
      return;
    }

    switch ($op) {
      case 'BETWEEN':
        list($formValues[$fieldName . '_from'], $formValues[$fieldName . '_to']) = $value;
        break;

      case '>=':
        $formValues[$fieldName . '_from'] = $value;
        break;

      case '<=':
        $formValues[$fieldName . '_to'] = $value;
        break;
    }
  }

}
