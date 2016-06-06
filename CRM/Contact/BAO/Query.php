<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This is the heart of the search query building mechanism.
 */
class CRM_Contact_BAO_Query {

  /**
   * The various search modes.
   *
   * @var int
   */
  const
    NO_RETURN_PROPERTIES = 'CRM_Contact_BAO_Query::NO_RETURN_PROPERTIES',
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
   * The default set of return properties.
   *
   * @var array
   */
  static $_defaultReturnProperties = NULL;

  /**
   * The default set of hier return properties.
   *
   * @var array
   */
  static $_defaultHierReturnProperties;

  /**
   * The set of input params.
   *
   * @var array
   */
  public $_params;

  public $_cfIDs;

  public $_paramLookup;

  public $_sort;
  /**
   * The set of output params
   *
   * @var array
   */
  public $_returnProperties;

  /**
   * The select clause
   *
   * @var array
   */
  public $_select;

  /**
   * The name of the elements that are in the select clause
   * used to extract the values
   *
   * @var array
   */
  public $_element;

  /**
   * The tables involved in the query
   *
   * @var array
   */
  public $_tables;

  /**
   * The table involved in the where clause
   *
   * @var array
   */
  public $_whereTables;

  /**
   * The where clause
   *
   * @var array
   */
  public $_where;

  /**
   * The where string
   *
   * @var string
   */
  public $_whereClause;

  /**
   * Additional permission Where Clause
   *
   * @var string
   */
  public $_permissionWhereClause;

  /**
   * The from string
   *
   * @var string
   */
  public $_fromClause;

  /**
   * Additional permission from clause
   *
   * @var string
   */
  public $_permissionFromClause;

  /**
   * The from clause for the simple select and alphabetical
   * select
   *
   * @var string
   */
  public $_simpleFromClause;

  /**
   * The having values
   *
   * @var string
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
   * The cache to translate the option values into labels.
   *
   * @var array
   */
  public $_options;

  /**
   * Are we in search mode.
   *
   * @var boolean
   */
  public $_search = TRUE;

  /**
   * Should we skip permission checking.
   *
   * @var boolean
   */
  public $_skipPermission = FALSE;

  /**
   * Should we skip adding of delete clause.
   *
   * @var boolean
   */
  public $_skipDeleteClause = FALSE;

  /**
   * Are we in strict mode (use equality over LIKE)
   *
   * @var boolean
   */
  public $_strict = FALSE;

  /**
   * What operator to use to group the clauses.
   *
   * @var string
   */
  public $_operator = 'AND';

  public $_mode = 1;

  /**
   * Should we only search on primary location.
   *
   * @var boolean
   */
  public $_primaryLocation = TRUE;

  /**
   * Are contact ids part of the query.
   *
   * @var boolean
   */
  public $_includeContactIds = FALSE;

  /**
   * Should we use the smart group cache.
   *
   * @var boolean
   */
  public $_smartGroupCache = TRUE;

  /**
   * Should we display contacts with a specific relationship type.
   *
   * @var string
   */
  public $_displayRelationshipType = NULL;

  /**
   * Reference to the query object for custom values.
   *
   * @var Object
   */
  public $_customQuery;

  /**
   * Should we enable the distinct clause, used if we are including
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
   * The relationship type direction
   *
   * @var array
   */
  static $_relType;

  /**
   * The activity role
   *
   * @var array
   */
  static $_activityRole;

  /**
   * Consider the component activity type
   * during activity search.
   *
   * @var array
   */
  static $_considerCompActivities;

  /**
   * Consider with contact activities only,
   * during activity search.
   *
   * @var array
   */
  static $_withContactActivitiesOnly;

  /**
   * Use distinct component clause for component searches
   *
   * @var string
   */
  public $_distinctComponentClause;

  public $_rowCountClause;

  /**
   * Use groupBy component clause for component searches
   *
   * @var string
   */
  public $_groupByComponentClause;

  /**
   * Track open panes, useful in advance search
   *
   * @var array
   */
  public static $_openedPanes = array();

  /**
   * For search builder - which custom fields are location-dependent
   * @var array
   */
  public $_locationSpecificCustomFields = array();

  /**
   * The tables which have a dependency on location and/or address
   *
   * @var array
   */
  static $_dependencies = array(
    'civicrm_state_province' => 1,
    'civicrm_country' => 1,
    'civicrm_county' => 1,
    'civicrm_address' => 1,
    'civicrm_location_type' => 1,
  );

  /**
   * List of location specific fields.
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
   * Remember if we handle either end of a number or date range
   * so we can skip the other
   */
  protected $_rangeCache = array();
  /**
   * Set to true when $this->relationship is run to avoid adding twice
   * @var Boolean
   */
  protected $_relationshipValuesAdded = FALSE;

  /**
   * Set to the name of the temp table if one has been created
   * @var String
   */
  static $_relationshipTempTable = NULL;

  public $_pseudoConstantsSelect = array();

  /**
   * Class constructor which also does all the work.
   *
   * @param array $params
   * @param array $returnProperties
   * @param array $fields
   * @param bool $includeContactIds
   * @param bool $strict
   * @param bool|int $mode - mode the search is operating on
   *
   * @param bool $skipPermission
   * @param bool $searchDescendentGroups
   * @param bool $smartGroupCache
   * @param null $displayRelationshipType
   * @param string $operator
   * @param string $apiEntity
   *
   * @return \CRM_Contact_BAO_Query
   */
  public function __construct(
    $params = NULL, $returnProperties = NULL, $fields = NULL,
    $includeContactIds = FALSE, $strict = FALSE, $mode = 1,
    $skipPermission = FALSE, $searchDescendentGroups = TRUE,
    $smartGroupCache = TRUE, $displayRelationshipType = NULL,
    $operator = 'AND',
    $apiEntity = NULL
  ) {

    $this->_params = &$params;
    if ($this->_params == NULL) {
      $this->_params = array();
    }

    if ($returnProperties === self::NO_RETURN_PROPERTIES) {
      $this->_returnProperties = array();
    }
    elseif (empty($returnProperties)) {
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
      $this->_fields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE, TRUE, FALSE, !$skipPermission);

      $fields = CRM_Core_Component::getQueryFields(!$this->_skipPermission);
      unset($fields['note']);
      $this->_fields = array_merge($this->_fields, $fields);

      // add activity fields
      $fields = CRM_Activity_BAO_Activity::exportableFields();
      $this->_fields = array_merge($this->_fields, $fields);

      // add any fields provided by hook implementers
      $extFields = CRM_Contact_BAO_Query_Hook::singleton()->getFields();
      $this->_fields = array_merge($this->_fields, $extFields);
    }

