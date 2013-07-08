<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class is a heart of search query building mechanism.
 */
class CRM_Contact_BAO_Query {

  /**
   * The various search modes
   *
   * @var int
   */
  CONST
    MODE_CONTACTS = 1,
    MODE_CONTRIBUTE = 2,
    MODE_MEMBER = 8,
    MODE_EVENT = 16,
    MODE_GRANT = 128,
    MODE_PLEDGEBANK = 256,
    MODE_PLEDGE = 512,
    MODE_CASE = 2048,
    MODE_ALL = 17407,
    MODE_ACTIVITY = 4096,
    MODE_CAMPAIGN = 8192,
    MODE_MAILING = 16384;

  /**
   * the default set of return properties
   *
   * @var array
   * @static
   */
  static $_defaultReturnProperties = NULL;

  /**
   * the default set of hier return properties
   *
   * @var array
   * @static
   */
  static $_defaultHierReturnProperties;

  /**
   * the set of input params
   *
   * @var array
   */
  public $_params;

  public $_cfIDs;

  public $_paramLookup;

  /**
   * the set of output params
   *
   * @var array
   */
  public $_returnProperties;

  /**
   * the select clause
   *
   * @var array
   */
  public $_select;

  /**
   * the name of the elements that are in the select clause
   * used to extract the values
   *
   * @var array
   */
  public $_element;

  /**
   * the tables involved in the query
   *
   * @var array
   */
  public $_tables;

  /**
   * the table involved in the where clause
   *
   * @var array
   */
  public $_whereTables;

  /**
   * the where clause
   *
   * @var array
   */
  public $_where;

  /**
   * the where string
   *
   * @var string
   *
   */
  public $_whereClause;

  /**
   * additional permission Where Clause
   *
   * @var string
   *
   */
  public $_permissionWhereClause;
  /**
   * the from string
   *
   * @var string
   *
   */
  public $_fromClause;

  /**
   * additional permission from clause
   *
   * @var string
   *
   */
  public $_permissionFromClause;

  /**
   * the from clause for the simple select and alphabetical
   * select
   *
   * @var string
   */
  public $_simpleFromClause;

  /**
   * the having values
   *
   * @var string
   *
   */
  public $_having;

  /**
   * The english language version of the query
   *
   * @var array
   */
  public $_qill;

  /**
   * All the fields that could potentially be involved in
   * this query
   *
   * @var array
   */
  public $_fields;

  /**
   * The cache to translate the option values into labels
   *
   * @var array
   */
  public $_options;

  /**
   * are we in search mode
   *
   * @var boolean
   */
  public $_search = TRUE;

  /**
   * should we skip permission checking
   *
   * @var boolean
   */
  public $_skipPermission = FALSE;

  /**
   * should we skip adding of delete clause
   *
   * @var boolean
   */
  public $_skipDeleteClause = FALSE;

  /**
   * are we in strict mode (use equality over LIKE)
   *
   * @var boolean
   */
  public $_strict = FALSE;

  /**
   * What operator to use to group the clauses
   *
   * @var string
   */
  public $_operator = 'AND';

  public $_mode = 1;

  /**
   * Should we only search on primary location
   *
   * @var boolean
   */
  public $_primaryLocation = TRUE;

  /**
   * are contact ids part of the query
   *
   * @var boolean
   */
  public $_includeContactIds = FALSE;

  /**
   * Should we use the smart group cache
   *
   * @var boolean
   */
  public $_smartGroupCache = TRUE;

  /**
   * Should we display contacts with a specific relationship type
   *
   * @var string
   */
  public $_displayRelationshipType = NULL;

  /**
   * reference to the query object for custom values
   *
   * @var Object
   */
  public $_customQuery;

  /**
   * should we enable the distinct clause, used if we are including
   * more than one group
   *
   * @var boolean
   */
  public $_useDistinct = FALSE;

  /**
   * Should we just display one contact record
   */
  public $_useGroupBy = FALSE;

  /**
   * the relationship type direction
   *
   * @var array
   * @static
   */
  static $_relType;

  /**
   * the activity role
   *
   * @var array
   * @static
   */
  static $_activityRole;

  /**
   * Consider the component activity type
   * during activity search.
   *
   * @var array
   * @static
   */
  static $_considerCompActivities;

  /**
   * Consider with contact activities only,
   * during activity search.
   *
   * @var array
   * @static
   */
  static $_withContactActivitiesOnly;

  /**
   * use distinct component clause for component searches
   *
   * @var string
   */
  public $_distinctComponentClause;

  /**
   * use groupBy component clause for component searches
   *
   * @var string
   */
  public $_groupByComponentClause;

  /**
   * Track open panes, useful in advance search
   *
   * @var array
   * @static
   */
  public static $_openedPanes = array();

  /**
   * The tables which have a dependency on location and/or address
   *
   * @var array
   * @static
   */
  static $_dependencies = array(
    'civicrm_state_province' => 1,
    'civicrm_country' => 1,
    'civicrm_county' => 1,
    'civicrm_address' => 1,
    'civicrm_location_type' => 1,
  );

  /**
   * List of location specific fields
   */
  static $_locationSpecificFields = array(
    'street_address',
    'street_number',
    'street_name',
    'street_unit',
    'supplemental_address_1',
    'supplemental_address_2',
    'city',
    'postal_code',
    'postal_code_suffix',
    'geo_code_1',
    'geo_code_2',
    'state_province',
    'country',
    'county',
    'phone',
    'email',
    'im',
    'address_name',
  );

  /**
   * Rememeber if we handle either end of a number or date range
   * so we can skip the other
   */
  protected $_rangeCache = array();

  /**
   * class constructor which also does all the work
   *
   * @param array   $params
   * @param array   $returnProperties
   * @param array   $fields
   * @param boolean $includeContactIds
   * @param boolean $strict
   * @param boolean $mode - mode the search is operating on
   *
   * @return Object
   * @access public
   */
  function __construct(
    $params = NULL, $returnProperties = NULL, $fields = NULL,
    $includeContactIds = FALSE, $strict = FALSE, $mode = 1,
    $skipPermission = FALSE, $searchDescendentGroups = TRUE,
    $smartGroupCache = TRUE, $displayRelationshipType = NULL,
    $operator = 'AND'
  ) {
    $this->_params = &$params;
    if ($this->_params == NULL) {
      $this->_params = array();
    }

    if (empty($returnProperties)) {
      $this->_returnProperties = self::defaultReturnProperties($mode);
    }
    else {
      $this->_returnProperties = &$returnProperties;
    }

    $this->_includeContactIds = $includeContactIds;
    $this->_strict = $strict;
    $this->_mode = $mode;
    $this->_skipPermission = $skipPermission;
    $this->_smartGroupCache = $smartGroupCache;
    $this->_displayRelationshipType = $displayRelationshipType;
    $this->setOperator($operator);

    if ($fields) {
      $this->_fields = &$fields;
      $this->_search = FALSE;
      $this->_skipPermission = TRUE;
    }
    else {
      $this->_fields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE, TRUE);

      $fields = CRM_Core_Component::getQueryFields();
      unset($fields['note']);
      $this->_fields = array_merge($this->_fields, $fields);

      // add activity fields
      $fields = CRM_Activity_BAO_Activity::exportableFields();
      $this->_fields = array_merge($this->_fields, $fields);
    }

