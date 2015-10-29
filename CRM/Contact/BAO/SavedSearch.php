<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
   */
  public static function &getFormValues($id) {
    $fv = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $id, 'form_values');
    $result = NULL;
    if ($fv) {
      // make sure u unserialize - since it's stored in serialized form
      $result = unserialize($fv);
    }

    $specialFields = array('contact_type', 'group', 'contact_tags', 'member_membership_type_id', 'member_status_id');
    foreach ($result as $element => $value) {
      if (CRM_Contact_BAO_Query::isAlreadyProcessedForQueryFormat($value)) {
        $id = CRM_Utils_Array::value(0, $value);
        $value = CRM_Utils_Array::value(2, $value);
        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $value = CRM_Utils_Array::value(key($value), $value);
        }
        $result[$id] = $value;
        unset($result[$element]);
        continue;
      }
      if (!empty($value) && is_array($value)) {
        if (in_array($element, $specialFields)) {
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
      if (substr($element, 0, 7) == 'custom_' &&
        (substr($element, -5, 5) == '_from' || substr($element, -3, 3) == '_to')
      ) {
        // Ensure the _relative field is set if from or to are set to ensure custom date
        // fields with 'from' or 'to' values are displayed when the are set in the smart group
        // being loaded. (CRM-17116)
        if (!isset($result[CRM_Contact_BAO_Query::getCustomFieldName($element) . '_relative'])) {
          $result[CRM_Contact_BAO_Query::getCustomFieldName($element) . '_relative'] = 0;
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

          $result['privacy_options'] = array();
          foreach ($result['privacy'] as $name => $val) {
            if ($val) {
              $result['privacy_options'][] = $name;
            }
          }
        }
        unset($result['privacy']);
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
   */
  public static function getSearchParams($id) {
    $fv = self::getFormValues($id);
    //check if the saved search has mapping id
    if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $id, 'mapping_id')) {
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
      $tables = $whereTables = array('civicrm_contact' => 1);
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
        $tables = $whereTables = array('civicrm_contact' => 1, 'civicrm_email' => 1);
        $where = CRM_Contact_BAO_SavedSearch::whereClause($id, $tables, $whereTables);
        $from = CRM_Contact_BAO_Query::fromClause($whereTables);
        return array($from, $where);
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
      return array($from, $where);
    }
  }

  /**
   * Given a saved search compute the clause and the tables and store it for future use.
   */
  public function buildClause() {
    $fv = unserialize($this->form_values);

    if ($this->mapping_id) {
      $params = CRM_Core_BAO_Mapping::formattedFields($fv);
    }
    else {
      $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    }

    if (!empty($params)) {
      $tables = $whereTables = array();
      $this->where_clause = CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
      if (!empty($tables)) {
        $this->select_tables = serialize($tables);
      }
      if (!empty($whereTables)) {
        $this->where_tables = serialize($whereTables);
      }
    }
  }

  /**
   * Save the search.
   *
   * @param bool $hook
   */
  public function save($hook = TRUE) {
    // first build the computed fields
    $this->buildClause();

    parent::save($hook);
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
    if (isset($params['formValues']) &&
      !empty($params['formValues'])
    ) {
      $savedSearch->form_values = serialize($params['formValues']);
    }
    else {
      $savedSearch->form_values = NULL;
    }

    $savedSearch->is_active = CRM_Utils_Array::value('is_active', $params, 1);
    $savedSearch->mapping_id = CRM_Utils_Array::value('mapping_id', $params, 'null');
    $savedSearch->custom_search_id = CRM_Utils_Array::value('custom_search_id', $params, 'null');
    $savedSearch->id = CRM_Utils_Array::value('id', $params, NULL);

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
          array('sort_name' => "SortName{$counter}"));
    }
    else {
      parent::assignTestValues($fieldName, $fieldDef, $counter);
    }
  }

}