    // basically do all the work once, and then reuse it
    $this->initialize($apiEntity);
  }

  /**
   * Function which actually does all the work for the constructor.
   */
  public function initialize($apiEntity = NULL) {
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

    $this->selectClause($apiEntity);
    $this->_whereClause = $this->whereClause();
    if (array_key_exists('civicrm_contribution', $this->_whereTables)) {
      $component = 'contribution';
    }
    if (array_key_exists('civicrm_membership', $this->_whereTables)) {
      $component = 'membership';
    }
    if (isset($component)) {
      CRM_Financial_BAO_FinancialType::buildPermissionedClause($this->_whereClause, $component);
    }

    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_primaryLocation, $this->_mode);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_primaryLocation, $this->_mode);

    $this->openedSearchPanes(TRUE);
  }

  /**
   * Function for same purpose as convertFormValues.
   *
   * Like convert form values this function exists to pre-Process parameters from the form.
   *
   * It is unclear why they are different functions & likely relates to advances search
   * versus search builder.
   *
   * The direction we are going is having the form convert values to a standardised format &
   * moving away from wierd & wonderful where clause switches.
   *
   * Fix and handle contact deletion nicely.
   *
   * this code is primarily for search builder use case where different clauses can specify if they want deleted.
   *
   * CRM-11971
   */
  public function buildParamsLookup() {
    $trashParamExists = FALSE;
    $paramByGroup = array();
    foreach ($this->_params as $k => $param) {
      if (!empty($param[0]) && $param[0] == 'contact_is_deleted') {
        $trashParamExists = TRUE;
      }
      if (!empty($param[3])) {
        $paramByGroup[$param[3]][$k] = $param;
      }
    }

    if ($trashParamExists) {
      $this->_skipDeleteClause = TRUE;

      //cycle through group sets and explicitly add trash param if not set
      foreach ($paramByGroup as $setID => $set) {
        if (
          !in_array(array('contact_is_deleted', '=', '1', $setID, '0'), $this->_params) &&
          !in_array(array('contact_is_deleted', '=', '0', $setID, '0'), $this->_params)
        ) {
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
      if (empty($value[0])) {
        continue;
      }
      $cfID = CRM_Core_BAO_CustomField::getKeyID($value[0]);
      if ($cfID) {
        if (!array_key_exists($cfID, $this->_cfIDs)) {
          $this->_cfIDs[$cfID] = array();
        }
        // Set wildcard value based on "and/or" selection
        foreach ($this->_params as $key => $param) {
          if ($param[0] == $value[0] . '_operator') {
            $value[4] = $param[2] == 'or';
            break;
          }
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
   * Some composite fields do not appear in the fields array hack to make them part of the query.
   */
  public function addSpecialFields($apiEntity) {
    static $special = array('contact_type', 'contact_sub_type', 'sort_name', 'display_name');
    // if get called via Contact.get API having address_id as return parameter
    if ($apiEntity == 'Contact') {
      $special[] = 'address_id';
    }
    foreach ($special as $name) {
      if (!empty($this->_returnProperties[$name])) {
        if ($name == 'address_id') {
          $this->_tables['civicrm_address'] = 1;
          $this->_select['address_id'] = 'civicrm_address.id as address_id';
          $this->_element['address_id'] = 1;
        }
        else {
          $this->_select[$name] = "contact_a.{$name} as $name";
          $this->_element[$name] = 1;
        }
      }
    }
  }

  /**
   * Given a list of conditions in params and a list of desired
   * return Properties generate the required select and from
   * clauses. Note that since the where clause introduces new
   * tables, the initial attempt also retrieves all variables used
   * in the params list
   */
  public function selectClause($apiEntity = NULL) {

    $this->addSpecialFields($apiEntity);

    foreach ($this->_fields as $name => $field) {
      // skip component fields
      // there are done by the alter query below
      // and need not be done on every field
      if (
        (substr($name, 0, 12) == 'participant_') ||
        (substr($name, 0, 7) == 'pledge_') ||
        (substr($name, 0, 5) == 'case_') ||
        (substr($name, 0, 13) == 'contribution_' &&
          (strpos($name, 'source') !== FALSE && strpos($name, 'recur') !== FALSE)) ||
        (substr($name, 0, 8) == 'payment_')
      ) {
        continue;
      }

      // redirect to activity select clause
      if (
        (substr($name, 0, 9) == 'activity_') ||
        ($name == 'parent_id')
      ) {
        CRM_Activity_BAO_Query::select($this);
        continue;
      }

      // if this is a hierarchical name, we ignore it
      $names = explode('-', $name);
      if (count($names) > 1 && isset($names[1]) && is_numeric($names[1])) {
        continue;
      }

      // make an exception for special cases, to add the field in select clause
      $makeException = FALSE;

      //special handling for groups/tags
      if (in_array($name, array('groups', 'tags', 'notes'))
        && isset($this->_returnProperties[substr($name, 0, -1)])
      ) {
        $makeException = TRUE;
      }

      // since note has 3 different options we need special handling
      // note / note_subject / note_body
      if ($name == 'notes') {
        foreach (array('note', 'note_subject', 'note_body') as $noteField) {
          if (isset($this->_returnProperties[$noteField])) {
            $makeException = TRUE;
            break;
          }
        }
      }

      if (in_array($name, array('prefix_id', 'suffix_id', 'gender_id', 'communication_style_id'))) {
        if (CRM_Utils_Array::value($field['pseudoconstant']['optionGroupName'], $this->_returnProperties)) {
          $makeException = TRUE;
        }
      }

      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if (!empty($this->_paramLookup[$name]) || !empty($this->_returnProperties[$name]) ||
        $makeException
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

            if ($tableName == 'im_provider' || $tableName == 'email_greeting' ||
              $tableName == 'postal_greeting' || $tableName == 'addressee'
            ) {
              if ($tableName == 'im_provider') {
                CRM_Core_OptionValue::select($this);
              }

              if (in_array($tableName,
                array('email_greeting', 'postal_greeting', 'addressee'))) {
                $this->_element["{$name}_id"] = 1;
                $this->_select["{$name}_id"] = "contact_a.{$name}_id as {$name}_id";
                $this->_pseudoConstantsSelect[$name] = array('pseudoField' => $tableName, 'idCol' => "{$name}_id");
                $this->_pseudoConstantsSelect[$name]['select'] = "{$name}.{$fieldName} as $name";
                $this->_pseudoConstantsSelect[$name]['element'] = $name;

                if ($tableName == 'email_greeting') {
                  $this->_pseudoConstantsSelect[$name]['join']
                    = " LEFT JOIN civicrm_option_group option_group_email_greeting ON (option_group_email_greeting.name = 'email_greeting')";
                  $this->_pseudoConstantsSelect[$name]['join'] .=
                    " LEFT JOIN civicrm_option_value email_greeting ON (contact_a.email_greeting_id = email_greeting.value AND option_group_email_greeting.id = email_greeting.option_group_id ) ";
                }
                elseif ($tableName == 'postal_greeting') {
                  $this->_pseudoConstantsSelect[$name]['join']
                    = " LEFT JOIN civicrm_option_group option_group_postal_greeting ON (option_group_postal_greeting.name = 'postal_greeting')";
                  $this->_pseudoConstantsSelect[$name]['join'] .=
                    " LEFT JOIN civicrm_option_value postal_greeting ON (contact_a.postal_greeting_id = postal_greeting.value AND option_group_postal_greeting.id = postal_greeting.option_group_id ) ";
                }
                elseif ($tableName == 'addressee') {
                  $this->_pseudoConstantsSelect[$name]['join']
                    = " LEFT JOIN civicrm_option_group option_group_addressee ON (option_group_addressee.name = 'addressee')";
                  $this->_pseudoConstantsSelect[$name]['join'] .=
                    " LEFT JOIN civicrm_option_value addressee ON (contact_a.addressee_id = addressee.value AND option_group_addressee.id = addressee.option_group_id ) ";
                }
                $this->_pseudoConstantsSelect[$name]['table'] = $tableName;

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
              if (!in_array($tableName, array('civicrm_state_province', 'civicrm_country', 'civicrm_county'))) {
                $this->_tables[$tableName] = 1;
              }

              // also get the id of the tableName
              $tName = substr($tableName, 8);
              if (in_array($tName, array('country', 'state_province', 'county'))) {
                if ($tName == 'state_province') {
                  $this->_pseudoConstantsSelect['state_province_name'] = array(
                    'pseudoField' => "{$tName}",
                    'idCol' => "{$tName}_id",
                    'bao' => 'CRM_Core_BAO_Address',
                    'table' => "civicrm_{$tName}",
                    'join' => " LEFT JOIN civicrm_{$tName} ON civicrm_address.{$tName}_id = civicrm_{$tName}.id ",
                  );

                  $this->_pseudoConstantsSelect[$tName] = array(
                    'pseudoField' => 'state_province_abbreviation',
                    'idCol' => "{$tName}_id",
                    'table' => "civicrm_{$tName}",
                    'join' => " LEFT JOIN civicrm_{$tName} ON civicrm_address.{$tName}_id = civicrm_{$tName}.id ",
                  );
                }
                else {
                  $this->_pseudoConstantsSelect[$name] = array(
                    'pseudoField' => "{$tName}_id",
                    'idCol' => "{$tName}_id",
                    'bao' => 'CRM_Core_BAO_Address',
                    'table' => "civicrm_{$tName}",
                    'join' => " LEFT JOIN civicrm_{$tName} ON civicrm_address.{$tName}_id = civicrm_{$tName}.id ",
                  );
                }

                $this->_select["{$tName}_id"] = "civicrm_address.{$tName}_id as {$tName}_id";
                $this->_element["{$tName}_id"] = 1;
              }
              elseif ($tName != 'contact') {
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

              if ($tName == 'contact') {
                // special case, when current employer is set for Individual contact
                if ($fieldName == 'organization_name') {
                  $this->_select[$name] = "IF ( contact_a.contact_type = 'Individual', NULL, contact_a.organization_name ) as organization_name";
                }
                elseif ($fieldName != 'id') {
                  if ($fieldName == 'prefix_id') {
                    $this->_pseudoConstantsSelect['individual_prefix'] = array(
                      'pseudoField' => 'prefix_id',
                      'idCol' => "prefix_id",
                      'bao' => 'CRM_Contact_BAO_Contact',
                    );
                  }
                  if ($fieldName == 'suffix_id') {
                    $this->_pseudoConstantsSelect['individual_suffix'] = array(
                      'pseudoField' => 'suffix_id',
                      'idCol' => "suffix_id",
                      'bao' => 'CRM_Contact_BAO_Contact',
                    );
                  }
                  if ($fieldName == 'gender_id') {
                    $this->_pseudoConstantsSelect['gender'] = array(
                      'pseudoField' => 'gender_id',
                      'idCol' => "gender_id",
                      'bao' => 'CRM_Contact_BAO_Contact',
                    );
                  }
                  if ($name == 'communication_style_id') {
                    $this->_pseudoConstantsSelect['communication_style'] = array(
                      'pseudoField' => 'communication_style_id',
                      'idCol' => "communication_style_id",
                      'bao' => 'CRM_Contact_BAO_Contact',
                    );
                  }
                  $this->_select[$name] = "contact_a.{$fieldName}  as `$name`";
                }
              }
              elseif (in_array($tName, array('country', 'county'))) {
                $this->_pseudoConstantsSelect[$name]['select'] = "{$field['where']} as `$name`";
                $this->_pseudoConstantsSelect[$name]['element'] = $name;
              }
              elseif ($tName == 'state_province') {
                $this->_pseudoConstantsSelect[$tName]['select'] = "{$field['where']} as `$name`";
                $this->_pseudoConstantsSelect[$tName]['element'] = $name;
              }
              elseif (strpos($name, 'contribution_soft_credit') !== FALSE) {
                if (CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($this->_params)) {
                  $this->_select[$name] = "{$field['where']} as `$name`";
                }
              }
              else {
                $this->_select[$name] = "{$field['where']} as `$name`";
              }
              if (!in_array($tName, array('state_province', 'country', 'county'))) {
                $this->_element[$name] = 1;
              }
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
          // if note field is subject then return subject else body of the note
          $noteColumn = 'note';
          if (isset($noteField) && $noteField == 'note_subject') {
            $noteColumn = 'subject';
          }

          $this->_useGroupBy = TRUE;
          $this->_select[$name] = "GROUP_CONCAT(DISTINCT(civicrm_note.$noteColumn)) as notes";
          $this->_element[$name] = 1;
          $this->_tables['civicrm_note'] = 1;
        }
        elseif ($name === 'current_employer') {
          $this->_select[$name] = "IF ( contact_a.contact_type = 'Individual', contact_a.organization_name, NULL ) as current_employer";
          $this->_element[$name] = 1;
        }
      }

      if ($cfID && !empty($field['is_search_range'])) {
        // this is a custom field with range search enabled, so we better check for two/from values
        if (!empty($this->_paramLookup[$name . '_from'])) {
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
        if (!empty($this->_paramLookup[$name . '_to'])) {
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

    CRM_Contact_BAO_Query_Hook::singleton()->alterSearchQuery($this, 'select');

    if (!empty($this->_cfIDs)) {
      // @todo This function is the select function but instead of running 'select' it
      // is running the whole query.
      $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs, TRUE, $this->_locationSpecificCustomFields);
      $this->_customQuery->query();
      $this->_select = array_merge($this->_select, $this->_customQuery->_select);
      $this->_element = array_merge($this->_element, $this->_customQuery->_element);
      $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
      $this->_whereTables = array_merge($this->_whereTables, $this->_customQuery->_whereTables);
      $this->_options = $this->_customQuery->_options;
    }
  }

  /**
   * If the return Properties are set in a hierarchy, traverse the hierarchy to get the return values.
   */
  public function addHierarchicalElements() {
    if (empty($this->_returnProperties['location'])) {
      return;
    }
    if (!is_array($this->_returnProperties['location'])) {
      return;
    }

    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
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
          if ((!$cond) && ($elementName == 'phone')) {
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

        // Check if there is a value, if so also add to where Clause
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
            if ((is_array($values) && $values[0] == $nm) ||
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
          $pf = substr($tableName, 8);
          $tName = $name . '-' . $pf . $elementType;
          if (isset($tableName)) {
            if ($tableName == 'civicrm_state_province' || $tableName == 'civicrm_country' || $tableName == 'civicrm_county') {
              $this->_select["{$tName}_id"] = "{$aName}.{$pf}_id as `{$tName}_id`";
            }
            else {
              $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
            }

            $this->_element["{$tName}_id"] = 1;
            if (substr($tName, -15) == '-state_province') {
              // FIXME: hack to fix CRM-1900
              $a = Civi::settings()->get('address_format');

              if (substr_count($a, 'state_province_name') > 0) {
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"] = array(
                  'pseudoField' => "{$pf}_id",
                  'idCol' => "{$tName}_id",
                  'bao' => 'CRM_Core_BAO_Address',
                );
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['select'] = "`$tName`.name as `{$name}-{$elementFullName}`";
              }
              else {
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"] = array(
                  'pseudoField' => 'state_province_abbreviation',
                  'idCol' => "{$tName}_id",
                );
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['select'] = "`$tName`.abbreviation as `{$name}-{$elementFullName}`";
              }
            }
            else {
              if (substr($elementFullName, 0, 2) == 'im') {
                $provider = "{$name}-{$elementFullName}-provider_id";
                $this->_select[$provider] = "`$tName`.provider_id as `{$name}-{$elementFullName}-provider_id`";
                $this->_element[$provider] = 1;
              }
              if ($pf == 'country' || $pf == 'county') {
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"] = array(
                  'pseudoField' => "{$pf}_id",
                  'idCol' => "{$tName}_id",
                  'bao' => 'CRM_Core_BAO_Address',
                );
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['select'] = "`$tName`.$fieldName as `{$name}-{$elementFullName}`";
              }
              else {
                $this->_select["{$name}-{$elementFullName}"] = "`$tName`.$fieldName as `{$name}-{$elementFullName}`";
              }
            }

            if (in_array($pf, array('state_province', 'country', 'county'))) {
              $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['element'] = "{$name}-{$elementFullName}";
            }
            else {
              $this->_element["{$name}-{$elementFullName}"] = 1;
            }

            if (empty($processed["`$tName`"])) {
              $processed["`$tName`"] = 1;
              $newName = $tableName . '_' . $index;
              switch ($tableName) {
                case 'civicrm_phone':
                case 'civicrm_email':
                case 'civicrm_im':
                case 'civicrm_openid':

                  $this->_tables[$tName] = "\nLEFT JOIN $tableName `$tName` ON contact_a.id = `$tName`.contact_id";
                  if ($tableName != 'civicrm_phone') {
                    $this->_tables[$tName] .= " AND `$tName`.$lCond";
                  }
                  elseif (is_numeric($name)) {
                    $this->_select[$tName] = "IF (`$tName`.is_primary = $name, `$tName`.phone, NULL) as `$tName`";
                  }

                  // this special case to add phone type
                  if ($cond) {
                    $phoneTypeCondition = " AND `$tName`.$cond ";
                    //gross hack to pickup corrupted data also, CRM-7603
                    if (strpos($cond, 'phone_type_id') !== FALSE) {
                      $phoneTypeCondition = " AND ( `$tName`.$cond OR `$tName`.phone_type_id IS NULL ) ";
                      if (!empty($lCond)) {
                        $phoneTypeCondition .= " AND ( `$tName`.$lCond ) ";
                      }
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
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['table'] = $tName;
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['join']
                    = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.state_province_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                  }
                  break;

                case 'civicrm_country':
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['table'] = $newName;
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['join']
                    = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.country_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                  }
                  break;

                case 'civicrm_county':
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['table'] = $newName;
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['join']
                    = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.county_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
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
          if (!empty($locationTypeJoin[$tableName])) {
            $locClause[] = $locationTypeJoin[$tableName];
          }
        }

        if (!empty($locClause)) {
          $this->_whereTables[$locationTypeName] = "\nLEFT JOIN civicrm_location_type $ltName ON ( " . implode('OR', $locClause) . " )";
        }
      }
    }

    if (!empty($addressCustomFieldIds)) {
      $customQuery = new CRM_Core_BAO_CustomQuery($addressCustomFieldIds);
      foreach ($addressCustomFieldIds as $cfID => $locTypeName) {
        foreach ($locTypeName as $name => $dnc) {
          $this->_locationSpecificCustomFields[$cfID] = array($name, array_search($name, $locationTypes));
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
   * If the return Properties are set in a hierarchy, traverse the hierarchy to get the return values.
   */
  public function addMultipleElements() {
    if (empty($this->_returnProperties['website'])) {
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
        $this->_tables[$tName] = "\nLEFT JOIN civicrm_website `$tName` ON (`$tName`.contact_id = contact_a.id AND `$tName`.website_type_id = $key )";
      }
    }
  }

  /**
   * Generate the query based on what type of query we need.
   *
   * @param bool $count
   * @param bool $sortByChar
   * @param bool $groupContacts
   * @param bool $onlyDeleted
   *
   * @return array
   *   sql query parts as an array
   */
  public function query($count = FALSE, $sortByChar = FALSE, $groupContacts = FALSE, $onlyDeleted = FALSE) {
    // build permission clause
    $this->generatePermissionClause($onlyDeleted, $count);

    if ($count) {
      if (isset($this->_rowCountClause)) {
        $select = "SELECT {$this->_rowCountClause}";
      }
      elseif (isset($this->_distinctComponentClause)) {
        // we add distinct to get the right count for components
        // for the more complex result set, we use GROUP BY the same id
        // CRM-9630
        $select = "SELECT count( DISTINCT {$this->_distinctComponentClause} ) as rowCount";
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
      if (!empty($this->_paramLookup['group'])) {

        list($name, $op, $value, $grouping, $wildcard) = $this->_paramLookup['group'][0];

        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $this->_paramLookup['group'][0][1] = key($value);
        }

        // make sure there is only one element
        // this is used when we are running under smog and need to know
        // how the contact was added (CRM-1203)
        $groups = (array) CRM_Utils_Array::value($this->_paramLookup['group'][0][1], $this->_paramLookup['group'][0][2], $this->_paramLookup['group'][0][2]);
        if ((count($this->_paramLookup['group']) == 1) &&
          (count($groups) == 1)
        ) {
          $groupId = $groups[0];

          //check if group is saved search
          $group = new CRM_Contact_BAO_Group();
          $group->id = $groupId;
          $group->find(TRUE);

          if (!isset($group->saved_search_id)) {
            $tbName = "`civicrm_group_contact-{$groupId}`";
            // CRM-17254 don't retrieve extra fields if contact_id is specifically requested
            // as this will add load to an intentionally light query.
            // ideally this code would be removed as it appears to be to support CRM-1203
            // and passing in the required returnProperties from the url would
            // make more sense that globally applying the requirements of one form.
            if (($this->_returnProperties != array('contact_id'))) {
              $this->_select['group_contact_id'] = "$tbName.id as group_contact_id";
              $this->_element['group_contact_id'] = 1;
              $this->_select['status'] = "$tbName.status as status";
              $this->_element['status'] = 1;
            }
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

    if (!empty($this->_permissionWhereClause) && empty($this->_displayRelationshipType)) {
      if (empty($where)) {
        $where = "WHERE $this->_permissionWhereClause";
      }
      else {
        $where = "$where AND $this->_permissionWhereClause";
      }
    }

    $having = '';
    if (!empty($this->_having)) {
      foreach ($this->_having as $havingSets) {
        foreach ($havingSets as $havingSet) {
          $havingValue[] = $havingSet;
        }
      }
      $having = ' HAVING ' . implode(' AND ', $havingValue);
    }

    // if we are doing a transform, do it here
    // use the $from, $where and $having to get the contact ID
    if ($this->_displayRelationshipType) {
      $this->filterRelatedContacts($from, $where, $having);
    }

    return array($select, $from, $where, $having);
  }

  /**
   * Get where values from the parameters.
   *
   * @param string $name
   * @param mixed $grouping
   *
   * @return mixed
   */
  public function getWhereValues($name, $grouping) {
    $result = NULL;
    foreach ($this->_params as $values) {
      if ($values[0] == $name && $values[3] == $grouping) {
        return $values;
      }
    }

    return $result;
  }

  /**
   * Fix date values.
   *
   * @param bool $relative
   * @param string $from
   * @param string $to
   */
  public static function fixDateValues($relative, &$from, &$to) {
    if ($relative) {
      list($from, $to) = CRM_Utils_Date::getFromTo($relative, $from, $to);
    }
  }

  /**
   * Convert values from form-appropriate to query-object appropriate.
   *
   * The query object is increasingly supporting the sql-filter syntax which is the most flexible syntax.
   * So, ideally we would convert all fields to look like
   *  array(
   *   0 => $fieldName
   *   // Set the operator for legacy reasons, but it is ignored
   *   1 =>  '='
   *   // array in sql filter syntax
   *   2 => array('BETWEEN' => array(1,60),
   *   3 => null
   *   4 => null
   *  );
   *
   * There are some examples of the syntax in
   * https://github.com/civicrm/civicrm-core/tree/master/api/v3/examples/Relationship
   *
   * More notes at CRM_Core_DAO::createSQLFilter
   *
   * and a list of supported operators in CRM_Core_DAO
   *
   * @param array $formValues
   * @param int $wildcard
   * @param bool $useEquals
   *
   * @param string $apiEntity
   *
   * @param array $entityReferenceFields
   *   Field names of any entity reference fields (which will need reformatting to IN syntax).
   *
   * @return array
   */
  public static function convertFormValues(&$formValues, $wildcard = 0, $useEquals = FALSE, $apiEntity = NULL,
    $entityReferenceFields = array()) {
    $params = array();
    if (empty($formValues)) {
      return $params;
    }

    self::filterCountryFromValuesIfStateExists($formValues);

    foreach ($formValues as $id => $values) {

      if (self::isAlreadyProcessedForQueryFormat($values)) {
        $params[] = $values;
        continue;
      }

      self::legacyConvertFormValues($id, $values);

      // The form uses 1 field to represent two db fields
      if ($id == 'contact_type' && $values && (!is_array($values) || !array_intersect(array_keys($values), CRM_Core_DAO::acceptedSQLOperators()))) {
        $contactType = array();
        $subType = array();
        foreach ((array) $values as $key => $type) {
          $types = explode('__', is_numeric($type) ? $key : $type);
          $contactType[$types[0]] = $types[0];
          // Add sub-type if specified
          if (!empty($types[1])) {
            $subType[$types[1]] = $types[1];
          }
        }
        $params[] = array('contact_type', 'IN', $contactType, 0, 0);
        if ($subType) {
          $params[] = array('contact_sub_type', 'IN', $subType, 0, 0);
        }
      }
      elseif ($id == 'privacy') {
        if (is_array($formValues['privacy'])) {
          $op = !empty($formValues['privacy']['do_not_toggle']) ? '=' : '!=';
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
      elseif (substr($id, 0, 7) == 'custom_'
        &&  (
          substr($id, -9, 9) == '_relative'
          || substr($id, -5, 5) == '_from'
          || substr($id, -3, 3) == '_to'
        )
      ) {
        self::convertCustomRelativeFields($formValues, $params, $values, $id);
      }
      elseif (preg_match('/_date_relative$/', $id) ||
        $id == 'event_relative' ||
        $id == 'case_from_relative' ||
        $id == 'case_to_relative' ||
        $id == 'participant_relative'
      ) {
        if ($id == 'event_relative') {
          $fromRange = 'event_start_date_low';
          $toRange = 'event_end_date_high';
        }
        elseif ($id == 'participant_relative') {
          $fromRange = 'participant_register_date_low';
          $toRange = 'participant_register_date_high';
        }
        elseif ($id == 'case_from_relative') {
          $fromRange = 'case_from_start_date_low';
          $toRange = 'case_from_start_date_high';
        }
        elseif ($id == 'case_to_relative') {
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
      elseif (in_array($id, $entityReferenceFields) && !empty($values) && is_string($values) && (strpos($values, ',') !=
        FALSE)) {
        $params[] = array($id, 'IN', explode(',', $values), 0, 0);
      }
      else {
        $values = CRM_Contact_BAO_Query::fixWhereValues($id, $values, $wildcard, $useEquals, $apiEntity);

        if (!$values) {
          continue;
        }
        $params[] = $values;
      }
    }
    return $params;
  }

  /**
   * Function to support legacy format for groups and tags.
   *
   * @param string $id
   * @param array|int $values
   *
   */
  public static function legacyConvertFormValues($id, &$values) {
    $legacyElements = array(
      'group',
      'tag',
      'contact_tags',
      'contact_type',
      'membership_type_id',
      'membership_status_id',
    );
    if (in_array($id, $legacyElements) && is_array($values)) {
      // prior to 4.7, formValues for some attributes (e.g. group, tag) are stored in array(id1 => 1, id2 => 1),
      // as per the recent Search fixes $values need to be in standard array(id1, id2) format
      CRM_Utils_Array::formatArrayKeys($values);
    }
  }

  /**
   * Fix values from query from/to something no-one cared enough to document.
   *
   * @param int $id
   * @param array $values
   * @param int $wildcard
   * @param bool $useEquals
   *
   * @param string $apiEntity
   *
   * @return array|null
   */
  public static function fixWhereValues($id, &$values, $wildcard = 0, $useEquals = FALSE, $apiEntity = NULL) {
    // skip a few search variables
    static $skipWhere = NULL;
    static $likeNames = NULL;
    $result = NULL;
    // Change camelCase EntityName to lowercase with underscores
    $apiEntity = _civicrm_api_get_entity_name_from_camel($apiEntity);

    if (CRM_Utils_System::isNull($values)) {
      return $result;
    }

    if (!$skipWhere) {
      $skipWhere = array(
        'task',
        'radio_ts',
        'uf_group_id',
        'component_mode',
        'qfKey',
        'operator',
        'display_relationship_type',
      );
    }

    if (in_array($id, $skipWhere) ||
      substr($id, 0, 4) == '_qf_' ||
      substr($id, 0, 7) == 'hidden_'
    ) {
      return $result;
    }

    if ($apiEntity &&
      (substr($id, 0, strlen($apiEntity)) != $apiEntity) &&
      (substr($id, 0, 10) != 'financial_' && substr($id, 0, 8) != 'payment_') &&
      (substr($id, 0, 7) != 'custom_')
    ) {
      $id = $apiEntity . '_' . $id;
    }

    if (!$likeNames) {
      $likeNames = array('sort_name', 'email', 'note', 'display_name');
    }

    // email comes in via advanced search
    // so use wildcard always
    if ($id == 'email') {
      $wildcard = 1;
    }

    if (!$useEquals && in_array($id, $likeNames)) {
      $result = array($id, 'LIKE', $values, 0, 1);
    }
    elseif (is_string($values) && strpos($values, '%') !== FALSE) {
      $result = array($id, 'LIKE', $values, 0, 0);
    }
    elseif ($id == 'contact_type' ||
      (!empty($values) && is_array($values) && !in_array(key($values), CRM_Core_DAO::acceptedSQLOperators(), TRUE))
    ) {
      $result = array($id, 'IN', $values, 0, $wildcard);
    }
    else {
      $result = array($id, '=', $values, 0, $wildcard);
    }

    return $result;
  }

  /**
   * Get the where clause for a single field.
   *
   * @param array $values
   */
  public function whereClauseSingle(&$values) {
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
      (substr($values[0], 0, 10) == 'financial_') ||
      (substr($values[0], 0, 8) == 'payment_') ||
      (substr($values[0], 0, 11) == 'membership_')
    ) {
      return;
    }

    // skip for hook injected fields / params
    $extFields = CRM_Contact_BAO_Query_Hook::singleton()->getFields();
    if (array_key_exists($values[0], $extFields)) {
      return;
    }

    switch ($values[0]) {
      case 'deleted_contacts':
        $this->deletedContacts($values);
        return;

      case 'contact_sub_type':
        $this->contactSubType($values);
        return;

      case 'group':
        $this->group($values);
        return;

      case 'group_type':
        // so we resolve this into a list of groups & proceed as if they had been
        // handed in
        list($name, $op, $value, $grouping, $wildcard) = $values;
        $values[0] = 'group';
        $values[1] = 'IN';
        $this->_paramLookup['group'][0][0] = 'group';
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
        return;

      case 'addressee':
      case 'postal_greeting':
      case 'email_greeting':
        $this->greetings($values);
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
      case 'state_province_id':
      case 'state_province_name':
        $this->stateProvince($values);
        return;

      case 'country':
      case 'country_id':
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
      case 'activity_status_id':
      case 'activity_status':
      case 'followup_parent_id':
      case 'parent_id':
      case 'source_contact_id':
      case 'activity_subject':
      case 'test_activities':
      case 'activity_type_id':
      case 'activity_type':
      case 'activity_survey_id':
      case 'activity_tags':
      case 'activity_taglist':
      case 'activity_test':
      case 'activity_campaign_id':
      case 'activity_engagement_level':
      case 'activity_id':
      case 'activity_result':
      case 'source_contact':
        CRM_Activity_BAO_Query::whereClauseSingle($values, $this);
        return;

      case 'age_low':
      case 'age_high':
      case 'birth_date_low':
      case 'birth_date_high':
      case 'deceased_date_low':
      case 'deceased_date_high':
        $this->demographics($values);
        return;

      case 'age_asof_date':
        // handled by demographics
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
      case 'relation_start_date_high':
      case 'relation_start_date_low':
      case 'relation_end_date_high':
      case 'relation_end_date_low':
      case 'relation_target_name':
      case 'relation_status':
      case 'relation_date_low':
      case 'relation_date_high':
        $this->relationship($values);
        $this->_relationshipValuesAdded = TRUE;
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
   * Given a list of conditions in params generate the required where clause.
   *
   * @return string
   */
  public function whereClause() {
    $this->_where[0] = array();
    $this->_qill[0] = array();

    $this->includeContactIds();
    if (!empty($this->_params)) {
      foreach (array_keys($this->_params) as $id) {
        if (empty($this->_params[$id][0])) {
          continue;
        }
        // check for both id and contact_id
        if ($this->_params[$id][0] == 'id' || $this->_params[$id][0] == 'contact_id') {
          $this->_where[0][] = self::buildClause("contact_a.id", $this->_params[$id][1], $this->_params[$id][2]);
        }
        else {
          $this->whereClauseSingle($this->_params[$id]);
        }
      }

      CRM_Core_Component::alterQuery($this, 'where');

      CRM_Contact_BAO_Query_Hook::singleton()->alterSearchQuery($this, 'where');
    }

    if ($this->_customQuery) {
      // Added following if condition to avoid the wrong value display for 'my account' / any UF info.
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

  /**
   * Generate where clause for any parameters not already handled.
   *
   * @param array $values
   *
   * @throws Exception
   */
  public function restWhere(&$values) {
    $name = CRM_Utils_Array::value(0, $values);
    $op = CRM_Utils_Array::value(1, $values);
    $value = CRM_Utils_Array::value(2, $values);
    $grouping = CRM_Utils_Array::value(3, $values);
    $wildcard = CRM_Utils_Array::value(4, $values);

    if (isset($grouping) && empty($this->_where[$grouping])) {
      $this->_where[$grouping] = array();
    }

    $multipleFields = array('url');

    //check if the location type exists for fields
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
    $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    if (isset($locType[1]) && is_numeric($locType[1])) {
      $lType = $locationType[$locType[1]];
    }
    if ($lType) {
      $field['title'] .= " ($lType)";
    }

    if (substr($name, 0, 14) === 'state_province') {
      if (isset($locType[1]) && is_numeric($locType[1])) {
        $setTables = FALSE;
        $aName = "{$lType}-address";
        $where = "`$aName`.state_province_id";
      }
      else {
        $where = "civicrm_address.state_province_id";
      }

      $this->_where[$grouping][] = self::buildClause($where, $op, $value);
      list($qillop, $qillVal) = self::buildQillForFieldValue('CRM_Core_DAO_Address', "state_province_id", $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", array(1 => $field['title'], 2 => $qillop, 3 => $qillVal));
    }
    elseif (!empty($field['pseudoconstant'])) {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        'CRM_Contact_DAO_Contact',
        $field,
        $field['title'],
        'String',
        TRUE
      );
      if ($name == 'gender_id') {
        self::$_openedPanes[ts('Demographics')] = TRUE;
      }
    }
    elseif (substr($name, 0, 7) === 'country' || substr($name, 0, 6) === 'county') {
      $name = (substr($name, 0, 7) === 'country') ? "country_id" : "county_id";
      if (isset($locType[1]) && is_numeric($locType[1])) {
        $setTables = FALSE;
        $aName = "{$lType}-address";
        $where = "`$aName`.$name";
      }
      else {
        $where = "civicrm_address.$name";
      }

      $this->_where[$grouping][] = self::buildClause($where, $op, $value, 'Positive');

      list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $name, $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", array(1 => $field['title'], 2 => $qillop, 3 => $qillVal));
    }
    elseif ($name === 'world_region') {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        NULL,
        $field,
        ts('World Region'),
        'Positive',
        TRUE
      );
    }
    elseif ($name === 'is_deceased') {
      $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $value);
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
      self::$_openedPanes[ts('Demographics')] = TRUE;
    }
    elseif ($name === 'created_date' || $name === 'modified_date' || $name === 'deceased_date' || $name === 'birth_date') {
      $appendDateTime = TRUE;
      if ($name === 'deceased_date' || $name === 'birth_date') {
        $appendDateTime = FALSE;
        self::$_openedPanes[ts('Demographics')] = TRUE;
      }
      $this->dateQueryBuilder($values, 'contact_a', $name, $name, $field['title'], $appendDateTime);
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
        $op = 'LIKE';
        $value = self::getWildCardedValue($wildcard, $op, $value);
      }
      $wc = self::caseImportant($op) ? "LOWER({$field['where']})" : "{$field['where']}";
      $this->_where[$grouping][] = self::buildClause($wc, $op, "'$value'");
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'current_employer') {
      $value = $strtolower(CRM_Core_DAO::escapeString($value));
      if ($wildcard) {
        $op = 'LIKE';
        $value = self::getWildCardedValue($wildcard, $op, $value);
      }
      $wc = self::caseImportant($op) ? "LOWER(contact_a.organization_name)" : "contact_a.organization_name";
      $ceWhereClause = self::buildClause($wc, $op,
        $value
      );
      $ceWhereClause .= " AND contact_a.contact_type = 'Individual'";
      $this->_where[$grouping][] = $ceWhereClause;
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
        $op = 'LIKE';
        $value = self::getWildCardedValue($wildcard, $op, $value);
      }

      $wc = 'civicrm_website.url';
      $this->_where[$grouping][] = $d = self::buildClause($wc, $op, $value);
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'contact_is_deleted') {
      $this->_where[$grouping][] = self::buildClause("contact_a.is_deleted", $op, $value);
      list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $name, $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", array(1 => $field['title'], 2 => $qillop, 3 => $qillVal));
    }
    elseif (!empty($field['where'])) {
      $type = NULL;
      if (!empty($field['type'])) {
        $type = CRM_Utils_Type::typeToString($field['type']);
      }

      list($tableName, $fieldName) = explode('.', $field['where'], 2);

      if (isset($locType[1]) &&
        is_numeric($locType[1])
      ) {
        $setTables = FALSE;

        //get the location name
        list($tName, $fldName) = self::getLocationTableName($field['where'], $locType);

        $fieldName = "LOWER(`$tName`.$fldName)";

        // we set both _tables & whereTables because whereTables doesn't seem to do what the name implies it should
        $this->_tables[$tName] = $this->_whereTables[$tName] = 1;

      }
      else {
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
      }

      list($qillop, $qillVal) = self::buildQillForFieldValue(NULL, $field['title'], $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", array(
        1 => $field['title'],
        2 => $qillop,
        3 => (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) ? $qillVal : "'$qillVal'"));

      if (is_array($value)) {
        // traditionally an array being passed has been a fatal error. We can take advantage of this to add support
        // for api style operators for functions that hit this point without worrying about regression
        // (the previous comments indicated the condition for hitting this point were unknown
        // per CRM-14743 we are adding modified_date & created_date operator support
        $operations = array_keys($value);
        foreach ($operations as $operator) {
          if (!in_array($operator, CRM_Core_DAO::acceptedSQLOperators())) {
            //Via Contact get api value is not in array(operator => array(values)) format ONLY for IN/NOT IN operators
            //so this condition will satisfy the search for now
            if (strpos($op, 'IN') !== FALSE) {
              $value = array($op => $value);
            }
            // we don't know when this might happen
            else {
              CRM_Core_Error::fatal(ts("%1 is not a valid operator", array(1 => $operator)));
            }
          }
        }
        $this->_where[$grouping][] = CRM_Core_DAO::createSQLFilter($fieldName, $value, $type);
      }
      else {
        if (!strpos($op, 'IN')) {
          $value = $strtolower($value);
        }
        if ($wildcard) {
          $op = 'LIKE';
          $value = self::getWildCardedValue($wildcard, $op, $value);
        }

        $this->_where[$grouping][] = self::buildClause($fieldName, $op, $value, $type);
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


  /**
   * @param $where
   * @param $locType
   *
   * @return array
   * @throws Exception
   */
  public static function getLocationTableName(&$where, &$locType) {
    if (isset($locType[1]) && is_numeric($locType[1])) {
      list($tbName, $fldName) = explode(".", $where);

      //get the location name
      $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
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
          'address_name',
          'street_address',
          'street_name',
          'street_number_suffix',
          'street_unit',
          'supplemental_address_1',
          'supplemental_address_2',
          'city',
          'postal_code',
          'postal_code_suffix',
          'geo_code_1',
          'geo_code_2',
          'master_id',
        )
      )) {
        //fix for search by profile with address fields.
        $tName = "{$locationType[$locType[1]]}-address";
      }
      elseif (in_array($locType[0],
          array(
            'on_hold',
            'signature_html',
            'signature_text',
            'is_bulkmail',
          )
        )) {
        $tName = "{$locationType[$locType[1]]}-email";
      }
      elseif ($locType[0] == 'provider_id') {
        $tName = "{$locationType[$locType[1]]}-im";
      }
      elseif ($locType[0] == 'openid') {
        $tName = "{$locationType[$locType[1]]}-openid";
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
   * @param CRM_Core_DAO $dao
   *
   * @return array
   *   values for this query
   */
  public function store($dao) {
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
   * Getter for tables array.
   *
   * @return array
   */
  public function tables() {
    return $this->_tables;
  }

  /**
   * Where tables is sometimes used to create the from clause, but, not reliably, set this AND set tables
   * It's unclear the intent - there is a 'simpleFrom' clause which takes whereTables into account & a fromClause which doesn't
   * logic may have eroded
   * @return array
   */
  public function whereTables() {
    return $this->_whereTables;
  }

  /**
   * Generate the where clause (used in match contacts and permissions)
   *
   * @param array $params
   * @param array $fields
   * @param array $tables
   * @param $whereTables
   * @param bool $strict
   *
   * @return string
   */
  public static function getWhereClause($params, $fields, &$tables, &$whereTables, $strict = FALSE) {
    $query = new CRM_Contact_BAO_Query($params, NULL, $fields,
      FALSE, $strict
    );

    $tables = array_merge($query->tables(), $tables);
    $whereTables = array_merge($query->whereTables(), $whereTables);

    return $query->_whereClause;
  }

  /**
   * Create the from clause.
   *
   * @param array $tables
   *   Tables that need to be included in this from clause.
   *                      if null, return mimimal from clause (i.e. civicrm_contact)
   * @param array $inner
   *   Tables that should be inner-joined.
   * @param array $right
   *   Tables that should be right-joined.
   *
   * @param bool $primaryLocation
   * @param int $mode
   *
   * @return string
   *   the from clause
   */
  public static function fromClause(&$tables, $inner = NULL, $right = NULL, $primaryLocation = TRUE, $mode = 1) {

    $from = ' FROM civicrm_contact contact_a';
    if (empty($tables)) {
      return $from;
    }

    if (!empty($tables['civicrm_worldregion'])) {
      $tables = array_merge(array('civicrm_country' => 1), $tables);
    }

    if ((!empty($tables['civicrm_state_province']) || !empty($tables['civicrm_country']) ||
        CRM_Utils_Array::value('civicrm_county', $tables)
      ) && empty($tables['civicrm_address'])
    ) {
      $tables = array_merge(array('civicrm_address' => 1),
        $tables
      );
    }

    // add group_contact and group_contact_cache table if group table is present
    if (!empty($tables['civicrm_group'])) {
      if (empty($tables['civicrm_group_contact'])) {
        $tables['civicrm_group_contact'] = " LEFT JOIN civicrm_group_contact ON civicrm_group_contact.contact_id = contact_a.id AND civicrm_group_contact.status = 'Added' ";
      }
      if (empty($tables['civicrm_group_contact_cache'])) {
        $tables['civicrm_group_contact_cache'] = " LEFT JOIN civicrm_group_contact_cache ON civicrm_group_contact_cache.contact_id = contact_a.id ";
      }
    }

    // add group_contact and group table is subscription history is present
    if (!empty($tables['civicrm_subscription_history']) && empty($tables['civicrm_group'])) {
      $tables = array_merge(array(
          'civicrm_group' => 1,
          'civicrm_group_contact' => 1,
        ),
        $tables
      );
    }

    // to handle table dependencies of components
    CRM_Core_Component::tableNames($tables);
    // to handle table dependencies of hook injected tables
    CRM_Contact_BAO_Query_Hook::singleton()->setTableDependency($tables);

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

      if (!empty($inner[$name])) {
        $side = 'INNER';
      }
      elseif (!empty($right[$name])) {
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
            //CRM-14263 further handling of address joins further down...
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

        case 'civicrm_worldregion':
          $from .= " $side JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id ";
          $from .= " $side JOIN civicrm_worldregion ON civicrm_country.region_id = civicrm_worldregion.id ";
          continue;

        case 'civicrm_location_type':
          $from .= " $side JOIN civicrm_location_type ON civicrm_address.location_type_id = civicrm_location_type.id ";
          continue;

        case 'civicrm_group':
          $from .= " $side JOIN civicrm_group ON (civicrm_group.id = civicrm_group_contact.group_id OR civicrm_group.id = civicrm_group_contact_cache.group_id) ";
          continue;

        case 'civicrm_group_contact':
          $from .= " $side JOIN civicrm_group_contact ON contact_a.id = civicrm_group_contact.contact_id ";
          continue;

        case 'civicrm_group_contact_cache':
          $from .= " $side JOIN civicrm_group_contact_cache ON contact_a.id = civicrm_group_contact_cache.contact_id ";
          continue;

        case 'civicrm_activity':
        case 'civicrm_activity_tag':
        case 'activity_type':
        case 'activity_status':
        case 'parent_id':
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

        case 'civicrm_relationship':
          if (self::$_relType == 'reciprocal') {
            if (self::$_relationshipTempTable) {
              // we have a temptable to join on
              $tbl = self::$_relationshipTempTable;
              $from .= " INNER JOIN {$tbl} civicrm_relationship ON civicrm_relationship.contact_id = contact_a.id";
            }
            else {
              $from .= " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = contact_a.id OR civicrm_relationship.contact_id_a = contact_a.id)";
              $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_a = contact_b.id OR civicrm_relationship.contact_id_b = contact_b.id)";
            }
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
          $from .= " INNER JOIN civicrm_log ON (civicrm_log.entity_id = contact_a.id AND civicrm_log.entity_table = 'civicrm_contact')";
          $from .= " INNER JOIN civicrm_contact contact_b_log ON (civicrm_log.modified_id = contact_b_log.id)";
          continue;

        case 'civicrm_tag':
          $from .= " $side  JOIN civicrm_tag ON civicrm_entity_tag.tag_id = civicrm_tag.id ";
          continue;

        case 'civicrm_grant':
          $from .= CRM_Grant_BAO_Query::from($name, $mode, $side);
          continue;

        case 'civicrm_website':
          $from .= " $side JOIN civicrm_website ON contact_a.id = civicrm_website.contact_id ";
          continue;

        default:
          $locationTypeName = '';
          if (strpos($name, '-address') != 0) {
            $locationTypeName = 'address';
          }
          elseif (strpos($name, '-phone') != 0) {
            $locationTypeName = 'phone';
          }
          elseif (strpos($name, '-email') != 0) {
            $locationTypeName = 'email';
          }
          elseif (strpos($name, '-im') != 0) {
            $locationTypeName = 'im';
          }
          elseif (strpos($name, '-openid') != 0) {
            $locationTypeName = 'openid';
          }

          if ($locationTypeName) {
            //we have a join on an location table - possibly in conjunction with search builder - CRM-14263
            $parts = explode('-', $name);
            $locationID = array_search($parts[0], CRM_Core_BAO_Address::buildOptions('location_type_id', 'get', array('name' => $parts[0])));
            $from .= " $side JOIN civicrm_{$locationTypeName} `{$name}` ON ( contact_a.id = `{$name}`.contact_id ) and `{$name}`.location_type_id = $locationID ";
          }
          else {
            $from .= CRM_Core_Component::from($name, $mode, $side);
          }
          $from .= CRM_Contact_BAO_Query_Hook::singleton()->buildSearchfrom($name, $mode, $side);

          continue;
      }
    }
    return $from;
  }

  /**
   * WHERE / QILL clause for deleted_contacts
   *
   * @param array $values
   */
  public function deletedContacts($values) {
    list($_, $_, $value, $grouping, $_) = $values;
    if ($value) {
      // *prepend* to the relevant grouping as this is quite an important factor
      array_unshift($this->_qill[$grouping], ts('Search in Trash'));
    }
  }

  /**
   * Where / qill clause for contact_type
   *
   * @param $values
   */
  public function contactType(&$values) {
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
      $quill = $clause;
      if ($op == 'IN' || $op == 'NOT IN') {
        $this->_where[$grouping][] = "contact_a.contact_type $op (" . implode(',', $clause) . ')';
      }
      else {
        $type = array_pop($clause);
        $this->_where[$grouping][] = self::buildClause("contact_a.contact_type", $op, $contactType);
      }

      $this->_qill[$grouping][] = ts('Contact Type') . " $op " . implode(' ' . ts('or') . ' ', $quill);

      if (!empty($subTypes)) {
        $this->includeContactSubTypes($subTypes, $grouping);
      }
    }
  }

  /**
   * Where / qill clause for contact_sub_type
   *
   * @param $values
   */
  public function contactSubType(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $this->includeContactSubTypes($value, $grouping, $op);
  }

  /**
   * @param $value
   * @param $grouping
   * @param string $op
   */
  public function includeContactSubTypes($value, $grouping, $op = 'LIKE') {

    if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($value);
      $value = $value[$op];
    }

    $clause = array();
    $alias = "contact_a.contact_sub_type";
    $qillOperators = CRM_Core_SelectValues::getSearchBuilderOperators();

    $op = str_replace('IN', 'LIKE', $op);
    $op = str_replace('=', 'LIKE', $op);
    $op = str_replace('!', 'NOT ', $op);

    if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
      $this->_where[$grouping][] = self::buildClause($alias, $op, $value, 'String');
    }
    elseif (is_array($value)) {
      foreach ($value as $k => $v) {
        if (!empty($k)) {
          $clause[$k] = "($alias $op '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($v, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
        }
      }
    }
    else {
      $clause[$value] = "($alias $op '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($value, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
    }

    if (!empty($clause)) {
      $this->_where[$grouping][] = "( " . implode(' OR ', $clause) . " )";
    }
    $this->_qill[$grouping][] = ts('Contact Subtype %1 ', array(1 => $qillOperators[$op])) . implode(' ' . ts('or') . ' ', array_keys($clause));
  }

  /**
   * Where / qill clause for groups
   *
   * @param $values
   */
  public function group(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    // If the $value is in OK (operator as key) array format we need to extract the key as operator and value first
    if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($value);
      $value = $value[$op];
    }

    // Replace pseudo operators from search builder
    $op = str_replace('EMPTY', 'NULL', $op);

    if (strpos($op, 'NULL')) {
      $value = NULL;
    }

    if (count($value) > 1) {
      if (strpos($op, 'IN') === FALSE && strpos($op, 'NULL') === FALSE) {
        CRM_Core_Error::fatal(ts("%1 is not a valid operator", array(1 => $op)));
      }
      $this->_useDistinct = TRUE;
    }

    if (isset($value)) {
      $value = CRM_Utils_Array::value($op, $value, $value);
    }

    $groupIds = NULL;
    $names = array();
    $isSmart = FALSE;
    $isNotOp = ($op == 'NOT IN' || $op == '!=');

    $statii = array();
    $gcsValues = $this->getWhereValues('group_contact_status', $grouping);
    if ($gcsValues &&
      is_array($gcsValues[2])
    ) {
      foreach ($gcsValues[2] as $k => $v) {
        if ($v) {
          $statii[] = "'" . CRM_Utils_Type::escape($k, 'String') . "'";
        }
      }
    }
    else {
      $statii[] = '"Added"';
    }

    $skipGroup = FALSE;
    if (!is_array($value) &&
      count($statii) == 1 &&
      $statii[0] == '"Added"' &&
      !$isNotOp
    ) {
      if (!empty($value) && CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $value, 'saved_search_id')) {
        $isSmart = TRUE;
      }
    }

    $ssClause = $this->addGroupContactCache($value, NULL, "contact_a", $op);
    $isSmart = (!$ssClause) ? FALSE : $isSmart;
    $groupClause = NULL;

    if (!$isSmart) {
      $groupIds = implode(',', (array) $value);
      $gcTable = "`civicrm_group_contact-{$groupIds}`";
      $joinClause = array("contact_a.id = {$gcTable}.contact_id");
      if ($statii) {
        $joinClause[] = "{$gcTable}.status IN (" . implode(', ', $statii) . ")";
      }
      $this->_tables[$gcTable] = $this->_whereTables[$gcTable] = " LEFT JOIN civicrm_group_contact {$gcTable} ON (" . implode(' AND ', $joinClause) . ")";
      if (strpos($op, 'IN') !== FALSE) {
        $groupClause = "{$gcTable}.group_id $op ( $groupIds )";
      }
      elseif ($op == '!=') {
        $groupClause = "{$gcTable}.contact_id NOT IN (SELECT contact_id FROM civicrm_group_contact cgc WHERE cgc.group_id = $groupIds)";
      }
      else {
        $groupClause = "{$gcTable}.group_id $op $groupIds";
      }
    }

    if ($ssClause) {
      $and = ($op == 'IS NULL') ? 'AND' : 'OR';
      if ($groupClause) {
        $groupClause = "( ( $groupClause ) $and ( $ssClause ) )";
      }
      else {
        $groupClause = $ssClause;
      }
    }

    list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contact_DAO_Group', 'id', $value, $op);
    $this->_qill[$grouping][] = ts("Group(s) %1 %2", array(1 => $qillop, 2 => $qillVal));
    if (strpos($op, 'NULL') === FALSE) {
      $this->_qill[$grouping][] = ts("Group Status %1", array(1 => implode(' ' . ts('or') . ' ', $statii)));
    }
    if ($groupClause) {
      $this->_where[$grouping][] = $groupClause;
    }
  }

  /**
   * Function translates selection of group type into a list of groups.
   * @param $value
   *
   * @return array
   */
  public function getGroupsFromTypeCriteria($value) {
    $groupIds = array();
    foreach ((array) $value as $groupTypeValue) {
      $groupList = CRM_Core_PseudoConstant::group($groupTypeValue);
      $groupIds = ($groupIds + $groupList);
    }
    return $groupIds;
  }

  /**
   * @param array $groups
   * @param string $tableAlias
   * @param string $joinTable
   * @param string $op
   *
   * @return null|string
   */
  public function addGroupContactCache($groups, $tableAlias = NULL, $joinTable = "contact_a", $op) {
    $isNullOp = (strpos($op, 'NULL') !== FALSE);
    $groupsIds = $groups;
    if (!$isNullOp && !$groups) {
      return NULL;
    }
    elseif (strpos($op, 'IN') !== FALSE) {
      $groups = array($op => $groups);
    }
    elseif (is_array($groups) && count($groups)) {
      $groups = array('IN' => $groups);
    }

    // Find all the groups that are part of a saved search.
    $smartGroupClause = self::buildClause("id", $op, $groups, 'Int');
    $sql = "
SELECT id, cache_date, saved_search_id, children
FROM   civicrm_group
WHERE  $smartGroupClause
  AND  ( saved_search_id != 0
   OR    saved_search_id IS NOT NULL
   OR    children IS NOT NULL )
";

    $group = CRM_Core_DAO::executeQuery($sql);

    while ($group->fetch()) {
      $this->_useDistinct = TRUE;
      if (!$this->_smartGroupCache || $group->cache_date == NULL) {
        CRM_Contact_BAO_GroupContactCache::load($group);
      }
    }

    if (!$tableAlias) {
      $tableAlias = "`civicrm_group_contact_cache_";
      $tableAlias .= ($isNullOp) ? "a`" : implode(',', (array) $groupsIds) . "`";
    }

    $this->_tables[$tableAlias] = $this->_whereTables[$tableAlias] = " LEFT JOIN civicrm_group_contact_cache {$tableAlias} ON {$joinTable}.id = {$tableAlias}.contact_id ";
    return self::buildClause("{$tableAlias}.group_id", $op, $groups, 'Int');
  }

  /**
   * Where / qill clause for cms users
   *
   * @param $values
   */
  public function ufUser(&$values) {
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
   * All tag search specific.
   *
   * @param array $values
   */
  public function tagSearch(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $op = "LIKE";
    $value = "%{$value}%";

    $useAllTagTypes = $this->getWhereValues('all_tag_types', $grouping);
    $tagTypesText = $this->getWhereValues('tag_types_text', $grouping);

    $etTable = "`civicrm_entity_tag-" . $value . "`";
    $tTable = "`civicrm_tag-" . $value . "`";

    if ($useAllTagTypes[2]) {
      $this->_tables[$etTable] = $this->_whereTables[$etTable]
        = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id)
            LEFT JOIN civicrm_tag {$tTable} ON ( {$etTable}.tag_id = {$tTable}.id  )";

      // search tag in cases
      $etCaseTable = "`civicrm_entity_case_tag-" . $value . "`";
      $tCaseTable = "`civicrm_case_tag-" . $value . "`";
      $this->_tables[$etCaseTable] = $this->_whereTables[$etCaseTable]
        = " LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
            LEFT JOIN civicrm_case
            ON (civicrm_case_contact.case_id = civicrm_case.id
                AND civicrm_case.is_deleted = 0 )
            LEFT JOIN civicrm_entity_tag {$etCaseTable} ON ( {$etCaseTable}.entity_table = 'civicrm_case' AND {$etCaseTable}.entity_id = civicrm_case.id )
            LEFT JOIN civicrm_tag {$tCaseTable} ON ( {$etCaseTable}.tag_id = {$tCaseTable}.id  )";
      // search tag in activities
      $etActTable = "`civicrm_entity_act_tag-" . $value . "`";
      $tActTable = "`civicrm_act_tag-" . $value . "`";
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

      $this->_tables[$etActTable] = $this->_whereTables[$etActTable]
        = " LEFT JOIN civicrm_activity_contact
            ON ( civicrm_activity_contact.contact_id = contact_a.id AND civicrm_activity_contact.record_type_id = {$targetID} )
            LEFT JOIN civicrm_activity
            ON ( civicrm_activity.id = civicrm_activity_contact.activity_id
            AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )
            LEFT JOIN civicrm_entity_tag as {$etActTable} ON ( {$etActTable}.entity_table = 'civicrm_activity' AND {$etActTable}.entity_id = civicrm_activity.id )
            LEFT JOIN civicrm_tag {$tActTable} ON ( {$etActTable}.tag_id = {$tActTable}.id  )";

      $this->_where[$grouping][] = "({$tTable}.name $op '" . $value . "' OR {$tCaseTable}.name $op '" . $value . "' OR {$tActTable}.name $op '" . $value . "')";
      $this->_qill[$grouping][] = ts('Tag %1 %2', array(1 => $tagTypesText[2], 2 => $op)) . ' ' . $value;
    }
    else {
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
   * Where / qill clause for tag
   *
   * @param array $values
   */
  public function tag(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    list($qillop, $qillVal) = self::buildQillForFieldValue('CRM_Core_DAO_EntityTag', "tag_id", $value, $op, array('onlyActive' => FALSE));
    // API/Search Builder format array(operator => array(values))
    if (is_array($value)) {
      if (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }
      if (count($value) > 1) {
        $this->_useDistinct = TRUE;
      }
      $value = implode(',', (array) $value);
    }

    $useAllTagTypes = $this->getWhereValues('all_tag_types', $grouping);
    $tagTypesText = $this->getWhereValues('tag_types_text', $grouping);

    $etTable = "`civicrm_entity_tag-" . $value . "`";

    if ($useAllTagTypes[2]) {
      $this->_tables[$etTable] = $this->_whereTables[$etTable]
        = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND {$etTable}.entity_table = 'civicrm_contact') ";

      // search tag in cases
      $etCaseTable = "`civicrm_entity_case_tag-" . $value . "`";
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

      $this->_tables[$etCaseTable] = $this->_whereTables[$etCaseTable]
        = " LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
            LEFT JOIN civicrm_case
            ON (civicrm_case_contact.case_id = civicrm_case.id
                AND civicrm_case.is_deleted = 0 )
            LEFT JOIN civicrm_entity_tag {$etCaseTable} ON ( {$etCaseTable}.entity_table = 'civicrm_case' AND {$etCaseTable}.entity_id = civicrm_case.id ) ";
      // search tag in activities
      $etActTable = "`civicrm_entity_act_tag-" . $value . "`";
      $this->_tables[$etActTable] = $this->_whereTables[$etActTable]
        = " LEFT JOIN civicrm_activity_contact
            ON ( civicrm_activity_contact.contact_id = contact_a.id AND civicrm_activity_contact.record_type_id = {$targetID} )
            LEFT JOIN civicrm_activity
            ON ( civicrm_activity.id = civicrm_activity_contact.activity_id
            AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )
            LEFT JOIN civicrm_entity_tag as {$etActTable} ON ( {$etActTable}.entity_table = 'civicrm_activity' AND {$etActTable}.entity_id = civicrm_activity.id ) ";

      // CRM-10338
      if (in_array($op, array('IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'))) {
        $this->_where[$grouping][] = "({$etTable}.tag_id $op OR {$etCaseTable}.tag_id $op OR {$etActTable}.tag_id $op)";
      }
      else {
        $this->_where[$grouping][] = "({$etTable}.tag_id $op (" . $value . ") OR {$etCaseTable}.tag_id $op (" . $value . ") OR {$etActTable}.tag_id $op (" . $value . "))";
      }
    }
    else {
      $this->_tables[$etTable] = $this->_whereTables[$etTable]
        = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND {$etTable}.entity_table = 'civicrm_contact') ";

      // CRM-10338
      if (in_array($op, array('IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'))) {
        // this converts IS (NOT)? EMPTY to IS (NOT)? NULL
        $op = str_replace('EMPTY', 'NULL', $op);
        $this->_where[$grouping][] = "{$etTable}.tag_id $op";
      }
      // CRM-16941: for tag tried with != operator we don't show contact who don't have given $value AND also in other tag
      elseif ($op == '!=') {
        $this->_where[$grouping][] = "{$etTable}.entity_id NOT IN (SELECT entity_id FROM civicrm_entity_tag cet WHERE cet.entity_table = 'civicrm_contact' AND " . self::buildClause("cet.tag_id", '=', $value, 'Int') . ")";
      }
      elseif ($op == '=' || strstr($op, 'IN')) {
        $op = ($op == '=') ? 'IN' : $op;
        $this->_where[$grouping][] = "{$etTable}.tag_id $op ( $value )";
      }
    }
    $this->_qill[$grouping][] = ts('Tagged %1 %2', array(1 => $qillop, 2 => $qillVal));
  }

  /**
   * Where/qill clause for notes
   *
   * @param array $values
   */
  public function notes(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $noteOptionValues = $this->getWhereValues('note_option', $grouping);
    $noteOption = CRM_Utils_Array::value('2', $noteOptionValues, '6');
    $noteOption = ($name == 'note_body') ? 2 : (($name == 'note_subject') ? 3 : $noteOption);

    $this->_useDistinct = TRUE;

    $this->_tables['civicrm_note'] = $this->_whereTables['civicrm_note']
      = " LEFT JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contact' AND contact_a.id = civicrm_note.entity_id ) ";

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $n = trim($value);
    $value = $strtolower(CRM_Core_DAO::escapeString($n));
    if ($wildcard) {
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
    if ($noteOption % 2 == 0) {
      $clauses[] = self::buildClause('civicrm_note.note', $op, $value, 'String');
      $label = ts('Note: Body Only');
    }
    if ($noteOption % 3 == 0) {
      $clauses[] = self::buildClause('civicrm_note.subject', $op, $value, 'String');
      $label = $label ? ts('Note: Body and Subject') : ts('Note: Subject Only');
    }
    $this->_where[$grouping][] = "( " . implode(' OR ', $clauses) . " )";
    list($qillOp, $qillVal) = self::buildQillForFieldValue(NULL, $name, $n, $op);
    $this->_qill[$grouping][] = ts("%1 %2 %3", array(1 => $label, 2 => $qillOp, 3 => $qillVal));
  }

  /**
   * @param string $name
   * @param $op
   * @param $grouping
   *
   * @return bool
   */
  public function nameNullOrEmptyOp($name, $op, $grouping) {
    switch ($op) {
      case 'IS NULL':
      case 'IS NOT NULL':
        $this->_where[$grouping][] = "contact_a.$name $op";
        $this->_qill[$grouping][] = ts('Name') . ' ' . $op;
        return TRUE;

      case 'IS EMPTY':
        $this->_where[$grouping][] = "(contact_a.$name IS NULL OR contact_a.$name = '')";
        $this->_qill[$grouping][] = ts('Name') . ' ' . $op;
        return TRUE;

      case 'IS NOT EMPTY':
        $this->_where[$grouping][] = "(contact_a.$name IS NOT NULL AND contact_a.$name <> '')";
        $this->_qill[$grouping][] = ts('Name') . ' ' . $op;
        return TRUE;

      default:
        return FALSE;
    }
  }

  /**
   * Where / qill clause for sort_name
   *
   * @param array $values
   */
  public function sortName(&$values) {
    list($fieldName, $op, $value, $grouping, $wildcard) = $values;

    // handle IS NULL / IS NOT NULL / IS EMPTY / IS NOT EMPTY
    if ($this->nameNullOrEmptyOp($fieldName, $op, $grouping)) {
      return;
    }

    $input = $value = trim($value);

    if (!strlen($value)) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    $sub = array();

    //By default, $sub elements should be joined together with OR statements (don't change this variable).
    $subGlue = ' OR ';

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

    $firstChar = substr($value, 0, 1);
    $lastChar = substr($value, -1, 1);
    $quotes = array("'", '"');
    // If string is quoted, strip quotes and otherwise don't alter it
    if ((strlen($value) > 2) && in_array($firstChar, $quotes) && in_array($lastChar, $quotes)) {
      $value = trim($value, implode('', $quotes));
    }
    // Replace spaces with wildcards for a LIKE operation
    // UNLESS string contains a comma (this exception is a tiny bit questionable)
    elseif ($op == 'LIKE' && strpos($value, ',') === FALSE) {
      $value = str_replace(' ', '%', $value);
    }
    $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));
    if (strlen($value)) {
      $fieldsub = array();
      $value = "'" . self::getWildCardedValue($wildcard, $op, $value) . "'";
      if ($fieldName == 'sort_name') {
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
    }

    $sub = ' ( ' . implode($subGlue, $sub) . ' ) ';

    $this->_where[$grouping][] = $sub;
    if ($config->includeEmailInName) {
      $this->_tables['civicrm_email'] = $this->_whereTables['civicrm_email'] = 1;
      $this->_qill[$grouping][] = ts('Name or Email') . " $op - '$input'";
    }
    else {
      $this->_qill[$grouping][] = ts('Name') . " $op - '$input'";
    }
  }

  /**
   * Where/qill clause for greeting fields.
   *
   * @param array $values
   */
  public function greetings(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $name .= '_display';

    list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $name, $value, $op);
    $this->_qill[$grouping][] = ts('Greeting %1 %2', array(1 => $qillop, 2 => $qillVal));
    $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $value, 'String');
  }

  /**
   * Where / qill clause for email
   *
   * @param array $values
   */
  protected function email(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $n = strtolower(trim($value));
    if ($n) {
      if (substr($n, 0, 1) == '"' &&
        substr($n, -1, 1) == '"'
      ) {
        $n = substr($n, 1, -1);
        $value = CRM_Core_DAO::escapeString($n);
        $op = '=';
      }
      else {
        $value = self::getWildCardedValue($wildcard, $op, $n);
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
   * Where / qill clause for phone number
   *
   * @param array $values
   */
  public function phone_numeric(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    // Strip non-numeric characters; allow wildcards
    $number = preg_replace('/[^\d%]/', '', $value);
    if ($number) {
      if (strpos($number, '%') === FALSE) {
        $number = "%$number%";
      }

      $this->_qill[$grouping][] = ts('Phone number contains') . " $number";
      $this->_where[$grouping][] = self::buildClause('civicrm_phone.phone_numeric', 'LIKE', "$number", 'String');
      $this->_tables['civicrm_phone'] = $this->_whereTables['civicrm_phone'] = 1;
    }
  }

  /**
   * Where / qill clause for phone type/location
   *
   * @param array $values
   */
  public function phone_option_group($values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $option = ($name == 'phone_phone_type_id' ? 'phone_type_id' : 'location_type_id');
    $options = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', $option);
    $optionName = $options[$value];
    $this->_qill[$grouping][] = ts('Phone') . ' ' . ($name == 'phone_phone_type_id' ? ts('type') : ('location')) . " $op $optionName";
    $this->_where[$grouping][] = self::buildClause('civicrm_phone.' . substr($name, 6), $op, $value, 'Integer');
    $this->_tables['civicrm_phone'] = $this->_whereTables['civicrm_phone'] = 1;
  }

  /**
   * Where / qill clause for street_address.
   *
   * @param array $values
   */
  public function street_address(&$values) {
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
   * Where / qill clause for street_unit.
   *
   * @param array $values
   */
  public function street_number(&$values) {
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
   * Where / qill clause for sorting by character.
   *
   * @param array $values
   */
  public function sortByCharacter(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $name = trim($value);
    $cond = " contact_a.sort_name LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($name)) . "%'";
    $this->_where[$grouping][] = $cond;
    $this->_qill[$grouping][] = ts('Showing only Contacts starting with: \'%1\'', array(1 => $name));
  }

  /**
   * Where / qill clause for including contact ids.
   */
  public function includeContactIDs() {
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
   * Where / qill clause for postal code.
   *
   * @param array $values
   */
  public function postalCode(&$values) {
    // skip if the fields dont have anything to do with postal_code
    if (empty($this->_fields['postal_code'])) {
      return;
    }

    list($name, $op, $value, $grouping, $wildcard) = $values;

    // Handle numeric postal code range searches properly by casting the column as numeric
    if (is_numeric($value)) {
      $field = "IF (civicrm_address.postal_code REGEXP '^[0-9]+$', CAST(civicrm_address.postal_code AS UNSIGNED), 0)";
      $val = CRM_Utils_Type::escape($value, 'Integer');
    }
    else {
      $field = 'civicrm_address.postal_code';
      // Per CRM-17060 we might be looking at an 'IN' syntax so don't case arrays to string.
      if (!is_array($value)) {
        $val = CRM_Utils_Type::escape($value, 'String');
      }
      else {
        // Do we need to escape values here? I would expect buildClause does.
        $val = $value;
      }
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
   * Where / qill clause for location type.
   *
   * @param array $values
   * @param null $status
   *
   * @return string
   */
  public function locationType(&$values, $status = NULL) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (is_array($value)) {
      $this->_where[$grouping][] = 'civicrm_address.location_type_id IN (' . implode(',', $value) . ')';
      $this->_tables['civicrm_address'] = 1;
      $this->_whereTables['civicrm_address'] = 1;

      $locationType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $names = array();
      foreach ($value as $id) {
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

  /**
   * @param $values
   * @param bool $fromStateProvince
   *
   * @return array|NULL
   */
  public function country(&$values, $fromStateProvince = TRUE) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!$fromStateProvince) {
      $stateValues = $this->getWhereValues('state_province', $grouping);
      if (!empty($stateValues)) {
        // return back to caller if there are state province values
        // since that handles this case
        return NULL;
      }
    }

    $countryClause = $countryQill = NULL;
    if ($values && !empty($value)) {
      $this->_tables['civicrm_address'] = 1;
      $this->_whereTables['civicrm_address'] = 1;

      $countryClause = self::buildClause('civicrm_address.country_id', $op, $value, 'Positive');
      list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, 'country_id', $value, $op);
      $countryQill = ts("%1 %2 %3", array(1 => 'Country', 2 => $qillop, 3 => $qillVal));

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
   * Where / qill clause for county (if present).
   *
   * @param array $values
   * @param null $status
   *
   * @return string
   */
  public function county(&$values, $status = NULL) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!is_array($value)) {
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
    if ($op == '=') {
      $op = 'IN';
    }
    elseif ($op == '!=') {
      $op = 'NOT IN';
    }
    else {
      // this converts IS (NOT)? EMPTY to IS (NOT)? NULL
      $op = str_replace('EMPTY', 'NULL', $op);
    }

    if (in_array($op, array('IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'))) {
      $clause = "civicrm_address.county_id $op";
    }
    elseif ($inputFormat == 'id') {
      $clause = 'civicrm_address.county_id IN (' . implode(',', $value) . ')';

      $county = CRM_Core_PseudoConstant::county();
      foreach ($value as $id) {
        $names[] = CRM_Utils_Array::value($id, $county);
      }
    }
    else {
      $inputClause = array();
      $county = CRM_Core_PseudoConstant::county();
      foreach ($value as $name) {
        $name = trim($name);
        $inputClause[] = CRM_Utils_Array::key($name, $county);
      }
      $clause = 'civicrm_address.county_id IN (' . implode(',', $inputClause) . ')';
      $names = $value;
    }
    $this->_tables['civicrm_address'] = 1;
    $this->_whereTables['civicrm_address'] = 1;

    $this->_where[$grouping][] = $clause;
    if (!$status) {
      $this->_qill[$grouping][] = ts('County') . ' - ' . implode(' ' . ts('or') . ' ', $names);
    }
    else {
      return implode(' ' . ts('or') . ' ', $names);
    }
  }

  /**
   * Where / qill clause for state/province AND country (if present).
   *
   * @param array $values
   * @param null $status
   *
   * @return string
   */
  public function stateProvince(&$values, $status = NULL) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $stateClause = self::buildClause('civicrm_address.state_province_id', $op, $value, 'Positive');
    $this->_tables['civicrm_address'] = 1;
    $this->_whereTables['civicrm_address'] = 1;

    $countryValues = $this->getWhereValues('country', $grouping);
    list($countryClause, $countryQill) = $this->country($countryValues, TRUE);
    if ($countryClause) {
      $clause = "( $stateClause AND $countryClause )";
    }
    else {
      $clause = $stateClause;
    }

    $this->_where[$grouping][] = $clause;
    list($qillop, $qillVal) = self::buildQillForFieldValue('CRM_Core_DAO_Address', "state_province_id", $value, $op);
    if (!$status) {
      $this->_qill[$grouping][] = ts("State/Province %1 %2 %3", array(1 => $qillop, 2 => $qillVal, 3 => $countryQill));
    }
    else {
      return implode(' ' . ts('or') . ' ', $qillVal) . $countryQill;
    }
  }

  /**
   * Where / qill clause for change log.
   *
   * @param array $values
   */
  public function changeLog(&$values) {
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
    $this->_qill[$grouping][] = ts('Modified By') . " $name";
  }

  /**
   * @param $values
   */
  public function modifiedDates($values) {
    $this->_useDistinct = TRUE;

    // CRM-11281, default to added date if not set
    $fieldTitle = ts('Added Date');
    $fieldName = 'created_date';
    foreach (array_keys($this->_params) as $id) {
      if ($this->_params[$id][0] == 'log_date') {
        if ($this->_params[$id][2] == 2) {
          $fieldTitle = ts('Modified Date');
          $fieldName = 'modified_date';
        }
      }
    }

    $this->dateQueryBuilder($values, 'contact_a', 'log_date', $fieldName, $fieldTitle);

    self::$_openedPanes[ts('Change Log')] = TRUE;
  }

  /**
   * @param $values
   */
  public function demographics(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (($name == 'age_low') || ($name == 'age_high')) {
      $this->ageRangeQueryBuilder($values,
        'contact_a', 'age', 'birth_date', ts('Age')
      );
    }
    elseif (($name == 'birth_date_low') || ($name == 'birth_date_high')) {

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

  /**
   * @param $values
   */
  public function privacy(&$values) {
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
    $op = CRM_Utils_Array::value($op, CRM_Core_SelectValues::getSearchBuilderOperators(), $op);
    $title = $field ? $field['title'] : $name;
    $this->_qill[$grouping][] = "$title $op $value";
  }

  /**
   * @param $values
   */
  public function privacyOptions($values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (empty($value) || !is_array($value)) {
      return;
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
    $compareOP = '!';
    if ($toggleValues &&
      $toggleValues[2] == 2
    ) {
      $compareOP = '';
    }

    $clauses = array();
    $qill = array();
    foreach ($value as $dontCare => $pOption) {
      $clauses[] = " ( contact_a.{$pOption} = 1 ) ";
      $field = CRM_Utils_Array::value($pOption, $this->_fields);
      $title = $field ? $field['title'] : $pOption;
      $qill[] = " $title = 1 ";
    }

    $this->_where[$grouping][] = $compareOP . '( ' . implode($operator, $clauses) . ' )';
    $this->_qill[$grouping][] = $compareOP . '( ' . implode($operator, $qill) . ' )';
  }

  /**
   * @param $values
   */
  public function preferredCommunication(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (!is_array($value)) {
      $value = str_replace(array('(', ')'), '', explode(",", $value));
    }
    elseif (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($value);
      $value = $value[$op];
    }
    list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contact_DAO_Contact', $name, $value, $op);

    if (self::caseImportant($op)) {
      $value = implode("[[:cntrl:]]|[[:cntrl:]]", (array) $value);
      $op = (strstr($op, '!') || strstr($op, 'NOT')) ? 'NOT RLIKE' : 'RLIKE';
      $value = "[[:cntrl:]]" . $value . "[[:cntrl:]]";
    }

    $this->_where[$grouping][] = self::buildClause("contact_a.preferred_communication_method", $op, $value);
    $this->_qill[$grouping][] = ts('Preferred Communication Method %1 %2', array(1 => $qillop, 2 => $qillVal));
  }

  /**
   * Where / qill clause for relationship.
   *
   * @param array $values
   */
  public function relationship(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    if ($this->_relationshipValuesAdded) {
      return;
    }
    // also get values array for relation_target_name
    // for relationship search we always do wildcard
    $relationType = $this->getWhereValues('relation_type_id', $grouping);
    $targetName = $this->getWhereValues('relation_target_name', $grouping);
    $relStatus = $this->getWhereValues('relation_status', $grouping);
    $relPermission = $this->getWhereValues('relation_permission', $grouping);
    $targetGroup = $this->getWhereValues('relation_target_group', $grouping);

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

    $rTypeValues = array();
    if (!empty($relationType)) {
      $rel = explode('_', $relationType[2]);
      self::$_relType = $rel[1];
      $params = array('id' => $rel[0]);
      $rType = CRM_Contact_BAO_RelationshipType::retrieve($params, $rTypeValues);
    }
    if (!empty($rTypeValues) && $rTypeValues['name_a_b'] == $rTypeValues['name_b_a']) {
      // if we don't know which end of the relationship we are dealing with we'll create a temp table
      //@todo unless we are dealing with a target group
      self::$_relType = 'reciprocal';
    }
    // if we are creating a temp table we build our own where for the relationship table
    $relationshipTempTable = NULL;
    if (self::$_relType == 'reciprocal' && empty($targetGroup)) {
      $where = array();
      self::$_relationshipTempTable = $relationshipTempTable = CRM_Core_DAO::createTempTableName('civicrm_rel');
      if ($nameClause) {
        $where[$grouping][] = " sort_name $nameClause ";
      }
    }
    else {
      $where = &$this->_where;
      if ($nameClause) {
        $where[$grouping][] = "( contact_b.sort_name $nameClause AND contact_b.id != contact_a.id )";
      }
    }

    $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, NULL, TRUE);

    if ($nameClause || !$targetGroup) {
      if (!empty($relationType)) {
        $this->_qill[$grouping][] = $allRelationshipType[$relationType[2]] . " $name";
      }
      else {
        $this->_qill[$grouping][] = $name;
      }
    }

    //check to see if the target contact is in specified group
    if ($targetGroup) {
      //add contacts from static groups
      $this->_tables['civicrm_relationship_group_contact'] = $this->_whereTables['civicrm_relationship_group_contact']
        = " LEFT JOIN civicrm_group_contact civicrm_relationship_group_contact ON civicrm_relationship_group_contact.contact_id = contact_b.id AND civicrm_relationship_group_contact.status = 'Added'";
      $groupWhere[] = "( civicrm_relationship_group_contact.group_id IN  (" .
        implode(",", $targetGroup[2]) . ") ) ";

      //add contacts from saved searches
      $ssWhere = $this->addGroupContactCache($targetGroup[2], "civicrm_relationship_group_contact_cache", "contact_b", $op);

      //set the group where clause
      if ($ssWhere) {
        $groupWhere[] = "( " . $ssWhere . " )";
      }
      $this->_where[$grouping][] = "( " . implode(" OR ", $groupWhere) . " )";

      //Get the names of the target groups for the qill
      $groupNames = CRM_Core_PseudoConstant::group();
      $qillNames = array();
      foreach ($targetGroup[2] as $groupId) {
        if (array_key_exists($groupId, $groupNames)) {
          $qillNames[] = $groupNames[$groupId];
        }
      }
      if (!empty($relationType)) {
        $this->_qill[$grouping][] = $allRelationshipType[$relationType[2]] . " ( " . implode(", ", $qillNames) . " )";
      }
      else {
        $this->_qill[$grouping][] = implode(", ", $qillNames);
      }
    }

    // Note we do not currently set mySql to handle timezones, so doing this the old-fashioned way
    $today = date('Ymd');
    //check for active, inactive and all relation status
    if ($relStatus[2] == 0) {
      $where[$grouping][] = "(
civicrm_relationship.is_active = 1 AND
( civicrm_relationship.end_date IS NULL OR civicrm_relationship.end_date >= {$today} ) AND
( civicrm_relationship.start_date IS NULL OR civicrm_relationship.start_date <= {$today} )
)";
      $this->_qill[$grouping][] = ts('Relationship - Active and Current');
    }
    elseif ($relStatus[2] == 1) {
      $where[$grouping][] = "(
civicrm_relationship.is_active = 0 OR
civicrm_relationship.end_date < {$today} OR
civicrm_relationship.start_date > {$today}
)";
      $this->_qill[$grouping][] = ts('Relationship - Inactive or not Current');
    }

    $onlyDeleted = 0;
    if (in_array(array('deleted_contacts', '=', '1', '0', '0'), $this->_params)) {
      $onlyDeleted = 1;
    }
    $where[$grouping][] = "(contact_b.is_deleted = {$onlyDeleted})";

    //check for permissioned, non-permissioned and all permissioned relations
    if ($relPermission[2] == 1) {
      $where[$grouping][] = "(
civicrm_relationship.is_permission_a_b = 1
)";
      $this->_qill[$grouping][] = ts('Relationship - Permissioned');
    }
    elseif ($relPermission[2] == 2) {
      //non-allowed permission relationship.
      $where[$grouping][] = "(
civicrm_relationship.is_permission_a_b = 0
)";
      $this->_qill[$grouping][] = ts('Relationship - Non-permissioned');
    }

    $this->addRelationshipDateClauses($grouping, $where);
    if (!empty($relationType) && !empty($rType) && isset($rType->id)) {
      $where[$grouping][] = 'civicrm_relationship.relationship_type_id = ' . $rType->id;
    }
    $this->_tables['civicrm_relationship'] = $this->_whereTables['civicrm_relationship'] = 1;
    $this->_useDistinct = TRUE;
    $this->_relationshipValuesAdded = TRUE;
    // it could be a or b, using an OR creates an unindexed join - better to create a temp table &
    // join on that,
    // @todo creating a temp table could be expanded to group filter
    // as even creating a temp table of all relationships is much much more efficient than
    // an OR in the join
    if ($relationshipTempTable) {
      $whereClause = '';
      if (!empty($where[$grouping])) {
        $whereClause = ' WHERE ' . implode(' AND ', $where[$grouping]);
        $whereClause = str_replace('contact_b', 'c', $whereClause);
      }
      $sql = "
        CREATE TEMPORARY TABLE {$relationshipTempTable}
          (SELECT contact_id_b as contact_id, civicrm_relationship.id
            FROM civicrm_relationship
            INNER JOIN  civicrm_contact c ON civicrm_relationship.contact_id_a = c.id
            $whereClause )
          UNION
            (SELECT contact_id_a as contact_id, civicrm_relationship.id
            FROM civicrm_relationship
            INNER JOIN civicrm_contact c ON civicrm_relationship.contact_id_b = c.id
            $whereClause )
      ";
      CRM_Core_DAO::executeQuery($sql);
    }

  }

  /**
   * Add start & end date criteria in
   * @param string $grouping
   * @param array $where
   *   = array to add where clauses to, in case you are generating a temp table.
   * not the main query.
   */
  public function addRelationshipDateClauses($grouping, &$where) {
    $dateValues = array();
    $dateTypes = array(
      'start_date',
      'end_date',
    );

    foreach ($dateTypes as $dateField) {
      $dateValueLow = $this->getWhereValues('relation_' . $dateField . '_low', $grouping);
      $dateValueHigh = $this->getWhereValues('relation_' . $dateField . '_high', $grouping);
      if (!empty($dateValueLow)) {
        $date = date('Ymd', strtotime($dateValueLow[2]));
        $where[$grouping][] = "civicrm_relationship.$dateField >= $date";
        $this->_qill[$grouping][] = ($dateField == 'end_date' ? ts('Relationship Ended on or After') : ts('Relationship Recorded Start Date On or Before')) . " " . CRM_Utils_Date::customFormat($date);
      }
      if (!empty($dateValueHigh)) {
        $date = date('Ymd', strtotime($dateValueHigh[2]));
        $where[$grouping][] = "civicrm_relationship.$dateField <= $date";
        $this->_qill[$grouping][] = ($dateField == 'end_date' ? ts('Relationship Ended on or Before') : ts('Relationship Recorded Start Date On or After')) . " " . CRM_Utils_Date::customFormat($date);
      }
    }
  }

  /**
   * Default set of return properties.
   *
   * @param int $mode
   *
   * @return array
   *   derault return properties
   */
  public static function &defaultReturnProperties($mode = 1) {
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
          'prefix_id' => 1,
          'suffix_id' => 1,
          'formal_title' => 1,
          'communication_style_id' => 1,
          'birth_date' => 1,
          'gender_id' => 1,
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
          'preferred_communication_method' => 1,
          'preferred_language' => 1,
        );
      }
    }
    return self::$_defaultReturnProperties[$mode];
  }

  /**
   * Get primary condition for a sql clause.
   *
   * @param int $value
   *
   * @return string|NULL
   */
  public static function getPrimaryCondition($value) {
    if (is_numeric($value)) {
      $value = (int ) $value;
      return ($value == 1) ? 'is_primary = 1' : 'is_primary = 0';
    }
    return NULL;
  }

  /**
   * Wrapper for a simple search query.
   *
   * @param array $params
   * @param array $returnProperties
   * @param bool $count
   *
   * @return string
   */
  public static function getQuery($params = NULL, $returnProperties = NULL, $count = FALSE) {
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
   * @param array $params
   * @param array $returnProperties
   * @param null $fields
   * @param string $sort
   * @param int $offset
   * @param int $row_count
   * @param bool $smartGroupCache
   *   ?? update smart group cache?.
   * @param bool $count
   *   Return count obnly.
   * @param bool $skipPermissions
   *   Should permissions be ignored or should the logged in user's permissions be applied.
   * @param int $mode
   *   This basically correlates to the component.
   *
   * @return array
   */
  public static function apiQuery(
    $params = NULL,
    $returnProperties = NULL,
    $fields = NULL,
    $sort = NULL,
    $offset = 0,
    $row_count = 25,
    $smartGroupCache = TRUE,
    $count = FALSE,
    $skipPermissions = TRUE,
    $mode = 1,
    $apiEntity = NULL
  ) {

    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, TRUE, FALSE, $mode,
      $skipPermissions,
      TRUE, $smartGroupCache,
      NULL, 'AND',
      $apiEntity
    );

    //this should add a check for view deleted if permissions are enabled
    if ($skipPermissions) {
      $query->_skipDeleteClause = TRUE;
    }
    $query->generatePermissionClause(FALSE, $count);

    // note : this modifies _fromClause and _simpleFromClause
    $query->includePseudoFieldsJoin($sort);

    list($select, $from, $where, $having) = $query->query($count);

    $options = $query->_options;
    if (!empty($query->_permissionWhereClause)) {
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
      $sort = CRM_Utils_Type::escape($sort, 'String');
      $sql .= " ORDER BY $sort ";
    }
    if ($row_count > 0 && $offset >= 0) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $row_count = CRM_Utils_Type::escape($row_count, 'Int');
      $sql .= " LIMIT $offset, $row_count ";
    }

    $dao = CRM_Core_DAO::executeQuery($sql);

    // @todo derive this from the component class rather than hard-code two options.
    $entityIDField = ($mode == CRM_Contact_BAO_Query::MODE_CONTRIBUTE) ? 'contribution_id' : 'contact_id';

    $values = array();
    while ($dao->fetch()) {
      if ($count) {
        $noRows = $dao->rowCount;
        $dao->free();
        return array($noRows, NULL);
      }
      $val = $query->store($dao);
      $convertedVals = $query->convertToPseudoNames($dao, TRUE);

      if (!empty($convertedVals)) {
        $val = array_replace_recursive($val, $convertedVals);
      }
      $values[$dao->$entityIDField] = $val;
    }
    $dao->free();
    return array($values, $options);
  }

  /**
   * Get the actual custom field name by stripping off the appended string.
   *
   * The string could be _relative, _from, or _to
   *
   * @todo use metadata rather than convention to do this.
   *
   * @param string $parameterName
   *   The name of the parameter submitted to the form.
   *   e.g
   *   custom_3_relative
   *   custom_3_from
   *
   * @return string
   */
  public static function getCustomFieldName($parameterName) {
    if (substr($parameterName, -5, 5) == '_from') {
      return substr($parameterName, 0, strpos($parameterName, '_from'));
    }
    if (substr($parameterName, -9, 9) == '_relative') {
      return substr($parameterName, 0, strpos($parameterName, '_relative'));
    }
    if (substr($parameterName, -3, 3) == '_to') {
      return substr($parameterName, 0, strpos($parameterName, '_to'));
    }
  }

  /**
   * Convert submitted values for relative custom fields to query object format.
   *
   * The query will support the sqlOperator format so convert to that format.
   *
   * @param array $formValues
   *   Submitted values.
   * @param array $params
   *   Converted parameters for the query object.
   * @param string $values
   *   Submitted value.
   * @param string $fieldName
   *   Submitted field name. (Matches form field not DB field.)
   */
  protected static function convertCustomRelativeFields(&$formValues, &$params, $values, $fieldName) {
    if (empty($values)) {
      // e.g we might have relative set & from & to empty. The form flow is a bit funky &
      // this function gets called again after they fields have been converted which can get ugly.
      return;
    }
    $customFieldName = self::getCustomFieldName($fieldName);

    if (substr($fieldName, -9, 9) == '_relative') {
      list($from, $to) = CRM_Utils_Date::getFromTo($values, NULL, NULL);
    }
    else {
      if ($fieldName == $customFieldName . '_to' && CRM_Utils_Array::value($customFieldName . '_from', $formValues)) {
        // Both to & from are set. We only need to acton one, choosing from.
        return;
      }

      $from = CRM_Utils_Array::value($customFieldName . '_from', $formValues, NULL);
      $to = CRM_Utils_Array::value($customFieldName . '_to', $formValues, NULL);

      if (self::isCustomDateField($customFieldName)) {
        list($from, $to) = CRM_Utils_Date::getFromTo(NULL, $from, $to);
      }
    }

    if ($from) {
      if ($to) {
        $relativeFunction = array('BETWEEN' => array($from, $to));
      }
      else {
        $relativeFunction = array('>=' => $from);
      }
    }
    else {
      $relativeFunction = array('<=' => $to);
    }
    $params[] = array(
      $customFieldName,
      '=',
      $relativeFunction,
      0,
      0,
    );
  }

  /**
   * Are we dealing with custom field of type date.
   *
   * @param $fieldName
   *
   * @return bool
   */
  public static function isCustomDateField($fieldName) {
    if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($fieldName)) == FALSE) {
      return FALSE;
    }
    if ('Date' == civicrm_api3('CustomField', 'getvalue', array('id' => $customFieldID, 'return' => 'data_type'))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Has this field already been reformatting to Query object syntax.
   *
   * The form layer passed formValues to this function in preProcess & postProcess. Reason unknown. This seems
   * to come with associated double queries & is possibly damaging performance.
   *
   * However, here we add a tested function to ensure convertFormValues identifies pre-processed fields & returns
   * them as they are.
   *
   * @param mixed $values
   *   Value in formValues for the field.
   *
   * @return bool;
   */
  public static function isAlreadyProcessedForQueryFormat($values) {
    if (!is_array($values)) {
      return FALSE;
    }
    if (($operator = CRM_Utils_Array::value(1, $values)) == FALSE) {
      return FALSE;
    }
    return in_array($operator, CRM_Core_DAO::acceptedSQLOperators());
  }

  /**
   * If the state and country are passed remove state.
   *
   * Country is implicit from the state, but including both results in
   * a poor query as there is no combined index on state AND country.
   *
   * CRM-18125
   *
   * @param array $formValues
   */
  public static function filterCountryFromValuesIfStateExists(&$formValues) {
    if (!empty($formValues['country']) && !empty($formValues['state_province'])) {
      // The use of array map sanitises the data by ensuring we are dealing with integers.
      $states = implode(', ', array_map('intval', $formValues['state_province']));
      $countryList = CRM_Core_DAO::singleValueQuery(
        "SELECT GROUP_CONCAT(country_id) FROM civicrm_state_province WHERE id IN ($states)"
      );
      if ($countryList == $formValues['country']) {
        unset($formValues['country']);
      }
    }
  }

  /**
   * Create and query the db for an contact search.
   *
   * @param int $offset
   *   The offset for the query.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string|CRM_Utils_Sort $sort
   *   The order by string.
   * @param bool $count
   *   Is this a count only query ?.
   * @param bool $includeContactIds
   *   Should we include contact ids?.
   * @param bool $sortByChar
   *   If true returns the distinct array of first characters for search results.
   * @param bool $groupContacts
   *   If true, return only the contact ids.
   * @param bool $returnQuery
   *   Should we return the query as a string.
   * @param string $additionalWhereClause
   *   If the caller wants to further restrict the search (used for components).
   * @param null $sortOrder
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   *
   * @param bool $skipOrderAndLimit
   *
   * @return CRM_Core_DAO
   */
  public function searchQuery(
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

    $onlyDeleted = in_array(array('deleted_contacts', '=', '1', '0', '0'), $this->_params);

    // if we’re explicitly looking for a certain contact’s contribs, events, etc.
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
      list($order, $additionalFromClause) = $this->prepareOrderBy($sort, $sortByChar, $sortOrder, $additionalFromClause);

      if ($rowCount > 0 && $offset >= 0) {
        $offset = CRM_Utils_Type::escape($offset, 'Int');
        $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');
        $limit = " LIMIT $offset, $rowCount ";
      }
    }

    // CRM-15231
    $this->_sort = $sort;

    //CRM-15967
    $this->includePseudoFieldsJoin($sort);

    list($select, $from, $where, $having) = $this->query($count, $sortByChar, $groupContacts, $onlyDeleted);

    if ($additionalWhereClause) {
      $where = $where . ' AND ' . $additionalWhereClause;
    }

    //additional from clause should be w/ proper joins.
    if ($additionalFromClause) {
      $from .= "\n" . $additionalFromClause;
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

    $dao = CRM_Core_DAO::executeQuery($query);
    if ($groupContacts) {
      $ids = array();
      while ($dao->fetch()) {
        $ids[] = $dao->id;
      }
      $dao->free();
      return implode(',', $ids);
    }

    return $dao;
  }

  /**
   * Fetch a list of contacts from the prev/next cache for displaying a search results page
   *
   * @param string $cacheKey
   * @param int $offset
   * @param int $rowCount
   * @param bool $includeContactIds
   * @return CRM_Core_DAO
   */
  public function getCachedContacts($cacheKey, $offset, $rowCount, $includeContactIds) {
    $this->_includeContactIds = $includeContactIds;
    $onlyDeleted = in_array(array('deleted_contacts', '=', '1', '0', '0'), $this->_params);
    list($select, $from, $where) = $this->query(FALSE, FALSE, FALSE, $onlyDeleted);
    $from = " FROM civicrm_prevnext_cache pnc INNER JOIN civicrm_contact contact_a ON contact_a.id = pnc.entity_id1 AND pnc.cacheKey = '$cacheKey' " . substr($from, 31);
    $order = " ORDER BY pnc.id";
    $groupBy = " GROUP BY contact_a.id";
    $limit = " LIMIT $offset, $rowCount";
    $query = "$select $from $where $groupBy $order $limit";

    return CRM_Core_DAO::executeQuery($query);
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
   * @param bool $onlyDeleted
   *   Only get deleted contacts.
   * @param bool $count
   *   Return Count only.
   */
  public function generatePermissionClause($onlyDeleted = FALSE, $count = FALSE) {
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
        //CRM-15231
        $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_primaryLocation, $this->_mode);
        $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_primaryLocation, $this->_mode);
        // note : this modifies _fromClause and _simpleFromClause
        $this->includePseudoFieldsJoin($this->_sort);
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

  /**
   * @param $val
   */
  public function setSkipPermission($val) {
    $this->_skipPermission = $val;
  }

  /**
   * @param null $context
   *
   * @return array
   */
  public function &summaryContribution($context = NULL) {
    list($innerselect, $from, $where, $having) = $this->query(TRUE);

    // hack $select
    $select = "
SELECT COUNT( conts.total_amount ) as total_count,
       SUM(   conts.total_amount ) as total_amount,
       AVG(   conts.total_amount ) as total_avg,
       conts.total_amount          as amount,
       conts.currency              as currency";
    if ($this->_permissionWhereClause) {
      $where .= " AND " . $this->_permissionWhereClause;
    }
    if ($context == 'search') {
      $where .= " AND contact_a.is_deleted = 0 ";
    }
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    if (!empty($financialTypes)) {
      $where .= " AND civicrm_contribution.financial_type_id IN (" . implode(',', array_keys($financialTypes)) . ") AND li.id IS NULL";
      $from .= " LEFT JOIN civicrm_line_item li
                      ON civicrm_contribution.id = li.contribution_id AND
                         li.entity_table = 'civicrm_contribution' AND li.financial_type_id NOT IN (" . implode(',', array_keys($financialTypes)) . ") ";
    }
    else {
      $where .= " AND civicrm_contribution.financial_type_id IN (0)";
    }

    // make sure contribution is completed - CRM-4989
    $completedWhere = $where . " AND civicrm_contribution.contribution_status_id = 1 ";

    $summary = array();
    $summary['total'] = array();
    $summary['total']['count'] = $summary['total']['amount'] = $summary['total']['avg'] = "n/a";
    $innerQuery = "SELECT civicrm_contribution.total_amount, COUNT(civicrm_contribution.total_amount) as civicrm_contribution_total_amount_count,
      civicrm_contribution.currency $from $completedWhere";
    $query = "$select FROM (
      $innerQuery GROUP BY civicrm_contribution.id
    ) as conts
    GROUP BY currency";

    $dao = CRM_Core_DAO::executeQuery($query);

    $summary['total']['count'] = 0;
    $summary['total']['amount'] = $summary['total']['avg'] = array();
    while ($dao->fetch()) {
      $summary['total']['count'] += $dao->total_count;
      $summary['total']['amount'][] = CRM_Utils_Money::format($dao->total_amount, $dao->currency);
      $summary['total']['avg'][] = CRM_Utils_Money::format($dao->total_avg, $dao->currency);
    }

    $orderBy = 'ORDER BY civicrm_contribution_total_amount_count DESC';
    $groupBy = 'GROUP BY currency, civicrm_contribution.total_amount';
    $modeSQL = "$select, conts.civicrm_contribution_total_amount_count as civicrm_contribution_total_amount_count FROM ($innerQuery
    $groupBy $orderBy) as conts
    GROUP BY currency";

    $summary['total']['mode'] = CRM_Contribute_BAO_Contribution::computeStats('mode', $modeSQL);

    $medianSQL = "{$from} {$completedWhere}";
    $summary['total']['median'] = CRM_Contribute_BAO_Contribution::computeStats('median', $medianSQL, 'civicrm_contribution');
    $summary['total']['currencyCount'] = count($summary['total']['median']);

    if (!empty($summary['total']['amount'])) {
      $summary['total']['amount'] = implode(',&nbsp;', $summary['total']['amount']);
      $summary['total']['avg'] = implode(',&nbsp;', $summary['total']['avg']);
      $summary['total']['mode'] = implode(',&nbsp;', $summary['total']['mode']);
      $summary['total']['median'] = implode(',&nbsp;', $summary['total']['median']);
    }
    else {
      $summary['total']['amount'] = $summary['total']['avg'] = $summary['total']['median'] = 0;
    }

    // soft credit summary
    if (CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled()) {
      $softCreditWhere = "{$completedWhere} AND civicrm_contribution_soft.id IS NOT NULL";
      $query = "
        $select FROM (
          SELECT civicrm_contribution_soft.amount as total_amount, civicrm_contribution_soft.currency $from $softCreditWhere
          GROUP BY civicrm_contribution_soft.id
        ) as conts
        GROUP BY currency";
      $dao = CRM_Core_DAO::executeQuery($query);
      $summary['soft_credit']['count'] = 0;
      $summary['soft_credit']['amount'] = $summary['soft_credit']['avg'] = array();
      while ($dao->fetch()) {
        $summary['soft_credit']['count'] += $dao->total_count;
        $summary['soft_credit']['amount'][] = CRM_Utils_Money::format($dao->total_amount, $dao->currency);
        $summary['soft_credit']['avg'][] = CRM_Utils_Money::format($dao->total_avg, $dao->currency);
      }
      if (!empty($summary['soft_credit']['amount'])) {
        $summary['soft_credit']['amount'] = implode(',&nbsp;', $summary['soft_credit']['amount']);
        $summary['soft_credit']['avg'] = implode(',&nbsp;', $summary['soft_credit']['avg']);
      }
      else {
        $summary['soft_credit']['amount'] = $summary['soft_credit']['avg'] = 0;
      }
    }

    // hack $select
    //@todo  - this could be one query using the IF in mysql - eg
    //  SELECT sum(total_completed), sum(count_completed), sum(count_cancelled), sum(total_cancelled) FROM (
    //   SELECT civicrm_contribution.total_amount, civicrm_contribution.currency  ,
    //  IF(civicrm_contribution.contribution_status_id = 1, 1, 0 ) as count_completed,
    //  IF(civicrm_contribution.contribution_status_id = 1, total_amount, 0 ) as total_completed,
    //  IF(civicrm_contribution.cancel_date IS NOT NULL = 1, 1, 0 ) as count_cancelled,
    //  IF(civicrm_contribution.cancel_date IS NOT NULL = 1, total_amount, 0 ) as total_cancelled
    // FROM civicrm_contact contact_a
    //  LEFT JOIN civicrm_contribution ON civicrm_contribution.contact_id = contact_a.id
    // WHERE  ( ... where clause....
    // AND (civicrm_contribution.cancel_date IS NOT NULL OR civicrm_contribution.contribution_status_id = 1)
    //  ) as conts

    $select = "
SELECT COUNT( conts.total_amount ) as cancel_count,
       SUM(   conts.total_amount ) as cancel_amount,
       AVG(   conts.total_amount ) as cancel_avg,
       conts.currency              as currency";

    $where .= " AND civicrm_contribution.cancel_date IS NOT NULL ";
    if ($context == 'search') {
      $where .= " AND contact_a.is_deleted = 0 ";
    }

    $query = "$select FROM (
      SELECT civicrm_contribution.total_amount, civicrm_contribution.currency $from $where
      GROUP BY civicrm_contribution.id
    ) as conts
    GROUP BY currency";

    $dao = CRM_Core_DAO::executeQuery($query);

    if ($dao->N <= 1) {
      if ($dao->fetch()) {
        $summary['cancel']['count'] = $dao->cancel_count;
        $summary['cancel']['amount'] = CRM_Utils_Money::format($dao->cancel_amount, $dao->currency);
        $summary['cancel']['avg'] = CRM_Utils_Money::format($dao->cancel_avg, $dao->currency);
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
   * Getter for the qill object.
   *
   * @return string
   */
  public function qill() {
    return $this->_qill;
  }

  /**
   * Default set of return default hier return properties.
   *
   * @return array
   */
  public static function &defaultHierReturnProperties() {
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
        'prefix_id' => 1,
        'suffix_id' => 1,
        'formal_title' => 1,
        'communication_style_id' => 1,
        'email_greeting' => 1,
        'postal_greeting' => 1,
        'addressee' => 1,
        'birth_date' => 1,
        'gender_id' => 1,
        'preferred_communication_method' => 1,
        'do_not_phone' => 1,
        'do_not_email' => 1,
        'do_not_mail' => 1,
        'do_not_sms' => 1,
        'do_not_trade' => 1,
        'location' => array(
          '1' => array(
            'location_type' => 1,
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

  /**
   * Build query for a date field.
   *
   * @param array $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param string $fieldTitle
   * @param bool $appendTimeStamp
   */
  public function dateQueryBuilder(
    &$values, $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $appendTimeStamp = TRUE
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName]) || !$value) {
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
      //In Get API, for operators other then '=' the $value is in array(op => value) format
      if (is_array($value) && !empty($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }

      $date = $format = NULL;
      if (strstr($op, 'IN')) {
        $format = array();
        foreach ($value as &$date) {
          $date = CRM_Utils_Date::processDate($date);
          if (!$appendTimeStamp) {
            $date = substr($date, 0, 8);
          }
          $format[] = CRM_Utils_Date::customFormat($date);
        }
        $date = "('" . implode("','", $value) . "')";
        $format = implode(', ', $format);
      }
      elseif ($value && (!strstr($op, 'NULL') && !strstr($op, 'EMPTY'))) {
        $date = CRM_Utils_Date::processDate($value);
        if (!$appendTimeStamp) {
          $date = substr($date, 0, 8);
        }
        $format = CRM_Utils_Date::customFormat($date);
        $date = "'$date'";
      }

      if ($date) {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op $date";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op";
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;

      $op = CRM_Utils_Array::value($op, CRM_Core_SelectValues::getSearchBuilderOperators(), $op);
      $this->_qill[$grouping][] = "$fieldTitle $op $format";
    }
  }

  /**
   * @param $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param $fieldTitle
   * @param null $options
   */
  public function numberRangeBuilder(
    &$values,
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

        $this->_qill[$grouping][]
          = "$fieldTitle - $firstPhrase \"$displayValue\" " . ts('AND') . " $secondPhrase \"$secondDisplayValue\"";
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
  }


  /**
   * @param $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param $fieldTitle
   * @param null $options
   */
  public function ageRangeQueryBuilder(
    &$values,
    $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $options = NULL
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $asofDateValues = $this->getWhereValues("{$fieldName}_asof_date", $grouping);
    $asofDate = NULL;  // will be treated as current day
    if ($asofDateValues) {
      $asofDate = CRM_Utils_Date::processDate($asofDateValues[2]);
      $asofDateFormat = CRM_Utils_Date::customFormat(substr($asofDate, 0, 8));
      $fieldTitle .= ' ' . ts('as of') . ' ' . $asofDateFormat;
    }

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName])) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = NULL;

      if ($name == "{$fieldName}_low") {
        $firstPhrase = ts('greater than or equal to');
        // NB: age > X means date of birth < Y
        $firstOP = '<=';
        $firstDate = self::calcDateFromAge($asofDate, $value, 'min');

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues)) {
          $secondOP = '>=';
          $secondPhrase = ts('less than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = self::calcDateFromAge($asofDate, $secondValue, 'max');
        }
      }
      else {
        $firstOP = '>=';
        $firstPhrase = ts('less than or equal to');
        $firstDate = self::calcDateFromAge($asofDate, $value, 'max');

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues)) {
          $secondOP = '<=';
          $secondPhrase = ts('greater than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = self::calcDateFromAge($asofDate, $secondValue, 'min');
        }
      }

      if ($secondOP) {
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP '$firstDate' ) AND
( {$tableName}.{$dbFieldName} $secondOP '$secondDate' )
";
        $displayValue = $options ? $options[$value] : $value;
        $secondDisplayValue = $options ? $options[$secondValue] : $secondValue;

        $this->_qill[$grouping][]
          = "$fieldTitle - $firstPhrase \"$displayValue\" " . ts('AND') . " $secondPhrase \"$secondDisplayValue\"";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP '$firstDate'";
        $displayValue = $options ? $options[$value] : $value;
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$displayValue\"";
      }
      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      return;
    }
  }

  /**
   * Calculate date from age.
   *
   * @param string $asofDate
   * @param int $age
   * @param string $type
   *
   * @return string
   */
  public static function calcDateFromAge($asofDate, $age, $type) {
    $date = new DateTime($asofDate);
    if ($type == "min") {
      // minimum age is $age: dob <= date - age "235959"
      $date->sub(new DateInterval("P" . $age . "Y"));
      return $date->format('Ymd') . "235959";
    }
    else {
      // max age is $age: dob >= date - (age + 1y) + 1d "000000"
      $date->sub(new DateInterval("P" . ($age + 1) . "Y"))->add(new DateInterval("P1D"));
      return $date->format('Ymd') . "000000";
    }
  }

  /**
   * Given the field name, operator, value & its data type
   * builds the where Clause for the query
   * used for handling 'IS NULL'/'IS NOT NULL' operators
   *
   * @param string $field
   *   Fieldname.
   * @param string $op
   *   Operator.
   * @param string $value
   *   Value.
   * @param string $dataType
   *   Data type of the field.
   *
   * @return string
   *   Where clause for the query.
   */
  public static function buildClause($field, $op, $value = NULL, $dataType = NULL) {
    $op = trim($op);
    $clause = "$field $op";

    switch ($op) {
      case 'IS NULL':
      case 'IS NOT NULL':
        return $clause;

      case 'IS EMPTY':
        $clause = " (NULLIF($field, '') IS NULL) ";
        return $clause;

      case 'IS NOT EMPTY':
        $clause = " (NULLIF($field, '') IS NOT NULL) ";
        return $clause;

      case 'IN':
      case 'NOT IN':
        // I feel like this would be escaped properly if passed through $queryString = CRM_Core_DAO::createSqlFilter.
        if (!empty($value) && (!is_array($value) || !array_key_exists($op, $value))) {
          $value = array($op => (array) $value);
        }

      default:
        if (empty($dataType)) {
          $dataType = 'String';
        }
        if (is_array($value)) {
          //this could have come from the api - as in the restWhere section we potentially use the api operator syntax which is becoming more
          // widely used and consistent across the codebase
          // adding this here won't accept the search functions which don't submit an array
          if (($queryString = CRM_Core_DAO::createSqlFilter($field, $value, $dataType)) != FALSE) {

            return $queryString;
          }

          // This is the here-be-dragons zone. We have no other hopes left for an array so lets assume it 'should' be array('IN' => array(2,5))
          // but we got only array(2,5) from the form.
          // We could get away with keeping this in 4.6 if we make it such that it throws an enotice in 4.7 so
          // people have to de-slopify it.
          if (!empty($value[0])) {
            if ($op != 'BETWEEN') {
              $dragonPlace = $iAmAnIntentionalENoticeThatWarnsOfAProblemYouShouldReport;
            }
            if (($queryString = CRM_Core_DAO::createSqlFilter($field, array($op => $value), $dataType)) != FALSE) {
              return $queryString;
            }
          }
          else {
            $op = 'IN';
            $dragonPlace = $iAmAnIntentionalENoticeThatWarnsOfAProblemYouShouldReportUsingOldFormat;
            if (($queryString = CRM_Core_DAO::createSqlFilter($field, array($op => array_keys($value)), $dataType)) != FALSE) {
              return $queryString;
            }
          }
        }

        $value = CRM_Utils_Type::escape($value, $dataType);
        // if we don't have a dataType we should assume
        if ($dataType == 'String' || $dataType == 'Text') {
          $value = "'" . strtolower($value) . "'";
        }
        return "$clause $value";
    }
  }

  /**
   * @param bool $reset
   *
   * @return array
   */
  public function openedSearchPanes($reset = FALSE) {
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
      ts('Mailings') => 'civicrm_mailing',
    );
    CRM_Contact_BAO_Query_Hook::singleton()->getPanesMapper($panesMapper);

    foreach (array_keys($this->_whereTables) as $table) {
      if ($panName = array_search($table, $panesMapper)) {
        self::$_openedPanes[$panName] = TRUE;
      }
    }

    return self::$_openedPanes;
  }

  /**
   * @param $operator
   */
  public function setOperator($operator) {
    $validOperators = array('AND', 'OR');
    if (!in_array($operator, $validOperators)) {
      $operator = 'AND';
    }
    $this->_operator = $operator;
  }

  /**
   * @return string
   */
  public function getOperator() {
    return $this->_operator;
  }

  /**
   * @param $from
   * @param $where
   * @param $having
   */
  public function filterRelatedContacts(&$from, &$where, &$having) {
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

    if (!empty($this->_permissionWhereClause)) {
      $_rTypeWhere .= "AND $this->_permissionWhereClause";
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

  /**
   * @param $op
   *
   * @return bool
   */
  public static function caseImportant($op) {
    return
      in_array($op, array('LIKE', 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY')) ? FALSE : TRUE;
  }

  /**
   * @param $returnProperties
   * @param $prefix
   *
   * @return bool
   */
  public static function componentPresent(&$returnProperties, $prefix) {
    foreach ($returnProperties as $name => $dontCare) {
      if (substr($name, 0, strlen($prefix)) == $prefix) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Builds the necessary structures for all fields that are similar to option value look-ups.
   *
   * @param string $name
   *   the name of the field.
   * @param string $op
   *   the sql operator, this function should handle ALL SQL operators.
   * @param string $value
   *   depends on the operator and who's calling the query builder.
   * @param int $grouping
   *   the index where to place the where clause.
   * @param string $daoName
   *   DAO Name.
   * @param array $field
   *   an array that contains various properties of the field identified by $name.
   * @param string $label
   *   The label for this field element.
   * @param string $dataType
   *   The data type for this element.
   * @param bool $useIDsOnly
   */
  public function optionValueQuery(
    $name,
    $op,
    $value,
    $grouping,
    $daoName = NULL,
    $field,
    $label,
    $dataType = 'String',
    $useIDsOnly = FALSE
  ) {

    $pseudoFields = array(
      'email_greeting',
      'postal_greeting',
      'addressee',
      'gender_id',
      'prefix_id',
      'suffix_id',
      'communication_style_id',
    );

    if ($useIDsOnly) {
      list($tableName, $fieldName) = explode('.', $field['where'], 2);
      if ($tableName == 'civicrm_contact') {
        $wc = "contact_a.$fieldName";
      }
      else {
        $wc = "$tableName.id";
      }
    }
    else {
      $wc = self::caseImportant($op) ? "LOWER({$field['where']})" : "{$field['where']}";
    }
    if (in_array($name, $pseudoFields)) {
      if (!in_array($name, array('gender_id', 'prefix_id', 'suffix_id', 'communication_style_id'))) {
        $wc = "contact_a.{$name}_id";
      }
      $dataType = 'Positive';
      $value = (!$value) ? 0 : $value;
    }
    if ($name == "world_region") {
      $field['name'] = $name;
    }

    list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue($daoName, $field['name'], $value, $op);
    $this->_qill[$grouping][] = ts("%1 %2 %3", array(1 => $label, 2 => $qillop, 3 => $qillVal));
    $this->_where[$grouping][] = self::buildClause($wc, $op, $value, $dataType);
  }

  /**
   * Check and explode a user defined numeric string into an array
   * this was the protocol used by search builder in the old old days before we had
   * super nice js widgets to do the hard work
   *
   * @param string $string
   * @param string $dataType
   *   The dataType we should check for the values, default integer.
   *
   * @return bool|array
   *   false if string does not match the pattern
   *   array of numeric values if string does match the pattern
   */
  public static function parseSearchBuilderString($string, $dataType = 'Integer') {
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
      if ($dataType == 'Integer' && !is_numeric($v)) {
        return FALSE;
      }
      elseif ($dataType == 'String' && !is_string($v)) {
        return FALSE;
      }
      $returnValues[] = trim($v);
    }

    if (empty($returnValues)) {
      return FALSE;
    }

    return $returnValues;
  }

  /**
   * Convert the pseudo constants id's to their names
   *
   * @param CRM_Core_DAO $dao
   * @param bool $return
   * @param bool $usedForAPI
   *
   * @return array|NULL
   */
  public function convertToPseudoNames(&$dao, $return = FALSE, $usedForAPI = FALSE) {
    if (empty($this->_pseudoConstantsSelect)) {
      return NULL;
    }
    $values = array();
    foreach ($this->_pseudoConstantsSelect as $key => $value) {
      if (!empty($this->_pseudoConstantsSelect[$key]['sorting'])) {
        continue;
      }

      if (is_object($dao) && property_exists($dao, $value['idCol'])) {
        $val = $dao->$value['idCol'];

        if (CRM_Utils_System::isNull($val)) {
          $dao->$key = NULL;
        }
        elseif ($baoName = CRM_Utils_Array::value('bao', $value, NULL)) {
          //preserve id value
          $idColumn = "{$key}_id";
          $dao->$idColumn = $val;

          if ($key == 'state_province_name') {
            $dao->$value['pseudoField'] = $dao->$key = CRM_Core_PseudoConstant::stateProvinceAbbreviation($val);
          }
          else {
            $dao->$value['pseudoField'] = $dao->$key = CRM_Core_PseudoConstant::getLabel($baoName, $value['pseudoField'], $val);
          }
        }
        elseif ($value['pseudoField'] == 'state_province_abbreviation') {
          $dao->$key = CRM_Core_PseudoConstant::stateProvinceAbbreviation($val);
        }
        // FIX ME: we should potentially move this to component Query and write a wrapper function that
        // handles pseudoconstant fixes for all component
        elseif (in_array($value['pseudoField'], array('participant_role_id', 'participant_role'))) {
          $viewValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $val);

          if ($value['pseudoField'] == 'participant_role') {
            $pseudoOptions = CRM_Core_PseudoConstant::get('CRM_Event_DAO_Participant', 'role_id');
            foreach ($viewValues as $k => $v) {
              $viewValues[$k] = $pseudoOptions[$v];
            }
          }
          $dao->$key = ($usedForAPI && count($viewValues) > 1) ? $viewValues : implode(', ', $viewValues);
        }
        else {
          $labels = CRM_Core_OptionGroup::values($value['pseudoField']);
          $dao->$key = $labels[$val];
        }

        // return converted values in array format
        if ($return) {
          if (strpos($key, '-') !== FALSE) {
            $keyVal = explode('-', $key);
            $current = &$values;
            $lastElement = array_pop($keyVal);
            foreach ($keyVal as $v) {
              if (!array_key_exists($v, $current)) {
                $current[$v] = array();
              }
              $current = &$current[$v];
            }
            $current[$lastElement] = $dao->$key;
          }
          else {
            $values[$key] = $dao->$key;
          }
        }
      }
    }
    return $values;
  }

  /**
   * Include pseudo fields LEFT JOIN.
   * @param string|array $sort can be a object or string
   *
   * @return array|NULL
   */
  public function includePseudoFieldsJoin($sort) {
    if (!$sort || empty($this->_pseudoConstantsSelect)) {
      return NULL;
    }
    $sort = is_string($sort) ? $sort : $sort->orderBy();
    $present = array();

    foreach ($this->_pseudoConstantsSelect as $name => $value) {
      if (!empty($value['table'])) {
        $regex = "/({$value['table']}\.|{$name})/";
        if (preg_match($regex, $sort)) {
          $this->_elemnt[$value['element']] = 1;
          $this->_select[$value['element']] = $value['select'];
          $this->_pseudoConstantsSelect[$name]['sorting'] = 1;
          $present[$value['table']] = $value['join'];
        }
      }
    }
    $presentSimpleFrom = $present;

    if (array_key_exists('civicrm_worldregion', $this->_whereTables) &&
      array_key_exists('civicrm_country', $presentSimpleFrom)
    ) {
      unset($presentSimpleFrom['civicrm_country']);
    }
    if (array_key_exists('civicrm_worldregion', $this->_tables) &&
      array_key_exists('civicrm_country', $present)
    ) {
      unset($present['civicrm_country']);
    }

    $presentClause = $presentSimpleFromClause = NULL;
    if (!empty($present)) {
      $presentClause = implode(' ', $present);
    }
    if (!empty($presentSimpleFrom)) {
      $presentSimpleFromClause = implode(' ', $presentSimpleFrom);
    }

    $this->_fromClause = $this->_fromClause . $presentClause;
    $this->_simpleFromClause = $this->_simpleFromClause . $presentSimpleFromClause;

    return array($presentClause, $presentSimpleFromClause);
  }

  /**
   * Build qill for field.
   *
   * Qill refers to the query detail visible on the UI.
   *
   * @param string $daoName
   * @param string $fieldName
   * @param mixed $fieldValue
   * @param string $op
   * @param array $pseudoExtraParam
   * @param int $type
   *   Type of the field per CRM_Utils_Type
   *
   * @return array
   */
  public static function buildQillForFieldValue(
    $daoName,
    $fieldName,
    $fieldValue,
    $op,
    $pseudoExtraParam = array(),
    $type = CRM_Utils_Type::T_STRING
  ) {
    $qillOperators = CRM_Core_SelectValues::getSearchBuilderOperators();

    // if Operator chosen is NULL/EMPTY then
    if (strpos($op, 'NULL') !== FALSE || strpos($op, 'EMPTY') !== FALSE) {
      return array(CRM_Utils_Array::value($op, $qillOperators, $op), '');
    }

    if ($fieldName == 'activity_type_id') {
      $pseudoOptions = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    }
    elseif ($fieldName == 'country_id') {
      $pseudoOptions = CRM_Core_PseudoConstant::country();
    }
    elseif ($fieldName == 'county_id') {
      $pseudoOptions = CRM_Core_PseudoConstant::county();
    }
    elseif ($fieldName == 'world_region') {
      $pseudoOptions = CRM_Core_PseudoConstant::worldRegion();
    }
    elseif ($daoName == 'CRM_Event_DAO_Event' && $fieldName == 'id') {
      $pseudoOptions = CRM_Event_BAO_Event::getEvents(0, $fieldValue, TRUE, TRUE, TRUE);
    }
    elseif ($fieldName == 'contribution_product_id') {
      $pseudoOptions = CRM_Contribute_PseudoConstant::products();
    }
    elseif ($daoName == 'CRM_Contact_DAO_Group' && $fieldName == 'id') {
      $pseudoOptions = CRM_Core_PseudoConstant::group();
    }
    elseif ($daoName) {
      $pseudoOptions = CRM_Core_PseudoConstant::get($daoName, $fieldName, $pseudoExtraParam);
    }

    //API usually have fieldValue format as array(operator => array(values)),
    //so we need to separate operator out of fieldValue param
    if (is_array($fieldValue) && in_array(key($fieldValue), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($fieldValue);
      $fieldValue = $fieldValue[$op];
    }

    if (is_array($fieldValue)) {
      $qillString = array();
      if (!empty($pseudoOptions)) {
        foreach ((array) $fieldValue as $val) {
          $qillString[] = CRM_Utils_Array::value($val, $pseudoOptions, $val);
        }
        $fieldValue = implode(', ', $qillString);
      }
      else {
        if ($type == CRM_Utils_Type::T_DATE) {
          foreach ($fieldValue as $index => $value) {
            $fieldValue[$index] = CRM_Utils_Date::customFormat($value);
          }
        }
        $separator = ', ';
        // @todo - this is a bit specific (one operator).
        // However it is covered by a unit test so can be altered later with
        // some confidence.
        if ($op == 'BETWEEN') {
          $separator = ' AND ';
        }
        $fieldValue = implode($separator, $fieldValue);
      }
    }
    elseif (!empty($pseudoOptions) && array_key_exists($fieldValue, $pseudoOptions)) {
      $fieldValue = $pseudoOptions[$fieldValue];
    }
    elseif ($type === CRM_Utils_Type::T_DATE) {
      $fieldValue = CRM_Utils_Date::customFormat($fieldValue);
    }

    return array(CRM_Utils_Array::value($op, $qillOperators, $op), $fieldValue);
  }

  /**
   * Alter value to reflect wildcard settings.
   *
   * The form will have tried to guess whether this is a good field to wildcard but there is
   * also a site-wide setting that specifies whether it is OK to append the wild card to the beginning
   * or only the end of the string
   *
   * @param bool $wildcard
   *   This is a bool made on an assessment 'elsewhere' on whether this is a good field to wildcard.
   * @param string $op
   *   Generally '=' or 'LIKE'.
   * @param string $value
   *   The search string.
   *
   * @return string
   */
  public static function getWildCardedValue($wildcard, $op, $value) {
    if ($wildcard && $op == 'LIKE') {
      if (CRM_Core_Config::singleton()->includeWildCardInName && (substr($value, 0, 1) != '%')) {
        return "%$value%";
      }
      else {
        return "$value%";
      }
    }
    else {
      return "$value";
    }
  }

  /**
   * Process special fields of Search Form in OK (Operator in Key) format
   *
   * @param array $formValues
   * @param array $specialFields
   *    Special params to be processed
   * @param array $changeNames
   *   Array of fields whose name should be changed
   */
  public static function processSpecialFormValue(&$formValues, $specialFields, $changeNames = array()) {
    foreach ($specialFields as $element) {
      $value = CRM_Utils_Array::value($element, $formValues);
      if ($value) {
        if (is_array($value)) {
          if (in_array($element, array_keys($changeNames))) {
            unset($formValues[$element]);
            $element = $changeNames[$element];
          }
          $formValues[$element] = array('IN' => $value);
        }
        else {
          // if wildcard is already present return searchString as it is OR append and/or prepend with wildcard
          $isWilcard = strstr($value, '%') ? FALSE : CRM_Core_Config::singleton()->includeWildCardInName;
          $formValues[$element] = array('LIKE' => self::getWildCardedValue($isWilcard, 'LIKE', $value));
        }
      }
    }
  }

  /**
   * Parse and assimilate the various sort options.
   *
   * Side-effect: if sorting on a common column from a related table (`city`, `postal_code`,
   * `email`), the related table may be joined automatically.
   *
   * At time of writing, this code is deeply flawed and should be rewritten. For the moment,
   * it's been extracted to a standalone function.
   *
   * @param string|CRM_Utils_Sort $sort
   *   The order by string.
   * @param bool $sortByChar
   *   If true returns the distinct array of first characters for search results.
   * @param null $sortOrder
   *   Who knows? Hu knows. He who knows Hu knows who.
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   * @return array
   *   list(string $orderByClause, string $additionalFromClause).
   */
  protected function prepareOrderBy($sort, $sortByChar, $sortOrder, $additionalFromClause) {
    $order = NULL;
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
        // Deliberately remove the backticks again, as they mess up the evil
        // string munging below. This balanced by re-escaping before use.
        $orderBy = str_replace('`', '', $orderBy);

        if (!empty($orderBy)) {
          // this is special case while searching for
          // change log CRM-1718
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

    // hack for order clause
    if ($order) {
      $fieldStr = trim(str_replace('ORDER BY', '', $order));
      $fieldOrder = explode(' ', $fieldStr);
      $field = $fieldOrder[0];

      if ($field) {
        switch ($field) {
          case 'city':
          case 'postal_code':
            $this->_tables["civicrm_address"] = $this->_whereTables["civicrm_address"] = 1;
            $order = str_replace($field, "civicrm_address.{$field}", $order);
            break;

          case 'country':
          case 'state_province':
            $this->_tables["civicrm_{$field}"] = $this->_whereTables["civicrm_{$field}"] = 1;
            if (is_array($this->_returnProperties) && empty($this->_returnProperties)) {
              $additionalFromClause .= " LEFT JOIN civicrm_{$field} ON civicrm_{$field}.id = civicrm_address.{$field}_id";
            }
            $order = str_replace($field, "civicrm_{$field}.name", $order);
            break;

          case 'email':
            $this->_tables["civicrm_email"] = $this->_whereTables["civicrm_email"] = 1;
            $order = str_replace($field, "civicrm_email.{$field}", $order);
            break;

          default:
            //CRM-12565 add "`" around $field if it is a pseudo constant
            foreach ($this->_pseudoConstantsSelect as $key => $value) {
              if (!empty($value['element']) && $value['element'] == $field) {
                $order = str_replace($field, "`{$field}`", $order);
              }
            }
        }
        $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_primaryLocation, $this->_mode);
        $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_primaryLocation, $this->_mode);
      }
    }

    // The above code relies on crazy brittle string manipulation of a peculiarly-encoded ORDER BY
    // clause. But this magic helper which forgivingly reescapes ORDER BY.
    // Note: $sortByChar implies that $order was hard-coded/trusted, so it can do funky things.
    if ($order && !$sortByChar) {
      $order = ' ORDER BY ' . CRM_Utils_Type::escape(preg_replace('/^\s*ORDER BY\s*/', '', $order), 'MysqlOrderBy');
      return array($order, $additionalFromClause);
    }
    return array($order, $additionalFromClause);
  }

}