    // basically do all the work once, and then reuse it
    $this->initialize();
  }

  /**
   * function which actually does all the work for the constructor
   *
   * @return void
   * @access private
   */
  function initialize() {
    $this->_select = array();
    $this->_element = array();
    $this->_tables = array();
    $this->_whereTables = array();
    $this->_where = array();
    $this->_qill = array();
    $this->_options = array();
    $this->_cfIDs = array();
    $this->_paramLookup = array();
    $this->_having = array();

    $this->_customQuery = NULL;

    // reset cached static variables - CRM-5803
    self::$_activityRole = NULL;
    self::$_considerCompActivities = NULL;
    self::$_withContactActivitiesOnly = NULL;

    $this->_select['contact_id'] = 'contact_a.id as contact_id';
    $this->_element['contact_id'] = 1;
    $this->_tables['civicrm_contact'] = 1;

    if (!empty($this->_params)) {
      $this->buildParamsLookup();
    }

    $this->_whereTables = $this->_tables;

    $this->selectClause();
    $this->_whereClause = $this->whereClause();

    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_primaryLocation, $this->_mode);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_primaryLocation, $this->_mode);

    $this->openedSearchPanes(TRUE);
  }

  function buildParamsLookup() {
    // first fix and handle contact deletion nicely
    // this code is primarily for search builder use case
    // where different clauses can specify if they want deleted
    // contacts or not
    // CRM-11971
    $trashParamExists = FALSE;
    $paramByGroup    = array();
    foreach ( $this->_params as $k => $param ) {
      if (!empty($param[0]) && $param[0] == 'contact_is_deleted' ) {
        $trashParamExists = TRUE;
      }
      if (!empty($param[3])) {
        $paramByGroup[$param[3]][$k] = $param;
      }
    }

    if ( $trashParamExists ) {
      $this->_skipDeleteClause = TRUE;

      //cycle through group sets and explicitly add trash param if not set
      foreach ( $paramByGroup as $setID => $set ) {
        if (
          !in_array(array('contact_is_deleted', '=', '1', $setID, '0'), $this->_params) &&
          !in_array(array('contact_is_deleted', '=', '0', $setID, '0'), $this->_params) ) {
          $this->_params[] = array(
            'contact_is_deleted',
            '=',
            '0',
            $setID,
            '0',
          );
        }
      }
    }

    foreach ($this->_params as $value) {
      if (!CRM_Utils_Array::value(0, $value)) {
        continue;
      }
      $cfID = CRM_Core_BAO_CustomField::getKeyID($value[0]);
      if ($cfID) {
        if (!array_key_exists($cfID, $this->_cfIDs)) {
          $this->_cfIDs[$cfID] = array();
        }
        $this->_cfIDs[$cfID][] = $value;
      }

      if (!array_key_exists($value[0], $this->_paramLookup)) {
        $this->_paramLookup[$value[0]] = array();
      }
      $this->_paramLookup[$value[0]][] = $value;
    }
  }

  /**
   * Some composite fields do not appear in the fields array
   * hack to make them part of the query
   *
   * @return void
   * @access public
   */
  function addSpecialFields() {
    static $special = array('contact_type', 'contact_sub_type', 'sort_name', 'display_name');
    foreach ($special as $name) {
      if (CRM_Utils_Array::value($name, $this->_returnProperties)) {
        $this->_select[$name] = "contact_a.{$name} as $name";
        $this->_element[$name] = 1;
      }
    }
  }

  /**
   * Given a list of conditions in params and a list of desired
   * return Properties generate the required select and from
   * clauses. Note that since the where clause introduces new
   * tables, the initial attempt also retrieves all variables used
   * in the params list
   *
   * @return void
   * @access public
   */
  function selectClause() {
    $properties = array();

    $this->addSpecialFields();

    foreach ($this->_fields as $name => $field) {

      // skip component fields
      // there are done by the alter query below
      // and need not be done on every field
      if ((substr($name, 0, 12) == 'participant_') ||
        (substr($name, 0, 7) == 'pledge_') ||
        (substr($name, 0, 5) == 'case_')
      ) {
        continue;
      }

      // redirect to activity select clause
      if (substr($name, 0, 9) == 'activity_') {
        CRM_Activity_BAO_Query::select($this);
        continue;
      }

      // if this is a hierarchical name, we ignore it
      $names = explode('-', $name);
      if (count($names > 1) && isset($names[1]) && is_numeric($names[1])) {
        continue;
      }

      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);

      if (CRM_Utils_Array::value($name, $this->_paramLookup) ||
        CRM_Utils_Array::value($name, $this->_returnProperties)
      ) {

        if ($cfID) {
          // add to cfIDs array if not present
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
        }
        elseif (isset($field['where'])) {
          list($tableName, $fieldName) = explode('.', $field['where'], 2);
          if (isset($tableName)) {

            if (CRM_Utils_Array::value($tableName, self::$_dependencies)) {
              $this->_tables['civicrm_address'] = 1;
              $this->_select['address_id'] = 'civicrm_address.id as address_id';
              $this->_element['address_id'] = 1;
            }

            if ($tableName == 'gender' || $tableName == 'individual_prefix'
              || $tableName == 'individual_suffix' || $tableName == 'im_provider'
              || $tableName == 'email_greeting' || $tableName == 'postal_greeting'
              || $tableName == 'addressee'
            ) {
              CRM_Core_OptionValue::select($this);
              if (in_array($tableName, array(
                'email_greeting', 'postal_greeting', 'addressee'))) {
                //get display
                $greetField = "{$name}_display";
                $this->_select[$greetField] = "contact_a.{$greetField} as {$greetField}";
                $this->_element[$greetField] = 1;
                //get custom
                $greetField = "{$name}_custom";
                $this->_select[$greetField] = "contact_a.{$greetField} as {$greetField}";
                $this->_element[$greetField] = 1;
              }
            }
            else {
              $this->_tables[$tableName] = 1;

              // also get the id of the tableName
              $tName = substr($tableName, 8);

              if ($tName != 'contact') {
                $this->_select["{$tName}_id"] = "{$tableName}.id as {$tName}_id";
                $this->_element["{$tName}_id"] = 1;
              }

              //special case for phone
              if ($name == 'phone') {
                $this->_select['phone_type_id'] = "civicrm_phone.phone_type_id as phone_type_id";
                $this->_element['phone_type_id'] = 1;
              }

              // if IM then select provider_id also
              // to get "IM Service Provider" in a file to be exported, CRM-3140
              if ($name == 'im') {
                $this->_select['provider_id'] = "civicrm_im.provider_id as provider_id";
                $this->_element['provider_id'] = 1;
              }

              if ($name == 'state_province') {
                $this->_select[$name] = "civicrm_state_province.abbreviation as `$name`, civicrm_state_province.name as state_province_name";
                $this->_element['state_province_name'] = 1;
              }
              elseif ($tName == 'contact') {
                // special case, when current employer is set for Individual contact
                if ($fieldName == 'organization_name') {
                  $this->_select[$name] = "IF ( contact_a.contact_type = 'Individual', NULL, contact_a.organization_name ) as organization_name";
                }
                elseif ($fieldName != 'id') {
                  $this->_select[$name] = "contact_a.{$fieldName}  as `$name`";
                }
              }
              else {
                $this->_select[$name] = "{$field['where']} as `$name`";
              }
              $this->_element[$name] = 1;
            }
          }
        }
        elseif ($name === 'tags') {
          $this->_useGroupBy = TRUE;
          $this->_select[$name] = "GROUP_CONCAT(DISTINCT(civicrm_tag.name)) as tags";
          $this->_element[$name] = 1;
          $this->_tables['civicrm_tag'] = 1;
          $this->_tables['civicrm_entity_tag'] = 1;
        }
        elseif ($name === 'groups') {
          $this->_useGroupBy = TRUE;
          $this->_select[$name] = "GROUP_CONCAT(DISTINCT(civicrm_group.title)) as groups";
          $this->_element[$name] = 1;
          $this->_tables['civicrm_group'] = 1;
        }
        elseif ($name === 'notes') {
          $this->_useGroupBy = TRUE;
          $this->_select[$name] = "GROUP_CONCAT(DISTINCT(civicrm_note.note)) as notes";
          $this->_element[$name] = 1;
          $this->_tables['civicrm_note'] = 1;
        }
        elseif ($name === 'current_employer') {
          $this->_select[$name] = "IF ( contact_a.contact_type = 'Individual', contact_a.organization_name, NULL ) as current_employer";
          $this->_element[$name] = 1;
        }
      }

      if ($cfID &&
        CRM_Utils_Array::value('is_search_range', $field)
      ) {
        // this is a custom field with range search enabled, so we better check for two/from values
        if (CRM_Utils_Array::value($name . '_from', $this->_paramLookup)) {
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
          foreach ($this->_paramLookup[$name . '_from'] as $pID => $p) {
            // search in the cdID array for the same grouping
            $fnd = FALSE;
            foreach ($this->_cfIDs[$cfID] as $cID => $c) {
              if ($c[3] == $p[3]) {
                $this->_cfIDs[$cfID][$cID][2]['from'] = $p[2];
                $fnd = TRUE;
              }
            }
            if (!$fnd) {
              $p[2] = array('from' => $p[2]);
              $this->_cfIDs[$cfID][] = $p;
            }
          }
        }
        if (CRM_Utils_Array::value($name . '_to', $this->_paramLookup)) {
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
          foreach ($this->_paramLookup[$name . '_to'] as $pID => $p) {
            // search in the cdID array for the same grouping
            $fnd = FALSE;
            foreach ($this->_cfIDs[$cfID] as $cID => $c) {
              if ($c[4] == $p[4]) {
                $this->_cfIDs[$cfID][$cID][2]['to'] = $p[2];
                $fnd = TRUE;
              }
            }
            if (!$fnd) {
              $p[2] = array('to' => $p[2]);
              $this->_cfIDs[$cfID][] = $p;
            }
          }
        }
      }
    }

    // add location as hierarchical elements
    $this->addHierarchicalElements();

    // add multiple field like website
    $this->addMultipleElements();

    //fix for CRM-951
    CRM_Core_Component::alterQuery($this, 'select');

    if (!empty($this->_cfIDs)) {
      $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs, TRUE);
      $this->_customQuery->query();
      $this->_select = array_merge($this->_select, $this->_customQuery->_select);
      $this->_element = array_merge($this->_element, $this->_customQuery->_element);
      $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
      $this->_whereTables = array_merge($this->_whereTables, $this->_customQuery->_whereTables);
      $this->_options = $this->_customQuery->_options;
    }
  }

  /**
   * If the return Properties are set in a hierarchy, traverse the hierarchy to get
   * the return values
   *
   * @return void
   * @access public
   */
  function addHierarchicalElements() {
    if (!CRM_Utils_Array::value('location', $this->_returnProperties)) {
      return;
    }
    if (!is_array($this->_returnProperties['location'])) {
      return;
    }

    $locationTypes = CRM_Core_PseudoConstant::locationType();
    $processed = array();
    $index = 0;

    $addressCustomFields = CRM_Core_BAO_CustomField::getFieldsForImport('Address');
    $addressCustomFieldIds = array();

    foreach ($this->_returnProperties['location'] as $name => $elements) {
      $lCond = self::getPrimaryCondition($name);

      if (!$lCond) {
        $locationTypeId = array_search($name, $locationTypes);
        if ($locationTypeId === FALSE) {
          continue;
        }
        $lCond = "location_type_id = $locationTypeId";
        $this->_useDistinct = TRUE;

        //commented for CRM-3256
        $this->_useGroupBy = TRUE;
      }

      $name = str_replace(' ', '_', $name);

      $tName = "$name-location_type";
      $ltName = "`$name-location_type`";
      $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
      $this->_select["{$tName}"] = "`$tName`.name as `{$tName}`";
      $this->_element["{$tName}_id"] = 1;
      $this->_element["{$tName}"] = 1;

      $locationTypeName = $tName;
      $locationTypeJoin = array();

      $addAddress = FALSE;
      $addWhereCount = 0;
      foreach ($elements as $elementFullName => $dontCare) {
        $index++;
        $elementName = $elementCmpName = $elementFullName;

        if (substr($elementCmpName, 0, 5) == 'phone') {
          $elementCmpName = 'phone';
        }

        if (in_array($elementCmpName, array_keys($addressCustomFields))) {
          if ($cfID = CRM_Core_BAO_CustomField::getKeyID($elementCmpName)) {
            $addressCustomFieldIds[$cfID][$name] = 1;
          }
        }
        //add address table only once
        if ((in_array($elementCmpName, self::$_locationSpecificFields) || !empty($addressCustomFieldIds))
          && !$addAddress
          && !in_array($elementCmpName, array('email', 'phone', 'im', 'openid'))
        ) {
          $tName = "$name-address";
          $aName = "`$name-address`";
          $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
          $this->_element["{$tName}_id"] = 1;
          $addressJoin = "\nLEFT JOIN civicrm_address $aName ON ($aName.contact_id = contact_a.id AND $aName.$lCond)";
          $this->_tables[$tName] = $addressJoin;
          $locationTypeJoin[$tName] = " ( $aName.location_type_id = $ltName.id ) ";
          $processed[$aName] = 1;
          $addAddress = TRUE;
        }

        $cond = $elementType = '';
        if (strpos($elementName, '-') !== FALSE) {
          // this is either phone, email or IM
          list($elementName, $elementType) = explode('-', $elementName);


          if (($elementName != 'phone') && ($elementName != 'im')) {
            $cond = self::getPrimaryCondition($elementType);
          }
          // CRM-13011 : If location type is primary, do not restrict search to the phone
          // type id - we want the primary phone, regardless of what type it is.
          // Otherwise, restrict to the specified phone type for the given field.
          if ((!$cond) && ($elementName == 'phone') && $elements['location_type'] != 'Primary') {
            $cond = "phone_type_id = '$elementType'";
          }
          elseif ((!$cond) && ($elementName == 'im')) {
            // IM service provider id, CRM-3140
            $cond = "provider_id = '$elementType'";
          }
          $elementType = '-' . $elementType;
        }

        $field = CRM_Utils_Array::value($elementName, $this->_fields);

        // hack for profile, add location id
        if (!$field) {
          if ($elementType &&
            // fix for CRM-882( to handle phone types )
            !is_numeric($elementType)
          ) {
            if (is_numeric($name)) {
              $field = CRM_Utils_Array::value($elementName . "-Primary$elementType", $this->_fields);
            }
            else {
              $field = CRM_Utils_Array::value($elementName . "-$locationTypeId$elementType", $this->_fields);
            }
          }
          elseif (is_numeric($name)) {
            //this for phone type to work
            if (in_array($elementName, array('phone', 'phone_ext'))) {
              $field = CRM_Utils_Array::value($elementName . "-Primary" . $elementType, $this->_fields);
            }
            else {
              $field = CRM_Utils_Array::value($elementName . "-Primary", $this->_fields);
            }
          }
          else {
            //this is for phone type to work for profile edit
            if (in_array($elementName, array('phone', 'phone_ext'))) {
              $field = CRM_Utils_Array::value($elementName . "-$locationTypeId$elementType", $this->_fields);
            }
            else {
              $field = CRM_Utils_Array::value($elementName . "-$locationTypeId", $this->_fields);
            }
          }
        }

        // check if there is a value, if so also add to where Clause
        $addWhere = FALSE;
        if ($this->_params) {
          $nm = $elementName;
          if (isset($locationTypeId)) {
            $nm .= "-$locationTypeId";
          }
          if (!is_numeric($elementType)) {
            $nm .= "$elementType";
          }

          foreach ($this->_params as $id => $values) {
            if ($values[0] == $nm ||
              (in_array($elementName, array('phone', 'im'))
                && (strpos($values[0], $nm) !== FALSE)
              )
            ) {
              $addWhere = TRUE;
              $addWhereCount++;
              break;
            }
          }
        }

        if ($field && isset($field['where'])) {
          list($tableName, $fieldName) = explode('.', $field['where'], 2);
          $tName = $name . '-' . substr($tableName, 8) . $elementType;
          if (isset($tableName)) {
            $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
            $this->_element["{$tName}_id"] = 1;
            if (substr($tName, -15) == '-state_province') {
              // FIXME: hack to fix CRM-1900
              $a = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
                'address_format'
              );

              if (substr_count($a, 'state_province_name') > 0) {
                $this->_select["{$name}-{$elementFullName}"] = "`$tName`.name as `{$name}-{$elementFullName}`";
              }
              else {
                $this->_select["{$name}-{$elementFullName}"] = "`$tName`.abbreviation as `{$name}-{$elementFullName}`";
              }
            }
            else {
              if (substr($elementFullName, 0, 2) == 'im') {
                $provider = "{$name}-{$elementFullName}-provider_id";
                $this->_select[$provider] = "`$tName`.provider_id as `{$name}-{$elementFullName}-provider_id`";
                $this->_element[$provider] = 1;
              }

              $this->_select["{$name}-{$elementFullName}"] = "`$tName`.$fieldName as `{$name}-{$elementFullName}`";
            }

            $this->_element["{$name}-{$elementFullName}"] = 1;
            if (!CRM_Utils_Array::value("`$tName`", $processed)) {
              $processed["`$tName`"] = 1;
              $newName = $tableName . '_' . $index;
              switch ($tableName) {
                case 'civicrm_phone':
                case 'civicrm_email':
                case 'civicrm_im':
                case 'civicrm_openid':

                  $this->_tables[$tName] = "\nLEFT JOIN $tableName `$tName` ON contact_a.id = `$tName`.contact_id AND `$tName`.$lCond";
                  // this special case to add phone type
                  if ($cond) {
                    $phoneTypeCondition = " AND `$tName`.$cond ";
                    //gross hack to pickup corrupted data also, CRM-7603
                    if (strpos($cond, 'phone_type_id') !== FALSE) {
                      $phoneTypeCondition = " AND ( `$tName`.$cond OR `$tName`.phone_type_id IS NULL ) ";
                    }
                    $this->_tables[$tName] .= $phoneTypeCondition;
                  }

                  //build locationType join
                  $locationTypeJoin[$tName] = " ( `$tName`.location_type_id = $ltName.id )";

                  if ($addWhere) {
                    $this->_whereTables[$tName] = $this->_tables[$tName];
                  }
                  break;

                case 'civicrm_state_province':
                  $this->_tables[$tName] = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.state_province_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                    $this->_whereTables[$tName] = $this->_tables[$tName];
                  }
                  break;

                case 'civicrm_country':
                  $this->_tables[$newName] = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.country_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                    $this->_whereTables[$newName] = $this->_tables[$newName];
                  }
                  break;

                case 'civicrm_county':
                  $this->_tables[$newName] = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.county_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                    $this->_whereTables[$newName] = $this->_tables[$newName];
                  }
                  break;

                default:
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                  }
                  break;
              }
            }
          }
        }
      }

      // add location type  join
      $ltypeJoin = "\nLEFT JOIN civicrm_location_type $ltName ON ( " . implode('OR', $locationTypeJoin) . " )";
      $this->_tables[$locationTypeName] = $ltypeJoin;

      // table should be present in $this->_whereTables,
      // to add its condition in location type join, CRM-3939.
      if ($addWhereCount) {
        $locClause = array();
        foreach ($this->_whereTables as $tableName => $clause) {
          if (CRM_Utils_Array::value($tableName, $locationTypeJoin)) {
            $locClause[] = $locationTypeJoin[$tableName];
          }
        }

        if (!empty($locClause)) {
          $this->_whereTables[$locationTypeName] = "\nLEFT JOIN civicrm_location_type $ltName ON ( " . implode('OR', $locClause) . " )";
        }
      }
    }

    if (!empty($addressCustomFieldIds)) {
      $cfIDs = $addressCustomFieldIds;
      $customQuery = new CRM_Core_BAO_CustomQuery($cfIDs);
      foreach ($addressCustomFieldIds as $cfID => $locTypeName) {
        foreach ($locTypeName as $name => $dnc) {
          $fieldName = "$name-custom_{$cfID}";
          $tName = "$name-address-custom-{$cfID}";
          $aName = "`$name-address-custom-{$cfID}`";
          $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
          $this->_element["{$tName}_id"] = 1;
          $this->_select[$fieldName] = "`$tName`.{$customQuery->_fields[$cfID]['column_name']} as `{$fieldName}`";
          $this->_element[$fieldName] = 1;
          $this->_tables[$tName] = "\nLEFT JOIN {$customQuery->_fields[$cfID]['table_name']} $aName ON ($aName.entity_id = `$name-address`.id)";
        }
      }
    }
  }

  /**
   * If the return Properties are set in a hierarchy, traverse the hierarchy to get
   * the return values
   *
   * @return void
   * @access public
   */
  function addMultipleElements() {
    if (!CRM_Utils_Array::value('website', $this->_returnProperties)) {
      return;
    }
    if (!is_array($this->_returnProperties['website'])) {
      return;
    }

    foreach ($this->_returnProperties['website'] as $key => $elements) {
      foreach ($elements as $elementFullName => $dontCare) {
        $tName = "website-{$key}-{$elementFullName}";
        $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
        $this->_select["{$tName}"] = "`$tName`.url as `{$tName}`";
        $this->_element["{$tName}_id"] = 1;
        $this->_element["{$tName}"] = 1;

        $type = "website-{$key}-website_type_id";
        $this->_select[$type] = "`$tName`.website_type_id as `{$type}`";
        $this->_element[$type] = 1;
        $this->_tables[$tName] = "\nLEFT JOIN civicrm_website `$tName` ON (`$tName`.contact_id = contact_a.id )";
      }
    }
  }

  /**
   * generate the query based on what type of query we need
   *
   * @param boolean $count
   * @param boolean $sortByChar
   * @param boolean $groupContacts
   *
   * @return the sql string for that query (this will most likely
   * change soon)
   * @access public
   */
  function query($count = FALSE, $sortByChar = FALSE, $groupContacts = FALSE) {
    if ($count) {
      if (isset($this->_distinctComponentClause)) {
        // we add distinct to get the right count for components
        // for the more complex result set, we use GROUP BY the same id
        // CRM-9630
        $select = "SELECT count( DISTINCT {$this->_distinctComponentClause} )";
      }
      else {
        $select = 'SELECT count(DISTINCT contact_a.id) as rowCount';
      }
      $from = $this->_simpleFromClause;
      if ($this->_useDistinct) {
        $this->_useGroupBy = TRUE;
      }
    }
    elseif ($sortByChar) {
      $select = 'SELECT DISTINCT UPPER(LEFT(contact_a.sort_name, 1)) as sort_name';
      $from = $this->_simpleFromClause;
    }
    elseif ($groupContacts) {
      $select = 'SELECT contact_a.id as id';
      if ($this->_useDistinct) {
        $this->_useGroupBy = TRUE;
      }
      $from = $this->_simpleFromClause;
    }
    else {
      if (CRM_Utils_Array::value('group', $this->_paramLookup)) {
        // make sure there is only one element
        // this is used when we are running under smog and need to know
        // how the contact was added (CRM-1203)
        if ((count($this->_paramLookup['group']) == 1) &&
          (count($this->_paramLookup['group'][0][2]) == 1)
        ) {
          $groups = array_keys($this->_paramLookup['group'][0][2]);
          $groupId = $groups[0];

          //check if group is saved search
          $group = new CRM_Contact_BAO_Group();
          $group->id = $groupId;
          $group->find(TRUE);

          if (!isset($group->saved_search_id)) {
            $tbName = "`civicrm_group_contact-{$groupId}`";
            $this->_select['group_contact_id'] = "$tbName.id as group_contact_id";
            $this->_element['group_contact_id'] = 1;
            $this->_select['status'] = "$tbName.status as status";
            $this->_element['status'] = 1;
            $this->_tables[$tbName] = 1;
          }
        }
        $this->_useGroupBy = TRUE;
      }
      if ($this->_useDistinct && !isset($this->_distinctComponentClause)) {
        if (!($this->_mode & CRM_Contact_BAO_Query::MODE_ACTIVITY)) {
          // CRM-5954
          $this->_select['contact_id'] = 'contact_a.id as contact_id';
          $this->_useDistinct = FALSE;
          $this->_useGroupBy = TRUE;
        }
      }

      $select = "SELECT ";
      if (isset($this->_distinctComponentClause)) {
        $select .= "{$this->_distinctComponentClause}, ";
      }
      $select .= implode(', ', $this->_select);
      $from = $this->_fromClause;
    }

    $where = '';
    if (!empty($this->_whereClause)) {
      $where = "WHERE {$this->_whereClause}";
    }

    $having = '';
    if (!empty($this->_having)) {
      foreach ($this->_having as $havingsets) {
        foreach ($havingsets as $havingset) {
          $havingvalue[] = $havingset;
        }
      }
      $having = ' HAVING ' . implode(' AND ', $havingvalue);
    }

    // if we are doing a transform, do it here
    // use the $from, $where and $having to get the contact ID
    if ($this->_displayRelationshipType) {
      $this->filterRelatedContacts($from, $where, $having);
    }

    return array($select, $from, $where, $having);
  }

  function &getWhereValues($name, $grouping) {
    $result = NULL;
    foreach ($this->_params as $id => $values) {
      if ($values[0] == $name && $values[3] == $grouping) {
        return $values;
      }
    }

    return $result;
  }

  static function fixDateValues($relative, &$from, &$to) {
    if ($relative) {
      list($from, $to) = CRM_Utils_Date::getFromTo($relative, $from, $to);
    }
  }

  static function convertFormValues(&$formValues, $wildcard = 0, $useEquals = FALSE) {
    $params = array();
    if (empty($formValues)) {
      return $params;
    }

    foreach ($formValues as $id => $values) {
      if ($id == 'privacy') {
        if (is_array($formValues['privacy'])) {
          $op = CRM_Utils_Array::value('do_not_toggle', $formValues['privacy']) ? '=' : '!=';
          foreach ($formValues['privacy'] as $key => $value) {
            if ($value) {
              $params[] = array($key, $op, $value, 0, 0);
            }
          }
        }
      }
      elseif ($id == 'email_on_hold') {
        if ($formValues['email_on_hold']['on_hold']) {
          $params[] = array('on_hold', '=', $formValues['email_on_hold']['on_hold'], 0, 0);
        }
      }
      elseif (preg_match('/_date_relative$/', $id) ||
              $id == 'event_relative' ||
              $id == 'case_from_relative' ||
              $id == 'case_to_relative'
              ) {
        if ($id == 'event_relative') {
          $fromRange = 'event_start_date_low';
          $toRange = 'event_end_date_high';
        }
        else if ($id == 'case_from_relative') {
          $fromRange = 'case_from_start_date_low';
          $toRange = 'case_from_start_date_high';
        }
        else if ($id == 'case_to_relative') {
          $fromRange = 'case_to_end_date_low';
          $toRange = 'case_to_end_date_high';
        }
        else {
          $dateComponent = explode('_date_relative', $id);
          $fromRange = "{$dateComponent[0]}_date_low";
          $toRange = "{$dateComponent[0]}_date_high";
        }

        if (array_key_exists($fromRange, $formValues) && array_key_exists($toRange, $formValues)) {
          CRM_Contact_BAO_Query::fixDateValues($formValues[$id], $formValues[$fromRange], $formValues[$toRange]);
          continue;
        }
      }
      else {
        $values = CRM_Contact_BAO_Query::fixWhereValues($id, $values, $wildcard, $useEquals);

        if (!$values) {
          continue;
        }
        $params[] = $values;
      }
    }
    return $params;
  }

  static function &fixWhereValues($id, &$values, $wildcard = 0, $useEquals = FALSE) {
    // skip a few search variables
    static $skipWhere = NULL;
    static $arrayValues = NULL;
    static $likeNames = NULL;
    $result = NULL;

    if (CRM_Utils_System::isNull($values)) {
      return $result;
    }

    if (!$skipWhere) {
      $skipWhere = array(
        'task', 'radio_ts', 'uf_group_id',
        'component_mode', 'qfKey', 'operator',
        'display_relationship_type',
      );
    }

    if (in_array($id, $skipWhere) ||
      substr($id, 0, 4) == '_qf_' ||
      substr($id, 0, 7) == 'hidden_'
    ) {
      return $result;
    }

    if (!$likeNames) {
      $likeNames = array('sort_name', 'email', 'note', 'display_name');
    }

    // email comes in via advanced search
    // so use wildcard always
    if ($id == 'email') {
      $wildcard = 1;
    }

    if (!$useEquals &&  in_array($id, $likeNames)) {
      $result = array($id, 'LIKE', $values, 0, 1);
    }
    elseif (is_string($values) && strpos($values, '%') !== FALSE) {
      $result = array($id, 'LIKE', $values, 0, 0);
    }
    elseif ($id == 'group') {
      if (is_array($values)) {
        foreach ($values as $groupIds => $val) {
          $matches = array();
          if (preg_match('/-(\d+)$/', $groupIds, $matches)) {
            if (strlen($matches[1]) > 0) {
              $values[$matches[1]] = 1;
              unset($values[$groupIds]);
            }
          }
        }
      }
      else {
        $groupIds = explode(',', $values);
        unset($values);
        foreach ($groupIds as $groupId) {
          $values[$groupId] = 1;
        }
      }

      $result = array($id, 'IN', $values, 0, 0);
    }
    elseif ($id == 'contact_tags' || $id == 'tag') {
      if (!is_array($values)) {
        $tagIds = explode(',', $values);
        unset($values);
        foreach ($tagIds as $tagId) {
          $values[$tagId] = 1;
        }
      }
      $result = array($id, 'IN', $values, 0, 0);
    }
    else {
      $result = array($id, '=', $values, 0, $wildcard);
    }

    return $result;
  }

  function whereClauseSingle(&$values) {
    // do not process custom fields or prefixed contact ids or component params
    if (CRM_Core_BAO_CustomField::getKeyID($values[0]) ||
      (substr($values[0], 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) ||
      (substr($values[0], 0, 13) == 'contribution_') ||
      (substr($values[0], 0, 6) == 'event_') ||
      (substr($values[0], 0, 12) == 'participant_') ||
      (substr($values[0], 0, 7) == 'member_') ||
      (substr($values[0], 0, 6) == 'grant_') ||
      (substr($values[0], 0, 7) == 'pledge_') ||
      (substr($values[0], 0, 5) == 'case_') ||
      (substr($values[0], 0, 10) == 'financial_')
    ) {
      return;
    }

    switch ($values[0]) {
      case 'deleted_contacts':
        $this->deletedContacts($values);
        return;

      case 'contact_type':
        $this->contactType($values);
        return;

      case 'contact_sub_type':
        $this->contactSubType($values);
        return;

      case 'group':
        list($name, $op, $value, $grouping, $wildcard) = $values;
        $this->group($values);
        return;
      case 'group_type':
        // so we resolve this into a list of groups & proceed as if they had been
        // handed in
        list($name, $op, $value, $grouping, $wildcard) = $values;
        $values[0] = 'group';
        $values[1] = 'IN';
        $this->_paramLookup['group'][0][0] ='group';
        $this->_paramLookup['group'][0][1] = 'IN';
        $this->_paramLookup['group'][0][2] = $values[2] = $this->getGroupsFromTypeCriteria($value);
        $this->group($values);
        return;
      // case tag comes from find contacts

      case 'tag_search':
        $this->tagSearch($values);
        return;

      case 'tag':
      case 'contact_tags':
        $this->tag($values);
        return;

      case 'note':
      case 'note_body':
      case 'note_subject':
        $this->notes($values);
        return;

      case 'uf_user':
        $this->ufUser($values);
        return;

      case 'sort_name':
      case 'display_name':
        $this->sortName($values);
        //force civicrm_activity_target, CRM-7812
        self::$_withContactActivitiesOnly = TRUE;
        return;

      case 'email':
        $this->email($values);
        return;

      case 'phone_numeric':
        $this->phone_numeric($values);
        return;

      case 'phone_phone_type_id':
      case 'phone_location_type_id':
        $this->phone_option_group($values);
        return;

      case 'street_address':
        $this->street_address($values);
        return;

      case 'street_number':
        $this->street_number($values);
        return;

      case 'sortByCharacter':
        $this->sortByCharacter($values);
        return;

      case 'location_type':
        $this->locationType($values);
        return;

      case 'county':
        $this->county($values);
        return;

      case 'state_province':
        $this->stateProvince($values);
        return;

      case 'country':
        $this->country($values, FALSE);
        return;

      case 'postal_code':
      case 'postal_code_low':
      case 'postal_code_high':
        $this->postalCode($values);
        return;

      case 'activity_date':
      case 'activity_date_low':
      case 'activity_date_high':
      case 'activity_role':
      case 'activity_status':
      case 'activity_subject':
      case 'test_activities':
      case 'activity_type_id':
      case 'activity_survey_id':
      case 'activity_tags':
      case 'activity_taglist':
      case 'activity_test':
      case 'activity_contact_name':
      case 'activity_campaign_id':
      case 'activity_engagement_level':
      case 'activity_id':
      case 'source_contact':
        CRM_Activity_BAO_Query::whereClauseSingle($values, $this);
        return;

      case 'birth_date_low':
      case 'birth_date_high':
      case 'deceased_date_low':
      case 'deceased_date_high':
        $this->demographics($values);
        return;

      case 'log_date_low':
      case 'log_date_high':
        $this->modifiedDates($values);
        return;

      case 'changed_by':
        $this->changeLog($values);
        return;

      case 'do_not_phone':
      case 'do_not_email':
      case 'do_not_mail':
      case 'do_not_sms':
      case 'do_not_trade':
      case 'is_opt_out':
        $this->privacy($values);
        return;

      case 'privacy_options':
        $this->privacyOptions($values);
        return;

      case 'privacy_operator':
      case 'privacy_toggle':
        // these are handled by privacy options
        return;

      case 'preferred_communication_method':
        $this->preferredCommunication($values);
        return;

      case 'relation_type_id':
        $this->relationship($values);
        return;

      case 'relation_target_name':
      case 'relation_status':
      case 'relation_date_low':
      case 'relation_date_high':
        // since this case is handled with the above
        return;

      case 'task_status_id':
        $this->task($values);
        return;

      case 'task_id':
        // since this case is handled with the above
        return;

      case 'prox_distance':
        CRM_Contact_BAO_ProximityQuery::process($this, $values);
        return;

      case 'prox_street_address':
      case 'prox_city':
      case 'prox_postal_code':
      case 'prox_state_province_id':
      case 'prox_country_id':
        // handled by the proximity_distance clause
        return;

      default:
        $this->restWhere($values);
        return;
    }
  }

  /**
   * Given a list of conditions in params generate the required
   * where clause
   *
   * @return void
   * @access public
   */
  function whereClause() {
    $this->_where[0] = array();
    $this->_qill[0] = array();

    $config = CRM_Core_Config::singleton();

    $this->includeContactIds();
    if (!empty($this->_params)) {

      foreach (array_keys($this->_params) as $id) {
        if (!CRM_Utils_Array::value(0, $this->_params[$id])) {
          continue;
        }
        // check for both id and contact_id
        if ($this->_params[$id][0] == 'id' || $this->_params[$id][0] == 'contact_id') {
          if (
            $this->_params[$id][1] == 'IS NULL' ||
            $this->_params[$id][1] == 'IS NOT NULL'
          ) {
            $this->_where[0][] = "contact_a.id {$this->_params[$id][1]}";
          }
          elseif (is_array($this->_params[$id][2])) {
            $idList = implode("','", $this->_params[$id][2]);
            $this->_where[0][] = "contact_a.id IN ({$idList})";
          }
          else {
            $this->_where[0][] = "contact_a.id {$this->_params[$id][1]} {$this->_params[$id][2]}";
          }
        }
        else {
          $this->whereClauseSingle($this->_params[$id]);
        }
      }

      CRM_Core_Component::alterQuery($this, 'where');
    }

    if ($this->_customQuery) {
      // Added following if condition to avoid the wrong value diplay for 'myaccount' / any UF info.
      // Hope it wont affect the other part of civicrm.. if it does please remove it.
      if (!empty($this->_customQuery->_where)) {
        $this->_where = CRM_Utils_Array::crmArrayMerge($this->_where, $this->_customQuery->_where);
      }

      $this->_qill = CRM_Utils_Array::crmArrayMerge($this->_qill, $this->_customQuery->_qill);
    }

    $clauses = array();
    $andClauses = array();

    $validClauses = 0;
    if (!empty($this->_where)) {
      foreach ($this->_where as $grouping => $values) {
        if ($grouping > 0 && !empty($values)) {
          $clauses[$grouping] = ' ( ' . implode(" {$this->_operator} ", $values) . ' ) ';
          $validClauses++;
        }
      }

      if (!empty($this->_where[0])) {
        $andClauses[] = ' ( ' . implode(" {$this->_operator} ", $this->_where[0]) . ' ) ';
      }
      if (!empty($clauses)) {
        $andClauses[] = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }

      if ($validClauses > 1) {
        $this->_useDistinct = TRUE;
      }
    }

    return implode(' AND ', $andClauses);
  }

  function restWhere(&$values) {
    $name = CRM_Utils_Array::value(0, $values);
    $op = CRM_Utils_Array::value(1, $values);
    $value = CRM_Utils_Array::value(2, $values);
    $grouping = CRM_Utils_Array::value(3, $values);
    $wildcard = CRM_Utils_Array::value(4, $values);

    if (isset($grouping) && !CRM_Utils_Array::value($grouping, $this->_where)) {
      $this->_where[$grouping] = array();
    }

    $multipleFields = array('url');

    //check if the location type exits for fields
    $lType = '';
    $locType = explode('-', $name);

    if (!in_array($locType[0], $multipleFields)) {
      //add phone type if exists
      if (isset($locType[2]) && $locType[2]) {
        $locType[2] = CRM_Core_DAO::escapeString($locType[2]);
      }
    }

    $field = CRM_Utils_Array::value($name, $this->_fields);

    if (!$field) {
      $field = CRM_Utils_Array::value($locType[0], $this->_fields);

      if (!$field) {
        return;
      }
    }

    $setTables = TRUE;

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

    if (substr($name, 0, 14) === 'state_province') {
      if (isset($locType[1]) && is_numeric($locType[1])) {
        $setTables = FALSE;

        list($tName, $fldName) = self::getLocationTableName($field['where'], $locType);
        $this->_whereTables[$tName] = $this->_tables[$tName];
        $where = "`$tName`.$fldName";
      }
      else {
        $where = $field['where'];
      }

      if (is_numeric($value)) {
        $where = str_replace('.name', '.id', $where);
        $this->_where[$grouping][] = self::buildClause($where, $op, $value, 'Positive');
        $states = CRM_Core_PseudoConstant::stateProvince();
        $value = $states[(int ) $value];
      }
      else {
        $wc = self::caseImportant($op) ? "LOWER($where)" : $where;
        $this->_where[$grouping][] = self::buildClause($wc, $op, $value, 'String');
      }
      if (!$lType) {
        $this->_qill[$grouping][] = ts('State') . " $op '$value'";
      }
      else {
        $this->_qill[$grouping][] = ts('State') . " ($lType) $op '$value'";
      }
    }
    elseif (substr($name, 0, 7) === 'country') {
      if (isset($locType[1]) && is_numeric($locType[1])) {
        $setTables = FALSE;

        list($tName, $fldName) = self::getLocationTableName($field['where'], $locType);
        $this->_whereTables[$tName] = $this->_tables[$tName];
        $where = "`$tName`.$fldName";
      }
      else {
        $where = $field['where'];
      }

      if (is_numeric($value)) {
        $where = str_replace('.name', '.id', $where);
        $this->_where[$grouping][] = self::buildClause($where, $op, $value, 'Positive');
        $countries = CRM_Core_PseudoConstant::country();
        $value = $countries[(int ) $value];
      }
      else {
        $wc = self::caseImportant($op) ? "LOWER($where)" : $where;
        $this->_where[$grouping][] = self::buildClause($wc, $op, $value, 'String');
      }
      if (!$lType) {
        $this->_qill[$grouping][] = ts('Country') . " $op '$value'";
      }
      else {
        $this->_qill[$grouping][] = ts('Country') . " ($lType) $op '$value'";
      }
    }
    elseif (substr($name, 0, 6) === 'county') {
      if (isset($locType[1]) && is_numeric($locType[1])) {
        $setTables = FALSE;

        list($tName, $fldName) = self::getLocationTableName($field['where'], $locType);
        $this->_whereTables[$tName] = $this->_tables[$tName];
        $where = "`$tName`.$fldName";
      }
      else {
        $where = $field['where'];
      }
      if (is_numeric($value)) {
        $where = str_replace('.name', '.id', $where);
        $this->_where[$grouping][] = self::buildClause($where, $op, $value, 'Positive');
        $counties = CRM_Core_PseudoConstant::county();
        $value = $counties[(int ) $value];
      }
      else {
        $wc = self::caseImportant($op) ? "LOWER($where)" : $where;
        $this->_where[$grouping][] = self::buildClause($wc, $op, $value, 'String');
      }

      if (!$lType) {
        $this->_qill[$grouping][] = ts('County') . " $op '$value'";
      }
      else {
        $this->_qill[$grouping][] = ts('County') . " ($lType) $op '$value'";
      }
    }
    elseif ($name === 'world_region') {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::worldRegion(),
        $field,
        ts('World Region')
      );
    }
    elseif ($name === 'individual_prefix') {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::individualPrefix(),
        $field,
        ts('Individual Prefix')
      );
    }
    elseif ($name === 'individual_suffix') {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::individualSuffix(),
        $field,
        ts('Individual Suffix')
      );
    }
    elseif ($name === 'gender') {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::gender(),
        $field,
        ts('Gender')
      );
      self::$_openedPanes[ts('Demographics')] = TRUE;
    }
    elseif ($name === 'birth_date') {
      $date = CRM_Utils_Date::processDate($value);
      $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $date);

      if ($date) {
        $date = CRM_Utils_Date::customFormat($date);
        $this->_qill[$grouping][] = "$field[title] $op \"$date\"";
      }
      else {
        $this->_qill[$grouping][] = "$field[title] $op";
      }
      self::$_openedPanes[ts('Demographics')] = TRUE;
    }
    elseif ($name === 'deceased_date') {
      $date = CRM_Utils_Date::processDate($value);
      $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $date);
      if ($date) {
        $date = CRM_Utils_Date::customFormat($date);
        $this->_qill[$grouping][] = "$field[title] $op \"$date\"";
      }
      else {
        $this->_qill[$grouping][] = "$field[title] $op";
      }
      self::$_openedPanes[ts('Demographics')] = TRUE;
    }
    elseif ($name === 'is_deceased') {
      $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $value);
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
      self::$_openedPanes[ts('Demographics')] = TRUE;
    }
    elseif ($name === 'contact_id') {
      if (is_int($value)) {
        $this->_where[$grouping][] = self::buildClause($field['where'], $op, $value);
        $this->_qill[$grouping][] = "$field[title] $op $value";
      }
    }
    elseif ($name === 'name') {
      $value = $strtolower(CRM_Core_DAO::escapeString($value));
      if ($wildcard) {
        $value = "%$value%";
        $op = 'LIKE';
      }
      $wc = self::caseImportant($op) ? "LOWER({$field['where']})" : "{$field['where']}";
      $this->_where[$grouping][] = self::buildClause($wc, $op, "'$value'");
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'current_employer') {
      $value = $strtolower(CRM_Core_DAO::escapeString($value));
      if ($wildcard) {
        $value = "%$value%";
        $op = 'LIKE';
      }
      $wc = self::caseImportant($op) ? "LOWER(contact_a.organization_name)" : "contact_a.organization_name";
      $this->_where[$grouping][] = self::buildClause($wc, $op,
        "'$value' AND contact_a.contact_type ='Individual'"
      );
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'email_greeting') {
      $filterCondition = array('greeting_type' => 'email_greeting');
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::greeting($filterCondition),
        $field,
        ts('Email Greeting')
      );
    }
    elseif ($name === 'postal_greeting') {
      $filterCondition = array('greeting_type' => 'postal_greeting');
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::greeting($filterCondition),
        $field,
        ts('Postal Greeting')
      );
    }
    elseif ($name === 'addressee') {
      $filterCondition = array('greeting_type' => 'addressee');
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        CRM_Core_PseudoConstant::greeting($filterCondition),
        $field,
        ts('Addressee')
      );
    }
    elseif (substr($name, 0, 4) === 'url-') {
      $tName = 'civicrm_website';
      $this->_whereTables[$tName] = $this->_tables[$tName] = "\nLEFT JOIN civicrm_website ON ( civicrm_website.contact_id = contact_a.id )";
      $value = $strtolower(CRM_Core_DAO::escapeString($value));
      if ($wildcard) {
        $value = "%$value%";
        $op = 'LIKE';
      }

      $wc = 'civicrm_website.url';
      $this->_where[$grouping][] = self::buildClause($wc, $op, "'$value'");
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'contact_is_deleted') {
      $this->_where[$grouping][] = self::buildClause("contact_a.is_deleted", $op, $value);
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    else {
      // sometime the value is an array, need to investigate and fix
      if (is_array($value)) {
        CRM_Core_Error::fatal();
      }

      if (!empty($field['where'])) {
        if ($op != 'IN') {
          $value = $strtolower($value);
        }
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }

        if (isset($locType[1]) &&
          is_numeric($locType[1])
        ) {
          $setTables = FALSE;

          //get the location name
          $locationType = CRM_Core_PseudoConstant::locationType();
          list($tName, $fldName) = self::getLocationTableName($field['where'], $locType);

          $where = "`$tName`.$fldName";

          $this->_where[$grouping][] = self::buildClause("LOWER($where)", $op, $value);
          $this->_whereTables[$tName] = $this->_tables[$tName];
          $this->_qill[$grouping][] = "$field[title] $op '$value'";
        }
        else {
          list($tableName, $fieldName) = explode('.', $field['where'], 2);
          if ($tableName == 'civicrm_contact') {
            $fieldName = "LOWER(contact_a.{$fieldName})";
          }
          else {
            if ($op != 'IN' && !is_numeric($value)) {
              $fieldName = "LOWER({$field['where']})";
            }
            else {
              $fieldName = "{$field['where']}";
            }
          }

          $type = NULL;
          if (CRM_Utils_Array::value('type', $field)) {
            $type = CRM_Utils_Type::typeToString($field['type']);
          }

          $this->_where[$grouping][] = self::buildClause($fieldName, $op, $value, $type);
          $this->_qill[$grouping][] = "$field[title] $op $value";
        }
      }
    }

    if ($setTables && isset($field['where'])) {
      list($tableName, $fieldName) = explode('.', $field['where'], 2);
      if (isset($tableName)) {
        $this->_tables[$tableName] = 1;
        $this->_whereTables[$tableName] = 1;
      }
    }
  }


  static function getLocationTableName(&$where, &$locType) {
    if (isset($locType[1]) && is_numeric($locType[1])) {
      list($tbName, $fldName) = explode(".", $where);

      //get the location name
      $locationType = CRM_Core_PseudoConstant::locationType();
      $specialFields = array('email', 'im', 'phone', 'openid', 'phone_ext');
      if (in_array($locType[0], $specialFields)) {
        //hack to fix / special handing for phone_ext
        if ($locType[0] == 'phone_ext') {
          $locType[0] = 'phone';
        }
        if (isset($locType[2]) && $locType[2]) {
          $tName = "{$locationType[$locType[1]]}-{$locType[0]}-{$locType[2]}";
        }
        else {
          $tName = "{$locationType[$locType[1]]}-{$locType[0]}";
        }
      }
      elseif (in_array($locType[0],
          array(
            'address_name', 'street_address', 'supplemental_address_1', 'supplemental_address_2',
            'city', 'postal_code', 'postal_code_suffix', 'geo_code_1', 'geo_code_2',
          )
        )) {
        //fix for search by profile with address fields.
        $tName = "{$locationType[$locType[1]]}-address";
      }
      elseif ($locType[0] == 'on_hold') {
        $tName = "{$locationType[$locType[1]]}-email";
      }
      else {
        $tName = "{$locationType[$locType[1]]}-{$locType[0]}";
      }
      $tName = str_replace(' ', '_', $tName);
      return array($tName, $fldName);
    }
    CRM_Core_Error::fatal();
  }

  /**
   * Given a result dao, extract the values and return that array
   *
   * @param Object $dao
   *
   * @return array values for this query
   */
  function store($dao) {
    $value = array();

    foreach ($this->_element as $key => $dontCare) {
      if (property_exists($dao, $key)) {
        if (strpos($key, '-') !== FALSE) {
          $values = explode('-', $key);
          $lastElement = array_pop($values);
          $current = &$value;
          $cnt = count($values);
          $count = 1;
          foreach ($values as $v) {
            if (!array_key_exists($v, $current)) {
              $current[$v] = array();
            }
            //bad hack for im_provider
            if ($lastElement == 'provider_id') {
              if ($count < $cnt) {
                $current = &$current[$v];
              }
              else {
                $lastElement = "{$v}_{$lastElement}";
              }
            }
            else {
              $current = &$current[$v];
            }
            $count++;
          }

          $current[$lastElement] = $dao->$key;
        }
        else {
          $value[$key] = $dao->$key;
        }
      }
    }
    return $value;
  }

  /**
   * getter for tables array
   *
   * @return array
   * @access public
   */
  function tables() {
    return $this->_tables;
  }

  function whereTables() {
    return $this->_whereTables;
  }

  /**
   * generate the where clause (used in match contacts and permissions)
   *
   * @param array $params
   * @param array $fields
   * @param array $tables
   * @param boolean $strict
   *
   * @return string
   * @access public
   * @static
   */
  static function getWhereClause($params, $fields, &$tables, &$whereTables, $strict = FALSE) {
    $query = new CRM_Contact_BAO_Query($params, NULL, $fields,
      FALSE, $strict
    );

    $tables = array_merge($query->tables(), $tables);
    $whereTables = array_merge($query->whereTables(), $whereTables);

    return $query->_whereClause;
  }

  /**
   * create the from clause
   *
   * @param array $tables tables that need to be included in this from clause
   *                      if null, return mimimal from clause (i.e. civicrm_contact)
   * @param array $inner  tables that should be inner-joined
   * @param array $right  tables that should be right-joined
   *
   * @return string the from clause
   * @access public
   * @static
   */
  static function fromClause(&$tables, $inner = NULL, $right = NULL, $primaryLocation = TRUE, $mode = 1) {

    $from = ' FROM civicrm_contact contact_a';
    if (empty($tables)) {
      return $from;
    }

    if (CRM_Utils_Array::value('civicrm_worldregion', $tables)) {
      $tables = array_merge(array('civicrm_country' => 1), $tables);
    }

    if ((CRM_Utils_Array::value('civicrm_state_province', $tables) ||
        CRM_Utils_Array::value('civicrm_country', $tables) ||
        CRM_Utils_Array::value('civicrm_county', $tables)
      ) &&
      !CRM_Utils_Array::value('civicrm_address', $tables)
    ) {
      $tables = array_merge(array('civicrm_address' => 1),
        $tables
      );
    }

    // add group_contact table if group table is present
    if (CRM_Utils_Array::value('civicrm_group', $tables) &&
      !CRM_Utils_Array::value('civicrm_group_contact', $tables)
    ) {
      $tables['civicrm_group_contact'] = " LEFT JOIN civicrm_group_contact ON civicrm_group_contact.contact_id = contact_a.id AND civicrm_group_contact.status = 'Added'";
    }

    // add group_contact and group table is subscription history is present
    if (CRM_Utils_Array::value('civicrm_subscription_history', $tables)
      && !CRM_Utils_Array::value('civicrm_group', $tables)
    ) {
      $tables = array_merge(array(
        'civicrm_group' => 1,
          'civicrm_group_contact' => 1,
        ),
        $tables
      );
    }

    // to handle table dependencies of components
    CRM_Core_Component::tableNames($tables);

    //format the table list according to the weight
    $info = CRM_Core_TableHierarchy::info();

    foreach ($tables as $key => $value) {
      $k = 99;
      if (strpos($key, '-') !== FALSE) {
        $keyArray = explode('-', $key);
        $k = CRM_Utils_Array::value('civicrm_' . $keyArray[1], $info, 99);
      }
      elseif (strpos($key, '_') !== FALSE) {
        $keyArray = explode('_', $key);
        if (is_numeric(array_pop($keyArray))) {
          $k = CRM_Utils_Array::value(implode('_', $keyArray), $info, 99);
        }
        else {
          $k = CRM_Utils_Array::value($key, $info, 99);
        }
      }
      else {
        $k = CRM_Utils_Array::value($key, $info, 99);
      }
      $tempTable[$k . ".$key"] = $key;
    }
    ksort($tempTable);
    $newTables = array();
    foreach ($tempTable as $key) {
      $newTables[$key] = $tables[$key];
    }

    $tables = $newTables;

    foreach ($tables as $name => $value) {
      if (!$value) {
        continue;
      }

      if (CRM_Utils_Array::value($name, $inner)) {
        $side = 'INNER';
      }
      elseif (CRM_Utils_Array::value($name, $right)) {
        $side = 'RIGHT';
      }
      else {
        $side = 'LEFT';
      }

      if ($value != 1) {
        // if there is already a join statement in value, use value itself
        if (strpos($value, 'JOIN')) {
          $from .= " $value ";
        }
        else {
          $from .= " $side JOIN $name ON ( $value ) ";
        }
        continue;
      }
      switch ($name) {
        case 'civicrm_address':
          if ($primaryLocation) {
            $from .= " $side JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 )";
          }
          else {
            $from .= " $side JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id ) ";
          }
          continue;

        case 'civicrm_phone':
          $from .= " $side JOIN civicrm_phone ON (contact_a.id = civicrm_phone.contact_id AND civicrm_phone.is_primary = 1) ";
          continue;

        case 'civicrm_email':
          $from .= " $side JOIN civicrm_email ON (contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1) ";
          continue;

        case 'civicrm_im':
          $from .= " $side JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id AND civicrm_im.is_primary = 1) ";
          continue;

        case 'im_provider':
          $from .= " $side JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id) ";
          $from .= " $side JOIN civicrm_option_group option_group_imProvider ON option_group_imProvider.name = 'instant_messenger_service'";
          $from .= " $side JOIN civicrm_option_value im_provider ON (civicrm_im.provider_id = im_provider.value AND option_group_imProvider.id = im_provider.option_group_id)";
          continue;

        case 'civicrm_openid':
          $from .= " $side JOIN civicrm_openid ON ( civicrm_openid.contact_id = contact_a.id AND civicrm_openid.is_primary = 1 )";
          continue;

        case 'civicrm_state_province':
          $from .= " $side JOIN civicrm_state_province ON civicrm_address.state_province_id = civicrm_state_province.id ";
          continue;

        case 'civicrm_country':
          $from .= " $side JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id ";
          continue;

        case 'civicrm_worldregion':
          $from .= " $side JOIN civicrm_worldregion ON civicrm_country.region_id = civicrm_worldregion.id ";
          continue;

        case 'civicrm_county':
          $from .= " $side JOIN civicrm_county ON civicrm_address.county_id = civicrm_county.id ";
          continue;

        case 'civicrm_location_type':
          $from .= " $side JOIN civicrm_location_type ON civicrm_address.location_type_id = civicrm_location_type.id ";
          continue;

        case 'civicrm_group':
          $from .= " $side JOIN civicrm_group ON civicrm_group.id =  civicrm_group_contact.group_id ";
          continue;

        case 'civicrm_group_contact':
          $from .= " $side JOIN civicrm_group_contact ON contact_a.id = civicrm_group_contact.contact_id ";
          continue;

        case 'civicrm_activity':
        case 'civicrm_activity_tag':
        case 'activity_type':
        case 'activity_status':
        case 'civicrm_activity_contact':
        case 'source_contact':
          $from .= CRM_Activity_BAO_Query::from($name, $mode, $side);
          continue;

        case 'civicrm_entity_tag':
          $from .= " $side JOIN civicrm_entity_tag ON ( civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                                                              civicrm_entity_tag.entity_id = contact_a.id ) ";
          continue;

        case 'civicrm_note':
          $from .= " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contact' AND
                                                        contact_a.id = civicrm_note.entity_id ) ";
          continue;

        case 'civicrm_subscription_history':
          $from .= " $side JOIN civicrm_subscription_history
                                   ON civicrm_group_contact.contact_id = civicrm_subscription_history.contact_id
                                  AND civicrm_group_contact.group_id =  civicrm_subscription_history.group_id";
          continue;

        case 'individual_prefix':
          $from .= " $side JOIN civicrm_option_group option_group_prefix ON (option_group_prefix.name = 'individual_prefix')";
          $from .= " $side JOIN civicrm_option_value individual_prefix ON (contact_a.prefix_id = individual_prefix.value AND option_group_prefix.id = individual_prefix.option_group_id ) ";
          continue;

        case 'individual_suffix':
          $from .= " $side JOIN civicrm_option_group option_group_suffix ON (option_group_suffix.name = 'individual_suffix')";
          $from .= " $side JOIN civicrm_option_value individual_suffix ON (contact_a.suffix_id = individual_suffix.value AND option_group_suffix.id = individual_suffix.option_group_id ) ";
          continue;

        case 'gender':
          $from .= " $side JOIN civicrm_option_group option_group_gender ON (option_group_gender.name = 'gender')";
          $from .= " $side JOIN civicrm_option_value gender ON (contact_a.gender_id = gender.value AND option_group_gender.id = gender.option_group_id) ";
          continue;

        case 'civicrm_relationship':
          if (self::$_relType == 'reciprocal') {
            $from .= " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = contact_a.id OR civicrm_relationship.contact_id_a = contact_a.id)";
            $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_a = contact_b.id OR civicrm_relationship.contact_id_b = contact_b.id)";
          }
          elseif (self::$_relType == 'b') {
            $from .= " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = contact_a.id )";
            $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_a = contact_b.id )";
          }
          else {
            $from .= " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_a = contact_a.id )";
            $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_b = contact_b.id )";
          }
          continue;

        case 'civicrm_log':
          $from .= " $side JOIN civicrm_log ON (civicrm_log.entity_id = contact_a.id AND civicrm_log.entity_table = 'civicrm_contact')";
          $from .= " $side JOIN civicrm_contact contact_b_log ON (civicrm_log.modified_id = contact_b_log.id)";
          continue;

        case 'civicrm_tag':
          $from .= " $side  JOIN civicrm_tag ON civicrm_entity_tag.tag_id = civicrm_tag.id ";
          continue;

        case 'civicrm_task_status':
          $from .= " $side JOIN civicrm_task_status ON ( civicrm_task_status.responsible_entity_table = 'civicrm_contact'
                                                          AND contact_a.id = civicrm_task_status.responsible_entity_id )";
          continue;

        case 'civicrm_grant':
          $from .= CRM_Grant_BAO_Query::from($name, $mode, $side);
          continue;

        //build fromClause for email greeting, postal greeting, addressee CRM-4575

        case 'email_greeting':
          $from .= " $side JOIN civicrm_option_group option_group_email_greeting ON (option_group_email_greeting.name = 'email_greeting')";
          $from .= " $side JOIN civicrm_option_value email_greeting ON (contact_a.email_greeting_id = email_greeting.value AND option_group_email_greeting.id = email_greeting.option_group_id ) ";
          continue;

        case 'postal_greeting':
          $from .= " $side JOIN civicrm_option_group option_group_postal_greeting ON (option_group_postal_greeting.name = 'postal_greeting')";
          $from .= " $side JOIN civicrm_option_value postal_greeting ON (contact_a.postal_greeting_id = postal_greeting.value AND option_group_postal_greeting.id = postal_greeting.option_group_id ) ";
          continue;

        case 'addressee':
          $from .= " $side JOIN civicrm_option_group option_group_addressee ON (option_group_addressee.name = 'addressee')";
          $from .= " $side JOIN civicrm_option_value addressee ON (contact_a.addressee_id = addressee.value AND option_group_addressee.id = addressee.option_group_id ) ";
          continue;

        case 'civicrm_website':
          $from .= " $side JOIN civicrm_website ON contact_a.id = civicrm_website.contact_id ";
          continue;

        default:
          $from .= CRM_Core_Component::from($name, $mode, $side);
          continue;
      }
    }

    return $from;
  }

  /**
   * WHERE / QILL clause for deleted_contacts
   *
   * @return void
   */
  function deletedContacts($values) {
    list($_, $_, $value, $grouping, $_) = $values;
    if ($value) {
      // *prepend* to the relevant grouping as this is quite an important factor
      array_unshift($this->_qill[$grouping], ts('Search in Trash'));
    }
  }

  /**
   * where / qill clause for contact_type
   *
   * @return void
   * @access public
   */
  function contactType(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $subTypes = array();
    $clause = array();

    // account for search builder mapping multiple values
    if (!is_array($value)) {
      $values = self::parseSearchBuilderString($value, 'String');
      if (is_array($values)) {
        $value = array_flip($values);
      }
    }

    if (is_array($value)) {
      foreach ($value as $k => $v) {
        // fix for CRM-771
        if ($k) {
          $subType = NULL;
          $contactType = $k;
          if (strpos($k, CRM_Core_DAO::VALUE_SEPARATOR)) {
            list($contactType, $subType) = explode(CRM_Core_DAO::VALUE_SEPARATOR, $k, 2);
          }

          if (!empty($subType)) {
            $subTypes[$subType] = 1;
          }
          $clause[$contactType] = "'" . CRM_Utils_Type::escape($contactType, 'String') . "'";
        }
      }
    }
    else {
      $contactTypeANDSubType = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value, 2);
      $contactType = $contactTypeANDSubType[0];
      $subType = CRM_Utils_Array::value(1, $contactTypeANDSubType);
      if (!empty($subType)) {
        $subTypes[$subType] = 1;
      }
      $clause[$contactType] = "'" . CRM_Utils_Type::escape($contactType, 'String') . "'";
    }

    // fix for CRM-771
    if (!empty($clause)) {
      $this->_where[$grouping][] = 'contact_a.contact_type IN (' . implode(',', $clause) . ')';
      $this->_qill[$grouping][] = ts('Contact Type') . ' - ' . implode(' ' . ts('or') . ' ', $clause);

      if (!empty($subTypes)) {
        $this->includeContactSubTypes($subTypes, $grouping);
      }
    }
  }

  /**
   * where / qill clause for contact_sub_type
   *
   * @return void
   * @access public
   */
  function contactSubType(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $this->includeContactSubTypes($value, $grouping);
  }

  function includeContactSubTypes($value, $grouping) {

    $clause = array();
    $alias = "contact_a.contact_sub_type";

    if (is_array($value)) {
      foreach ($value as $k => $v) {
        if (!empty($k)) {
          $clause[$k] = "($alias like '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($k, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
        }
      }
    }
    else {
      $clause[$value] = "($alias like '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($value, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
    }

    if (!empty($clause)) {
      $this->_where[$grouping][] = "( " . implode(' OR ', $clause) . " )";
      $this->_qill[$grouping][] = ts('Contact Subtype') . ' - ' . implode(' ' . ts('or') . ' ', array_keys($clause));
    }
  }

  /**
   * where / qill clause for groups
   *
   * @return void
   * @access public
   */
  function group(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (count($value) > 1) {
      $this->_useDistinct = TRUE;
    }

    $groupNames = CRM_Core_PseudoConstant::group();
    $groupIds = implode(',', array_keys($value));

    $names = array();
    foreach ($value as $id => $dontCare) {
      if (array_key_exists($id, $groupNames) && $dontCare) {
        $names[] = $groupNames[$id];
      }
    }

    $statii = array();
    $in = FALSE;
    $gcsValues = &$this->getWhereValues('group_contact_status', $grouping);
    if ($gcsValues &&
      is_array($gcsValues[2])
    ) {
      foreach ($gcsValues[2] as $k => $v) {
        if ($v) {
          if ($k == 'Added') {
            $in = TRUE;
          }
          $statii[] = "'" . CRM_Utils_Type::escape($k, 'String') . "'";
        }
      }
    }
    else {
      $statii[] = '"Added"';
      $in = TRUE;
    }

    $skipGroup = FALSE;
    if (count($value) == 1 &&
      count($statii) == 1 &&
      $statii[0] == '"Added"'
    ) {
      // check if smart group, if so we can get rid of that one additional
      // left join
      $groupIDs = array_keys($value);

      if (CRM_Utils_Array::value(0, $groupIDs) &&
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
          $groupIDs[0],
          'saved_search_id'
        )
      ) {
        $skipGroup = TRUE;
      }
    }

    if (!$skipGroup) {
      $gcTable = "`civicrm_group_contact-{$groupIds}`";
      $this->_tables[$gcTable] = $this->_whereTables[$gcTable] = " LEFT JOIN civicrm_group_contact {$gcTable} ON contact_a.id = {$gcTable}.contact_id ";
    }

    $qill = ts('Contacts %1', array(1 => $op));
    $qill .= ' ' . implode(' ' . ts('or') . ' ', $names);

    $groupClause = NULL;

    if (!$skipGroup) {
      $groupClause = "{$gcTable}.group_id $op ( $groupIds )";
      if (!empty($statii)) {
        $groupClause .= " AND {$gcTable}.status IN (" . implode(', ', $statii) . ")";
        $qill .= " " . ts('AND') . " " . ts('Group Status') . ' - ' . implode(' ' . ts('or') . ' ', $statii);
      }
    }

    if ($in) {
      $ssClause = $this->savedSearch($values);
      if ($ssClause) {
        if ($groupClause) {
          $groupClause = "( ( $groupClause ) OR ( $ssClause ) )";
        }
        else {
          $groupClause = $ssClause;
        }
      }
    }

    $this->_where[$grouping][] = $groupClause;
    $this->_qill[$grouping][] = $qill;
  }
  /*
   * Function translates selection of group type into a list of groups
   */
  function getGroupsFromTypeCriteria($value){
    $groupIds = array();
    foreach ($value as $groupTypeValue) {
      $groupList = CRM_Core_PseudoConstant::group($groupTypeValue);
      $groupIds = ($groupIds + $groupList);
    }
    return $groupIds;
  }

  /**
   * where / qill clause for smart groups
   *
   * @return void
   * @access public
   */
  function savedSearch(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    return $this->addGroupContactCache(array_keys($value));
  }

  function addGroupContactCache($groups, $tableAlias = NULL, $joinTable = "contact_a") {
    $config = CRM_Core_Config::singleton();

    // find all the groups that are part of a saved search
    $groupIDs = implode(',', $groups);
    if (empty($groupIDs)) {
      return NULL;
    }

    $sql = "
SELECT id, cache_date, saved_search_id, children
FROM   civicrm_group
WHERE  id IN ( $groupIDs )
  AND  ( saved_search_id != 0
   OR    saved_search_id IS NOT NULL
   OR    children IS NOT NULL )
";
    $group = CRM_Core_DAO::executeQuery($sql);
    $ssWhere = array();
    while ($group->fetch()) {
      if ($tableAlias == NULL) {
        $alias = "`civicrm_group_contact_cache_{$group->id}`";
      }
      else {
        $alias = $tableAlias;
      }

      $this->_useDistinct = TRUE;

      if (!$this->_smartGroupCache || $group->cache_date == NULL) {
        CRM_Contact_BAO_GroupContactCache::load($group);
      }

      $this->_tables[$alias] = $this->_whereTables[$alias] = " LEFT JOIN civicrm_group_contact_cache {$alias} ON {$joinTable}.id = {$alias}.contact_id ";
      $ssWhere[] = "{$alias}.group_id = {$group->id}";
    }

    if (!empty($ssWhere)) {
      return implode(' OR ', $ssWhere);
    }
    return NULL;
  }

  /**
   * where / qill clause for cms users
   *
   * @return void
   * @access public
   */
  function ufUser(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($value == 1) {
      $this->_tables['civicrm_uf_match'] = $this->_whereTables['civicrm_uf_match'] = ' INNER JOIN civicrm_uf_match ON civicrm_uf_match.contact_id = contact_a.id ';

      $this->_qill[$grouping][] = ts('CMS User');
    }
    elseif ($value == 0) {
      $this->_tables['civicrm_uf_match'] = $this->_whereTables['civicrm_uf_match'] = ' LEFT JOIN civicrm_uf_match ON civicrm_uf_match.contact_id = contact_a.id ';

      $this->_where[$grouping][] = " civicrm_uf_match.contact_id IS NULL";
      $this->_qill[$grouping][] = ts('Not a CMS User');
    }
  }

  /**
   * all tag search specific
   *
   * @return void
   * @access public
   */
  function tagSearch(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $op = "LIKE";
    $value = "%{$value}%";


    $useAllTagTypes = $this->getWhereValues('all_tag_types', $grouping);
    $tagTypesText = $this->getWhereValues('tag_types_text', $grouping);

    $etTable = "`civicrm_entity_tag-" . $value . "`";
    $tTable = "`civicrm_tag-" . $value . "`";

    if ($useAllTagTypes[2]) {
      $this->_tables[$etTable] =
        $this->_whereTables[$etTable] =
        " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id)
          LEFT JOIN civicrm_tag {$tTable} ON ( {$etTable}.tag_id = {$tTable}.id  )";

      // search tag in cases
      $etCaseTable = "`civicrm_entity_case_tag-" . $value . "`";
      $tCaseTable = "`civicrm_case_tag-" . $value . "`";
      $this->_tables[$etCaseTable] =
        $this->_whereTables[$etCaseTable] =
        " LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
          LEFT JOIN civicrm_case
            ON (civicrm_case_contact.case_id = civicrm_case.id
                AND civicrm_case.is_deleted = 0 )
          LEFT JOIN civicrm_entity_tag {$etCaseTable} ON ( {$etCaseTable}.entity_table = 'civicrm_case' AND {$etCaseTable}.entity_id = civicrm_case.id )
          LEFT JOIN civicrm_tag {$tCaseTable} ON ( {$etCaseTable}.tag_id = {$tCaseTable}.id  )";
      // search tag in activities
      $etActTable = "`civicrm_entity_act_tag-" . $value . "`";
      $tActTable = "`civicrm_act_tag-" . $value . "`";
      $this->_tables[$etActTable] =
        $this->_whereTables[$etActTable] =
        " LEFT JOIN civicrm_activity_target
            ON ( civicrm_activity_target.target_contact_id = contact_a.id )
          LEFT JOIN civicrm_activity
            ON ( civicrm_activity.id = civicrm_activity_target.activity_id
            AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )
          LEFT JOIN civicrm_entity_tag as {$etActTable} ON ( {$etActTable}.entity_table = 'civicrm_activity' AND {$etActTable}.entity_id = civicrm_activity.id )
          LEFT JOIN civicrm_tag {$tActTable} ON ( {$etActTable}.tag_id = {$tActTable}.id  )";

      $this->_where[$grouping][] = "({$tTable}.name $op '". $value . "' OR {$tCaseTable}.name $op '". $value . "' OR {$tActTable}.name $op '". $value . "')";
      $this->_qill[$grouping][] = ts('Tag %1 %2 ', array(1 => $tagTypesText[2], 2 => $op)) . ' ' . $value;
    } else {
      $etTable = "`civicrm_entity_tag-" . $value . "`";
      $tTable = "`civicrm_tag-" . $value . "`";
      $this->_tables[$etTable] = $this->_whereTables[$etTable] = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND
      {$etTable}.entity_table = 'civicrm_contact' )
                LEFT JOIN civicrm_tag {$tTable} ON ( {$etTable}.tag_id = {$tTable}.id  ) ";

      $this->_where[$grouping][] = self::buildClause("{$tTable}.name", $op, $value, 'String');
      $this->_qill[$grouping][] = ts('Tagged %1', array(1 => $op)) . ' ' . $value;
    }
  }

  /**
   * where / qill clause for tag
   *
   * @return void
   * @access public
   */
  function tag(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $tagNames = CRM_Core_PseudoConstant::tag();
    if (is_array($value)) {
      if (count($value) > 1) {
        $this->_useDistinct = TRUE;
      }
      foreach ($value as $id => $dontCare) {
        $names[] = CRM_Utils_Array::value($id, $tagNames);
      }
      $names = implode(' ' . ts('or') . ' ', $names);
      $value = implode(',', array_keys($value));
    }
    else {
      $names = CRM_Utils_Array::value($value, $tagNames);
    }


    $useAllTagTypes = $this->getWhereValues('all_tag_types', $grouping);
    $tagTypesText = $this->getWhereValues('tag_types_text', $grouping);

    $etTable = "`civicrm_entity_tag-" . $value . "`";

    if ($useAllTagTypes[2]) {
      $this->_tables[$etTable] =
        $this->_whereTables[$etTable] =
        " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND {$etTable}.entity_table = 'civicrm_contact') ";

      // search tag in cases
      $etCaseTable = "`civicrm_entity_case_tag-" . $value . "`";
      $this->_tables[$etCaseTable] =
        $this->_whereTables[$etCaseTable] =
        " LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
          LEFT JOIN civicrm_case
            ON (civicrm_case_contact.case_id = civicrm_case.id
                AND civicrm_case.is_deleted = 0 )
          LEFT JOIN civicrm_entity_tag {$etCaseTable} ON ( {$etCaseTable}.entity_table = 'civicrm_case' AND {$etCaseTable}.entity_id = civicrm_case.id ) ";
      // search tag in activities
      $etActTable = "`civicrm_entity_act_tag-" . $value . "`";
      $this->_tables[$etActTable] =
        $this->_whereTables[$etActTable] =
        " LEFT JOIN civicrm_activity_target
            ON ( civicrm_activity_target.target_contact_id = contact_a.id )
          LEFT JOIN civicrm_activity
            ON ( civicrm_activity.id = civicrm_activity_target.activity_id
            AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )
          LEFT JOIN civicrm_entity_tag as {$etActTable} ON ( {$etActTable}.entity_table = 'civicrm_activity' AND {$etActTable}.entity_id = civicrm_activity.id ) ";

      // CRM-10338
      if ( in_array( $op, array( 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY' ) ) ) {
        $this->_where[$grouping][] = "({$etTable}.tag_id $op OR {$etCaseTable}.tag_id $op OR {$etActTable}.tag_id $op)";
      }
      else {
        $this->_where[$grouping][] = "({$etTable}.tag_id $op (". $value . ") OR {$etCaseTable}.tag_id $op (". $value . ") OR {$etActTable}.tag_id $op (". $value . "))";
      }
      $this->_qill[$grouping][] = ts('Tag %1 %2', array(1 => $op, 2 => $tagTypesText[2])) . ' ' . $names;
    } else {
      $this->_tables[$etTable] =
        $this->_whereTables[$etTable] =
        " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND {$etTable}.entity_table = 'civicrm_contact') ";

      // CRM-10338
      if ( in_array( $op, array( 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY' ) ) ) {
        // this converts IS (NOT)? EMPTY to IS (NOT)? NULL
        $op = str_replace('EMPTY', 'NULL', $op);
        $this->_where[$grouping][] = "{$etTable}.tag_id $op";
      }
      else {
        $this->_where[$grouping][] = "{$etTable}.tag_id $op (" . $value . ')';
      }
      $this->_qill[$grouping][] = ts('Tagged %1', array( 1 => $op)) . ' ' . $names;
    }

  }

  /**
   * where/qill clause for notes
   *
   * @return void
   * @access public
   */
  function notes(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $noteOptionValues = $this->getWhereValues('note_option', $grouping);
    $noteOption = CRM_Utils_Array::value('2', $noteOptionValues, '6');
    $noteOption = ($name == 'note_body') ? 2 : (($name == 'note_subject') ? 3 : $noteOption);

    $this->_useDistinct = TRUE;

    $this->_tables['civicrm_note'] =
      $this->_whereTables['civicrm_note'] =
      " LEFT JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contact' AND contact_a.id = civicrm_note.entity_id ) ";

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $n = trim($value);
    $value = $strtolower(CRM_Core_DAO::escapeString($n));
    if ($wildcard || $op == 'LIKE') {
      if (strpos($value, '%') === FALSE) {
        $value = "%$value%";
      }
      $op = 'LIKE';
    }
    elseif ($op == 'IS NULL' || $op == 'IS NOT NULL') {
      $value = NULL;
    }

    $label = NULL;
    $clauses = array();
    if ( $noteOption % 2 ==  0 ) {
      $clauses[] = self::buildClause('civicrm_note.note', $op, $value, 'String');
      $label = ts('Note: Body Only');
    }
    if ( $noteOption % 3 ==  0 ) {
      $clauses[] = self::buildClause('civicrm_note.subject', $op, $value, 'String');
      $label = $label ? ts('Note: Body and Subject') : ts('Note: Subject Only');
    }
    $this->_where[$grouping][] = "( " . implode(' OR ', $clauses) . " )";
    $this->_qill[$grouping][] = $label . " $op - '$n'";
  }

  function nameNullOrEmptyOp($name, $op, $grouping) {
    switch ( $op ) {
      case 'IS NULL':
      case 'IS NOT NULL':
        $this->_where[$grouping][] = "contact_a.$name $op";
        $this->_qill[$grouping][] = ts('Name') . ' ' . $op;
        return true;

      case 'IS EMPTY':
        $this->_where[$grouping][] = "(contact_a.$name IS NULL OR contact_a.$name = '')";
        $this->_qill[$grouping][] = ts('Name') . ' ' . $op;
        return true;

      case 'IS NOT EMPTY':
        $this->_where[$grouping][] = "(contact_a.$name IS NOT NULL AND contact_a.$name <> '')";
        $this->_qill[$grouping][] = ts('Name') . ' ' . $op;
        return true;

      default:
        return false;
    }
  }

  /**
   * where / qill clause for sort_name
   *
   * @return void
   * @access public
   */
  function sortName(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    // handle IS NULL / IS NOT NULL / IS EMPTY / IS NOT EMPTY
    if ( $this->nameNullOrEmptyOp( $name, $op, $grouping ) ) {
      return;
    }

    $newName = $name;
    $name = trim($value);

    if (empty($name)) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    $sub = array();

    //By default, $sub elements should be joined together with OR statements (don't change this variable).
    $subGlue = ' OR ';

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

    if (substr($name, 0, 1) == '"' &&
      substr($name, -1, 1) == '"'
    ) {
      //If name is encased in double quotes, the value should be taken to be the string in entirety and the
      $value = substr($name, 1, -1);
      $value = $strtolower(CRM_Core_DAO::escapeString($value));
      $wc = ($newName == 'sort_name') ? 'LOWER(contact_a.sort_name)' : 'LOWER(contact_a.display_name)';
      $sub[] = " ( $wc = '$value' ) ";
      if ($config->includeEmailInName) {
        $sub[] = " ( civicrm_email.email = '$value' ) ";
      }
    }
    elseif (strpos($name, ',') !== FALSE) {
      // if we have a comma in the string, search for the entire string
      $value = $strtolower(CRM_Core_DAO::escapeString($name));
      if ($wildcard) {
        if ($config->includeWildCardInName) {
          $value = "'%$value%'";
        }
        else {
          $value = "'$value%'";
        }
        $op = 'LIKE';
      }
      else {
        $value = "'$value'";
      }
      if ($newName == 'sort_name') {
        $wc = self::caseImportant($op) ? "LOWER(contact_a.sort_name)" : "contact_a.sort_name";
      }
      else {
        $wc = self::caseImportant($op) ? "LOWER(contact_a.display_name)" : "contact_a.display_name";
      }
      $sub[] = " ( $wc $op $value )";
      if ($config->includeNickNameInName) {
        $wc = self::caseImportant($op) ? "LOWER(contact_a.nick_name)" : "contact_a.nick_name";
        $sub[] = " ( $wc $op $value )";
      }
      if ($config->includeEmailInName) {
        $sub[] = " ( civicrm_email.email $op $value ) ";
      }
    }
    else {
      // the string should be treated as a series of keywords to be matched with match ANY OR
      // match ALL depending on Civi config settings (see CiviAdmin)

      // The Civi configuration setting can be overridden if the string *starts* with the case
      // insenstive strings 'AND:' or 'OR:'TO THINK ABOUT: what happens when someone searches
      // for the following "AND: 'a string in quotes'"? - probably nothing - it would make the
      // AND OR variable reduntant because there is only one search string?

      // Check to see if the $subGlue is overridden in the search text
      if (strtolower(substr($name, 0, 4)) == 'and:') {
        $name = substr($name, 4);
        $subGlue = ' AND ';
      }
      if (strtolower(substr($name, 0, 3)) == 'or:') {
        $name = substr($name, 3);
        $subGlue = ' OR ';
      }

      $firstChar = substr($name, 0, 1);
      $lastChar = substr($name, -1, 1);
      $quotes = array("'", '"');
      if ((strlen($name) > 2) && in_array($firstChar, $quotes) &&
        in_array($lastChar, $quotes)
      ) {
        $name = substr($name, 1);
        $name = substr($name, 0, -1);
        $pieces = array($name);
      }
      else {
        $pieces = explode(' ', $name);
      }
      foreach ($pieces as $piece) {
        $value = $strtolower(CRM_Core_DAO::escapeString(trim($piece)));
        if (strlen($value)) {
          // Added If as a sanitization - without it, when you do an OR search, any string with
          // double spaces (i.e. "  ") or that has a space after the keyword (e.g. "OR: ") will
          // return all contacts because it will include a condition similar to "OR contact
          // name LIKE '%'".  It might be better to replace this with array_filter.
          $fieldsub = array();
          if ($wildcard) {
            if ($config->includeWildCardInName) {
              $value = "'%$value%'";
            }
            else {
              $value = "'$value%'";
            }
            $op = 'LIKE';
          }
          else {
            $value = "'$value'";
          }
          if ($newName == 'sort_name') {
            $wc = self::caseImportant($op) ? "LOWER(contact_a.sort_name)" : "contact_a.sort_name";
          }
          else {
            $wc = self::caseImportant($op) ? "LOWER(contact_a.display_name)" : "contact_a.display_name";
          }
          $fieldsub[] = " ( $wc $op $value )";
          if ($config->includeNickNameInName) {
            $wc = self::caseImportant($op) ? "LOWER(contact_a.nick_name)" : "contact_a.nick_name";
            $fieldsub[] = " ( $wc $op $value )";
          }
          if ($config->includeEmailInName) {
            $fieldsub[] = " ( civicrm_email.email $op $value ) ";
          }
          $sub[] = ' ( ' . implode(' OR ', $fieldsub) . ' ) ';
          // I seperated the glueing in two.  The first stage should always be OR because we are searching for matches in *ANY* of these fields
        }
      }
    }

    $sub = ' ( ' . implode($subGlue, $sub) . ' ) ';

    $this->_where[$grouping][] = $sub;
    if ($config->includeEmailInName) {
      $this->_tables['civicrm_email'] = $this->_whereTables['civicrm_email'] = 1;
      $this->_qill[$grouping][] = ts('Name or Email ') . "$op - '$name'";
    }
    else {
      $this->_qill[$grouping][] = ts('Name like') . " - '$name'";
    }
  }

  /**
   * where / qill clause for email
   *
   * @return void
   * @access public
   */
  function email(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $n = trim($value);
    if ($n) {
      $config = CRM_Core_Config::singleton();

      if (substr($n, 0, 1) == '"' &&
        substr($n, -1, 1) == '"'
      ) {
        $n = substr($n, 1, -1);
        $value = strtolower(CRM_Core_DAO::escapeString($n));
        $value = "'$value'";
        $op = '=';
      }
      else {
        $value = strtolower($n);
        if ($wildcard) {
          if (strpos($value, '%') === FALSE) {
            $value = "%{$value}%";
          }
          $op = 'LIKE';
        }
      }
      $this->_qill[$grouping][] = ts('Email') . " $op '$n'";
      $this->_where[$grouping][] = self::buildClause('civicrm_email.email', $op, $value, 'String');
    }
    else {
      $this->_qill[$grouping][] = ts('Email') . " $op ";
      $this->_where[$grouping][] = self::buildClause('civicrm_email.email', $op, NULL, 'String');
    }

    $this->_tables['civicrm_email'] = $this->_whereTables['civicrm_email'] = 1;
  }

  /**
   * where / qill clause for phone number
   *
   * @return void
   * @access public
   */
  function phone_numeric(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    // Strip non-numeric characters
    $number = preg_replace('/[^\d]/', '', $value);
    if ($number) {
      $this->_qill[$grouping][] = ts('Phone number contains') . " $number";
      $this->_where[$grouping][] = self::buildClause('civicrm_phone.phone_numeric', 'LIKE', "%$number%", 'String');
      $this->_tables['civicrm_phone'] = $this->_whereTables['civicrm_phone'] = 1;
    }
  }

  /**
   * where / qill clause for phone type/location
   *
   * @return void
   * @access public
   */
  function phone_option_group($values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $option = $name == 'phone_phone_type_id' ? 'phoneType' : 'locationType';
    $options = CRM_Core_PseudoConstant::$option();
    $optionName = $options[$value];
    $this->_qill[$grouping][] = ts('Phone') . ' ' . ($name == 'phone_phone_type_id' ? ts('type') : ('location')) . " $op $optionName";
    $this->_where[$grouping][] = self::buildClause('civicrm_phone.' . substr($name, 6), $op, $value, 'Integer');
    $this->_tables['civicrm_phone'] = $this->_whereTables['civicrm_phone'] = 1;
  }

  /**
   * where / qill clause for street_address
   *
   * @return void
   * @access public
   */
  function street_address(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!$op) {
      $op = 'LIKE';
    }

    $n = trim($value);

    if ($n) {
      $value = strtolower($n);
      if (strpos($value, '%') === FALSE) {
        // only add wild card if not there
        $value = "%{$value}%";
      }
      $op = 'LIKE';
      $this->_where[$grouping][] = self::buildClause('LOWER(civicrm_address.street_address)', $op, $value, 'String');
      $this->_qill[$grouping][] = ts('Street') . " $op '$n'";
    }
    else {
      $this->_where[$grouping][] = self::buildClause('civicrm_address.street_address', $op, NULL, 'String');
      $this->_qill[$grouping][] = ts('Street') . " $op ";
    }

    $this->_tables['civicrm_address'] = $this->_whereTables['civicrm_address'] = 1;
  }

  /**
   * where / qill clause for street_unit
   *
   * @return void
   * @access public
   */
  function street_number(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!$op) {
      $op = '=';
    }

    $n = trim($value);

    if (strtolower($n) == 'odd') {
      $this->_where[$grouping][] = " ( civicrm_address.street_number % 2 = 1 )";
      $this->_qill[$grouping][] = ts('Street Number is odd');
    }
    elseif (strtolower($n) == 'even') {
      $this->_where[$grouping][] = " ( civicrm_address.street_number % 2 = 0 )";
      $this->_qill[$grouping][] = ts('Street Number is even');
    }
    else {
      $value = strtolower($n);

      $this->_where[$grouping][] = self::buildClause('LOWER(civicrm_address.street_number)', $op, $value, 'String');
      $this->_qill[$grouping][] = ts('Street Number') . " $op '$n'";
    }

    $this->_tables['civicrm_address'] = $this->_whereTables['civicrm_address'] = 1;
  }

  /**
   * where / qill clause for sorting by character
   *
   * @return void
   * @access public
   */
  function sortByCharacter(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $name = trim($value);
    $cond = " contact_a.sort_name LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($name)) . "%'";
    $this->_where[$grouping][] = $cond;
    $this->_qill[$grouping][] = ts('Showing only Contacts starting with: \'%1\'', array(1 => $name));
  }

  /**
   * where / qill clause for including contact ids
   *
   * @return void
   * @access public
   */
  function includeContactIDs() {
    if (!$this->_includeContactIds || empty($this->_params)) {
      return;
    }

    $contactIds = array();
    foreach ($this->_params as $id => $values) {
      if (substr($values[0], 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
        $contactIds[] = substr($values[0], CRM_Core_Form::CB_PREFIX_LEN);
      }
    }
    if (!empty($contactIds)) {
      $this->_where[0][] = " ( contact_a.id IN (" . implode(',', $contactIds) . " ) ) ";
    }
  }

  /**
   * where / qill clause for postal code
   *
   * @return void
   * @access public
   */
  function postalCode(&$values) {
    // skip if the fields dont have anything to do with postal_code
    if (!CRM_Utils_Array::value('postal_code', $this->_fields)) {
      return;
    }

    list($name, $op, $value, $grouping, $wildcard) = $values;

    // Handle numeric postal code range searches properly by casting the column as numeric
    if (is_numeric($value)) {
      $field = 'ROUND(civicrm_address.postal_code)';
      $val = CRM_Utils_Type::escape($value, 'Integer');
    }
    else {
      $field = 'civicrm_address.postal_code';
      $val = CRM_Utils_Type::escape($value, 'String');
    }

    $this->_tables['civicrm_address'] = $this->_whereTables['civicrm_address'] = 1;

    if ($name == 'postal_code') {
      $this->_where[$grouping][] = self::buildClause($field, $op, $val, 'String');
      $this->_qill[$grouping][] = ts('Postal code') . " {$op} {$value}";
    }
    elseif ($name == 'postal_code_low') {
      $this->_where[$grouping][] = " ( $field >= '$val' ) ";
      $this->_qill[$grouping][] = ts('Postal code greater than or equal to \'%1\'', array(1 => $value));
    }
    elseif ($name == 'postal_code_high') {
      $this->_where[$grouping][] = " ( $field <= '$val' ) ";
      $this->_qill[$grouping][] = ts('Postal code less than or equal to \'%1\'', array(1 => $value));
    }
  }

  /**
   * where / qill clause for location type
   *
   * @return void
   * @access public
   */
  function locationType(&$values, $status = NULL) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (is_array($value)) {
      $this->_where[$grouping][] = 'civicrm_address.location_type_id IN (' . implode(',', array_keys($value)) . ')';
      $this->_tables['civicrm_address'] = 1;
      $this->_whereTables['civicrm_address'] = 1;

      $locationType = CRM_Core_PseudoConstant::locationType();
      $names = array();
      foreach (array_keys($value) as $id) {
        $names[] = $locationType[$id];
      }

      $this->_primaryLocation = FALSE;

      if (!$status) {
        $this->_qill[$grouping][] = ts('Location Type') . ' - ' . implode(' ' . ts('or') . ' ', $names);
      }
      else {
        return implode(' ' . ts('or') . ' ', $names);
      }
    }
  }

  function country(&$values, $fromStateProvince = TRUE) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!$fromStateProvince) {
      $stateValues = $this->getWhereValues('state_province', $grouping);
      if (!empty($stateValues)) {
        // return back to caller if there are state province values
        // since that handles this case
        return;
      }
    }

    $countryClause = $countryQill = NULL;
    if (
      $values &&
      !empty($value)
    ) {
      $this->_tables['civicrm_country'] = 1;
      $this->_whereTables['civicrm_country'] = 1;

      $countries = CRM_Core_PseudoConstant::country();
      if (is_numeric($value)) {
        $countryClause = self::buildClause(
          'civicrm_country.id',
          $op,
          $value,
          'Positive'
        );
        $countryName = $countries[(int ) $value];
      }

      else {
        $intValues = self::parseSearchBuilderString($value);
        if ($intValues && ($op == 'IN' || $op == 'NOT IN')) {
          $countryClause = self::buildClause(
            'civicrm_country.id',
            $op,
            $intValues,
            'Positive'
          );
          $countryNames = array();
          foreach ($intValues as $v) {
            $countryNames[] = $countries[$v];
          }
          $countryName = implode(',', $countryNames);
        }
        else {
          $wc = ($op != 'LIKE') ? "LOWER('civicrm_country.name')" : 'civicrm_country.name';
          $countryClause = self::buildClause(
            'civicrm_country.name',
            $op,
            $value,
            'String'
          );
          $countryName = $value;
        }
      }
      $countryQill = ts('Country') . " {$op} '$countryName'";

      if (!$fromStateProvince) {
        $this->_where[$grouping][] = $countryClause;
        $this->_qill[$grouping][] = $countryQill;
      }
    }

    if ($fromStateProvince) {
      if (!empty($countryClause)) {
        return array(
          $countryClause,
          " ...AND... " . $countryQill,
        );
      }
      else {
        return array(NULL, NULL);
      }
    }
  }

  /**
   * where / qill clause for county (if present)
   *
   * @return void
   * @access public
   */
  function county(&$values, $status = null) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (! is_array($value)) {
      // force the county to be an array
      $value = array($value);
    }

    // check if the values are ids OR names of the counties
    $inputFormat = 'id';
    foreach ($value as $v) {
      if (!is_numeric($v)) {
        $inputFormat = 'name';
        break;
      }
    }
    $names = array();
    if ($inputFormat == 'id') {
      $clause = 'civicrm_county.id IN (' . implode(',', $value) . ')';

      $county = CRM_Core_PseudoConstant::county();
      foreach ($value as $id) {
        $names[] = CRM_Utils_Array::value($id, $county);
      }
    }
    else {
      $inputClause = array();
      foreach ($value as $name) {
        $name = trim($name);
        $inputClause[] = "'$name'";
      }
      $clause = 'civicrm_county.name IN (' . implode(',', $inputClause) . ')';
      $names = $value;
    }
    $this->_tables['civicrm_county'] = 1;
    $this->_whereTables['civicrm_county'] = 1;

    $this->_where[$grouping][] = $clause;
    if (! $status) {
      $this->_qill[$grouping][] = ts('County') . ' - ' . implode(' ' . ts('or') . ' ', $names);
    } else {
      return implode(' ' . ts('or') . ' ', $names);
    }
  }

  /**
   * where / qill clause for state/province AND country (if present)
   *
   * @return void
   * @access public
   */
  function stateProvince(&$values, $status = NULL) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    // quick escape for IS NULL
    if ( in_array( $op, array( 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY' ) ) ) {
      $value = NULL;
    }
    else if (!is_array($value)) {
      // force the state to be an array
      // check if its in the mapper format!
      $values = self::parseSearchBuilderString($value);
      if (is_array($values)) {
        $value = $values;
      }
      else {
        $value = array($value);
      }
    }

    // check if the values are ids OR names of the states
    $inputFormat = 'id';
    if ($value) {
      foreach ($value as $v) {
        if (!is_numeric($v)) {
          $inputFormat = 'name';
          break;
        }
      }
    }

    $names = array();
    if ($op == '=') {
      $op = 'IN';
    }
    else if ($op == '!=') {
      $op = 'NOT IN';
    }
    else {
      // this converts IS (NOT)? EMPTY to IS (NOT)? NULL
      $op = str_replace('EMPTY', 'NULL', $op);
    }
    if ( in_array( $op, array( 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY' ) ) ) {
      $stateClause = "civicrm_state_province.id $op";
    }
    else if ($inputFormat == 'id') {
      if ($op != 'NOT IN') {
        $op = 'IN';
      }
      $stateClause = "civicrm_state_province.id $op (" . implode(',', $value) . ')';

      $stateProvince = CRM_Core_PseudoConstant::stateProvince();
      foreach ($value as $id) {
        $names[] = CRM_Utils_Array::value($id, $stateProvince);
      }
    }
    else {
      $inputClause = array();
      foreach ($value as $name) {
        $name = trim($name);
        $inputClause[] = "'$name'";
      }
      $stateClause = "civicrm_state_province.name $op (" . implode(',', $inputClause) . ')';
      $names = $value;
    }

    $this->_tables['civicrm_state_province'] = 1;
    $this->_whereTables['civicrm_state_province'] = 1;

    $countryValues = $this->getWhereValues('country', $grouping);
    list($countryClause, $countryQill) = $this->country($countryValues, TRUE);

    if ($countryClause) {
      $clause = "( $stateClause AND $countryClause )";
    }
    else {
      $clause = $stateClause;
    }

    $this->_where[$grouping][] = $clause;
    if (!$status) {
      $this->_qill[$grouping][] = ts('State/Province') . " $op " . implode(' ' . ts('or') . ' ', $names) . $countryQill;
    }
    else {
      return implode(' ' . ts('or') . ' ', $names) . $countryQill;;
    }
  }

  /**
   * where / qill clause for change log
   *
   * @return void
   * @access public
   */
  function changeLog(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $targetName = $this->getWhereValues('changed_by', $grouping);
    if (!$targetName) {
      return;
    }

    $name = trim($targetName[2]);
    $name = strtolower(CRM_Core_DAO::escapeString($name));
    $name = $targetName[4] ? "%$name%" : $name;
    $this->_where[$grouping][] = "contact_b_log.sort_name LIKE '%$name%'";
    $this->_tables['civicrm_log'] = $this->_whereTables['civicrm_log'] = 1;
    $this->_qill[$grouping][] = ts('Changed by') . ": $name";
  }

  function modifiedDates($values) {
    $this->_useDistinct = TRUE;

    // CRM-11281, default to added date if not set
    $fieldTitle = ts('Added Date');

    foreach (array_keys($this->_params) as $id) {
      if ($this->_params[$id][0] == 'log_date') {
        if ($this->_params[$id][2] == 2) {
          $fieldTitle = ts('Modified Date');
        }
      }
    }

    $this->dateQueryBuilder($values,
      'civicrm_log', 'log_date', 'modified_date', $fieldTitle
    );
  }

  function demographics(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (($name == 'birth_date_low') || ($name == 'birth_date_high')) {

      $this->dateQueryBuilder($values,
        'contact_a', 'birth_date', 'birth_date', ts('Birth Date')
      );
    }
    elseif (($name == 'deceased_date_low') || ($name == 'deceased_date_high')) {

      $this->dateQueryBuilder($values,
        'contact_a', 'deceased_date', 'deceased_date', ts('Deceased Date')
      );
    }

    self::$_openedPanes[ts('Demographics')] = TRUE;
  }

  function privacy(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    //fixed for profile search listing CRM-4633
    if (strpbrk($value, "[")) {
      $value = "'{$value}'";
      $op = "!{$op}";
      $this->_where[$grouping][] = "contact_a.{$name} $op $value";
    }
    else {
      $this->_where[$grouping][] = "contact_a.{$name} $op $value";
    }
    $field = CRM_Utils_Array::value($name, $this->_fields);
    $title = $field ? $field['title'] : $name;
    $this->_qill[$grouping][] = "$title $op $value";
  }

  function privacyOptions($values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (empty($value) ||
      !is_array($value)
    ) {
      continue;
    }

    // get the operator and toggle values
    $opValues = $this->getWhereValues('privacy_operator', $grouping);
    $operator = 'OR';
    if ($opValues &&
      strtolower($opValues[2] == 'AND')
    ) {
      $operator = 'AND';
    }

    $toggleValues = $this->getWhereValues('privacy_toggle', $grouping);
    $compareOP = '!=';
    if ($toggleValues &&
      $toggleValues[2] == 2
    ) {
      $compareOP = '=';
    }

    $clauses = array();
    $qill = array();
    foreach ($value as $dontCare => $pOption) {
      $clauses[] = " ( contact_a.{$pOption} $compareOP 1 ) ";
      $field = CRM_Utils_Array::value($pOption, $this->_fields);
      $title = $field ? $field['title'] : $pOption;
      $qill[] = " $title $compareOP 1 ";
    }

    $this->_where[$grouping][] = '( ' . implode($operator, $clauses) . ' )';
    $this->_qill[$grouping][] = implode($operator, $qill);
  }

  function preferredCommunication(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $pref = array();
    if (!is_array($value)) {
      $v = array();
      $value = trim($value, ' ()');
      if (strpos($value, CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
        $v = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
      }
      else {
        $v = explode(",", $value);
      }

      foreach ($v as $item) {
        if ($item) {
          $pref[] = $item;
        }
      }
    }
    else {
      foreach ($value as $key => $checked) {
        if ($checked) {
          $pref[] = $key;
        }
      }
    }

    $commPref = CRM_Core_PseudoConstant::pcm();

    $sqlValue = array();
    $sql = "contact_a.preferred_communication_method";
    foreach ($pref as $val) {
      $sqlValue[] = "( $sql like '%" . CRM_Core_DAO::VALUE_SEPARATOR . $val . CRM_Core_DAO::VALUE_SEPARATOR . "%' ) ";
      $showValue[] = $commPref[$val];
    }
    $this->_where[$grouping][] = "( " . implode(' OR ', $sqlValue) . " )";
    $this->_qill[$grouping][] = ts('Preferred Communication Method') . " $op " . implode(' ' . ts('or') . ' ', $showValue);
  }

  /**
   * where / qill clause for task / task status
   *
   * @return void
   * @access public
   */
  function task(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $targetName = $this->getWhereValues('task_id', $grouping);
    if (!$targetName) {
      return;
    }

    $taskID = CRM_Utils_Type::escape($targetName[2], 'Integer');
    $clause = "civicrm_task_status.task_id = $taskID ";

    $statusID = NULL;
    if ($value) {
      $statusID = CRM_Utils_Type::escape($value, 'Integer');
      $clause .= " AND civicrm_task_status.status_id = $statusID";
    }

    $this->_where[$grouping][] = "civicrm_task_status.task_id = $taskID AND civicrm_task_status.status_id = $statusID";
    $this->_tables['civicrm_task_status'] = $this->_whereTables['civicrm_task_status'] = 1;

    $taskSelect = CRM_Core_PseudoConstant::tasks();
    $this->_qill[$grouping][] = ts('Task') . ": $taskSelect[$taskID]";
    if ($statusID) {
      $statusSelect = CRM_Core_OptionGroup::values('task_status');
      $this->_qill[$grouping][] = ts('Task Status') . ": $statusSelect[$statusID]";
    }
  }

  /**
   * where / qill clause for relationship
   *
   * @return void
   * @access public
   */
  function relationship(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    // also get values array for relation_target_name
    // for relatinship search we always do wildcard
    $targetName = $this->getWhereValues('relation_target_name', $grouping);
    $relStatus = $this->getWhereValues('relation_status', $grouping);
    $targetGroup = $this->getWhereValues('relation_target_group', $grouping);
    $start = $this->getWhereValues('relation_date_low', $grouping);
    $end = $this->getWhereValues('relation_date_high', $grouping);

    $nameClause = $name = NULL;
    if ($targetName) {
      $name = trim($targetName[2]);
      if (substr($name, 0, 1) == '"' &&
        substr($name, -1, 1) == '"'
      ) {
        $name = substr($name, 1, -1);
        $name = strtolower(CRM_Core_DAO::escapeString($name));
        $nameClause = "= '$name'";
      }
      else {
        $name = strtolower(CRM_Core_DAO::escapeString($name));
        $nameClause = "LIKE '%{$name}%'";
      }
    }

    $rel = explode('_', $value);

    self::$_relType = $rel[1];

      $params = array('id' => $rel[0]);
      $rTypeValues = array();
      $rType = CRM_Contact_BAO_RelationshipType::retrieve($params, $rTypeValues);
      if (!$rType) {
        return;
      }

      if ($rTypeValues['name_a_b'] == $rTypeValues['name_b_a']) {
        self::$_relType = 'reciprocal';
      }

    if ($nameClause) {
      $this->_where[$grouping][] = "( contact_b.sort_name $nameClause AND contact_b.id != contact_a.id )";
    }

    $relTypeInd = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, 'Individual');
    $relTypeOrg = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, 'Organization');
    $relTypeHou = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, 'Household');
    $allRelationshipType = array();
    $allRelationshipType = array_merge($relTypeInd, $relTypeOrg);
    $allRelationshipType = array_merge($allRelationshipType, $relTypeHou);

    if ($nameClause || !$targetGroup) {
      $this->_qill[$grouping][] = "$allRelationshipType[$value]  $name";
    }


    //check to see if the target contact is in specified group
    if ($targetGroup) {
      //add contacts from static groups
      $this->_tables['civicrm_relationship_group_contact'] =
        $this->_whereTables['civicrm_relationship_group_contact'] =
        " LEFT JOIN civicrm_group_contact civicrm_relationship_group_contact ON civicrm_relationship_group_contact.contact_id = contact_b.id";
      $groupWhere[] = "( civicrm_relationship_group_contact.group_id IN  (" . implode(",", $targetGroup[2]) . ") )";

      //add contacts from saved searches
      $ssWhere = $this->addGroupContactCache($targetGroup[2], "civicrm_relationship_group_contact_cache", "contact_b");

      //set the group where clause
      if ($ssWhere) {
        $groupWhere[] = "( " . $ssWhere . " )";
      }
      $this->_where[$grouping][] = "( " . implode(" OR ", $groupWhere) . " )";

      //Get the names of the target groups for the qill
      $groupNames = &CRM_Core_PseudoConstant::group();
      $qillNames = array();
      foreach ($targetGroup[2] as $groupId) {
        if (array_key_exists($groupId, $groupNames)) {
          $qillNames[] = $groupNames[$groupId];
        }
      }
      $this->_qill[$grouping][] = "$allRelationshipType[$value]  ( " . implode(", ", $qillNames) . " )";
    }

    // Note we do not currently set mySql to handle timezones, so doing this the old-fashioned way
    $today = date('Ymd');
    //check for active, inactive and all relation status
    if ($relStatus[2] == 0) {
      $this->_where[$grouping][] = "(
civicrm_relationship.is_active = 1 AND
( civicrm_relationship.end_date IS NULL OR civicrm_relationship.end_date >= {$today} ) AND
( civicrm_relationship.start_date IS NULL OR civicrm_relationship.start_date <= {$today} )
)";
      $this->_qill[$grouping][] = ts('Relationship - Active and Current');
    }
    elseif ($relStatus[2] == 1) {
      $this->_where[$grouping][] = "(
civicrm_relationship.is_active = 0 OR
civicrm_relationship.end_date < {$today} OR
civicrm_relationship.start_date > {$today}
)";
      $this->_qill[$grouping][] = ts('Relationship - Inactive or not Current');
    }

    // Search by dates
    if ($start || $end) {
      foreach (array('start' => '>=', 'end' => '<=') as $d => $op) {
        if (!empty(${$d}[2])) {
          $date = date('Ymd', strtotime(${$d}[2]));
          $this->_where[$grouping][] = "civicrm_relationship.{$d}_date $op $date";
          $this->_qill[$grouping][] = ($d == 'end' ? ts('Relationship Ended by') : ts('Relationship Started On or After')) . " " . CRM_Utils_Date::customFormat($date);
        }
      }
    }

    $this->_where[$grouping][] = 'civicrm_relationship.relationship_type_id = ' . $rel[0];
    $this->_tables['civicrm_relationship'] = $this->_whereTables['civicrm_relationship'] = 1;
    $this->_useDistinct = TRUE;
  }

  /**
   * default set of return properties
   *
   * @return void
   * @access public
   */
  static function &defaultReturnProperties($mode = 1) {
    if (!isset(self::$_defaultReturnProperties)) {
      self::$_defaultReturnProperties = array();
    }

    if (!isset(self::$_defaultReturnProperties[$mode])) {
      // add activity return properties
      if ($mode & CRM_Contact_BAO_Query::MODE_ACTIVITY) {
        self::$_defaultReturnProperties[$mode] = CRM_Activity_BAO_Query::defaultReturnProperties($mode, FALSE);
      }
      else {
        self::$_defaultReturnProperties[$mode] = CRM_Core_Component::defaultReturnProperties($mode, FALSE);
      }

      if (empty(self::$_defaultReturnProperties[$mode])) {
        self::$_defaultReturnProperties[$mode] = array(
          'home_URL' => 1,
          'image_URL' => 1,
          'legal_identifier' => 1,
          'external_identifier' => 1,
          'contact_type' => 1,
          'contact_sub_type' => 1,
          'sort_name' => 1,
          'display_name' => 1,
          'preferred_mail_format' => 1,
          'nick_name' => 1,
          'first_name' => 1,
          'middle_name' => 1,
          'last_name' => 1,
          'individual_prefix' => 1,
          'individual_suffix' => 1,
          'birth_date' => 1,
          'gender' => 1,
          'street_address' => 1,
          'supplemental_address_1' => 1,
          'supplemental_address_2' => 1,
          'city' => 1,
          'postal_code' => 1,
          'postal_code_suffix' => 1,
          'state_province' => 1,
          'country' => 1,
          'world_region' => 1,
          'geo_code_1' => 1,
          'geo_code_2' => 1,
          'email' => 1,
          'on_hold' => 1,
          'phone' => 1,
          'im' => 1,
          'household_name' => 1,
          'organization_name' => 1,
          'deceased_date' => 1,
          'is_deceased' => 1,
          'job_title' => 1,
          'legal_name' => 1,
          'sic_code' => 1,
          'current_employer' => 1,
          // FIXME: should we use defaultHierReturnProperties() for the below?
          'do_not_email' => 1,
          'do_not_mail' => 1,
          'do_not_sms' => 1,
          'do_not_phone' => 1,
          'do_not_trade' => 1,
          'is_opt_out' => 1,
          'contact_is_deleted' => 1,
        );
      }
    }
    return self::$_defaultReturnProperties[$mode];
  }

  /**
   * get primary condition for a sql clause
   *
   * @param int $value
   *
   * @return void
   * @access public
   */
  static function getPrimaryCondition($value) {
    if (is_numeric($value)) {
      $value = (int ) $value;
      return ($value == 1) ? 'is_primary = 1' : 'is_primary = 0';
    }
    return NULL;
  }

  /**
   * wrapper for a simple search query
   *
   * @param array $params
   * @param array $returnProperties
   * @param bolean $count
   *
   * @return void
   * @access public
   */
  static function getQuery($params = NULL, $returnProperties = NULL, $count = FALSE) {
    $query = new CRM_Contact_BAO_Query($params, $returnProperties);
    list($select, $from, $where, $having) = $query->query();

    return "$select $from $where $having";
  }

  /**
   * These are stub comments as this function needs more explanation - particularly in terms of how it
   * relates to $this->searchQuery and why it replicates rather than calles $this->searchQuery.
   *
   * This function was originally written as a wrapper for the api query but is called from multiple places
   * in the core code directly so the name is misleading. This function does not use the searchQuery function
   * but it is unclear as to whehter that is historical or there is a reason
   *  CRM-11290 led to the permissioning action being extracted from searchQuery & shared with this function
   *
   * @param array  $params
   * @param array  $returnProperties
   * @param string $sort
   * @param int    $offset
   * @param int    $row_count
   * @params bool $smartGroupCache ?? update smart group cache?
   * @param bool $count return count obnly
   * @param bool $skipPermissions Should permissions be ignored or should the logged in user's permissions be applied
   *
   * @return void
   * @access public
   */
  static function apiQuery(
    $params = NULL,
    $returnProperties = NULL,
    $fields = NULL,
    $sort = NULL,
    $offset = 0,
    $row_count = 25,
    $smartGroupCache = TRUE,
    $count = FALSE,
    $skipPermissions = True
  ) {

    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, TRUE, FALSE, 1,
      $skipPermissions,
      TRUE, $smartGroupCache
    );

   //this should add a check for view deleted if permissions are enabled
   if ($skipPermissions){
     $query->_skipDeleteClause = TRUE;
   }
    $query->generatePermissionClause(FALSE, $count);
    list($select, $from, $where, $having) = $query->query($count);

    $options = $query->_options;
    if(!empty($query->_permissionWhereClause)){
      if (empty($where)) {
        $where = "WHERE $query->_permissionWhereClause";
      }
      else {
        $where = "$where AND $query->_permissionWhereClause";
      }
    }

    $sql = "$select $from $where $having";

    // add group by
    if ($query->_useGroupBy) {
      $sql .= ' GROUP BY contact_a.id';
    }
    if (!empty($sort)) {
      $sql .= " ORDER BY $sort ";
    }
    if ($row_count > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $row_count ";
    }

    $dao = CRM_Core_DAO::executeQuery($sql);

    $values = array();
    while ($dao->fetch()) {
      if ($count) {
        $noRows = $dao->rowCount;
        $dao->free();
        return array($noRows,NULL);
      }
      $values[$dao->contact_id] = $query->store($dao);
    }
    $dao->free();
    return array($values, $options);
  }

  /**
   * create and query the db for an contact search
   *
   * @param int      $offset   the offset for the query
   * @param int      $rowCount the number of rows to return
   * @param string   $sort     the order by string
   * @param boolean  $count    is this a count only query ?
   * @param boolean  $includeContactIds should we include contact ids?
   * @param boolean  $sortByChar if true returns the distinct array of first characters for search results
   * @param boolean  $groupContacts if true, return only the contact ids
   * @param boolean  $returnQuery   should we return the query as a string
   * @param string   $additionalWhereClause if the caller wants to further restrict the search (used for components)
   * @param string   $additionalFromClause should be clause with proper joins, effective to reduce where clause load.
   *
   * @return CRM_Contact_DAO_Contact
   * @access public
   */
  function searchQuery(
    $offset = 0, $rowCount = 0, $sort = NULL,
    $count = FALSE, $includeContactIds = FALSE,
    $sortByChar = FALSE, $groupContacts = FALSE,
    $returnQuery = FALSE,
    $additionalWhereClause = NULL, $sortOrder = NULL,
    $additionalFromClause = NULL, $skipOrderAndLimit = FALSE
  ) {

    if ($includeContactIds) {
      $this->_includeContactIds = TRUE;
      $this->_whereClause = $this->whereClause();
    }

    // hack for now, add permission only if we are in search
    // FIXME: we should actually filter out deleted contacts (unless requested to do the opposite)
    $permission = ' ( 1 ) ';
    $onlyDeleted = FALSE;
    $onlyDeleted = in_array(array('deleted_contacts', '=', '1', '0', '0'), $this->_params);

    // if we’re explicitely looking for a certain contact’s contribs, events, etc.
    // and that contact happens to be deleted, set $onlyDeleted to true
    foreach ($this->_params as $values) {
      $name = CRM_Utils_Array::value(0, $values);
      $op = CRM_Utils_Array::value(1, $values);
      $value = CRM_Utils_Array::value(2, $values);
      if ($name == 'contact_id' and $op == '=') {
        if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'is_deleted')) {
          $onlyDeleted = TRUE;
        }
        break;
      }
    }
    $this->generatePermissionClause($onlyDeleted, $count);

    list($select, $from, $where, $having) = $this->query($count, $sortByChar, $groupContacts);

    //additional from clause should be w/ proper joins.
    if ($additionalFromClause) {
      $from .= "\n" . $additionalFromClause;
    }

    if (empty($where)) {
      $where = "WHERE $this->_permissionWhereClause";
    }
    else {
      $where = "$where AND $this->_permissionWhereClause";
    }

    if ($additionalWhereClause) {
      $where = $where . ' AND ' . $additionalWhereClause;
    }

    // building the query string
    $groupBy = NULL;
    if (!$count) {
      if (isset($this->_groupByComponentClause)) {
        $groupBy = $this->_groupByComponentClause;
      }
      elseif ($this->_useGroupBy) {
        $groupBy = ' GROUP BY contact_a.id';
      }
    }
    if ($this->_mode & CRM_Contact_BAO_Query::MODE_ACTIVITY && (!$count)) {
      $groupBy = 'GROUP BY civicrm_activity.id ';
    }

    $order = $orderBy = $limit = '';
    if (!$count) {
      $config = CRM_Core_Config::singleton();
      if ($config->includeOrderByClause ||
        isset($this->_distinctComponentClause)
      ) {
        if ($sort) {
          if (is_string($sort)) {
            $orderBy = $sort;
          }
          else {
            $orderBy = trim($sort->orderBy());
          }
          if (!empty($orderBy)) {
            // this is special case while searching for
            // changelog CRM-1718
            if (preg_match('/sort_name/i', $orderBy)) {
              $orderBy = str_replace('sort_name', 'contact_a.sort_name', $orderBy);
            }

            $order = " ORDER BY $orderBy";

            if ($sortOrder) {
              $order .= " $sortOrder";
            }

            // always add contact_a.id to the ORDER clause
            // so the order is deterministic
            if (strpos('contact_a.id', $order) === FALSE) {
              $order .= ", contact_a.id";
            }
          }
        }
        elseif ($sortByChar) {
          $order = " ORDER BY UPPER(LEFT(contact_a.sort_name, 1)) asc";
        }
        else {
          $order = " ORDER BY contact_a.sort_name asc, contact_a.id";
        }
      }

      $doOpt = TRUE;
      // hack for order clause
      if ($order) {
        $fieldStr = trim(str_replace('ORDER BY', '', $order));
        $fieldOrder = explode(' ', $fieldStr);
        $field = $fieldOrder[0];

        if ($field) {
          switch ($field) {
            case 'sort_name':
            case 'id':
            case 'contact_a.sort_name':
            case 'contact_a.id':
              break;

            case 'city':
            case 'postal_code':
              $this->_whereTables["civicrm_address"] = 1;
              $order = str_replace($field, "civicrm_address.{$field}", $order);
              break;

            case 'country':
            case 'state_province':
              $this->_whereTables["civicrm_{$field}"] = 1;
              $order = str_replace($field, "civicrm_{$field}.name", $order);
              break;

            case 'email':
              $this->_whereTables["civicrm_email"] = 1;
              $order = str_replace($field, "civicrm_email.{$field}", $order);
              break;

            default:
              $doOpt = FALSE;
          }
        }
      }


      if ($rowCount > 0 && $offset >= 0) {
        $limit = " LIMIT $offset, $rowCount ";

        // ok here is a first hack at an optimization, lets get all the contact ids
        // that are restricted and we'll then do the final clause with it
        // CRM-5954
        if (isset($this->_distinctComponentClause)) {
          if (strpos($this->_distinctComponentClause, 'DISTINCT') == FALSE) {
            $limitSelect = "SELECT DISTINCT {$this->_distinctComponentClause}";
          }
          else {
            $limitSelect = "SELECT {$this->_distinctComponentClause}";
          }
        }
        else {
          $limitSelect = 'SELECT DISTINCT contact_a.id as id';
        }

        if ($doOpt) {
          $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL,
            $this->_primaryLocation, $this->_mode
          );

          if ($additionalFromClause) {
            $this->_simpleFromClause .= "\n" . $additionalFromClause;
          }
          // if we are doing a transform, do it here
          // CRM-7969
          $having = NULL;
          if ($this->_displayRelationshipType) {
            $this->filterRelatedContacts($this->_simpleFromClause, $where, $having);
          }

          $limitQuery = "$limitSelect {$this->_simpleFromClause} $where $groupBy $order $limit";
          $limitDAO = CRM_Core_DAO::executeQuery($limitQuery);
          $limitIDs = array();
          while ($limitDAO->fetch()) {
            if ($limitDAO->id) {
              $limitIDs[] = $limitDAO->id;
            }
          }
          if (empty($limitIDs)) {
            $limitClause = ' AND ( 0 ) ';
          }
          else {
            if (isset($this->_distinctComponentClause)) {
              $limitClause = " AND {$this->_distinctComponentClause} IN ( ";
            }
            else {
              $limitClause = ' AND contact_a.id IN ( ';
            }
            $limitClause .= implode(',', $limitIDs) . ' ) ';
          }
          $where .= $limitClause;
          // reset limit clause since we already restrict what records we want
          $limit = NULL;
        }
      }
    }

    // if we are doing a transform, do it here
    // use the $from, $where and $having to get the contact ID
    if ($this->_displayRelationshipType) {
      $this->filterRelatedContacts($from, $where, $having);
    }

    if ($skipOrderAndLimit) {
      $query = "$select $from $where $having $groupBy";
    }
    else {
      $query = "$select $from $where $having $groupBy $order $limit";
    }

    if ($returnQuery) {
      return $query;
    }

    if ($count) {
      return CRM_Core_DAO::singleValueQuery($query);
    }

    //crm_core_error::debug('$query', $query); //exit;

    $dao = CRM_Core_DAO::executeQuery($query);
    if ($groupContacts) {
      $ids = array();
      while ($dao->fetch()) {
        $ids[] = $dao->id;
      }
      return implode(',', $ids);
    }

    return $dao;
  }

  /**
   * Populate $this->_permissionWhereClause with permission related clause and update other
   * query related properties.
   *
   * Function calls ACL permission class and hooks to filter the query appropriately
   *
   * Note that these 2 params were in the code when extracted from another function
   * and a second round extraction would be to make them properties of the class
   *
   * @param bool $onlyDeleted Only get deleted contacts
   * @param bool $count Return Count only
   *
   * @return null
   */
  function generatePermissionClause($onlyDeleted = FALSE, $count = FALSE) {
    if (!$this->_skipPermission) {
      $this->_permissionWhereClause = CRM_ACL_API::whereClause(
        CRM_Core_Permission::VIEW,
        $this->_tables,
        $this->_whereTables,
        NULL,
        $onlyDeleted,
        $this->_skipDeleteClause
      );

      // regenerate fromClause since permission might have added tables
      if ($this->_permissionWhereClause) {
        //fix for row count in qill (in contribute/membership find)
        if (!$count) {
          $this->_useDistinct = TRUE;
        }
        $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_primaryLocation, $this->_mode);
        $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_primaryLocation, $this->_mode);
      }
    }
    else {
      // add delete clause if needed even if we are skipping permission
      // CRM-7639
      if (!$this->_skipDeleteClause) {
        if (CRM_Core_Permission::check('access deleted contacts') and $onlyDeleted) {
          $this->_permissionWhereClause = '(contact_a.is_deleted)';
        }
        else {
          // CRM-6181
          $this->_permissionWhereClause = '(contact_a.is_deleted = 0)';
        }
      }
    }
  }

  function setSkipPermission($val) {
    $this->_skipPermission = $val;
  }

  function &summaryContribution($context = NULL) {
    list($select, $from, $where, $having) = $this->query(TRUE);

    // hack $select
    $select = "
SELECT COUNT( civicrm_contribution.total_amount ) as total_count,
       SUM(   civicrm_contribution.total_amount ) as total_amount,
       AVG(   civicrm_contribution.total_amount ) as total_avg,
       civicrm_contribution.currency              as currency";

    // make sure contribution is completed - CRM-4989
    $where .= " AND civicrm_contribution.contribution_status_id = 1 ";
    if ($context == 'search') {
      $where .= " AND contact_a.is_deleted = 0 ";
    }

    $summary = array();
    $summary['total'] = array();
    $summary['total']['count'] = $summary['total']['amount'] = $summary['total']['avg'] = "n/a";

    $query = "$select $from $where GROUP BY currency";
    $params = array();

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $summary['total']['count'] = 0;
    $summary['total']['amount'] = $summary['total']['avg'] = array();
    while ($dao->fetch()) {
      $summary['total']['count'] += $dao->total_count;
      $summary['total']['amount'][] = CRM_Utils_Money::format($dao->total_amount, $dao->currency);
      $summary['total']['avg'][] = CRM_Utils_Money::format($dao->total_avg, $dao->currency);
    }
    if (!empty($summary['total']['amount'])) {
      $summary['total']['amount'] = implode(',&nbsp;', $summary['total']['amount']);
      $summary['total']['avg'] = implode(',&nbsp;', $summary['total']['avg']);
    }
    else {
      $summary['total']['amount'] = $summary['total']['avg'] = 0;
    }

    // hack $select
    $select = "
SELECT COUNT( civicrm_contribution.total_amount ) as cancel_count,
       SUM(   civicrm_contribution.total_amount ) as cancel_amount,
       AVG(   civicrm_contribution.total_amount ) as cancel_avg,
       civicrm_contribution.currency              as currency";

    $where .= " AND civicrm_contribution.cancel_date IS NOT NULL ";
    if ($context == 'search') {
      $where .= " AND contact_a.is_deleted = 0 ";
    }

    $query = "$select $from $where GROUP BY currency";
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->N <= 1) {
      if ($dao->fetch()) {
        $summary['cancel']['count'] = $dao->cancel_count;
        $summary['cancel']['amount'] = $dao->cancel_amount;
        $summary['cancel']['avg'] = $dao->cancel_avg;
      }
    }
    else {
      $summary['cancel']['count'] = 0;
      $summary['cancel']['amount'] = $summary['cancel']['avg'] = array();
      while ($dao->fetch()) {
        $summary['cancel']['count'] += $dao->cancel_count;
        $summary['cancel']['amount'][] = CRM_Utils_Money::format($dao->cancel_amount, $dao->currency);
        $summary['cancel']['avg'][] = CRM_Utils_Money::format($dao->cancel_avg, $dao->currency);
      }
      $summary['cancel']['amount'] = implode(',&nbsp;', $summary['cancel']['amount']);
      $summary['cancel']['avg'] = implode(',&nbsp;', $summary['cancel']['avg']);
    }

    return $summary;
  }

  /**
   * getter for the qill object
   *
   * @return string
   * @access public
   */
  function qill() {
    return $this->_qill;
  }

  /**
   * default set of return default hier return properties
   *
   * @return void
   * @access public
   */
  static function &defaultHierReturnProperties() {
    if (!isset(self::$_defaultHierReturnProperties)) {
      self::$_defaultHierReturnProperties = array(
        'home_URL' => 1,
        'image_URL' => 1,
        'legal_identifier' => 1,
        'external_identifier' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'nick_name' => 1,
        'first_name' => 1,
        'middle_name' => 1,
        'last_name' => 1,
        'individual_prefix' => 1,
        'individual_suffix' => 1,
        'email_greeting' => 1,
        'postal_greeting' => 1,
        'addressee' => 1,
        'birth_date' => 1,
        'gender' => 1,
        'preferred_communication_method' => 1,
        'do_not_phone' => 1,
        'do_not_email' => 1,
        'do_not_mail' => 1,
        'do_not_sms' => 1,
        'do_not_trade' => 1,
        'location' =>
        array(
          '1' => array('location_type' => 1,
            'street_address' => 1,
            'city' => 1,
            'state_province' => 1,
            'postal_code' => 1,
            'postal_code_suffix' => 1,
            'country' => 1,
            'phone-Phone' => 1,
            'phone-Mobile' => 1,
            'phone-Fax' => 1,
            'phone-1' => 1,
            'phone-2' => 1,
            'phone-3' => 1,
            'im-1' => 1,
            'im-2' => 1,
            'im-3' => 1,
            'email-1' => 1,
            'email-2' => 1,
            'email-3' => 1,
          ),
          '2' => array(
            'location_type' => 1,
            'street_address' => 1,
            'city' => 1,
            'state_province' => 1,
            'postal_code' => 1,
            'postal_code_suffix' => 1,
            'country' => 1,
            'phone-Phone' => 1,
            'phone-Mobile' => 1,
            'phone-1' => 1,
            'phone-2' => 1,
            'phone-3' => 1,
            'im-1' => 1,
            'im-2' => 1,
            'im-3' => 1,
            'email-1' => 1,
            'email-2' => 1,
            'email-3' => 1,
          ),
        ),
      );
    }
    return self::$_defaultHierReturnProperties;
  }

  function dateQueryBuilder(
    &$values, $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $appendTimeStamp = TRUE
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!$value) {
      return;
    }

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName])) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = $secondDate = $secondDateFormat = NULL;

      if ($name == $fieldName . '_low') {
        $firstOP = '>=';
        $firstPhrase = ts('greater than or equal to');
        $firstDate = CRM_Utils_Date::processDate($value);

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '<=';
          $secondPhrase = ts('less than or equal to');
          $secondValue = $secondValues[2];

          if ($appendTimeStamp && strlen($secondValue) == 10) {
            $secondValue .= ' 23:59:59';
          }
          $secondDate = CRM_Utils_Date::processDate($secondValue);
        }
      }
      elseif ($name == $fieldName . '_high') {
        $firstOP = '<=';
        $firstPhrase = ts('less than or equal to');

        if ($appendTimeStamp && strlen($value) == 10) {
          $value .= ' 23:59:59';
        }
        $firstDate = CRM_Utils_Date::processDate($value);

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = CRM_Utils_Date::processDate($secondValue);
        }
      }

      if (!$appendTimeStamp) {
        $firstDate = substr($firstDate, 0, 8);
      }
      $firstDateFormat = CRM_Utils_Date::customFormat($firstDate);

      if ($secondDate) {
        if (!$appendTimeStamp) {
          $secondDate = substr($secondDate, 0, 8);
        }
        $secondDateFormat = CRM_Utils_Date::customFormat($secondDate);
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      if ($secondDate) {
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP '$firstDate' ) AND
( {$tableName}.{$dbFieldName} $secondOP '$secondDate' )
";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\" " . ts('AND') . " $secondPhrase \"$secondDateFormat\"";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP '$firstDate'";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\"";
      }
    }

    if ($name == $fieldName) {
      // $op = '=';
      $phrase = $op;

      $date = CRM_Utils_Date::processDate($value);

      if (!$appendTimeStamp) {
        $date = substr($date, 0, 8);
      }

      $format = CRM_Utils_Date::customFormat($date);

      if ($date) {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op '$date'";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op";
      }
      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      $this->_qill[$grouping][] = "$fieldTitle - $phrase \"$format\"";
    }

    if (
      $tableName == 'civicrm_log' &&
      $fieldTitle == ts('Added Date')
    ) {
      //CRM-6903 --hack to check modified date of first record.
      //as added date means first modified date of object.
      $addedDateQuery = 'select id from civicrm_log group by entity_id order by id';
      $this->_where[$grouping][] = "civicrm_log.id IN ( {$addedDateQuery} )";
    }
  }

  function numberRangeBuilder(&$values,
    $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $options = NULL
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName])) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = NULL;

      if ($name == "{$fieldName}_low") {
        $firstOP = '>=';
        $firstPhrase = ts('greater than');

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues)) {
          $secondOP = '<=';
          $secondPhrase = ts('less than');
          $secondValue = $secondValues[2];
        }
      }
      else {
        $firstOP = '<=';
        $firstPhrase = ts('less than');

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues)) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than');
          $secondValue = $secondValues[2];
        }
      }

      if ($secondOP) {
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP {$value} ) AND
( {$tableName}.{$dbFieldName} $secondOP {$secondValue} )
";
        $displayValue = $options ? $options[$value] : $value;
        $secondDisplayValue = $options ? $options[$secondValue] : $secondValue;

        $this->_qill[$grouping][] =
          "$fieldTitle - $firstPhrase \"$displayValue\" " . ts('AND') . " $secondPhrase \"$secondDisplayValue\"";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP {$value}";
        $displayValue = $options ? $options[$value] : $value;
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$displayValue\"";
      }
      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;

      return;
    }

    if ($name == $fieldName) {
      $op = '=';
      $phrase = '=';

      $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op {$value}";

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      $displayValue = $options ? $options[$value] : $value;
      $this->_qill[$grouping][] = "$fieldTitle - $phrase \"$displayValue\"";
    }

    return;
  }

  /**
   * Given the field name, operator, value & its data type
   * builds the where Clause for the query
   * used for handling 'IS NULL'/'IS NOT NULL' operators
   *
   * @param string  $field       fieldname
   * @param string  $op          operator
   * @param string  $value       value
   * @param string  $dataType    data type of the field
   *
   * @return where clause for the query
   * @access public
   */
  static function buildClause($field, $op, $value = NULL, $dataType = NULL) {
    $op = trim($op);
    $clause = "$field $op";

    switch ($op) {
      case 'IS NULL':
      case 'IS NOT NULL':
        return $clause;

      case 'IS EMPTY':
        $clause = " ( $field IS NULL OR $field = '' ) ";
        return $clause;

      case 'IS NOT EMPTY':
        $clause = " ( $field IS NOT NULL AND $field <> '' ) ";
        return $clause;

      case 'IN':
      case 'NOT IN':
        if (isset($dataType)) {
          if (is_array($value)) {
            $values = $value;
          }
          else {
            $value = CRM_Utils_Type::escape($value, "String");
            $values = explode(',', CRM_Utils_Array::value(0, explode(')', CRM_Utils_Array::value(1, explode('(', $value)))));
          }
          // supporting multiple values in IN clause
          $val = array();
          foreach ($values as $v) {
            $v = trim($v);
            $val[] = "'" . CRM_Utils_Type::escape($v, $dataType) . "'";
          }
          $value = "(" . implode($val, ",") . ")";
        }
        return "$clause $value";

      default:
        if (empty($dataType)) {
          $dataType = 'String';
        }

        $value = CRM_Utils_Type::escape($value, $dataType);

        // if we dont have a dataType we should assume
        if ($dataType == 'String') {
          $value = "'" . strtolower($value) . "'";
        }
        return "$clause $value";
    }
  }

  function openedSearchPanes($reset = FALSE) {
    if (!$reset || empty($this->_whereTables)) {
      return self::$_openedPanes;
    }

    // pane name to table mapper
    $panesMapper = array(
      ts('Contributions') => 'civicrm_contribution',
      ts('Memberships') => 'civicrm_membership',
      ts('Events') => 'civicrm_participant',
      ts('Relationships') => 'civicrm_relationship',
      ts('Activities') => 'civicrm_activity',
      ts('Pledges') => 'civicrm_pledge',
      ts('Cases') => 'civicrm_case',
      ts('Grants') => 'civicrm_grant',
      ts('Address Fields') => 'civicrm_address',
      ts('Notes') => 'civicrm_note',
      ts('Change Log') => 'civicrm_log',
      ts('Mailings') => 'civicrm_mailing_event_queue',
    );

    foreach (array_keys($this->_whereTables) as $table) {
      if ($panName = array_search($table, $panesMapper)) {
        self::$_openedPanes[$panName] = TRUE;
      }
    }

    return self::$_openedPanes;
  }

  function setOperator($operator) {
    $validOperators = array('AND', 'OR');
    if (!in_array($operator, $validOperators)) {
      $operator = 'AND';
    }
    $this->_operator = $operator;
  }

  function getOperator() {
    return $this->_operator;
  }

  function filterRelatedContacts(&$from, &$where, &$having) {
    static $_rTypeProcessed = NULL;
    static $_rTypeFrom = NULL;
    static $_rTypeWhere = NULL;

    if (!$_rTypeProcessed) {
      $_rTypeProcessed = TRUE;

      // create temp table with contact ids
      $tableName = CRM_Core_DAO::createTempTableName('civicrm_transform', TRUE);
      $sql = "CREATE TEMPORARY TABLE $tableName ( contact_id int primary key) ENGINE=HEAP";
      CRM_Core_DAO::executeQuery($sql);

      $sql = "
REPLACE INTO $tableName ( contact_id )
SELECT contact_a.id
       $from
       $where
       $having
";
      CRM_Core_DAO::executeQuery($sql);

      $qillMessage = ts('Contacts with a Relationship Type of: ');
      $rTypes = CRM_Core_PseudoConstant::relationshipType();

      if (is_numeric($this->_displayRelationshipType)) {
        $relationshipTypeLabel = $rTypes[$this->_displayRelationshipType]['label_a_b'];
        $_rTypeFrom = "
INNER JOIN civicrm_relationship displayRelType ON ( displayRelType.contact_id_a = contact_a.id OR displayRelType.contact_id_b = contact_a.id )
INNER JOIN $tableName transform_temp ON ( transform_temp.contact_id = displayRelType.contact_id_a OR transform_temp.contact_id = displayRelType.contact_id_b )
";
        $_rTypeWhere = "
WHERE displayRelType.relationship_type_id = {$this->_displayRelationshipType}
AND   displayRelType.is_active = 1
";
      }
      else {
        list($relType, $dirOne, $dirTwo) = explode('_', $this->_displayRelationshipType);
        if ($dirOne == 'a') {
          $relationshipTypeLabel = $rTypes[$relType]['label_a_b'];
          $_rTypeFrom .= "
INNER JOIN civicrm_relationship displayRelType ON ( displayRelType.contact_id_a = contact_a.id )
INNER JOIN $tableName transform_temp ON ( transform_temp.contact_id = displayRelType.contact_id_b )
";
        }
        else {
          $relationshipTypeLabel = $rTypes[$relType]['label_b_a'];
          $_rTypeFrom .= "
INNER JOIN civicrm_relationship displayRelType ON ( displayRelType.contact_id_b = contact_a.id )
INNER JOIN $tableName transform_temp ON ( transform_temp.contact_id = displayRelType.contact_id_a )
";
        }
        $_rTypeWhere = "
WHERE displayRelType.relationship_type_id = $relType
AND   displayRelType.is_active = 1
";
      }
      $this->_qill[0][] = $qillMessage . "'" . $relationshipTypeLabel . "'";
    }

    if (strpos($from, $_rTypeFrom) === FALSE) {
      // lets replace all the INNER JOIN's in the $from so we dont exclude other data
      // this happens when we have an event_type in the quert (CRM-7969)
      $from = str_replace("INNER JOIN", "LEFT JOIN", $from);
      $from .= $_rTypeFrom;
      $where = $_rTypeWhere;
    }

    $having = NULL;
  }

  static function caseImportant( $op ) {
    return
      in_array($op, array('LIKE', 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY')) ? FALSE : TRUE;
  }

  static function componentPresent( &$returnProperties, $prefix ) {
    foreach ($returnProperties as $name => $dontCare ) {
      if (substr($name, 0, strlen($prefix)) == $prefix) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Builds the necessary structures for all fields that are similar to option value lookups
   *
   * @param $name     string the name of the field
   * @param $op       string the sql operator, this function should handle ALL SQL operators
   * @param $value    any    string / integer / array depends on the operator and whos calling the query builder
   * @param $grouping int    the index where to place the where clause
   * @param $selectValue array the key value pairs for this element. This allows us to use this function for things besides option-value pairs
   * @param $field    array  an array that contains various properties of the field identified by $name
   * @param $label    string The label for this field element
   * @param $dataType string The data type for this element
   *
   * @return void     adds the where clause and qill to the query object
   */
  function optionValueQuery(
    $name,
    $op,
    $value,
    $grouping,
    $selectValues,
    $field,
    $label,
    $dataType = 'String'
  ) {
    $qill = $value;
    if (is_numeric($value)) {
      $qill = $value = $selectValues[(int ) $value];
    }
    elseif ($op == 'IN' || $op == 'NOT IN') {
      $values = self::parseSearchBuilderString($value);
      if (is_array($values)) {
        $newValues = array();
        foreach ($values as $v) {
          $newValues[] = $selectValues[(int ) $v];
        }
        $value = $newValues;
        $qill = implode(', ', $value);
      }
    }
    $wc = self::caseImportant($op) ? "LOWER({$field['where']})" : "{$field['where']}";
    $this->_where[$grouping][] = self::buildClause($wc, $op, $value, $dataType);
    $this->_qill[$grouping][] = $label . " $op '$qill'";
  }

  /** function to check and explode a user defined numeric string into an array
   * this was the protocol used by search builder in the old old days before we had
   * super nice js widgets to do the hard work
   *
   * @param string the string to check
   * @param string the dataType we should check for the values, default integer
   *
   * @return FALSE if string does not match the patter
   *         array of numeric values if string does match the pattern
   * @static
   */
  static function parseSearchBuilderString($string, $dataType = 'Integer') {
    $string = trim($string);
    if (substr($string, 0, 1) != '(' || substr($string, -1, 1) != ')') {
      Return FALSE;
    }

    $string = substr($string, 1, -1);
    $values = explode(',', $string);
    if (empty($values)) {
      return FALSE;
    }

    $returnValues = array();
    foreach ($values as $v) {
      if ($dataType == 'Integer' && ! is_numeric($v)) {
        return FALSE;
      }
      else if ($dataType == 'String' && ! is_string($v)) {
        return FALSE;
      }
      $returnValues[] = trim($v);
    }

    if (empty($returnValues)) {
      return FALSE;
    }

    return $returnValues;
  }
}

