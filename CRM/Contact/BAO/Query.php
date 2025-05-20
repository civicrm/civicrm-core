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
 * This is the heart of the search query building mechanism.
 */
class CRM_Contact_BAO_Query {

  /**
   * The various search modes.
   *
   * As of February 2017, entries not present for 4, 32, 64, 1024.
   *
   * MODE_ALL seems to be out of sync with the available constants;
   * if this is intentionally excluding MODE_MAILING then that may
   * bear documenting?
   *
   * Likewise if there's reason for the missing modes (4, 32, 64 etc).
   *
   * @var int
   */
  const
    NO_RETURN_PROPERTIES = 'CRM_Contact_BAO_Query::NO_RETURN_PROPERTIES',
    MODE_CONTACTS = 1,
    MODE_CONTRIBUTE = 2,
    // There is no 4,
    MODE_MEMBER = 8,
    MODE_EVENT = 16,
    MODE_CONTACTSRELATED = 32,
    // no 64.
    MODE_GRANT = 128,
    MODE_PLEDGEBANK = 256,
    MODE_PLEDGE = 512,
    // There is no 1024,
    MODE_CASE = 2048,
    MODE_ACTIVITY = 4096,
    MODE_CAMPAIGN = 8192,
    MODE_MAILING = 16384,
    MODE_ALL = 17407;

  /**
   * Constants for search operators
   */
  const
    SEARCH_OPERATOR_AND = 'AND',
    SEARCH_OPERATOR_OR = 'OR';

  /**
   * The default set of return properties.
   *
   * @var array
   */
  public static $_defaultReturnProperties;

  /**
   * The default set of hier return properties.
   *
   * @var array
   */
  public static $_defaultHierReturnProperties;

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
   * used to extract the values.
   *
   * @var array
   */
  public $_element;

  /**
   * The tables involved in the query.
   *
   * @var array
   */
  public $_tables;

  /**
   * The table involved in the where clause.
   *
   * @var array
   */
  public $_whereTables;

  /**
   * Array of WHERE clause components.
   *
   * @var array
   */
  public $_where;

  /**
   * The WHERE clause as a string.
   *
   * @var string
   */
  public $_whereClause;

  /**
   * Additional WHERE clause for permissions.
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
   * @var array
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
   * Fields hacked for legacy reasons.
   *
   * Generally where a field has a option group defining it's options we add them to
   * the fields array as pseudofields - eg for gender we would add the key 'gender' to fields
   * using CRM_Core_DAO::appendPseudoConstantsToFields($fields);
   *
   * The rendered results would hold an id in the gender_id field and the label in the pseudo 'Gender'
   * field. The heading for the pseudofield would come form the the option group name & for the id field
   * from the xml.
   *
   * These fields are handled in a more legacy way - ie overwriting 'gender_id' with the label on output
   * via the convertToPseudoNames function. Ideally we would convert them but they would then need to be fixed
   * in some other places & there are also some issues around the name (ie. Gender currently has the label in the
   * schema 'Gender' so adding a second 'Gender' field to search builder & export would be confusing and the standard is
   * not fully agreed here.
   *
   * @var array
   */
  protected $legacyHackedFields = [
    'gender_id' => 'gender',
    'prefix_id' => 'individual_prefix',
    'suffix_id' => 'individual_suffix',
    'communication_style_id' => 'communication_style',
  ];

  /**
   * Are we in search mode.
   *
   * @var bool
   */
  public $_search = TRUE;

  /**
   * Should we skip permission checking.
   *
   * @var bool
   */
  public $_skipPermission = FALSE;

  /**
   * Should we skip adding of delete clause.
   *
   * @var bool
   */
  public $_skipDeleteClause = FALSE;

  /**
   * Are we in strict mode (use equality over LIKE)
   *
   * @var bool
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
   * @var bool
   */
  public $_primaryLocation = TRUE;

  /**
   * Are contact ids part of the query.
   *
   * @var bool
   */
  public $_includeContactIds = FALSE;

  /**
   * Should we use the smart group cache.
   *
   * @var bool
   */
  public $_smartGroupCache = TRUE;

  /**
   * Should we display contacts with a specific relationship type.
   *
   * @var string
   */
  public $_displayRelationshipType;

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
   * @var bool
   */
  public $_useDistinct = FALSE;

  /**
   * Should we just display one contact record
   * @var bool
   */
  public $_useGroupBy = FALSE;

  /**
   * The relationship type direction
   *
   * @var array
   */
  public static $_relType;

  /**
   * The activity role
   *
   * @var array
   */
  public static $_activityRole;

  /**
   * Consider the component activity type
   * during activity search.
   *
   * @var array
   */
  public static $_considerCompActivities;

  /**
   * Consider with contact activities only,
   * during activity search.
   *
   * @var array
   */
  public static $_withContactActivitiesOnly;

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
  public static $_openedPanes = [];

  /**
   * For search builder - which custom fields are location-dependent
   * @var array
   */
  public $_locationSpecificCustomFields = [];

  /**
   * The tables which have a dependency on location and/or address
   *
   * @var array
   */
  public static $_dependencies = [
    'civicrm_state_province' => 1,
    'civicrm_country' => 1,
    'civicrm_county' => 1,
    'civicrm_address' => 1,
    'civicrm_location_type' => 1,
  ];

  /**
   * List of location specific fields.
   * @var array
   */
  public static $_locationSpecificFields = [
    'street_address',
    'street_number',
    'street_name',
    'street_unit',
    'supplemental_address_1',
    'supplemental_address_2',
    'supplemental_address_3',
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
    'master_id',
    'location_type',
  ];

  /**
   * Remember if we handle either end of a number or date range
   * so we can skip the other
   * @var array
   */
  protected $_rangeCache = [];
  /**
   * Set to true when $this->relationship is run to avoid adding twice.
   *
   * @var bool
   */
  protected $_relationshipValuesAdded = FALSE;

  /**
   * Set to the name of the temp table if one has been created.
   *
   * @var string
   */
  public static $_relationshipTempTable;

  public $_pseudoConstantsSelect = [];

  public $_groupUniqueKey;
  public $_groupKeys = [];

  /**
   * @var int
   * Set to 1 if Search in Trash selected.
   */
  public $_onlyDeleted = 0;

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
   * @param bool|null $primaryLocationOnly
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct(
    $params = NULL, $returnProperties = NULL, $fields = NULL,
    $includeContactIds = FALSE, $strict = FALSE, $mode = 1,
    $skipPermission = FALSE, $searchDescendentGroups = TRUE,
    $smartGroupCache = TRUE, $displayRelationshipType = NULL,
    $operator = 'AND',
    $apiEntity = NULL,
    $primaryLocationOnly = NULL
  ) {
    if ($primaryLocationOnly === NULL) {
      $primaryLocationOnly = Civi::settings()->get('searchPrimaryDetailsOnly');
    }
    $this->_primaryLocation = $primaryLocationOnly;
    $this->_params = &$params;
    if ($this->_params == NULL) {
      $this->_params = [];
    }

    if ($returnProperties === self::NO_RETURN_PROPERTIES) {
      $this->_returnProperties = [];
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
      //  The legacy hacked fields will output as a string rather than their underlying type.
      foreach (array_keys($this->legacyHackedFields) as $fieldName) {
        $this->_fields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
      }
      $relationMetadata = CRM_Contact_BAO_Relationship::fields();
      $relationFields = array_intersect_key($relationMetadata, array_fill_keys(['relationship_start_date', 'relationship_end_date'], 1));
      // No good option other than hard-coding metadata for this 'special' field in.
      $relationFields['relation_active_period_date'] = [
        'name' => 'relation_active_period_date',
        'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
        'title' => ts('Active Period'),
        'table_name' => 'civicrm_relationship',
        'where' => 'civicrm_relationship.start_date',
        'where_end' => 'civicrm_relationship.end_date',
        'html' => ['type' => 'SelectDate', 'formatType' => 'activityDateTime'],
      ];
      $this->_fields = array_merge($relationFields, $this->_fields);

      $fields = CRM_Core_Component::getQueryFields(!$this->_skipPermission);
      unset($fields['note']);
      $this->_fields = array_merge($this->_fields, $fields);

      // add activity fields
      $this->_fields = array_merge($this->_fields, CRM_Activity_BAO_Activity::exportableFields());
      $this->_fields = array_merge($this->_fields, CRM_Activity_BAO_Activity::exportableFields('Case'));
      // Add hack as no unique name is defined for the field but the search form is in denial.
      $this->_fields['activity_priority_id'] = $this->_fields['priority_id'];

      // add any fields provided by hook implementers
      $extFields = CRM_Contact_BAO_Query_Hook::singleton()->getFields();
      $this->_fields = array_merge($this->_fields, $extFields);
    }

    // basically do all the work once, and then reuse it
    $this->initialize($apiEntity);
  }

  /**
   * Function which actually does all the work for the constructor.
   *
   * @param string $apiEntity
   *   The api entity being called.
   *   This sort-of duplicates $mode in a confusing way. Probably not by design.
   *
   * @throws \CRM_Core_Exception
   */
  public function initialize($apiEntity = NULL) {
    $this->_select = [];
    $this->_element = [];
    $this->_tables = [];
    $this->_whereTables = [];
    $this->_where = [];
    $this->_qill = [];
    $this->_cfIDs = [];
    $this->_paramLookup = [];
    $this->_having = [];

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
    if (!empty($this->_cfIDs)) {
      // @todo This function is the select function but instead of running 'select' it
      // is running the whole query.
      $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs, TRUE, $this->_locationSpecificCustomFields);
      $this->_customQuery->query();
      $this->_select = array_merge($this->_select, $this->_customQuery->_select);
      $this->_element = array_merge($this->_element, $this->_customQuery->_element);
      $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
    }
    $isForcePrimaryOnly = !empty($apiEntity);
    $this->_whereClause = $this->whereClause($isForcePrimaryOnly);
    if (array_key_exists('civicrm_contribution', $this->_whereTables)) {
      $component = 'contribution';
    }
    if (array_key_exists('civicrm_membership', $this->_whereTables)) {
      $component = 'membership';
    }
    if (isset($component) && !$this->_skipPermission) {
      // Unit test coverage in api_v3_FinancialTypeACLTest::testGetACLContribution.
      $clauses = [];
      if ($component === 'contribution') {
        $clauses = array_filter(CRM_Contribute_BAO_Contribution::getSelectWhereClause());
      }
      if ($component === 'membership') {
        $clauses = array_filter(CRM_Member_BAO_Membership::getSelectWhereClause());
      }
      if ($clauses) {
        $this->_whereClause .= ' AND ' . implode(' AND ', $clauses);
      }
    }
    // Flag if we are only lookif for deleted contacts. Once flagged, keep it
    // because we seem to forget it.
    if (in_array(['deleted_contacts', '=', '1', '0', '0'], $this->_params)) {
      $this->_onlyDeleted = 1;
    }
    else {
      $this->_onlyDeleted = 0;
    }

    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL,
      $this->_primaryLocation, $this->_mode, $apiEntity, $this->_onlyDeleted);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL,
      NULL, $this->_primaryLocation, $this->_mode, NULL, $this->_onlyDeleted);
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
   * moving away from weird & wonderful where clause switches.
   *
   * Fix and handle contact deletion nicely.
   *
   * this code is primarily for search builder use case where different clauses can specify if they want deleted.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-11971
   */
  public function buildParamsLookup() {
    $trashParamExists = FALSE;
    $paramByGroup = [];
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
          !in_array(['contact_is_deleted', '=', '1', $setID, '0'], $this->_params) &&
          !in_array(['contact_is_deleted', '=', '0', $setID, '0'], $this->_params)
        ) {
          $this->_params[] = [
            'contact_is_deleted',
            '=',
            '0',
            $setID,
            '0',
          ];
        }
      }
    }

    foreach ($this->_params as $value) {
      if (empty($value[0])) {
        continue;
      }
      $cfID = CRM_Core_BAO_CustomField::getKeyID(str_replace(['_relative', '_low', '_high', '_to', '_high'], '', $value[0]));
      if ($cfID) {
        if (!array_key_exists($cfID, $this->_cfIDs)) {
          $this->_cfIDs[$cfID] = [];
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
        $this->_paramLookup[$value[0]] = [];
      }
      if ($value[0] !== 'group') {
        // Just trying to unravel how group interacts here! This whole function is weird.
        $this->_paramLookup[$value[0]][] = $value;
      }
    }
  }

  /**
   * Some composite fields do not appear in the fields array hack to make them part of the query.
   *
   * @param $apiEntity
   *   The api entity being called.
   *   This sort-of duplicates $mode in a confusing way. Probably not by design.
   */
  public function addSpecialFields($apiEntity) {
    static $special = ['contact_type', 'contact_sub_type', 'sort_name', 'display_name'];
    // if get called via Contact.get API having address_id as return parameter
    if ($apiEntity === 'Contact') {
      $special[] = 'address_id';
    }
    foreach ($special as $name) {
      if (!empty($this->_returnProperties[$name])) {
        if ($name === 'address_id') {
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
   *
   * @param string $apiEntity
   *   The api entity being called.
   *   This sort-of duplicates $mode in a confusing way. Probably not by design.
   */
  public function selectClause($apiEntity = NULL) {

    // @todo Tidy up this. This arises because 1) we are ignoring the $mode & adding a new
    // param ($apiEntity) instead - presumably an oversight & 2 because
    // contact is not implemented as a component.
    $this->addSpecialFields($apiEntity);

    foreach ($this->_fields as $name => $field) {
      // skip component fields
      // there are done by the alter query below
      // and need not be done on every field
      // @todo remove these & handle using metadata - only obscure fields
      // that are hack-added should need to be excluded from the main loop.
      if (
        (substr($name, 0, 12) === 'participant_') ||
        (substr($name, 0, 7) === 'pledge_') ||
        (substr($name, 0, 5) === 'case_')
      ) {
        continue;
      }

      // redirect to activity select clause
      if (
        (substr($name, 0, 9) === 'activity_') ||
        ($name === 'parent_id')
      ) {
        CRM_Activity_BAO_Query::select($this);
      }

      // if this is a hierarchical name, we ignore it
      $names = explode('-', $name);
      if (count($names) > 1 && isset($names[1]) && is_numeric($names[1])) {
        continue;
      }

      // make an exception for special cases, to add the field in select clause
      $makeException = FALSE;

      //special handling for groups/tags
      if (in_array($name, ['groups', 'tags', 'notes'])
        && isset($this->_returnProperties[substr($name, 0, -1)])
      ) {
        // @todo instead of setting make exception to get us into
        // an if clause that has handling for these fields buried with in it
        // move the handling to here.
        $makeException = TRUE;
      }

      // since note has 3 different options we need special handling
      // note / note_subject / note_body
      if ($name === 'notes') {
        foreach (['note', 'note_subject', 'note_body'] as $noteField) {
          if (isset($this->_returnProperties[$noteField])) {
            $makeException = TRUE;
            break;
          }
        }
      }

      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if (
        !empty($this->_paramLookup[$name])
        || !empty($this->_returnProperties[$name])
        || $this->pseudoConstantNameIsInReturnProperties($field, $name)
        || $makeException
      ) {
        if ($cfID) {
          // add to cfIDs array if not present
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = [];
          }
        }
        elseif (isset($field['where'])) {
          [$tableName, $fieldName] = explode('.', $field['where'], 2);
          if (isset($tableName)) {
            if (!empty(self::$_dependencies[$tableName])) {
              $this->_tables['civicrm_address'] = 1;
              $this->_select['address_id'] = 'civicrm_address.id as address_id';
              $this->_element['address_id'] = 1;
            }

            if ($tableName === 'im_provider' || $tableName === 'email_greeting' ||
              $tableName === 'postal_greeting' || $tableName === 'addressee'
            ) {
              if ($tableName === 'im_provider') {
                CRM_Core_OptionValue::select($this);
              }

              if (in_array($tableName,
                ['email_greeting', 'postal_greeting', 'addressee'])) {
                $this->_element["{$name}_id"] = 1;
                $this->_select["{$name}_id"] = "contact_a.{$name}_id as {$name}_id";
                $this->_pseudoConstantsSelect[$name] = ['pseudoField' => $tableName, 'idCol' => "{$name}_id"];
                $this->_pseudoConstantsSelect[$name]['select'] = "{$name}.{$fieldName} as $name";
                $this->_pseudoConstantsSelect[$name]['element'] = $name;

                if ($tableName === 'email_greeting') {
                  // @todo bad join.
                  $this->_pseudoConstantsSelect[$name]['join']
                    = " LEFT JOIN civicrm_option_group option_group_email_greeting ON (option_group_email_greeting.name = 'email_greeting')";
                  $this->_pseudoConstantsSelect[$name]['join'] .=
                    " LEFT JOIN civicrm_option_value email_greeting ON (contact_a.email_greeting_id = email_greeting.value AND option_group_email_greeting.id = email_greeting.option_group_id ) ";
                }
                elseif ($tableName === 'postal_greeting') {
                  // @todo bad join.
                  $this->_pseudoConstantsSelect[$name]['join']
                    = " LEFT JOIN civicrm_option_group option_group_postal_greeting ON (option_group_postal_greeting.name = 'postal_greeting')";
                  $this->_pseudoConstantsSelect[$name]['join'] .=
                    " LEFT JOIN civicrm_option_value postal_greeting ON (contact_a.postal_greeting_id = postal_greeting.value AND option_group_postal_greeting.id = postal_greeting.option_group_id ) ";
                }
                elseif ($tableName == 'addressee') {
                  // @todo bad join.
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
              if (!in_array($tableName, ['civicrm_state_province', 'civicrm_country', 'civicrm_county'])) {
                $this->_tables[$tableName] = 1;
              }

              // also get the id of the tableName
              $tName = substr($tableName, 8);
              if (in_array($tName, ['country', 'state_province', 'county'])) {
                if ($tName == 'state_province') {
                  $this->_pseudoConstantsSelect['state_province_name'] = [
                    'pseudoField' => "{$tName}",
                    'idCol' => "{$tName}_id",
                    'bao' => 'CRM_Core_BAO_Address',
                    'table' => "civicrm_{$tName}",
                    'join' => " LEFT JOIN civicrm_{$tName} ON civicrm_address.{$tName}_id = civicrm_{$tName}.id ",
                  ];

                  $this->_pseudoConstantsSelect[$tName] = [
                    'pseudoField' => 'state_province_abbreviation',
                    'idCol' => "{$tName}_id",
                    'table' => "civicrm_{$tName}",
                    'join' => " LEFT JOIN civicrm_{$tName} ON civicrm_address.{$tName}_id = civicrm_{$tName}.id ",
                  ];
                }
                else {
                  $this->_pseudoConstantsSelect[$name] = [
                    'pseudoField' => "{$tName}_id",
                    'idCol' => "{$tName}_id",
                    'bao' => 'CRM_Core_BAO_Address',
                    'table' => "civicrm_{$tName}",
                    'join' => " LEFT JOIN civicrm_{$tName} ON civicrm_address.{$tName}_id = civicrm_{$tName}.id ",
                  ];
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

              if ($tName == 'contact' && $fieldName == 'organization_name') {
                // special case, when current employer is set for Individual contact
                $this->_select[$name] = "IF ( contact_a.contact_type = 'Individual', NULL, contact_a.organization_name ) as organization_name";
              }
              elseif ($tName == 'contact' && $fieldName === 'id') {
                // Handled elsewhere, explicitly ignore. Possibly for all tables...
              }
              elseif (in_array($tName, ['country', 'county'])) {
                $this->_pseudoConstantsSelect[$name]['select'] = "{$field['where']} as `$name`";
                $this->_pseudoConstantsSelect[$name]['element'] = $name;
              }
              elseif ($tName == 'state_province') {
                $this->_pseudoConstantsSelect[$tName]['select'] = "{$field['where']} as `$name`";
                $this->_pseudoConstantsSelect[$tName]['element'] = $name;
              }
              elseif (str_contains($name, 'contribution_soft_credit')) {
                if (CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($this->_params)) {
                  $this->_select[$name] = "{$field['where']} as `$name`";
                }
              }
              elseif ($this->pseudoConstantNameIsInReturnProperties($field, $name)) {
                $this->addPseudoconstantFieldToSelect($name);
              }
              else {
                $this->_select[$name] = str_replace('civicrm_contact.', 'contact_a.', "{$field['where']} as `$name`");
              }
              if (!in_array($tName, ['state_province', 'country', 'county'])) {
                $this->_element[$name] = 1;
              }
            }
          }
        }
        elseif ($name === 'tags') {
          //@todo move this handling outside the big IF & ditch $makeException
          $this->_useGroupBy = TRUE;
          $this->_select[$name] = "GROUP_CONCAT(DISTINCT(civicrm_tag.name)) as tags";
          $this->_element[$name] = 1;
          $this->_tables['civicrm_tag'] = 1;
          $this->_tables['civicrm_entity_tag'] = 1;
        }
        elseif ($name === 'groups') {
          //@todo move this handling outside the big IF & ditch $makeException
          $this->_useGroupBy = TRUE;
          // Duplicates will be created here but better to sort them out in php land.
          $this->_select[$name] = "
            CONCAT_WS(',',
            GROUP_CONCAT(DISTINCT IF(civicrm_group_contact.status = 'Added', civicrm_group_contact.group_id, '')),
            GROUP_CONCAT(DISTINCT civicrm_group_contact_cache.group_id)
          )
          as `groups`";
          $this->_element[$name] = 1;
          $this->_tables['civicrm_group_contact'] = 1;
          $this->_tables['civicrm_group_contact_cache'] = 1;
          $this->_pseudoConstantsSelect["{$name}"] = [
            'pseudoField' => "groups",
            'idCol' => 'groups',
          ];
        }
        elseif ($name === 'notes') {
          //@todo move this handling outside the big IF & ditch $makeException
          // if note field is subject then return subject else body of the note
          $noteColumn = 'note';
          if (isset($noteField) && $noteField === 'note_subject') {
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
            $this->_cfIDs[$cfID] = [];
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
              $p[2] = ['from' => $p[2]];
              $this->_cfIDs[$cfID][] = $p;
            }
          }
        }
        if (!empty($this->_paramLookup[$name . '_to'])) {
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = [];
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
              $p[2] = ['to' => $p[2]];
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

    $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');
    $processed = [];
    $index = 0;

    $addressCustomFields = CRM_Core_BAO_CustomField::getFieldsForImport('Address');
    $addressCustomFieldIds = [];

    foreach ($this->_returnProperties['location'] as $name => $elements) {
      $lCond = self::getPrimaryCondition($name);
      $locationTypeId = is_numeric($name) ? NULL : array_search($name, $locationTypes);

      if (!$lCond) {
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
      $locationTypeJoin = [];

      $addWhereCount = 0;
      foreach ($elements as $elementFullName => $dontCare) {
        $index++;
        $elementName = $elementCmpName = $elementFullName;

        if (substr($elementCmpName, 0, 5) == 'phone') {
          $elementCmpName = 'phone';
        }

        if (array_key_exists($elementCmpName, $addressCustomFields)) {
          if ($cfID = CRM_Core_BAO_CustomField::getKeyID($elementCmpName)) {
            $addressCustomFieldIds[$cfID][$name] = 1;
          }
        }
        // add address table - doesn't matter if we do it mutliple times - it's the same data
        // @todo ditch the double processing of addressJoin
        if ((in_array($elementCmpName, self::$_locationSpecificFields) || !empty($addressCustomFieldIds))
          && !in_array($elementCmpName, ['email', 'phone', 'im', 'openid'])
        ) {
          [$aName, $addressJoin] = $this->addAddressTable($name, $lCond);
          $locationTypeJoin[$tName] = " ( $aName.location_type_id = $ltName.id ) ";
          $processed[$aName] = 1;
        }

        $cond = $elementType = '';
        if (str_contains($elementName, '-')) {
          // this is either phone, email or IM
          [$elementName, $elementType] = explode('-', $elementName);

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

        $field = $this->_fields[$elementName] ?? NULL;
        if (!empty($field)) {
          if (isset($this->_pseudoConstantsSelect[$field['name']])) {
            $this->_pseudoConstantsSelect[$name . '-' . $field['name']] = $this->_pseudoConstantsSelect[$field['name']];
          }
        }

        // hack for profile, add location id
        if (!$field) {
          if ($elementType &&
            // fix for CRM-882( to handle phone types )
            !is_numeric($elementType)
          ) {
            if (is_numeric($name)) {
              $field = $this->_fields[$elementName . "-Primary$elementType"] ?? NULL;
            }
            else {
              $field = $this->_fields[$elementName . "-$locationTypeId$elementType"] ?? NULL;
            }
          }
          elseif (is_numeric($name)) {
            //this for phone type to work
            if (in_array($elementName, ['phone', 'phone_ext'])) {
              $field = $this->_fields[$elementName . "-Primary" . $elementType] ?? NULL;
            }
            else {
              $field = $this->_fields[$elementName . "-Primary"] ?? NULL;
            }
          }
          else {
            //this is for phone type to work for profile edit
            if (in_array($elementName, ['phone', 'phone_ext'])) {
              $field = $this->_fields[$elementName . "-$locationTypeId$elementType"] ?? NULL;
            }
            else {
              $field = $this->_fields[$elementName . "-$locationTypeId"] ?? NULL;
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
              (in_array($elementName, ['phone', 'im'])
                && (str_contains($values[0], $nm))
              )
            ) {
              $addWhere = TRUE;
              $addWhereCount++;
              break;
            }
          }
        }

        if ($field && isset($field['where'])) {
          [$tableName, $fieldName] = explode('.', $field['where'], 2);
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
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"] = [
                  'pseudoField' => "{$pf}_id",
                  'idCol' => "{$tName}_id",
                  'bao' => 'CRM_Core_BAO_Address',
                ];
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['select'] = "`$tName`.name as `{$name}-{$elementFullName}`";
              }
              else {
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"] = [
                  'pseudoField' => 'state_province_abbreviation',
                  'idCol' => "{$tName}_id",
                ];
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
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"] = [
                  'pseudoField' => "{$pf}_id",
                  'idCol' => "{$tName}_id",
                  'bao' => 'CRM_Core_BAO_Address',
                ];
                $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['select'] = "`$tName`.$fieldName as `{$name}-{$elementFullName}`";
              }
              else {
                $this->_select["{$name}-{$elementFullName}"] = "`$tName`.$fieldName as `{$name}-{$elementFullName}`";
              }
            }

            if (in_array($pf, ['state_province', 'country', 'county'])) {
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
                    if (str_contains($cond, 'phone_type_id')) {
                      $phoneTypeCondition = " AND ( `$tName`.$cond OR `$tName`.phone_type_id IS NULL ) ";
                      if (!empty($lCond)) {
                        $phoneTypeCondition .= " AND ( `$tName`.$lCond ) ";
                      }
                    }
                    $this->_tables[$tName] .= $phoneTypeCondition;
                  }

                  //build locationType join
                  $locationTypeJoin[$tName] = " ( `$tName`.location_type_id = $ltName.id )";
                  break;

                case 'civicrm_state_province':
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['table'] = $tName;
                  $this->_pseudoConstantsSelect["{$name}-{$elementFullName}"]['join']
                    = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.state_province_id";
                  if ($addWhere) {
                    $this->_whereTables["{$name}-address"] = $addressJoin;
                  }
                  break;

                case 'civicrm_location_type':
                  $this->_tables[$tName] = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.location_type_id";

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
                  if (isset($addressCustomFields[$elementName]['custom_field_id']) && !empty($addressCustomFields[$elementName]['custom_field_id'])) {
                    $this->_tables[$tName] = "\nLEFT JOIN $tableName `$tName` ON `$tName`.id = $aName.id";
                  }
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
        $locClause = [];
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
          $this->_locationSpecificCustomFields[$cfID] = [$name, array_search($name, $locationTypes)];
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
   * @param int $onlyDeleted
   *
   * @return array
   *   sql query parts as an array
   */
  public function query($count = FALSE, $sortByChar = FALSE, $groupContacts = FALSE, $onlyDeleted = 0) {
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
      // @fixme add the deprecated warning back in (it breaks CRM_Contact_SelectorTest::testSelectorQuery)
      // CRM_Core_Error::deprecatedFunctionWarning('sort by char is deprecated - use alphabetQuery method');
      $select = 'SELECT DISTINCT LEFT(contact_a.sort_name, 1) as sort_name';
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

        [$name, $op, $value, $grouping, $wildcard] = $this->_paramLookup['group'][0];

        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $this->_paramLookup['group'][0][1] = key($value);
        }

        // Presumably the lines below come into manage groups screen.
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
            $tbName = "civicrm_group_contact";
            // CRM-17254 don't retrieve extra fields if contact_id is specifically requested
            // as this will add load to an intentionally light query.
            // ideally this code would be removed as it appears to be to support CRM-1203
            // and passing in the required returnProperties from the url would
            // make more sense that globally applying the requirements of one form.
            if (($this->_returnProperties != ['contact_id'])) {
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

      $select = $this->getSelect();
      $from = $this->_fromClause;
    }

    $where = '';
    if (!empty($this->_whereClause)) {
      $where = "WHERE {$this->_whereClause}";
    }

    if (!empty($this->_permissionWhereClause) && empty($this->_displayRelationshipType)) {
      if (!empty($this->_permissionFromClause)) {
        $from .= " $this->_permissionFromClause";
      }
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

    return [$select, $from, $where, $having];
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
      [$from, $to] = CRM_Utils_Date::getFromTo($relative, $from, $to);
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
   * More notes at CRM_Core_DAO::createSQLFilter
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
    $entityReferenceFields = []) {
    $params = [];
    if (empty($formValues)) {
      return $params;
    }

    self::filterCountryFromValuesIfStateExists($formValues);
    CRM_Core_BAO_CustomValue::fixCustomFieldValue($formValues);

    foreach ($formValues as $id => $values) {
      if (self::isAlreadyProcessedForQueryFormat($values)) {
        $params[] = $values;
        continue;
      }

      self::legacyConvertFormValues($id, $values);

      // The form uses 1 field to represent two db fields
      if ($id === 'contact_type' && $values && (!is_array($values) || !array_intersect(array_keys($values), CRM_Core_DAO::acceptedSQLOperators()))) {
        $contactType = [];
        $subType = [];
        foreach ((array) $values as $key => $type) {
          $types = explode('__', is_numeric($type) ? $key : $type, 2);
          $contactType[$types[0]] = $types[0];
          // Add sub-type if specified
          if (!empty($types[1])) {
            $subType[$types[1]] = $types[1];
          }
        }
        $params[] = ['contact_type', 'IN', $contactType, 0, 0];
        if ($subType) {
          $params[] = ['contact_sub_type', 'IN', $subType, 0, 0];
        }
      }
      elseif ($id === 'privacy') {
        if (is_array($formValues['privacy'])) {
          $op = !empty($formValues['privacy']['do_not_toggle']) ? '=' : '!=';
          foreach ($formValues['privacy'] as $key => $value) {
            if ($value) {
              $params[] = [$key, $op, $value, 0, 0];
            }
          }
        }
      }
      elseif ($id === 'email_on_hold') {
        $onHoldValue = $formValues['email_on_hold'] ?? NULL;
        if ($onHoldValue) {
          // onHoldValue should be 0 or 1 or an array. Some legacy groups may hold ''
          // so in 5.11 we have an extra if that should become redundant over time.
          // https://lab.civicrm.org/dev/core/issues/745
          // @todo this renaming of email_on_hold to on_hold needs revisiting
          // it precedes recent changes but causes the default not to reload.
          $onHoldValue = array_filter((array) $onHoldValue, 'is_numeric');
          if (!empty($onHoldValue)) {
            $params[] = ['on_hold', 'IN', $onHoldValue, 0, 0];
          }
        }
      }
      elseif (substr($id, 0, 7) === 'custom_'
        &&  (
          substr($id, -5, 5) === '_from'
          || substr($id, -3, 3) === '_to'
        )
      ) {
        self::convertCustomRelativeFields($formValues, $params, $values, $id);
      }
      elseif (in_array($id, $entityReferenceFields) && !empty($values) && is_string($values) && (strpos($values, ',') !=
        FALSE)) {
        $params[] = [$id, 'IN', explode(',', $values), 0, 0];
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
    $legacyElements = [
      'group',
      'tag',
      'contact_tags',
      'contact_type',
      'membership_type_id',
      'membership_status_id',
    ];
    if (in_array($id, $legacyElements) && is_array($values)) {
      // prior to 4.7, formValues for some attributes (e.g. group, tag) are stored in array(id1 => 1, id2 => 1),
      // as per the recent Search fixes $values need to be in standard array(id1, id2) format
      $values = CRM_Utils_Array::convertCheckboxFormatToArray($values);
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

    // check if $value is in OK (Operator as Key) format as used by Get API
    if (CRM_Utils_System::isNull($values)) {
      return $result;
    }

    if (!$skipWhere) {
      $skipWhere = [
        'task',
        'radio_ts',
        'uf_group_id',
        'component_mode',
        'qfKey',
        'operator',
        'display_relationship_type',
      ];
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
      $likeNames = ['sort_name', 'email', 'note', 'display_name'];
    }

    // email comes in via advanced search
    // so use wildcard always
    if ($id == 'email') {
      $wildcard = 1;
    }

    if (!$useEquals && in_array($id, $likeNames)) {
      $result = [$id, 'LIKE', $values, 0, 1];
    }
    elseif (is_string($values) && str_contains($values, '%')) {
      $result = [$id, 'LIKE', $values, 0, 0];
    }
    elseif ($id == 'contact_type' ||
      (!empty($values) && is_array($values) && !in_array(key($values), CRM_Core_DAO::acceptedSQLOperators(), TRUE))
    ) {
      $result = [$id, 'IN', $values, 0, $wildcard];
    }
    else {
      $result = [$id, '=', $values, 0, $wildcard];
    }

    return $result;
  }

  /**
   * Get the where clause for a single field.
   *
   * @param array $values
   * @param bool $isForcePrimaryOnly
   *
   * @throws \CRM_Core_Exception
   */
  public function whereClauseSingle(&$values, $isForcePrimaryOnly = FALSE) {
    if ($this->isARelativeDateField($values[0])) {
      $this->buildRelativeDateQuery($values);
      return;
    }
    // @todo also handle _low, _high generically here with if ($query->buildDateRangeQuery($values)) {return}

    // do not process custom fields or prefixed contact ids or component params
    if (CRM_Core_BAO_CustomField::getKeyID($values[0]) ||
      (substr($values[0], 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) ||
      (substr($values[0], 0, 13) === 'contribution_') ||
      (substr($values[0], 0, 6) === 'event_') ||
      (substr($values[0], 0, 12) === 'participant_') ||
      (substr($values[0], 0, 7) === 'member_') ||
      (substr($values[0], 0, 6) === 'grant_') ||
      (substr($values[0], 0, 7) === 'pledge_') ||
      (substr($values[0], 0, 5) === 'case_') ||
      (substr($values[0], 0, 10) === 'financial_') ||
      (substr($values[0], 0, 8) === 'payment_') ||
      (substr($values[0], 0, 11) === 'membership_')
      // temporary fix for regression https://lab.civicrm.org/dev/core/issues/1551
      //  ideally the metadata would allow  this field to be parsed below & the special handling would not
      // be needed.
      || $values[0] === 'mailing_id'
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
      case 'group_type':
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
      case 'email_id':
        $this->email($values, $isForcePrimaryOnly);
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
      case 'activity_date_time_low':
      case 'activity_date_time_high':
      case 'activity_role':
      case 'activity_status_id':
      case 'activity_status':
      case 'activity_priority':
      case 'activity_priority_id':
      case 'followup_parent_id':
      case 'parent_id':
      case 'source_contact_id':
      case 'activity_text':
      case 'activity_option':
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
      case 'relationship_start_date_high':
      case 'relationship_start_date_low':
      case 'relationship_end_date_high':
      case 'relationship_end_date_low':
      case 'relation_active_period_date_high':
      case 'relation_active_period_date_low':
      case 'relation_target_name':
      case 'relation_status':
      case 'relation_description':
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
      case 'prox_geo_code_1':
      case 'prox_geo_code_2':
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
   * @param bool $isForcePrimaryEmailOnly
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function whereClause($isForcePrimaryEmailOnly = NULL): string {
    $this->_where[0] = [];
    $this->_qill[0] = [];

    $this->includeContactIDs();
    if (!empty($this->_params)) {
      foreach (array_keys($this->_params) as $id) {
        if (empty($this->_params[$id][0])) {
          continue;
        }
        // check for both id and contact_id
        if ($this->_params[$id][0] == 'id' || $this->_params[$id][0] == 'contact_id') {
          $this->_where[0][] = self::buildClause("contact_a.id", $this->_params[$id][1], $this->_params[$id][2]);
          $field = $this->_fields['id'] ?? NULL;
          [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue(
            'CRM_Contact_BAO_Contact',
            "contact_a.id",
            $this->_params[$id][2],
            $this->_params[$id][1]
          );
          $this->_qill[0][] = ts("%1 %2 %3", [
            1 => $field['title'] ?? '',
            2 => $qillop,
            3 => $qillVal,
          ]);
        }
        else {
          $this->whereClauseSingle($this->_params[$id], $isForcePrimaryEmailOnly);
        }
      }

      CRM_Core_Component::alterQuery($this, 'where');

      CRM_Contact_BAO_Query_Hook::singleton()->alterSearchQuery($this, 'where');
    }

    if ($this->_customQuery) {
      $this->_whereTables = array_merge($this->_whereTables, $this->_customQuery->_whereTables);
      // Added following if condition to avoid the wrong value display for 'my account' / any UF info.
      // Hope it wont affect the other part of civicrm.. if it does please remove it.
      if (!empty($this->_customQuery->_where)) {
        $this->_where = CRM_Utils_Array::crmArrayMerge($this->_where, $this->_customQuery->_where);
      }
      $this->_qill = CRM_Utils_Array::crmArrayMerge($this->_qill, $this->_customQuery->_qill);
    }

    $clauses = [];
    $andClauses = [];

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

    return $andClauses ? implode(' AND ', $andClauses) : ' 1 ';
  }

  /**
   * Generate where clause for any parameters not already handled.
   *
   * @param array $values
   *
   * @throws Exception
   */
  public function restWhere(&$values) {
    $name = $values[0] ?? NULL;
    $op = $values[1] ?? NULL;
    $value = $values[2] ?? NULL;
    $grouping = $values[3] ?? NULL;
    $wildcard = $values[4] ?? NULL;

    if (isset($grouping) && empty($this->_where[$grouping])) {
      $this->_where[$grouping] = [];
    }

    $multipleFields = ['url'];

    //check if the location type exists for fields
    $lType = '';
    $locType = explode('-', $name);

    if (!in_array($locType[0], $multipleFields)) {
      //add phone type if exists
      if (isset($locType[2]) && $locType[2]) {
        $locType[2] = CRM_Core_DAO::escapeString($locType[2]);
      }
    }

    $field = $this->_fields[$name] ?? NULL;

    if (!$field) {
      $field = $this->_fields[$locType[0]] ?? NULL;

      if (!$field) {
        // Strip any trailing _high & _low that might be appended.
        $realFieldName = str_replace(['_high', '_low'], '', $name);
        if (isset($this->_fields[$realFieldName])) {
          $field = $this->_fields[str_replace(['_high', '_low'], '', $realFieldName)];
          $columnName = $field['column_name'] ?? $field['name'];
          $this->dateQueryBuilder($values, $field['table_name'], $realFieldName, $columnName, $field['title']);
        }
        return;
      }
    }

    $setTables = TRUE;

    $locationType = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');
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
      $this->_tables[$aName] = $this->_whereTables[$aName] = 1;
      [$qillop, $qillVal] = self::buildQillForFieldValue('CRM_Core_DAO_Address', "state_province_id", $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $field['title'], 2 => $qillop, 3 => $qillVal]);
    }
    elseif (!empty($field['pseudoconstant'])) {
      // For the hacked fields we want to undo the hack to type to avoid missing the index by adding quotes.
      $dataType = !empty($this->legacyHackedFields[$name]) ? CRM_Utils_Type::T_INT : $field['type'];
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        'CRM_Contact_DAO_Contact',
        $field,
        $field['html']['label'] ?? $field['title'],
        CRM_Utils_Type::typeToString($dataType)
      );
      if ($name === 'gender_id') {
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
      $this->_tables[$aName] = $this->_whereTables[$aName] = 1;

      [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $name, $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $field['title'], 2 => $qillop, 3 => $qillVal]);
    }
    elseif ($name === 'world_region') {
      $this->optionValueQuery(
        $name, $op, $value, $grouping,
        NULL,
        $field,
        ts('World Region'),
        'Positive'
      );
    }
    elseif ($name === 'is_deceased') {
      $this->setQillAndWhere($name, $op, $value, $grouping, $field);
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
      $value = CRM_Core_DAO::escapeString($value);
      if ($wildcard) {
        $op = 'LIKE';
        $value = self::getWildCardedValue($wildcard, $op, $value);
      }
      CRM_Core_Error::deprecatedFunctionWarning('Untested code path');
      // @todo it's likely this code path is obsolete / never called. It is definitely not
      // passed through in our test suite.
      $this->_where[$grouping][] = self::buildClause($field['where'], $op, "'$value'");
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'current_employer') {
      if ($wildcard) {
        $op = 'LIKE';
        $value = self::getWildCardedValue($wildcard, $op, $value);
      }
      $ceWhereClause = self::buildClause("contact_a.organization_name", $op,
        $value
      );
      $ceWhereClause .= " AND contact_a.contact_type = 'Individual'";
      $this->_where[$grouping][] = $ceWhereClause;
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif (substr($name, 0, 4) === 'url-') {
      $tName = 'civicrm_website';
      $this->_whereTables[$tName] = $this->_tables[$tName] = "\nLEFT JOIN civicrm_website ON ( civicrm_website.contact_id = contact_a.id )";
      $value = CRM_Core_DAO::escapeString($value);
      if ($wildcard) {
        $op = 'LIKE';
        $value = self::getWildCardedValue($wildcard, $op, $value);
      }

      $this->_where[$grouping][] = $d = self::buildClause('civicrm_website.url', $op, $value);
      $this->_qill[$grouping][] = "$field[title] $op \"$value\"";
    }
    elseif ($name === 'contact_is_deleted') {
      $this->setQillAndWhere('is_deleted', $op, $value, $grouping, $field);
    }
    elseif (!empty($field['where'])) {
      $type = NULL;
      if (!empty($field['type'])) {
        $type = CRM_Utils_Type::typeToString($field['type']);
      }

      [$tableName, $fieldName] = explode('.', $field['where'], 2);

      if (isset($locType[1]) &&
        is_numeric($locType[1])
      ) {
        $setTables = FALSE;

        //get the location name
        [$tName, $fldName] = self::getLocationTableName($field['where'], $locType);
        $fieldName = "`$tName`.$fldName";

        // we set both _tables & whereTables because whereTables doesn't seem to do what the name implies it should
        $this->_tables[$tName] = $this->_whereTables[$tName] = 1;

      }
      else {
        if ($tableName == 'civicrm_contact') {
          $fieldName = "contact_a.{$fieldName}";
        }
        else {
          $fieldName = $field['where'];
        }
      }

      [$qillop, $qillVal] = self::buildQillForFieldValue(NULL, $field['title'], $value, $op);
      $this->_qill[$grouping][] = ts("%1 %2 %3", [
        1 => $field['title'],
        2 => $qillop,
        3 => (str_contains($op, 'NULL') || str_contains($op, 'EMPTY')) ? $qillVal : "'$qillVal'",
      ]);

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
            if (str_contains($op, 'IN')) {
              $value = [$op => $value];
            }
            // we don't know when this might happen
            else {
              throw new CRM_Core_Exception(ts("%1 is not a valid operator", [1 => $operator]));
            }
          }
        }
        $this->_where[$grouping][] = CRM_Core_DAO::createSQLFilter($fieldName, $value, $type);
      }
      else {
        if ($wildcard) {
          $op = 'LIKE';
          $value = self::getWildCardedValue($wildcard, $op, $value);
        }

        $this->_where[$grouping][] = self::buildClause($fieldName, $op, $value, $type);
      }
    }

    if ($setTables && isset($field['where'])) {
      [$tableName, $fieldName] = explode('.', $field['where'], 2);
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
      [$tbName, $fldName] = explode(".", $where);

      //get the location name
      $locationType = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');
      $specialFields = ['email', 'im', 'phone', 'openid', 'phone_ext'];
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
        [
          'address_name',
          'street_address',
          'street_name',
          'street_number_suffix',
          'street_unit',
          'supplemental_address_1',
          'supplemental_address_2',
          'supplemental_address_3',
          'city',
          'postal_code',
          'postal_code_suffix',
          'geo_code_1',
          'geo_code_2',
          'master_id',
        ]
      )) {
        //fix for search by profile with address fields.
        $tName = "{$locationType[$locType[1]]}-address";
      }
      elseif (in_array($locType[0],
          [
            'on_hold',
            'signature_html',
            'signature_text',
            'is_bulkmail',
          ]
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
      return [$tName, $fldName];
    }
    throw new CRM_Core_Exception('Cannot determine location table information');
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
    $value = [];

    foreach ($this->_element as $key => $dontCare) {
      if (property_exists($dao, $key)) {
        if (str_contains($key, '-')) {
          $values = explode('-', $key);
          $lastElement = array_pop($values);
          $current = &$value;
          $cnt = count($values);
          $count = 1;
          foreach ($values as $v) {
            if (!array_key_exists($v, $current)) {
              $current[$v] = [];
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
   * Sometimes used to create the from clause, but, not reliably, set
   * this AND set tables.
   *
   * It's unclear the intent - there is a 'simpleFrom' clause which
   * takes whereTables into account & a fromClause which doesn't.
   *
   * logic may have eroded?
   *
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
   * @throws \CRM_Core_Exception
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
   *   Tables that need to be included in this from clause. If null,
   *   return mimimal from clause (i.e. civicrm_contact).
   * @param array $inner
   *   Tables that should be inner-joined.
   * @param array $right
   *   Tables that should be right-joined.
   * @param bool $primaryLocation
   *   Search on primary location. See note below.
   * @param int $mode
   *   Determines search mode based on bitwise MODE_* constants.
   * @param string|null $apiEntity
   *   Determines search mode based on entity by string.
   * @param int $onlyDeleted
   *   Determines if we are only looking for deleted contacts
   *
   * The $primaryLocation flag only seems to be used when
   * locationType() has been called. This may be a search option
   * exposed, or perhaps it's a "search all details" approach which
   * predates decoupling of location types and primary fields?
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-19967
   *
   * @return string
   *   the from clause
   */
  public static function fromClause(&$tables, $inner = NULL, $right = NULL,
    $primaryLocation = TRUE, $mode = 1, $apiEntity = NULL, $onlyDeleted = 0) {

    $from = ' FROM civicrm_contact contact_a';
    if (empty($tables)) {
      return $from;
    }

    if (!empty($tables['civicrm_worldregion'])) {
      $tables = array_merge(['civicrm_country' => 1], $tables);
    }

    if ((!empty($tables['civicrm_state_province']) || !empty($tables['civicrm_country']) ||
        !empty($tables['civicrm_county'])) && empty($tables['civicrm_address'])) {
      $tables = array_merge(['civicrm_address' => 1],
        $tables
      );
    }

    // add group_contact and group table is subscription history is present
    if (!empty($tables['civicrm_subscription_history']) && empty($tables['civicrm_group'])) {
      $tables = array_merge([
        'civicrm_group' => 1,
        'civicrm_group_contact' => 1,
      ],
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
      if (str_contains($key, '-')) {
        $keyArray = explode('-', $key);
        $k = $info['civicrm_' . $keyArray[1]] ?? 99;
      }
      elseif (str_contains($key, '_')) {
        $keyArray = explode('_', $key);
        if (is_numeric(array_pop($keyArray))) {
          $k = CRM_Utils_Array::value(implode('_', $keyArray), $info, 99);
        }
        else {
          $k = $info[$key] ?? 99;
        }
      }
      else {
        $k = $info[$key] ?? 99;
      }
      $tempTable[$k . ".$key"] = $key;
    }
    ksort($tempTable);
    $newTables = [];
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

      $from .= ' ' . trim(self::getEntitySpecificJoins($name, $mode, $side, $primaryLocation, $onlyDeleted)) . ' ';
    }
    return $from;
  }

  /**
   * Get join statements for the from clause depending on entity type
   *
   * @param string $name
   * @param int $mode
   * @param string $side
   * @param string $primaryLocation
   * @param int $onlyDeleted
   * @return string
   */
  protected static function getEntitySpecificJoins($name, $mode, $side, $primaryLocation, $onlyDeleted = 0): string {
    $limitToPrimaryClause = $primaryLocation ? "AND {$name}.is_primary = 1" : '';
    switch ($name) {
      case 'civicrm_address':
        //CRM-14263 further handling of address joins further down...
        return " $side JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id {$limitToPrimaryClause} )";

      case 'civicrm_state_province':
        // This is encountered when doing an export after having applied a 'sort' - it pretty much implies primary
        // but that will have been implied-in by the calling function.
        // test cover in testContactIDQuery
        return " $side JOIN civicrm_state_province ON ( civicrm_address.state_province_id = civicrm_state_province.id )";

      case 'civicrm_country':
        // This is encountered when doing an export after having applied a 'sort' - it pretty much implies primary
        // but that will have been implied-in by the calling function.
        // test cover in testContactIDQuery
        return " $side JOIN civicrm_country ON ( civicrm_address.country_id = civicrm_country.id )";

      case 'civicrm_phone':
        return " $side JOIN civicrm_phone ON (contact_a.id = civicrm_phone.contact_id {$limitToPrimaryClause}) ";

      case 'civicrm_email':
        return " $side JOIN civicrm_email ON (contact_a.id = civicrm_email.contact_id {$limitToPrimaryClause})";

      case 'civicrm_im':
        return " $side JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id {$limitToPrimaryClause}) ";

      case 'im_provider':
        $from = " $side JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id) ";
        $from .= " $side JOIN civicrm_option_group option_group_imProvider ON option_group_imProvider.name = 'instant_messenger_service'";
        $from .= " $side JOIN civicrm_option_value im_provider ON (civicrm_im.provider_id = im_provider.value AND option_group_imProvider.id = im_provider.option_group_id)";
        return $from;

      case 'civicrm_openid':
        return " $side JOIN civicrm_openid ON ( civicrm_openid.contact_id = contact_a.id {$limitToPrimaryClause} )";

      case 'civicrm_worldregion':
        // We can be sure from the calling function that country will already be joined in.
        // we really don't need world_region - we could use a pseudoconstant for it.
        return " $side JOIN civicrm_worldregion ON civicrm_country.region_id = civicrm_worldregion.id ";

      case 'civicrm_location_type':
        return " $side JOIN civicrm_location_type ON civicrm_address.location_type_id = civicrm_location_type.id ";

      case 'civicrm_group':
        return " $side JOIN civicrm_group ON civicrm_group.id = civicrm_group_contact.group_id ";

      case 'civicrm_group_contact':
        return " $side JOIN civicrm_group_contact ON contact_a.id = civicrm_group_contact.contact_id ";

      case 'civicrm_group_contact_cache':
        return " $side JOIN civicrm_group_contact_cache ON contact_a.id = civicrm_group_contact_cache.contact_id ";

      case 'civicrm_activity':
      case 'civicrm_activity_tag':
      case 'activity_type':
      case 'activity_status':
      case 'parent_id':
      case 'civicrm_activity_contact':
      case 'source_contact':
      case 'activity_priority':
        return CRM_Activity_BAO_Query::from($name, $mode, $side, $onlyDeleted) ?? '';

      case 'civicrm_entity_tag':
        $from = " $side JOIN civicrm_entity_tag ON ( civicrm_entity_tag.entity_table = 'civicrm_contact'";
        return "$from AND civicrm_entity_tag.entity_id = contact_a.id ) ";

      case 'civicrm_note':
        $from = " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contact'";
        return "$from AND contact_a.id = civicrm_note.entity_id ) ";

      case 'civicrm_subscription_history':
        $from = " $side JOIN civicrm_subscription_history";
        $from .= " ON civicrm_group_contact.contact_id = civicrm_subscription_history.contact_id";
        return "$from AND civicrm_group_contact.group_id =  civicrm_subscription_history.group_id";

      case 'civicrm_relationship':
        if (self::$_relType == 'reciprocal') {
          if (self::$_relationshipTempTable) {
            // we have a temptable to join on
            $tbl = self::$_relationshipTempTable;
            return " INNER JOIN {$tbl} civicrm_relationship ON civicrm_relationship.contact_id = contact_a.id";
          }
          else {
            $from = " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = contact_a.id OR civicrm_relationship.contact_id_a = contact_a.id)";
            $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_a = contact_b.id OR civicrm_relationship.contact_id_b = contact_b.id)";
            return $from;
          }
        }
        elseif (self::$_relType == 'b') {
          $from = " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = contact_a.id )";
          return "$from $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_a = contact_b.id )";
        }
        else {
          $from = " $side JOIN civicrm_relationship ON (civicrm_relationship.contact_id_a = contact_a.id )";
          return "$from $side JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_b = contact_b.id )";
        }

      case 'civicrm_log':
        $from = " INNER JOIN civicrm_log ON (civicrm_log.entity_id = contact_a.id AND civicrm_log.entity_table = 'civicrm_contact')";
        return "$from INNER JOIN civicrm_contact contact_b_log ON (civicrm_log.modified_id = contact_b_log.id)";

      case 'civicrm_tag':
        return " $side  JOIN civicrm_tag ON civicrm_entity_tag.tag_id = civicrm_tag.id ";

      case 'civicrm_grant':
        return CRM_Grant_BAO_Query::from($name, $mode, $side);

      case 'civicrm_website':
        return " $side JOIN civicrm_website ON contact_a.id = civicrm_website.contact_id ";

      case 'civicrm_campaign':
        //Move to default case if not in either mode.
        if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
          return CRM_Contribute_BAO_Query::from($name, $mode, $side);
        }
        elseif ($mode & CRM_Contact_BAO_Query::MODE_MAILING) {
          return CRM_Mailing_BAO_Query::from($name, $mode, $side);
        }
        elseif ($mode & CRM_Contact_BAO_Query::MODE_CAMPAIGN) {
          return CRM_Campaign_BAO_Query::from($name, $mode, $side);
        }

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
          $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');
          foreach ($locationTypes as $locationTypeID => $locationType) {
            if ($parts[0] == str_replace(' ', '_', $locationType)) {
              $locationID = $locationTypeID;
            }
          }
          $from = " $side JOIN civicrm_{$locationTypeName} `{$name}` ON ( contact_a.id = `{$name}`.contact_id ) and `{$name}`.location_type_id = $locationID ";
        }
        else {
          $from = CRM_Core_Component::from($name, $mode, $side);
        }
        $from .= CRM_Contact_BAO_Query_Hook::singleton()->buildSearchfrom($name, $mode, $side);

        return $from;
    }
  }

  /**
   * WHERE / QILL clause for deleted_contacts
   *
   * @param array $values
   */
  public function deletedContacts($values) {
    [$_, $_, $value, $grouping, $_] = $values;
    if ($value) {
      // *prepend* to the relevant grouping as this is quite an important factor
      array_unshift($this->_qill[$grouping], ts('Search Deleted Contacts'));
    }
  }

  /**
   * Where / qill clause for contact_type
   *
   * @param $values
   *
   * @throws \CRM_Core_Exception
   */
  public function contactType(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $subTypes = [];
    $clause = [];

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
            [$contactType, $subType] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $k, 2);
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
      $subType = $contactTypeANDSubType[1] ?? NULL;
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
   * Where / qill clause for contact_sub_type.
   *
   * @param array $values
   */
  public function contactSubType(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    $this->includeContactSubTypes($value, $grouping, $op);
  }

  /**
   * @param $value
   * @param $grouping
   * @param string $op
   *
   * @throws \CRM_Core_Exception
   */
  public function includeContactSubTypes($value, $grouping, $op = 'LIKE') {

    if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($value);
      $value = $value[$op];
    }

    $clause = [];
    $alias = "contact_a.contact_sub_type";
    $qillOperators = CRM_Core_SelectValues::getSearchBuilderOperators();

    $op = str_replace('IN', 'LIKE', $op);
    $op = str_replace('=', 'LIKE', $op);
    $op = str_replace('!', 'NOT ', $op);

    if (str_contains($op, 'NULL') || str_contains($op, 'EMPTY')) {
      $this->_where[$grouping][] = self::buildClause($alias, $op, $value, 'String');
    }
    elseif (is_array($value)) {
      foreach ($value as $k => $v) {
        $clause[$k] = "($alias $op '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($v, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
      }
    }
    else {
      $clause[$value] = "($alias $op '%" . CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Type::escape($value, 'String') . CRM_Core_DAO::VALUE_SEPARATOR . "%')";
    }

    if (!empty($clause)) {
      $this->_where[$grouping][] = "( " . implode(' OR ', $clause) . " )";
    }
    $this->_qill[$grouping][] = ts('Contact Subtype %1 ', [1 => $qillOperators[$op]]) . implode(' ' . ts('or') . ' ', array_keys($clause));
  }

  /**
   * Where / qill clause for groups.
   *
   * @param $values
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function group($values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    // If the $value is in OK (operator as key) array format we need to extract the key as operator and value first
    if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($value);
      $value = $value[$op];
    }
    // Translate EMPTY to NULL as EMPTY is cannot be used in it's intended meaning here
    // so has to be 'squashed into' NULL. (ie. group membership cannot be '').
    // even one group might equate to multiple when looking at children so IN is simpler.
    // @todo - also look at != casting but there are rows below to review.
    $opReplacements = [
      'IS EMPTY' => 'IS NULL',
      'IS NOT EMPTY' => 'IS NOT NULL',
      '=' => 'IN',
    ];
    if (isset($opReplacements[$op])) {
      $op = $opReplacements[$op];
    }

    if (strpos($op, 'NULL')) {
      $value = NULL;
    }

    if (is_array($value) && count($value) > 1) {
      if (!str_contains($op, 'IN') && !str_contains($op, 'NULL')) {
        throw new CRM_Core_Exception(ts("%1 is not a valid operator", [1 => $op]));
      }
      $this->_useDistinct = TRUE;
    }

    if (isset($value)) {
      $value = $value[$op] ?? $value;
    }

    if ($name === 'group_type') {
      $value = array_keys($this->getGroupsFromTypeCriteria($value));
    }

    $regularGroupIDs = $smartGroupIDs = [];
    foreach ((array) $value as $id) {
      if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'saved_search_id')) {
        $smartGroupIDs[] = (int) $id;
      }
      else {
        $regularGroupIDs[] = (int) trim($id);
      }
    }
    $hasNonSmartGroups = count($regularGroupIDs);

    $isNotOp = ($op === 'NOT IN' || $op === '!=');

    $statusJoinClause = $this->getGroupStatusClause($grouping);
    // If we are searching for 'Removed' contacts then despite it being a smart group we only care about the group_contact table.
    $isGroupStatusSearch = (!empty($this->getSelectedGroupStatuses($grouping)) && $this->getSelectedGroupStatuses($grouping) !== ["'Added'"]);
    $groupClause = [];
    if ($hasNonSmartGroups || empty($value) || $isGroupStatusSearch) {
      // include child groups IDs if any
      $childGroupIds = (array) CRM_Contact_BAO_Group::getChildGroupIds($regularGroupIDs);
      foreach ($childGroupIds as $key => $id) {
        if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'saved_search_id')) {
          $smartGroupIDs[] = $id;
          unset($childGroupIds[$key]);
        }
      }
      if (count($childGroupIds)) {
        $regularGroupIDs = array_merge($regularGroupIDs, $childGroupIds);
      }

      if (empty($regularGroupIDs)) {
        if ($isGroupStatusSearch) {
          $regularGroupIDs = $smartGroupIDs;
        }
        // If it is still empty we want a filter that blocks all results.
        if (empty($regularGroupIDs)) {
          $regularGroupIDs = [0];
        }
      }

      $gcTable = '`civicrm_group_contact-' . uniqid() . "`";
      $joinClause = ["contact_a.id = {$gcTable}.contact_id"];

      // @todo consider just casting != to NOT IN & handling both together.
      if ($op === '!=') {
        $groupIds = '';
        if (!empty($regularGroupIDs)) {
          $groupIds = CRM_Utils_Type::validate(implode(',', (array) $regularGroupIDs), 'CommaSeparatedIntegers');
        }
        $clause = "{$gcTable}.contact_id NOT IN (SELECT contact_id FROM civicrm_group_contact cgc WHERE cgc.group_id = $groupIds )";
      }
      else {
        $clause = self::buildClause("{$gcTable}.group_id", $op, $regularGroupIDs);
      }
      $groupClause[] = "( {$clause} )";

      if ($statusJoinClause) {
        $joinClause[] = "{$gcTable}.$statusJoinClause";
      }
      $this->_tables[$gcTable] = $this->_whereTables[$gcTable] = " LEFT JOIN civicrm_group_contact {$gcTable} ON (" . implode(' AND ', $joinClause) . ")";
    }

    //CRM-19589: contact(s) removed from a Smart Group, resides in civicrm_group_contact table
    // If we are only searching for Removed or Pending contacts we don't need to resolve the smart group
    // as that info is in the group_contact table.
    if ((count($smartGroupIDs) || empty($value)) && !$isGroupStatusSearch) {
      $this->_groupUniqueKey = uniqid();
      $this->_groupKeys[] = $this->_groupUniqueKey;
      $gccTableAlias = "civicrm_group_contact_cache_{$this->_groupUniqueKey}";
      $groupContactCacheClause = $this->addGroupContactCache($smartGroupIDs, $gccTableAlias, "contact_a", $op);
      if (!empty($groupContactCacheClause)) {
        if ($isNotOp) {
          $groupIds = CRM_Utils_Type::validate(implode(',', (array) $smartGroupIDs), 'CommaSeparatedIntegers');
          $gcTable = "civicrm_group_contact_{$this->_groupUniqueKey}";
          $joinClause = ["contact_a.id = {$gcTable}.contact_id"];
          $this->_tables[$gcTable] = $this->_whereTables[$gcTable] = " LEFT JOIN civicrm_group_contact {$gcTable} ON (" . implode(' AND ', $joinClause) . ")";
          if (str_contains($op, 'IN')) {
            $groupClause[] = "{$gcTable}.group_id $op ( $groupIds ) AND {$gccTableAlias}.group_id IS NULL";
          }
          else {
            $groupClause[] = "{$gcTable}.group_id $op $groupIds AND {$gccTableAlias}.group_id IS NULL";
          }
        }
        $groupClause[] = " ( {$groupContactCacheClause} ) ";
      }
    }

    $and = ($op == 'IS NULL') ? ' AND ' : ' OR ';
    if (!empty($groupClause)) {
      $this->_where[$grouping][] = ' ( ' . implode($and, $groupClause) . ' ) ';
    }

    [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contact_DAO_Group', 'id', $value, $op);
    $this->_qill[$grouping][] = ts("Group(s) %1 %2", [1 => $qillop, 2 => $qillVal]);
    if (!str_contains($op, 'NULL')) {
      $this->_qill[$grouping][] = ts("Group Status %1", [1 => implode(' ' . ts('or') . ' ', $this->getSelectedGroupStatuses($grouping))]);
    }
  }

  /**
   * @return array
   */
  public function getGroupCacheTableKeys() {
    return $this->_groupKeys;
  }

  /**
   * Function translates selection of group type into a list of groups.
   * @param $value
   *
   * @return array
   */
  public function getGroupsFromTypeCriteria($value) {
    $groupIds = [];
    foreach ((array) $value as $groupTypeValue) {
      $groupList = CRM_Core_PseudoConstant::group($groupTypeValue);
      $groupIds = ($groupIds + $groupList);
    }
    return $groupIds;
  }

  /**
   * Prime smart group cache for smart groups in the search, and join
   * civicrm_group_contact_cache table into the query.
   *
   * @param array $groups IDs of groups specified in search criteria.
   * @param string $tableAlias Alias to use for civicrm_group_contact_cache table.
   * @param string $joinTable Table on which to join civicrm_group_contact_cache
   * @param string $op SQL comparison operator (NULL, IN, !=, IS NULL, etc.)
   * @param string $joinColumn Column in $joinTable on which to join civicrm_group_contact_cache.contact_id
   *
   * @return string WHERE clause component for smart group criteria.
   * @throws \CRM_Core_Exception
   */
  public function addGroupContactCache($groups, $tableAlias, $joinTable, $op, $joinColumn = 'id') {
    $isNullOp = (str_contains($op, 'NULL'));
    $groupsIds = $groups;

    $operator = ['=' => 'IN', '!=' => 'NOT IN'];
    if (!empty($operator[$op]) && is_array($groups)) {
      $op = $operator[$op];
    }
    if (!$isNullOp && !$groups) {
      return NULL;
    }
    elseif (str_contains($op, 'IN')) {
      $groups = [$op => $groups];
    }
    elseif (is_array($groups) && count($groups)) {
      $groups = ['IN' => $groups];
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
    if ($group->N == 0 && $op != 'NOT IN') {
      return NULL;
    }

    $this->_tables[$tableAlias] = $this->_whereTables[$tableAlias] = " LEFT JOIN civicrm_group_contact_cache {$tableAlias} ON {$joinTable}.{$joinColumn} = {$tableAlias}.contact_id ";

    if ($op == 'NOT IN') {
      return "{$tableAlias}.contact_id NOT IN (SELECT contact_id FROM civicrm_group_contact_cache cgcc WHERE cgcc.group_id IN ( " . implode(',', (array) $groupsIds) . " ) )";
    }
    return self::buildClause("{$tableAlias}.group_id", $op, $groups, 'Int');
  }

  /**
   * Where / qill clause for cms users
   *
   * @param $values
   */
  public function ufUser(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

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
   *
   * @throws \CRM_Core_Exception
   */
  public function tagSearch(array $values): void {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $op = 'LIKE';
    $value = "%{$value}%";
    $escapedValue = CRM_Utils_Type::escape("%{$value}%", 'String');

    $useAllTagTypes = $this->getWhereValues('all_tag_types', $grouping);
    $tagTypesText = $this->getWhereValues('tag_types_text', $grouping);

    $etTable = '`civicrm_entity_tag-' . uniqid() . '`';
    $tTable = '`civicrm_tag-' . uniqid() . '`';
    // All Tag Types will only be added as a field on the form if tags are available
    // for entities other than contact
    if ($useAllTagTypes && $useAllTagTypes[2]) {
      $this->_tables[$etTable] = $this->_whereTables[$etTable]
        = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id)
            LEFT JOIN civicrm_tag {$tTable} ON ( {$etTable}.tag_id = {$tTable}.id  )";

      // search tag in cases
      $etCaseTable = '`civicrm_entity_case_tag-' . uniqid() . '`';
      $tCaseTable = '`civicrm_case_tag-' . uniqid() . '`';
      $this->_tables[$etCaseTable] = $this->_whereTables[$etCaseTable]
        = " LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
            LEFT JOIN civicrm_case
            ON (civicrm_case_contact.case_id = civicrm_case.id
                AND civicrm_case.is_deleted = 0 )
            LEFT JOIN civicrm_entity_tag {$etCaseTable} ON ( {$etCaseTable}.entity_table = 'civicrm_case' AND {$etCaseTable}.entity_id = civicrm_case.id )
            LEFT JOIN civicrm_tag {$tCaseTable} ON ( {$etCaseTable}.tag_id = {$tCaseTable}.id  )";
      // search tag in activities
      $etActTable = '`civicrm_entity_act_tag-' . uniqid() . '`';
      $tActTable = '`civicrm_act_tag-' . uniqid() . '`';
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

      $this->_tables[$etActTable] = $this->_whereTables[$etActTable]
        = " LEFT JOIN civicrm_activity_contact
            ON ( civicrm_activity_contact.contact_id = contact_a.id AND civicrm_activity_contact.record_type_id = {$targetID} )
            LEFT JOIN civicrm_activity
            ON ( civicrm_activity.id = civicrm_activity_contact.activity_id
            AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )
            LEFT JOIN civicrm_entity_tag as {$etActTable} ON ( {$etActTable}.entity_table = 'civicrm_activity' AND {$etActTable}.entity_id = civicrm_activity.id )
            LEFT JOIN civicrm_tag {$tActTable} ON ( {$etActTable}.tag_id = {$tActTable}.id  )";

      $this->_where[$grouping][] = "({$tTable}.name $op '" . $escapedValue . "' OR {$tCaseTable}.name $op '" . $escapedValue . "' OR {$tActTable}.name $op '" . $escapedValue . "')";
      $this->_qill[$grouping][] = ts('Tag %1 %2', [1 => $tagTypesText[2], 2 => $op]) . ' ' . $value;
    }
    else {
      $etTable = '`civicrm_entity_tag-' . uniqid() . "`";
      $tTable = '`civicrm_tag-' . uniqid() . '`';
      $this->_tables[$etTable] = $this->_whereTables[$etTable] = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND
      {$etTable}.entity_table = 'civicrm_contact' )
                LEFT JOIN civicrm_tag {$tTable} ON ( {$etTable}.tag_id = {$tTable}.id  ) ";

      $this->_where[$grouping][] = self::buildClause("{$tTable}.name", $op, $value, 'String');
      $this->_qill[$grouping][] = ts('Tagged %1', [1 => $op]) . ' ' . $value;
    }
  }

  /**
   * Where / qill clause for tag.
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function tag(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    // API/Search Builder format array(operator => array(values))
    if (is_array($value)) {
      if (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }
      if (count($value) > 1) {
        $this->_useDistinct = TRUE;
      }
    }

    if (strpos($op, 'NULL') || strpos($op, 'EMPTY')) {
      $value = NULL;
    }

    $tagTree = CRM_Core_BAO_Tag::getChildTags();
    foreach ((array) $value as $tagID) {
      if (!empty($tagTree[$tagID])) {
        // make sure value is an array here (see CORE-2502)
        $value = array_unique(array_merge((array) $value, $tagTree[$tagID]));
      }
    }

    [$qillop, $qillVal] = self::buildQillForFieldValue('CRM_Core_DAO_EntityTag', "tag_id", $value, $op, ['onlyActive' => FALSE]);

    // implode array, then remove all spaces
    $value = str_replace(' ', '', implode(',', (array) $value));
    if (!empty($value)) {
      $value = CRM_Utils_Type::validate($value, 'CommaSeparatedIntegers');
    }

    $useAllTagTypes = $this->getWhereValues('all_tag_types', $grouping);
    $tagTypesText = $this->getWhereValues('tag_types_text', $grouping);

    $etTable = "`civicrm_entity_tag-" . uniqid() . "`";

    if (!empty($useAllTagTypes[2])) {
      $this->_tables[$etTable] = $this->_whereTables[$etTable]
        = " LEFT JOIN civicrm_entity_tag {$etTable} ON ( {$etTable}.entity_id = contact_a.id  AND {$etTable}.entity_table = 'civicrm_contact') ";

      // search tag in cases
      $etCaseTable = "`civicrm_entity_case_tag-" . uniqid() . "`";
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

      $this->_tables[$etCaseTable] = $this->_whereTables[$etCaseTable]
        = " LEFT JOIN civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
            LEFT JOIN civicrm_case
            ON (civicrm_case_contact.case_id = civicrm_case.id
                AND civicrm_case.is_deleted = 0 )
            LEFT JOIN civicrm_entity_tag {$etCaseTable} ON ( {$etCaseTable}.entity_table = 'civicrm_case' AND {$etCaseTable}.entity_id = civicrm_case.id ) ";
      // search tag in activities
      $etActTable = "`civicrm_entity_act_tag-" . uniqid() . "`";
      $this->_tables[$etActTable] = $this->_whereTables[$etActTable]
        = " LEFT JOIN civicrm_activity_contact
            ON ( civicrm_activity_contact.contact_id = contact_a.id AND civicrm_activity_contact.record_type_id = {$targetID} )
            LEFT JOIN civicrm_activity
            ON ( civicrm_activity.id = civicrm_activity_contact.activity_id
            AND civicrm_activity.is_deleted = 0 AND civicrm_activity.is_current_revision = 1 )
            LEFT JOIN civicrm_entity_tag as {$etActTable} ON ( {$etActTable}.entity_table = 'civicrm_activity' AND {$etActTable}.entity_id = civicrm_activity.id ) ";

      // CRM-10338
      if (in_array($op, ['IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'])) {
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
      if (in_array($op, ['IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'])) {
        // this converts IS (NOT)? EMPTY to IS (NOT)? NULL
        $op = str_replace('EMPTY', 'NULL', $op);
        $this->_where[$grouping][] = "{$etTable}.tag_id $op";
      }
      // CRM-16941: for tag tried with != operator we don't show contact who don't have given $value AND also in other tag
      elseif ($op == '!=') {
        $this->_where[$grouping][] = "{$etTable}.entity_id NOT IN (SELECT entity_id FROM civicrm_entity_tag cet WHERE cet.entity_table = 'civicrm_contact' AND " . self::buildClause("cet.tag_id", '=', $value, 'Int') . ")";
      }
      elseif ($op == '=' || str_contains($op, 'IN')) {
        $op = ($op == '=') ? 'IN' : $op;
        $this->_where[$grouping][] = "{$etTable}.tag_id $op ( $value )";
      }
    }
    $this->_qill[$grouping][] = ts('Tagged %1 %2', [1 => $qillop, 2 => $qillVal]);
  }

  /**
   * Where/qill clause for notes
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function notes(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $noteOptionValues = $this->getWhereValues('note_option', $grouping);
    $noteOption = $noteOptionValues['2'] ?? '6';
    $noteOption = ($name == 'note_body') ? 2 : (($name == 'note_subject') ? 3 : $noteOption);

    $this->_useDistinct = TRUE;

    $this->_tables['civicrm_note'] = $this->_whereTables['civicrm_note']
      = " LEFT JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contact' AND contact_a.id = civicrm_note.entity_id ) ";

    $n = trim($value);
    $value = CRM_Core_DAO::escapeString($n);
    if ($wildcard) {
      if (!str_contains($value, '%')) {
        $value = "%$value%";
      }
      $op = 'LIKE';
    }
    elseif ($op == 'IS NULL' || $op == 'IS NOT NULL') {
      $value = NULL;
    }

    $label = NULL;
    $clauses = [];
    if ($noteOption % 2 == 0) {
      $clauses[] = self::buildClause('civicrm_note.note', $op, $value, 'String');
      $label = ts('Note: Body Only');
    }
    if ($noteOption % 3 == 0) {
      $clauses[] = self::buildClause('civicrm_note.subject', $op, $value, 'String');
      $label = $label ? ts('Note: Body and Subject') : ts('Note: Subject Only');
    }
    $this->_where[$grouping][] = "( " . implode(' OR ', $clauses) . " )";
    [$qillOp, $qillVal] = self::buildQillForFieldValue(NULL, $name, $n, $op);
    $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $label, 2 => $qillOp, 3 => $qillVal]);
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
    [$fieldName, $op, $value, $grouping, $wildcard] = $values;

    // handle IS NULL / IS NOT NULL / IS EMPTY / IS NOT EMPTY
    if ($this->nameNullOrEmptyOp($fieldName, $op, $grouping)) {
      return;
    }

    $input = $value = is_array($value) ? trim($value['LIKE']) : trim($value);

    if (!strlen($value)) {
      return;
    }

    $config = CRM_Core_Config::singleton();

    $sub = [];

    //By default, $sub elements should be joined together with OR statements (don't change this variable).
    $subGlue = ' OR ';

    $firstChar = substr($value, 0, 1);
    $lastChar = substr($value, -1, 1);
    $quotes = ["'", '"'];
    // If string is quoted, strip quotes and otherwise don't alter it
    if ((strlen($value) > 2) && in_array($firstChar, $quotes) && in_array($lastChar, $quotes)) {
      $value = trim($value, implode('', $quotes));
    }
    // Replace spaces with wildcards for a LIKE operation
    // UNLESS string contains a comma (this exception is a tiny bit questionable)
    // Also need to check if there is space in between sort name.
    elseif ($op == 'LIKE' && !str_contains($value, ',') && str_contains($value, ' ')) {
      $value = str_replace(' ', '%', $value);
    }
    $value = CRM_Core_DAO::escapeString(trim($value));
    if (strlen($value)) {
      $fieldsub = [];
      $value = "'" . self::getWildCardedValue($wildcard, $op, $value) . "'";
      if ($fieldName == 'sort_name') {
        $wc = "contact_a.sort_name";
      }
      else {
        $wc = "contact_a.display_name";
      }
      $fieldsub[] = " ( $wc $op $value )";
      if ($config->includeNickNameInName) {
        $wc = "contact_a.nick_name";
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
   *
   * @throws \CRM_Core_Exception
   */
  public function greetings(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    $name .= '_display';

    [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $name, $value, $op);
    $this->_qill[$grouping][] = ts('Greeting %1 %2', [1 => $qillop, 2 => $qillVal]);
    $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $value, 'String');
  }

  /**
   * Where / qill clause for email
   *
   * @param array $values
   * @param string $isForcePrimaryOnly
   *
   * @throws \CRM_Core_Exception
   */
  protected function email(&$values, $isForcePrimaryOnly) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    $this->_tables['civicrm_email'] = $this->_whereTables['civicrm_email'] = 1;

    // CRM-18147: for Contact's GET API, email fieldname got appended with its entity as in {$apiEntiy}_{$name}
    // so following code is use build whereClause for contact's primart email id
    if (!empty($isForcePrimaryOnly)) {
      $this->_where[$grouping][] = self::buildClause('civicrm_email.is_primary', '=', 1, 'Integer');
    }
    // @todo - this should come from the $this->_fields array
    $dbName = $name === 'email_id' ? 'id' : $name;

    if (is_array($value) || $name === 'email_id') {
      $this->_qill[$grouping][] = $this->getQillForField($name, $value, $op, [], ts('Email'));
      $this->_where[$grouping][] = self::buildClause('civicrm_email.' . $dbName, $op, $value, 'String');
      return;
    }

    // Is this ever hit now? Ideally ensure always an array & handle above.
    $n = trim($value);
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
  }

  /**
   * Where / qill clause for phone number
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function phone_numeric(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    // Strip non-numeric characters; allow wildcards
    $number = preg_replace('/[^\d%]/', '', $value);
    if ($number) {
      if (!str_contains($number, '%')) {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function phone_option_group($values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    $option = ($name == 'phone_phone_type_id' ? 'phone_type_id' : 'location_type_id');
    $options = CRM_Core_DAO_Phone::buildOptions($option);
    $optionName = $options[$value];
    $this->_qill[$grouping][] = ts('Phone') . ' ' . ($name == 'phone_phone_type_id' ? ts('type') : ('location')) . " $op $optionName";
    $this->_where[$grouping][] = self::buildClause('civicrm_phone.' . substr($name, 6), $op, $value, 'Integer');
    $this->_tables['civicrm_phone'] = $this->_whereTables['civicrm_phone'] = 1;
  }

  /**
   * Where / qill clause for street_address.
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function street_address(&$values) {
    [$name, $op, $value, $grouping] = $values;

    if (!$op) {
      $op = 'LIKE';
    }

    $n = trim($value);

    if ($n) {
      if (!str_contains($value, '%')) {
        // only add wild card if not there
        $value = "%{$value}%";
      }
      $op = 'LIKE';
      $this->_where[$grouping][] = self::buildClause('civicrm_address.street_address', $op, $value, 'String');
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
   *
   * @throws \CRM_Core_Exception
   */
  public function street_number(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

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
      $value = $n;
      $this->_where[$grouping][] = self::buildClause('civicrm_address.street_number', $op, $value, 'String');
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
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $name = trim($value);
    $cond = " contact_a.sort_name LIKE '" . CRM_Core_DAO::escapeWildCardString($name) . "%'";
    $this->_where[$grouping][] = $cond;
    $this->_qill[$grouping][] = ts('Showing only Contacts starting with: \'%1\'', [1 => $name]);
  }

  /**
   * Where / qill clause for including contact ids.
   */
  public function includeContactIDs() {
    if (!$this->_includeContactIds || empty($this->_params)) {
      return;
    }

    $contactIds = [];
    foreach ($this->_params as $id => $values) {
      if (substr($values[0], 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
        $contactIds[] = substr($values[0], CRM_Core_Form::CB_PREFIX_LEN);
      }
    }
    CRM_Utils_Type::validateAll($contactIds, 'Positive');
    if (!empty($contactIds)) {
      $this->_where[0][] = ' ( contact_a.id IN (' . implode(',', $contactIds) . " ) ) ";
    }
  }

  /**
   * Where / qill clause for postal code.
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function postalCode(&$values) {
    // skip if the fields dont have anything to do with postal_code
    if (empty($this->_fields['postal_code'])) {
      return;
    }

    [$name, $op, $value, $grouping, $wildcard] = $values;

    // Handle numeric postal code range searches properly by casting the column as numeric
    if (is_numeric($value)) {
      $field = "IF (civicrm_address.postal_code REGEXP '^[0-9]{1,10}$', CAST(civicrm_address.postal_code AS UNSIGNED), 0)";
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
      $this->_qill[$grouping][] = ts('Postal code greater than or equal to \'%1\'', [1 => $value]);
    }
    elseif ($name == 'postal_code_high') {
      $this->_where[$grouping][] = " ( $field <= '$val' ) ";
      $this->_qill[$grouping][] = ts('Postal code less than or equal to \'%1\'', [1 => $value]);
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
    [$name, $op, $value, $grouping, $wildcard] = $values;

    if (is_array($value)) {
      $this->_where[$grouping][] = 'civicrm_address.location_type_id IN (' . implode(',', $value) . ')';
      $this->_tables['civicrm_address'] = 1;
      $this->_whereTables['civicrm_address'] = 1;

      $locationType = CRM_Core_DAO_Address::buildOptions('location_type_id');
      $names = [];
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
   * @throws \CRM_Core_Exception
   */
  public function country(&$values, $fromStateProvince = TRUE) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    if (!$fromStateProvince) {
      $stateValues = $this->getWhereValues('state_province', $grouping);
      if (!empty($stateValues)) {
        // return back to caller if there are state province values
        // since that handles this case
        return NULL;
      }
    }

    $countryClause = $countryQill = NULL;
    if (in_array($op, ['IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY']) || ($values && !empty($value))) {
      $this->_tables['civicrm_address'] = 1;
      $this->_whereTables['civicrm_address'] = 1;

      $countryClause = self::buildClause('civicrm_address.country_id', $op, $value, 'Positive');
      [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, 'country_id', $value, $op);
      $countryQill = ts("%1 %2 %3", [1 => 'Country', 2 => $qillop, 3 => $qillVal]);

      if (!$fromStateProvince) {
        $this->_where[$grouping][] = $countryClause;
        $this->_qill[$grouping][] = $countryQill;
      }
    }

    if ($fromStateProvince) {
      if (!empty($countryClause)) {
        return [
          $countryClause,
          " ...AND... " . $countryQill,
        ];
      }
      else {
        return [NULL, NULL];
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
    [$name, $op, $value, $grouping, $wildcard] = $values;

    if (!is_array($value)) {
      // force the county to be an array
      $value = [$value];
    }

    // check if the values are ids OR names of the counties
    $inputFormat = 'id';
    foreach ($value as $v) {
      if (!is_numeric($v)) {
        $inputFormat = 'name';
        break;
      }
    }
    $names = [];
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

    if (in_array($op, ['IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'])) {
      $clause = "civicrm_address.county_id $op";
    }
    elseif ($inputFormat == 'id') {
      $clause = 'civicrm_address.county_id IN (' . implode(',', $value) . ')';

      $county = CRM_Core_PseudoConstant::county();
      foreach ($value as $id) {
        $names[] = $county[$id] ?? NULL;
      }
    }
    else {
      $inputClause = [];
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
   * @throws \CRM_Core_Exception
   */
  public function stateProvince(&$values, $status = NULL) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $stateClause = self::buildClause('civicrm_address.state_province_id', $op, $value, 'Positive');
    $this->_tables['civicrm_address'] = 1;
    $this->_whereTables['civicrm_address'] = 1;

    $countryValues = $this->getWhereValues('country', $grouping);
    [$countryClause, $countryQill] = $this->country($countryValues, TRUE);
    if ($countryClause) {
      $clause = "( $stateClause AND $countryClause )";
    }
    else {
      $clause = $stateClause;
    }

    $this->_where[$grouping][] = $clause;
    [$qillop, $qillVal] = self::buildQillForFieldValue('CRM_Core_DAO_Address', "state_province_id", $value, $op);
    if (!$status) {
      $this->_qill[$grouping][] = ts("State/Province %1 %2 %3", [1 => $qillop, 2 => $qillVal, 3 => $countryQill]);
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
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $targetName = $this->getWhereValues('changed_by', $grouping);
    if (!$targetName) {
      return;
    }

    $name = trim($targetName[2]);
    $name = CRM_Core_DAO::escapeString($name);
    $name = $targetName[4] ? "%$name%" : $name;
    $this->_where[$grouping][] = "contact_b_log.sort_name LIKE '%$name%'";
    $this->_tables['civicrm_log'] = $this->_whereTables['civicrm_log'] = 1;
    $fieldTitle = ts('Altered By');

    [$qillop, $qillVal] = self::buildQillForFieldValue(NULL, 'changed_by', $name, 'LIKE');
    $this->_qill[$grouping][] = ts("%1 %2 '%3'", [
      1 => $fieldTitle,
      2 => $qillop,
      3 => $qillVal,
    ]);
  }

  /**
   * @param $values
   *
   * @throws \CRM_Core_Exception
   */
  public function modifiedDates($values) {
    $this->_useDistinct = TRUE;
    CRM_Core_Error::deprecatedWarning('function should not be reachable');
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
   *
   * @throws \CRM_Core_Exception
   */
  public function demographics(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

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
    [$name, $op, $value, $grouping] = $values;
    if (is_array($value)) {
      if (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }
    }
    $field = $this->_fields[$name] ?? NULL;
    CRM_Utils_Type::validate($value, 'Integer');
    $this->_where[$grouping][] = "contact_a.{$name} $op $value";
    $op = CRM_Core_SelectValues::getSearchBuilderOperators()[$op] ?? $op;
    $title = $field ? $field['title'] : $name;
    $this->_qill[$grouping][] = "$title $op $value";
  }

  /**
   * @param $values
   */
  public function privacyOptions($values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    if (empty($value) || !is_array($value)) {
      return;
    }

    // get the operator and toggle values
    $opValues = $this->getWhereValues('privacy_operator', $grouping);
    $operator = 'OR';
    if ($opValues &&
      strtolower($opValues[2] == 'AND')
    ) {
      // @todo this line is logially unreachable
      $operator = 'AND';
    }

    $toggleValues = $this->getWhereValues('privacy_toggle', $grouping);
    $compareOP = '!';
    if ($toggleValues &&
      $toggleValues[2] == 2
    ) {
      $compareOP = '';
    }

    $clauses = [];
    $qill = [];
    foreach ($value as $dontCare => $pOption) {
      $clauses[] = " ( contact_a.{$pOption} = 1 ) ";
      $field = $this->_fields[$pOption] ?? NULL;
      $title = $field ? $field['title'] : $pOption;
      $qill[] = " $title = 1 ";
    }

    $this->_where[$grouping][] = $compareOP . '( ' . implode($operator, $clauses) . ' )';
    $this->_qill[$grouping][] = $compareOP . '( ' . implode($operator, $qill) . ' )';
  }

  /**
   * @param $values
   *
   * @throws \CRM_Core_Exception
   */
  public function preferredCommunication(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    if (!is_array($value)) {
      $value = str_replace(['(', ')'], '', explode(",", $value));
    }
    elseif (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($value);
      $value = $value[$op];
    }
    [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contact_DAO_Contact', $name, $value, $op);

    if (self::caseImportant($op)) {
      $value = implode("[[:cntrl:]]|[[:cntrl:]]", (array) $value);
      $op = (str_contains($op, '!') || str_contains($op, 'NOT')) ? 'NOT RLIKE' : 'RLIKE';
      $value = "[[:cntrl:]]" . $value . "[[:cntrl:]]";
    }

    $this->_where[$grouping][] = self::buildClause("contact_a.preferred_communication_method", $op, $value);
    $this->_qill[$grouping][] = ts('Preferred Communication Method %1 %2', [1 => $qillop, 2 => $qillVal]);
  }

  /**
   * Where / qill clause for relationship.
   *
   * @param array $values
   */
  public function relationship(&$values) {
    [$name, $op, $value, $grouping, $wildcard] = $values;
    if ($this->_relationshipValuesAdded) {
      return;
    }
    // also get values array for relation_target_name
    // for relationship search we always do wildcard
    $relationType = $this->getWhereValues('relation_type_id', $grouping);
    $description = $this->getWhereValues('relation_description', $grouping);
    $targetName = $this->getWhereValues('relation_target_name', $grouping);
    $relStatus = $this->getWhereValues('relation_status', $grouping);
    $targetGroup = $this->getWhereValues('relation_target_group', $grouping);

    $nameClause = $name = NULL;
    if ($targetName) {
      $name = trim($targetName[2]);
      if (substr($name, 0, 1) == '"' &&
        substr($name, -1, 1) == '"'
      ) {
        $name = substr($name, 1, -1);
        $name = CRM_Core_DAO::escapeString($name);
        $nameClause = "= '$name'";
      }
      else {
        $name = CRM_Core_DAO::escapeString($name);
        $nameClause = "LIKE '%{$name}%'";
      }
    }

    $relTypes = $relTypesIds = [];
    if (!empty($relationType)) {
      $relationType[2] = (array) $relationType[2];
      foreach ($relationType[2] as $relType) {
        $rel = explode('_', $relType);
        self::$_relType = $rel[1];
        $params = ['id' => $rel[0]];
        $typeValues = [];
        $rTypeValue = CRM_Contact_BAO_RelationshipType::retrieve($params, $typeValues);
        if (!empty($rTypeValue)) {
          if ($rTypeValue->name_a_b == $rTypeValue->name_b_a) {
            // if we don't know which end of the relationship we are dealing with we'll create a temp table
            self::$_relType = 'reciprocal';
          }
          $relTypesIds[] = $rel[0];
          $relTypes[] = $relType;
        }
      }
    }

    // if we are creating a temp table we build our own where for the relationship table
    $relationshipTempTable = NULL;
    if (self::$_relType == 'reciprocal') {
      $where = [];
      self::$_relationshipTempTable = $relationshipTempTable = CRM_Utils_SQL_TempTable::build()
        ->createWithColumns("`contact_id` int(10) unsigned NOT NULL DEFAULT '0', `contact_id_alt` int(10) unsigned NOT NULL DEFAULT '0', id int unsigned, KEY `contact_id` (`contact_id`), KEY `contact_id_alt` (`contact_id_alt`)")
        ->getName();
      if ($nameClause) {
        $where[$grouping][] = " sort_name $nameClause ";
      }
      $groupJoinTable = "civicrm_relationship";
      $groupJoinColumn = "contact_id_alt";
    }
    else {
      $where = &$this->_where;
      if ($nameClause) {
        $where[$grouping][] = "( contact_b.sort_name $nameClause AND contact_b.id != contact_a.id )";
      }
      $groupJoinTable = "contact_b";
      $groupJoinColumn = "id";
    }
    $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, NULL, TRUE, 'label', FALSE);
    if ($nameClause || !$targetGroup) {
      if (!empty($relationType)) {
        $relQill = '';
        foreach ($relTypes as $rel) {
          if (!empty($relQill)) {
            $relQill .= ' OR ';
          }
          $relQill .= $allRelationshipType[$rel];
        }
        $this->_qill[$grouping][] = 'Relationship Type(s) ' . $relQill . " $name";
      }
      elseif ($name) {
        $this->_qill[$grouping][] = $name;
      }
    }

    //check to see if the target contact is in specified group
    if ($targetGroup) {
      //add contacts from static groups
      $this->_tables['civicrm_relationship_group_contact'] = $this->_whereTables['civicrm_relationship_group_contact']
        = " LEFT JOIN civicrm_group_contact civicrm_relationship_group_contact ON civicrm_relationship_group_contact.contact_id = {$groupJoinTable}.{$groupJoinColumn} AND civicrm_relationship_group_contact.status = 'Added'";
      $groupWhere[] = "( civicrm_relationship_group_contact.group_id IN  (" .
        implode(",", $targetGroup[2]) . ") ) ";

      //add contacts from saved searches
      $ssWhere = $this->addGroupContactCache($targetGroup[2], "civicrm_relationship_group_contact_cache", $groupJoinTable, $op, $groupJoinColumn);

      //set the group where clause
      if ($ssWhere) {
        $groupWhere[] = "( " . $ssWhere . " )";
      }
      $this->_where[$grouping][] = "( " . implode(" OR ", $groupWhere) . " )";

      //Get the names of the target groups for the qill
      $groupNames = CRM_Core_PseudoConstant::group();
      $qillNames = [];
      foreach ($targetGroup[2] as $groupId) {
        if (array_key_exists($groupId, $groupNames)) {
          $qillNames[] = $groupNames[$groupId];
        }
      }
      if (!empty($relationType)) {
        $relQill = '';
        foreach ($relTypes as $rel) {
          if (!empty($relQill)) {
            $relQill .= ' OR ';
          }
          $relQill .= $allRelationshipType[$rel] ?? '';
        }
        $this->_qill[$grouping][] = 'Relationship Type(s) ' . $relQill . " ( " . implode(", ", $qillNames) . " )";
      }
      else {
        $this->_qill[$grouping][] = implode(", ", $qillNames);
      }
    }

    // Description
    if (!empty($description[2]) && trim($description[2])) {
      $this->_qill[$grouping][] = ts('Relationship description - %1', [1 => $description[2]]);
      $description = CRM_Core_DAO::escapeString(trim($description[2]));
      $where[$grouping][] = "civicrm_relationship.description LIKE '%{$description}%'";
    }

    // Note we do not currently set mySql to handle timezones, so doing this the old-fashioned way
    $today = date('Ymd');
    //check for active, inactive and all relation status
    if (empty($relStatus[2])) {
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
    if (in_array(['deleted_contacts', '=', '1', '0', '0'], $this->_params)) {
      $onlyDeleted = 1;
    }
    $where[$grouping][] = "(contact_b.is_deleted = {$onlyDeleted})";

    $this->addRelationshipPermissionClauses($grouping, $where);
    $this->addRelationshipDateClauses($grouping, $where);
    $this->addRelationshipActivePeriodClauses($grouping, $where);
    if (!empty($relTypes)) {
      $where[$grouping][] = 'civicrm_relationship.relationship_type_id IN (' . implode(',', $relTypesIds) . ')';
    }
    $this->_tables['civicrm_relationship'] = $this->_whereTables['civicrm_relationship'] = 1;
    $this->_useDistinct = TRUE;
    $this->_relationshipValuesAdded = TRUE;
    // it could be a or b, using an OR creates an unindexed join - better to create a temp table &
    // join on that,
    if ($relationshipTempTable) {
      $whereClause = '';
      if (!empty($where[$grouping])) {
        $whereClause = ' WHERE ' . implode(' AND ', $where[$grouping]);
        $whereClause = str_replace('contact_b', 'c', $whereClause);
      }
      $sql = "
        INSERT INTO {$relationshipTempTable} (contact_id, contact_id_alt, id)
          (SELECT contact_id_b as contact_id, contact_id_a as contact_id_alt, civicrm_relationship.id
            FROM civicrm_relationship
            INNER JOIN  civicrm_contact c ON civicrm_relationship.contact_id_a = c.id
            $whereClause )
          UNION
            (SELECT contact_id_a as contact_id, contact_id_b as contact_id_alt, civicrm_relationship.id
            FROM civicrm_relationship
            INNER JOIN civicrm_contact c ON civicrm_relationship.contact_id_b = c.id
            $whereClause )
      ";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Add relationship permission criteria to where clause.
   *
   * @param string $grouping
   * @param array $where Array to add "where" criteria to, in case you are generating a temp table.
   *   Not the main query.
   */
  public function addRelationshipPermissionClauses($grouping, &$where) {
    $relPermission = $this->getWhereValues('relation_permission', $grouping);
    if ($relPermission) {
      if (!is_array($relPermission[2])) {
        // this form value was scalar in previous versions of Civi
        $relPermission[2] = [$relPermission[2]];
      }
      $where[$grouping][] = "(civicrm_relationship.is_permission_a_b IN (" . implode(",", $relPermission[2]) . "))";

      $allRelationshipPermissions = CRM_Contact_BAO_Relationship::buildOptions('is_permission_a_b');

      $relPermNames = array_intersect_key($allRelationshipPermissions, array_flip($relPermission[2]));
      $this->_qill[$grouping][] = ts('Permissioned Relationships') . ' - ' . implode(' OR ', $relPermNames);
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
    foreach (['start_date', 'end_date'] as $dateField) {
      $dateValueLow = $this->getWhereValues('relationship_' . $dateField . '_low', $grouping);
      $dateValueHigh = $this->getWhereValues('relationship_' . $dateField . '_high', $grouping);
      if (!empty($dateValueLow)) {
        $date = date('Ymd', strtotime($dateValueLow[2]));
        $where[$grouping][] = "civicrm_relationship.$dateField >= $date";
        $this->_qill[$grouping][] = ($dateField == 'end_date' ? ts('Relationship Ended on or After') : ts('Relationship Recorded Start Date On or After')) . " " . CRM_Utils_Date::customFormat($date);
      }
      if (!empty($dateValueHigh)) {
        $date = date('Ymd', strtotime($dateValueHigh[2]));
        $where[$grouping][] = "civicrm_relationship.$dateField <= $date";
        $this->_qill[$grouping][] = ($dateField == 'end_date' ? ts('Relationship Ended on or Before') : ts('Relationship Recorded Start Date On or Before')) . " " . CRM_Utils_Date::customFormat($date);
      }
    }
  }

  /**
   * Add start & end active period criteria in
   * @param string $grouping
   * @param array $where
   *   = array to add where clauses to, in case you are generating a temp table.
   * not the main query.
   */
  public function addRelationshipActivePeriodClauses($grouping, &$where) {
    $dateValues = [];
    $dateField = 'active_period_date';

    $dateValueLow = $this->getWhereValues('relation_active_period_date_low', $grouping);
    $dateValueHigh = $this->getWhereValues('relation_active_period_date_high', $grouping);
    $dateValueLowFormated = $dateValueHighFormated = NULL;
    if (!empty($dateValueLow) && !empty($dateValueHigh)) {
      $dateValueLowFormated = date('Ymd', strtotime($dateValueLow[2]));
      $dateValueHighFormated = date('Ymd', strtotime($dateValueHigh[2]));
      $this->_qill[$grouping][] = (ts('Relationship was active between')) . " " . CRM_Utils_Date::customFormat($dateValueLowFormated) . " and " . CRM_Utils_Date::customFormat($dateValueHighFormated);
    }
    elseif (!empty($dateValueLow)) {
      $dateValueLowFormated = date('Ymd', strtotime($dateValueLow[2]));
      $this->_qill[$grouping][] = (ts('Relationship was active after')) . " " . CRM_Utils_Date::customFormat($dateValueLowFormated);
    }
    elseif (!empty($dateValueHigh)) {
      $dateValueHighFormated = date('Ymd', strtotime($dateValueHigh[2]));
      $this->_qill[$grouping][] = (ts('Relationship was active before')) . " " . CRM_Utils_Date::customFormat($dateValueHighFormated);
    }

    if ($activePeriodClauses = self::getRelationshipActivePeriodClauses($dateValueLowFormated, $dateValueHighFormated, TRUE)) {
      $where[$grouping][] = $activePeriodClauses;
    }
  }

  /**
   * Get start & end active period criteria
   *
   * @param $from
   * @param $to
   * @param $forceTableName
   *
   * @return string
   */
  public static function getRelationshipActivePeriodClauses($from, $to, $forceTableName) {
    $tableName = $forceTableName ? 'civicrm_relationship.' : '';
    if (!is_null($from) && !is_null($to)) {
      return '(((' . $tableName . 'start_date >= ' . $from . ' AND ' . $tableName . 'start_date <= ' . $to . ') OR
                (' . $tableName . 'end_date >= ' . $from . ' AND ' . $tableName . 'end_date <= ' . $to . ') OR
                (' . $tableName . 'start_date <= ' . $from . ' AND ' . $tableName . 'end_date >= ' . $to . ' )) OR
               (' . $tableName . 'start_date IS NULL AND ' . $tableName . 'end_date IS NULL) OR
               (' . $tableName . 'start_date IS NULL AND ' . $tableName . 'end_date >= ' . $from . ') OR
               (' . $tableName . 'end_date IS NULL AND ' . $tableName . 'start_date <= ' . $to . '))';
    }
    elseif (!is_null($from)) {
      return '((' . $tableName . 'start_date >= ' . $from . ') OR
               (' . $tableName . 'start_date IS NULL AND ' . $tableName . 'end_date IS NULL) OR
               (' . $tableName . 'start_date IS NULL AND ' . $tableName . 'end_date >= ' . $from . '))';
    }
    elseif (!is_null($to)) {
      return '((' . $tableName . 'start_date <= ' . $to . ') OR
               (' . $tableName . 'start_date IS NULL AND ' . $tableName . 'end_date IS NULL) OR
               (' . $tableName . 'end_date IS NULL AND ' . $tableName . 'start_date <= ' . $to . '))';
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
      self::$_defaultReturnProperties = [];
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
        self::$_defaultReturnProperties[$mode] = [
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
          'birth_date' => 1,
          'gender_id' => 1,
          'street_address' => 1,
          'supplemental_address_1' => 1,
          'supplemental_address_2' => 1,
          'supplemental_address_3' => 1,
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
          'contact_source' => 1,
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
        ];
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
   * @throws \CRM_Core_Exception
   */
  public static function getQuery($params = NULL, $returnProperties = NULL, $count = FALSE) {
    $query = new CRM_Contact_BAO_Query($params, $returnProperties);
    [$select, $from, $where, $having] = $query->query();
    $groupBy = ($query->_useGroupBy) ? 'GROUP BY contact_a.id' : '';

    $query = "$select $from $where $groupBy $having";
    return $query;
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
   * @param string $apiEntity
   *   The api entity being called.
   *   This sort-of duplicates $mode in a confusing way. Probably not by design.
   *
   * @param bool|null $primaryLocationOnly
   *
   * @return array
   * @throws \CRM_Core_Exception
   *
   * @deprecated since 5.71 - will be removed after all core usages are fully removed.
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
    $mode = CRM_Contact_BAO_Query::MODE_CONTACTS,
    $apiEntity = NULL,
    $primaryLocationOnly = NULL
  ) {

    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, TRUE, FALSE, $mode,
      $skipPermissions,
      TRUE, $smartGroupCache,
      NULL, 'AND',
      $apiEntity, $primaryLocationOnly
    );

    //this should add a check for view deleted if permissions are enabled
    if ($skipPermissions) {
      $query->_skipDeleteClause = TRUE;
    }
    $query->generatePermissionClause(FALSE, $count);

    // note : this modifies _fromClause and _simpleFromClause
    $query->includePseudoFieldsJoin($sort);

    [$select, $from, $where, $having] = $query->query($count);

    if (!empty($query->_permissionWhereClause)) {
      if (!empty($query->_permissionFromClause) && !stripos($from, 'aclContactCache')) {
        $from .= " $query->_permissionFromClause";
      }
      if (empty($where)) {
        $where = "WHERE $query->_permissionWhereClause";
      }
      else {
        $where = "$where AND $query->_permissionWhereClause";
      }
    }

    $sql = "$select $from $where $having";

    // add group by only when API action is not getcount
    //  otherwise query fetches incorrect count
    if ($query->_useGroupBy && !$count) {
      $sql .= self::getGroupByFromSelectColumns($query->_select, 'contact_a.id');
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

    $values = [];
    while ($dao->fetch()) {
      if ($count) {
        $noRows = $dao->rowCount;
        return [$noRows, NULL];
      }
      $val = $query->store($dao);
      $convertedVals = $query->convertToPseudoNames($dao, TRUE, TRUE);

      if (!empty($convertedVals)) {
        $val = array_replace_recursive($val, $convertedVals);
      }
      $values[$dao->$entityIDField] = $val;
    }
    return [$values];
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
      [$from, $to] = CRM_Utils_Date::getFromTo($values, NULL, NULL);
    }
    else {
      if ($fieldName == $customFieldName . '_to' && !empty($formValues[$customFieldName . '_from'])) {
        // Both to & from are set. We only need to acton one, choosing from.
        return;
      }

      $from = $formValues[$customFieldName . '_from'] ?? NULL;
      $to = $formValues[$customFieldName . '_to'] ?? NULL;

      if (self::isCustomDateField($customFieldName)) {
        [$from, $to] = CRM_Utils_Date::getFromTo(NULL, $from, $to);
      }
    }

    if ($from) {
      if ($to) {
        $relativeFunction = ['BETWEEN' => [$from, $to]];
      }
      else {
        $relativeFunction = ['>=' => $from];
      }
    }
    else {
      $relativeFunction = ['<=' => $to];
    }
    $params[] = [
      $customFieldName,
      '=',
      $relativeFunction,
      0,
      0,
    ];
  }

  /**
   * Are we dealing with custom field of type date.
   *
   * @param $fieldName
   *
   * @return bool
   * @throws Exception
   */
  public static function isCustomDateField($fieldName) {
    if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($fieldName)) == FALSE) {
      return FALSE;
    }
    try {
      $customFieldData = CRM_Core_BAO_CustomField::getField($customFieldID);
      if ($customFieldData && 'Date' == $customFieldData['data_type']) {
        return TRUE;
      }
    }
    catch (Exception $e) {
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
    $operator = $values[1] ?? FALSE;
    if (!$operator) {
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
   * @see https://issues.civicrm.org/jira/browse/CRM-18125
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
   * For some special cases, grouping by subset of select fields becomes mandatory.
   * Hence, full_group_by mode is handled by appending any_value
   * keyword to select fields not present in groupBy
   *
   * @param array $selectClauses
   * @param array $groupBy - Columns already included in GROUP By clause.
   * @param string $aggregateFunction
   *
   * @return string
   */
  public static function appendAnyValueToSelect($selectClauses, $groupBy, $aggregateFunction = 'ANY_VALUE') {
    if (!CRM_Utils_SQL::disableFullGroupByMode()) {
      $groupBy = array_map('trim', (array) $groupBy);
      $aggregateFunctions = '/(ROUND|AVG|COUNT|GROUP_CONCAT|SUM|MAX|MIN|IF)[[:blank:]]*\(/i';
      foreach ($selectClauses as $key => &$val) {
        [$selectColumn, $alias] = array_pad(preg_split('/ as /i', $val), 2, NULL);
        // append ANY_VALUE() keyword
        if (!in_array($selectColumn, $groupBy) && preg_match($aggregateFunctions, trim($selectColumn)) !== 1) {
          $val = ($aggregateFunction == 'GROUP_CONCAT') ?
            str_replace($selectColumn, "$aggregateFunction(DISTINCT {$selectColumn})", $val) :
            str_replace($selectColumn, "$aggregateFunction({$selectColumn})", $val);
        }
      }
    }

    return "SELECT " . implode(', ', $selectClauses) . " ";
  }

  /**
   * For some special cases, where if non-aggregate ORDER BY columns are not present in GROUP BY
   *  on full_group_by mode, then append the those missing columns to GROUP BY clause
   * keyword to select fields not present in groupBy
   *
   * @param string $groupBy - GROUP BY clause where missing ORDER BY columns will be appended if not present
   * @param array $orderBys - ORDER BY sub-clauses
   *
   */
  public static function getGroupByFromOrderBy(&$groupBy, $orderBys) {
    if (!CRM_Utils_SQL::disableFullGroupByMode()) {
      foreach ($orderBys as $orderBy) {
        // remove sort syntax from ORDER BY clauses if present
        $orderBy = str_ireplace([' DESC', ' ASC', '`'], '', $orderBy);
        // if ORDER BY column is not present in GROUP BY then append it to end
        if (preg_match('/(MAX|MIN)\(/i', trim($orderBy)) !== 1 && !strstr($groupBy, $orderBy)) {
          $groupBy .= ", {$orderBy}";
        }
      }
    }
  }

  /**
   * Include select columns in groupBy clause.
   *
   * @param array $selectClauses
   * @param array|string|null $groupBy - Columns already included in GROUP By clause.
   *
   * @return string
   */
  public static function getGroupByFromSelectColumns($selectClauses, $groupBy = NULL) {
    $groupBy = (array) $groupBy;
    $sqlMode = CRM_Core_DAO::singleValueQuery('SELECT @@sql_mode');

    //return if ONLY_FULL_GROUP_BY is not enabled.
    if (CRM_Utils_SQL::supportsFullGroupBy() && !empty($sqlMode) && in_array('ONLY_FULL_GROUP_BY', explode(',', $sqlMode))) {
      $regexToExclude = '/(ROUND|AVG|COUNT|GROUP_CONCAT|SUM|MAX|MIN|IF)[[:blank:]]*\(/i';
      foreach ($selectClauses as $key => $val) {
        $aliasArray = preg_split('/ as /i', $val);
        // if more than 1 alias we need to split by ','.
        if (count($aliasArray) > 2) {
          $aliasArray = preg_split('/,/', $val);
          foreach ($aliasArray as $key => $value) {
            $alias = current(preg_split('/ as /i', $value));
            if (!in_array($alias, $groupBy) && preg_match($regexToExclude, trim($alias)) !== 1) {
              $groupBy[] = $alias;
            }
          }
        }
        else {
          [$selectColumn, $alias] = array_pad($aliasArray, 2, NULL);
          $dateRegex = '/^(DATE_FORMAT|DATE_ADD|CASE)/i';
          $tableName = current(explode('.', $selectColumn));
          $primaryKey = "{$tableName}.id";
          // exclude columns which are already included in groupBy and aggregate functions from select
          // CRM-18439 - Also exclude the columns which are functionally dependent on columns in $groupBy (MySQL 5.7+)
          if (!in_array($selectColumn, $groupBy) && !in_array($primaryKey, $groupBy) && preg_match($regexToExclude, trim($selectColumn)) !== 1) {
            if (!empty($alias) && preg_match($dateRegex, trim($selectColumn))) {
              $groupBy[] = $alias;
            }
            else {
              $groupBy[] = $selectColumn;
            }
          }
        }
      }
    }

    if (!empty($groupBy)) {
      return " GROUP BY " . implode(', ', $groupBy);
    }
    return '';
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
   * @return string|null|CRM_Core_DAO
   */
  public function searchQuery(
    $offset = 0, $rowCount = 0, $sort = NULL,
    $count = FALSE, $includeContactIds = FALSE,
    $sortByChar = FALSE, $groupContacts = FALSE,
    $returnQuery = FALSE,
    $additionalWhereClause = NULL, $sortOrder = NULL,
    $additionalFromClause = NULL, $skipOrderAndLimit = FALSE
  ) {

    $query = $this->getSearchSQL($offset, $rowCount, $sort, $count, $includeContactIds, $sortByChar, $groupContacts, $additionalWhereClause, $sortOrder, $additionalFromClause, $skipOrderAndLimit);

    if ($returnQuery) {
      return $query;
    }
    if ($count) {
      return CRM_Core_DAO::singleValueQuery($query);
    }

    $dao = CRM_Core_DAO::executeQuery($query);

    // We can always call this - it will only re-enable if it was originally enabled.
    CRM_Core_DAO::reenableFullGroupByMode();

    if ($groupContacts) {
      $ids = [];
      while ($dao->fetch()) {
        $ids[] = $dao->id;
      }
      return implode(',', $ids);
    }

    return $dao;
  }

  /**
   * Create and query the db for the list of all first letters used by contacts
   *
   * @return CRM_Core_DAO
   */
  public function alphabetQuery() {
    $sqlParts = $this->getSearchSQLParts(NULL, NULL, NULL, FALSE, FALSE, TRUE);
    $query = "SELECT DISTINCT LEFT(contact_a.sort_name, 1) as sort_name
      {$sqlParts['from']}
      {$sqlParts['where']}";
    $dao = CRM_Core_DAO::executeQuery($query);
    return $dao;
  }

  /**
   * Fetch a list of contacts for displaying a search results page
   *
   * @param array $cids
   *   List of contact IDs
   * @param bool $includeContactIds
   * @return CRM_Core_DAO
   */
  public function getCachedContacts($cids, $includeContactIds) {
    CRM_Core_DAO::disableFullGroupByMode();
    CRM_Utils_Type::validateAll($cids, 'Positive');
    $this->_includeContactIds = $includeContactIds;
    $onlyDeleted = in_array(['deleted_contacts', '=', '1', '0', '0'], $this->_params);
    [$select, $from, $where] = $this->query(FALSE, FALSE, FALSE, $onlyDeleted);
    $select .= sprintf(", (%s) AS _wgt", $this->createSqlCase('contact_a.id', $cids));
    $where .= sprintf(' AND contact_a.id IN (%s)', implode(',', $cids));
    $order = 'ORDER BY _wgt';
    $groupBy = $this->_useGroupBy ? ' GROUP BY contact_a.id' : '';
    $limit = '';
    $query = "$select $from $where $groupBy $order $limit";

    $result = CRM_Core_DAO::executeQuery($query);
    CRM_Core_DAO::reenableFullGroupByMode();
    return $result;
  }

  /**
   * Construct a SQL CASE expression.
   *
   * @param string $idCol
   *   The name of a column with ID's (eg 'contact_a.id').
   * @param array $cids
   *   Array(int $weight => int $id).
   * @return string
   *   CASE WHEN id=123 THEN 1 WHEN id=456 THEN 2 END
   */
  private function createSqlCase($idCol, $cids) {
    $buf = "CASE\n";
    foreach ($cids as $weight => $cid) {
      $buf .= " WHEN $idCol = $cid THEN $weight \n";
    }
    $buf .= "END\n";
    return $buf;
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
      $permissionClauses = CRM_Contact_BAO_Contact_Permission::cacheClause();
      $this->_permissionWhereClause = $permissionClauses[1];
      $this->_permissionFromClause = $permissionClauses[0] ?: '';

      if (CRM_Core_Permission::check('access deleted contacts')) {
        if (!$onlyDeleted) {
          $this->_permissionWhereClause .= ' AND (contact_a.is_deleted = 0)';
        }
        else {
          $this->_permissionWhereClause .= " AND (contact_a.is_deleted) ";
        }
      }

      // Add ACL permission clause generated by the BAO. This is the same clause used by API::get.
      // TODO: Why only activities and contributions?. Not likely to change now as we move away from this.
      if (isset($this->_tables['civicrm_activity'])) {
        $clauses = array_filter(CRM_Activity_BAO_Activity::getSelectWhereClause());
        if ($clauses) {
          $this->_permissionWhereClause .= ($this->_permissionWhereClause ? ' AND ' : '') . '(' . implode(' AND ', $clauses) . ')';
        }
      }
      if (isset($this->_tables['civicrm_contribution'])) {
        $contributionClauses = array_filter(CRM_Contribute_BAO_Contribution::getSelectWhereClause());
        if (!empty($contributionClauses)) {
          $this->_permissionWhereClause .= ($this->_permissionWhereClause ? ' AND ' : '') . '(' . implode(' AND ', $contributionClauses) . ')';
        }
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
   * @param string|null $context
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function summaryContribution(?string $context = NULL): array {
    [, $from, $where] = $this->query(TRUE);
    if ($context === 'search') {
      $where .= " AND contact_a.is_deleted = 0 ";
    }

    $summary = ['total' => [], 'soft_credit' => ['count' => 0, 'avg' => 0, 'amount' => 0]];
    $this->addBasicStatsToSummary($summary, $where, $from);

    if (CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled()) {
      $this->addBasicSoftCreditStatsToStats($summary, $where, $from);
    }

    $this->addBasicCancelStatsToSummary($summary, $where, $from);

    return $summary;
  }

  /**
   * Getter for the qill object.
   *
   * @return array
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
      self::$_defaultHierReturnProperties = [
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
        'location' => [
          '1' => [
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
          ],
          '2' => [
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
          ],
        ],
      ];
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
   * @param string $dateFormat
   * @param string|null $highDBFieldName
   *   Optional field name for when the 'high' part of the calculation uses a different field than the 'low' part.
   *   This is an obscure situation & one we don't want to do more of but supporting them here is the only way for now.
   *   Examples are event date & relationship active date -in both cases we are looking for things greater than the start
   *   date & less than the end date.
   *
   * @throws \CRM_Core_Exception
   */
  public function dateQueryBuilder(
    $values, $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $appendTimeStamp = TRUE,
    $dateFormat = 'YmdHis',
    $highDBFieldName = NULL
  ) {
    // @todo - remove dateFormat - pretty sure it's never passed in...
    [$name, $op, $value, $grouping, $wildcard] = $values;
    if ($tableName === 'civicrm_contact') {
      // Special handling for contact table as it has a known alias in advanced search.
      $tableName = 'contact_a';
    }
    if ($name === "{$fieldName}_low" ||
      $name === "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName]) || !$value) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = $secondDate = $secondDateFormat = NULL;

      if ($name == $fieldName . '_low') {
        $firstOP = '>=';
        $firstPhrase = ts('greater than or equal to');
        $firstDate = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '<=';
          $secondPhrase = ts('less than or equal to');
          $secondValue = $secondValues[2];

          if ($appendTimeStamp && strlen($secondValue) == 10) {
            $secondValue .= ' 23:59:59';
          }
          $secondDate = CRM_Utils_Date::processDate($secondValue, NULL, FALSE, $dateFormat);
        }
      }
      elseif ($name == $fieldName . '_high') {
        $firstOP = '<=';
        $firstPhrase = ts('less than or equal to');

        if ($appendTimeStamp && strlen($value) == 10) {
          $value .= ' 23:59:59';
        }
        $firstDate = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = CRM_Utils_Date::processDate($secondValue, NULL, FALSE, $dateFormat);
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

      if ($secondDate) {
        $highDBFieldName ??= $dbFieldName;
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP '$firstDate' ) AND
( {$tableName}.{$highDBFieldName} $secondOP '$secondDate' )
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
      if (str_contains($op, 'IN')) {
        $format = [];
        foreach ($value as &$date) {
          $date = CRM_Utils_Date::processDate($date, NULL, FALSE, $dateFormat);
          if (!$appendTimeStamp) {
            $date = substr($date, 0, 8);
          }
          $format[] = CRM_Utils_Date::customFormat($date);
        }
        $date = "('" . implode("','", $value) . "')";
        $format = implode(', ', $format);
      }
      elseif ($value && (!strstr($op, 'NULL') && !strstr($op, 'EMPTY'))) {
        $date = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);
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
        $this->_where[$grouping][] = self::buildClause("{$tableName}.{$dbFieldName}", $op);
      }

      $op = CRM_Core_SelectValues::getSearchBuilderOperators()[$op] ?? $op;
      $this->_qill[$grouping][] = "$fieldTitle $op $format";
    }

    // Ensure the tables are set, but don't whomp anything.
    $this->_tables[$tableName] ??= 1;
    $this->_whereTables[$tableName] ??= 1;
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
    [$name, $op, $value, $grouping, $wildcard] = $values;

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
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $asofDateValues = $this->getWhereValues("{$fieldName}_asof_date", $grouping);
    // will be treated as current day
    $asofDate = '';
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
   * @throws \Exception
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
   * @param bool $isAlreadyEscaped
   *   Ideally we would be consistent about whether we escape
   *   before calling this or after, but the code is a fearsome beast
   *   and poking the sleeping dragon could throw a cat among the
   *   can of worms. Hence we just schmooze this parameter in
   *   to prevent double escaping where it is known to occur.
   *
   * @return string
   *   Where clause for the query.
   * @throws \CRM_Core_Exception
   */
  public static function buildClause($field, $op, $value = NULL, $dataType = 'String', $isAlreadyEscaped = FALSE): string {
    $op = trim($op);
    $clause = "$field $op";

    switch ($op) {
      case 'IS NULL':
      case 'IS NOT NULL':
        return $clause;

      case 'IS EMPTY':
        $clause = ($dataType === 'Date') ? " $field IS NULL " : " (NULLIF($field, '') IS NULL) ";
        return $clause;

      case 'IS NOT EMPTY':
        return ($dataType === 'Date') ? " $field IS NOT NULL " : " (NULLIF($field, '') IS NOT NULL) ";

      case 'RLIKE':
        return " CAST({$field} AS BINARY) RLIKE BINARY '{$value}' ";

      case 'IN':
      case 'NOT IN':
        // I feel like this would be escaped properly if passed through $queryString = CRM_Core_DAO::createSqlFilter.
        if (!empty($value) && (!is_array($value) || !array_key_exists($op, $value))) {
          $value = [$op => (array) $value];
        }

      default:
        if (empty($dataType) || $dataType == 'Date') {
          $dataType = 'String';
        }
        if (is_array($value)) {
          //this could have come from the api - as in the restWhere section we potentially use the api operator syntax which is becoming more
          // widely used and consistent across the codebase
          // adding this here won't accept the search functions which don't submit an array
          if (($queryString = CRM_Core_DAO::createSQLFilter($field, $value, $dataType)) != FALSE) {

            return $queryString;
          }
          throw new CRM_Core_Exception(ts('Failed to interpret input for search'));
        }
        $emojiWhere = CRM_Utils_SQL::handleEmojiInQuery($value);
        if ($emojiWhere === '0 = 1') {
          $value = $emojiWhere;
        }
        $value = $isAlreadyEscaped ? $value : CRM_Utils_Type::escape($value, $dataType);
        // if we don't have a dataType we should assume
        if ($dataType === 'String' || $dataType === 'Text') {
          $value = "'" . $value . "'";
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
    $panesMapper = [
      ts('Contributions') => 'civicrm_contribution',
      ts('Memberships') => 'civicrm_membership',
      ts('Events') => 'civicrm_participant',
      ts('Relationships') => 'civicrm_relationship',
      ts('Activities') => 'civicrm_activity',
      ts('Pledges') => 'civicrm_pledge',
      ts('Cases') => 'civicrm_case',
      ts('Address Fields') => 'civicrm_address',
      ts('Notes') => 'civicrm_note',
      ts('Change Log') => 'civicrm_log',
      ts('Mailings') => 'civicrm_mailing',
    ];
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
    $validOperators = ['AND', 'OR'];
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
    if (!isset(Civi::$statics[__CLASS__]['related_contacts_filter'])) {
      Civi::$statics[__CLASS__]['related_contacts_filter'] = [];
    }
    $_rTempCache =& Civi::$statics[__CLASS__]['related_contacts_filter'];
    // since there only can be one instance of this filter in every query
    // skip if filter has already applied
    foreach ($_rTempCache as $acache) {
      foreach ($acache['queries'] as $aqcache) {
        if (str_contains($from, $aqcache['from'])) {
          $having = NULL;
          return;
        }
      }
    }
    $arg_sig = sha1("$from $where $having");
    if (isset($_rTempCache[$arg_sig])) {
      $cache = $_rTempCache[$arg_sig];
    }
    else {
      // create temp table with contact ids

      $tableName = CRM_Utils_SQL_TempTable::build()->createWithColumns('contact_id int primary key')->setMemory(TRUE)->getName();

      $sql = "
REPLACE INTO $tableName ( contact_id )
SELECT contact_a.id
       $from
       $where
       $having
";
      CRM_Core_DAO::executeQuery($sql);

      $cache = ['tableName' => $tableName, 'queries' => []];
      $_rTempCache[$arg_sig] = $cache;
    }
    // upsert the query depending on relationship type
    if (isset($cache['queries'][$this->_displayRelationshipType])) {
      $qcache = $cache['queries'][$this->_displayRelationshipType];
    }
    else {
      $tableName = $cache['tableName'];
      $qcache = [
        "from" => "",
        "where" => "",
      ];
      $rTypes = CRM_Core_PseudoConstant::relationshipType();
      if (is_numeric($this->_displayRelationshipType)) {
        $relationshipTypeLabel = $rTypes[$this->_displayRelationshipType]['label_a_b'];
        $qcache['from'] = "
INNER JOIN civicrm_relationship displayRelType ON ( displayRelType.contact_id_a = contact_a.id OR displayRelType.contact_id_b = contact_a.id )
INNER JOIN $tableName transform_temp ON ( transform_temp.contact_id = displayRelType.contact_id_a OR transform_temp.contact_id = displayRelType.contact_id_b )
";
        $qcache['where'] = "
WHERE displayRelType.relationship_type_id = {$this->_displayRelationshipType}
AND   displayRelType.is_active = 1
";
      }
      else {
        [$relType, $dirOne, $dirTwo] = explode('_', $this->_displayRelationshipType);
        if ($dirOne == 'a') {
          $relationshipTypeLabel = $rTypes[$relType]['label_a_b'];
          $qcache['from'] .= "
INNER JOIN civicrm_relationship displayRelType ON ( displayRelType.contact_id_a = contact_a.id )
INNER JOIN $tableName transform_temp ON ( transform_temp.contact_id = displayRelType.contact_id_b )
";
        }
        else {
          $relationshipTypeLabel = $rTypes[$relType]['label_b_a'];
          $qcache['from'] .= "
INNER JOIN civicrm_relationship displayRelType ON ( displayRelType.contact_id_b = contact_a.id )
INNER JOIN $tableName transform_temp ON ( transform_temp.contact_id = displayRelType.contact_id_a )
";
        }
        $qcache['where'] = "
WHERE displayRelType.relationship_type_id = $relType
AND   displayRelType.is_active = 1
";
      }
      $qcache['relTypeLabel'] = $relationshipTypeLabel;
      $_rTempCache[$arg_sig]['queries'][$this->_displayRelationshipType] = $qcache;
    }
    $qillMessage = ts('Contacts with a Relationship Type of: ');
    $iqill = $qillMessage . "'" . $qcache['relTypeLabel'] . "'";
    if (!is_array($this->_qill[0]) || !in_array($iqill, $this->_qill[0])) {
      $this->_qill[0][] = $iqill;
    }
    if (!str_contains($from, $qcache['from'])) {
      if (str_contains($from, "INNER JOIN")) {
        // lets replace all the INNER JOIN's in the $from so we dont exclude other data
        // this happens when we have an event_type in the quert (CRM-7969)
        $from = str_replace("INNER JOIN", "LEFT JOIN", $from);
        // Make sure the relationship join right after the FROM and other joins afterwards.
        // This gives us the possibility to change the join on civicrm case.
        $from = preg_replace("/LEFT JOIN/", $qcache['from'] . " LEFT JOIN", $from, 1);
      }
      else {
        $from .= $qcache['from'];
      }
      $where = $qcache['where'];
      if (!empty($this->_tables['civicrm_case'])) {
        // Change the join on CiviCRM case so that it joins on the right contac from the relationship.
        $from = str_replace("ON civicrm_case_contact.contact_id = contact_a.id", "ON civicrm_case_contact.contact_id = transform_temp.contact_id", $from);
        $where .= " AND displayRelType.case_id = civicrm_case_contact.case_id ";
      }
      if (!empty($this->_permissionFromClause) && !stripos($from, 'aclContactCache')) {
        $from .= " $this->_permissionFromClause";
      }
      if (!empty($this->_permissionWhereClause)) {
        $where .= "AND $this->_permissionWhereClause";
      }
    }

    $having = NULL;
  }

  /**
   * See CRM-19811 for why this is database hurty without apparent benefit.
   *
   * @param $op
   *
   * @return bool
   */
  public static function caseImportant($op) {
    return !in_array($op, ['LIKE', 'IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY']);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function optionValueQuery(
    $name,
    $op,
    $value,
    $grouping,
    $daoName,
    $field,
    $label,
    $dataType = 'String'
  ) {

    $pseudoFields = [
      'email_greeting',
      'postal_greeting',
      'addressee',
    ];

    [$tableName, $fieldName] = explode('.', $field['where'], 2);
    if ($tableName == 'civicrm_contact') {
      $wc = "contact_a.$fieldName";
    }
    else {
      // Special handling for on_hold, so that we actually use the 'where'
      // property in order to limit the query by the on_hold status of the email,
      // instead of using email.id which would be nonsensical.
      if ($field['name'] === 'on_hold') {
        $wc = $field['where'];
      }
      else {
        $wc = "$tableName.id";
      }
    }

    if (in_array($name, $pseudoFields)) {
      $wc = "contact_a.{$name}_id";
      $dataType = 'Positive';
      $value = (!$value) ? 0 : $value;
    }
    if ($name == "world_region") {
      $field['name'] = $name;
    }

    [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue($daoName, $field['name'], $value, $op);
    $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $label, 2 => $qillop, 3 => $qillVal]);
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
      return FALSE;
    }

    $string = substr($string, 1, -1);
    $values = explode(',', $string);
    if (empty($values)) {
      return FALSE;
    }

    $returnValues = [];
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
    $values = $labels = [];
    foreach ($this->_pseudoConstantsSelect as $key => $value) {
      if (!empty($this->_pseudoConstantsSelect[$key]['sorting'])) {
        continue;
      }

      if (is_object($dao) && property_exists($dao, $value['idCol'])) {
        $val = $dao->{$value['idCol']};
        if ($key == 'groups') {
          $dao->groups = $this->convertGroupIDStringToLabelString($dao, $val);
          continue;
        }

        $baoName = $value['bao'] ?? NULL;
        if (CRM_Utils_System::isNull($val)) {
          $dao->$key = NULL;
        }
        elseif (!empty($value['pseudoconstant'])) {
          // If pseudoconstant is set that is kind of defacto for 'we have a bit more info about this'
          // and we can use the metadata to figure it out.
          // ideally this bit of IF will absorb & replace all the rest in time as we move to
          // more metadata based choices.
          $labels[$value['bao']][$value['idCol']] ??= $value['bao']::buildOptions($value['idCol'], 'get');
          if (str_contains($val, CRM_Core_DAO::VALUE_SEPARATOR)) {
            $dbValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($val, CRM_Core_DAO::VALUE_SEPARATOR));
            foreach ($dbValues as $pseudoValue) {
              $convertedValues[] = $labels[$value['bao']][$value['idCol']][$pseudoValue] ?? NULL;
            }

            $dao->$key = ($usedForAPI) ? $convertedValues : implode(', ', $convertedValues);
            $realFieldName = $this->_pseudoConstantsSelect[$key]['field_name'] ?? NULL;
            if ($usedForAPI && $realFieldName) {
              // normally we would see 2 fields returned for pseudoConstants. An exception is
              // preferred_communication_method where there is no id-variant.
              // For the api we prioritise getting the real data returned.
              // over the resolved version
              $dao->$realFieldName = $dbValues;
            }

          }
          else {
            $dao->$key = $labels[$value['bao']][$value['idCol']][$val] ?? NULL;
          }
        }
        elseif ($baoName) {
          //preserve id value
          $idColumn = "{$key}_id";
          $dao->$idColumn = $val;

          if ($key == 'state_province_name') {
            $dao->{$value['pseudoField']} = $dao->$key = CRM_Core_PseudoConstant::stateProvince($val);
          }
          else {
            $dao->{$value['pseudoField']} = $dao->$key = CRM_Core_PseudoConstant::getLabel($baoName, $value['pseudoField'], $val);
          }
        }
        elseif ($value['pseudoField'] == 'state_province_abbreviation') {
          $dao->$key = CRM_Core_PseudoConstant::stateProvinceAbbreviation($val);
        }
        // @todo handle this in the section above for pseudoconstants.
        elseif (in_array($value['pseudoField'], ['participant_role_id', 'participant_role'])) {
          // @todo define bao on this & merge into the above condition.
          $viewValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $val);

          if ($value['pseudoField'] == 'participant_role') {
            $pseudoOptions = CRM_Event_DAO_Participant::buildOptions('role_id');
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
          if (str_contains($key, '-')) {
            $keyVal = explode('-', $key);
            $current = &$values;
            $lastElement = array_pop($keyVal);
            foreach ($keyVal as $v) {
              if (!array_key_exists($v, $current)) {
                $current[$v] = [];
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
    if (!$usedForAPI) {
      foreach ($this->legacyHackedFields as $realField => $labelField) {
        // This is a temporary routine for handling these fields while
        // we figure out how to handled them based on metadata in
        /// export and search builder. CRM-19815, CRM-19830.
        if (isset($dao->$realField) && is_numeric($dao->$realField) && isset($dao->$labelField)) {
          $dao->$realField = $dao->$labelField;
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
    $present = [];

    foreach ($this->_pseudoConstantsSelect as $name => $value) {
      if (!empty($value['table'])) {
        $regex = "/({$value['table']}\.|{$name})/";
        if (preg_match($regex, $sort)) {
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

    return [$presentClause, $presentSimpleFromClause];
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
    $pseudoExtraParam = [],
    $type = CRM_Utils_Type::T_STRING
  ) {
    $qillOperators = CRM_Core_SelectValues::getSearchBuilderOperators();

    //API usually have fieldValue format as array(operator => array(values)),
    //so we need to separate operator out of fieldValue param
    if (is_array($fieldValue) && in_array(key($fieldValue), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      $op = key($fieldValue);
      $fieldValue = $fieldValue[$op];
    }

    // if Operator chosen is NULL/EMPTY then
    if (str_contains($op, 'NULL') || str_contains($op, 'EMPTY')) {
      return [$qillOperators[$op] ?? $op, ''];
    }

    // @todo - if the right BAO is passed in special handling for the below
    // fields should not be required. testQillOptions.
    if ($fieldName == 'country_id') {
      $pseudoOptions = CRM_Core_PseudoConstant::country();
    }
    elseif ($fieldName == 'county_id') {
      $pseudoOptions = CRM_Core_PseudoConstant::county();
    }
    elseif ($fieldName == 'world_region') {
      $pseudoOptions = CRM_Core_PseudoConstant::worldRegion();
    }
    elseif ($daoName == 'CRM_Event_DAO_Event' && $fieldName == 'id') {
      $checkPermission = $pseudoExtraParam['check_permission'] ?? TRUE;
      $pseudoOptions = CRM_Event_BAO_Event::getEvents(0, $fieldValue, TRUE, $checkPermission, TRUE);
    }
    elseif ($fieldName == 'contribution_product_id') {
      $pseudoOptions = CRM_Contribute_PseudoConstant::products();
    }
    elseif ($daoName == 'CRM_Contact_DAO_Group' && $fieldName == 'id') {
      $pseudoOptions = CRM_Core_PseudoConstant::group();
    }
    elseif ($daoName == 'CRM_Batch_BAO_EntityBatch' && $fieldName == 'batch_id') {
      $pseudoOptions = CRM_Contribute_PseudoConstant::batch();
    }
    elseif ($daoName) {
      $pseudoOptions = CRM_Core_PseudoConstant::get($daoName, $fieldName, $pseudoExtraParam);
    }

    if (is_array($fieldValue)) {
      $qillString = [];
      if (!empty($pseudoOptions)) {
        foreach ((array) $fieldValue as $val) {
          $qillString[] = $pseudoOptions[$val] ?? $val;
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
        if ($op === 'BETWEEN') {
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

    return [$qillOperators[$op] ?? $op, $fieldValue];
  }

  /**
   * Get the qill (search description for field) for the specified field.
   *
   * @param string $daoName
   * @param string $name
   * @param string $value
   * @param string|array $op
   * @param string $label
   *
   * @return string
   */
  public static function getQillValue($daoName, string $name, $value, $op, string $label) {
    [$op, $value] = self::buildQillForFieldValue($daoName, $name, $value, $op);
    return ts('%1 %2 %3', [1 => $label, 2 => $op, 3 => $value]);
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
    if ($wildcard && $op === 'LIKE') {
      if (CRM_Core_Config::singleton()->includeWildCardInName && (substr($value, 0, 1) != '%')) {
        $value = "%$value";
      }
      if (substr($value, -1, 1) != '%') {
        $value = "$value%";
      }
    }
    return "$value";
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
  public static function processSpecialFormValue(&$formValues, $specialFields, $changeNames = []) {
    // Array of special fields whose value are considered only for NULL or EMPTY operators
    $nullableFields = ['contribution_batch_id'];

    foreach ($specialFields as $element) {
      $value = $formValues[$element] ?? NULL;
      if ($value) {
        if (is_array($value)) {
          if (array_key_exists($element, $changeNames)) {
            unset($formValues[$element]);
            $element = $changeNames[$element];
          }
          $formValues[$element] = ['IN' => $value];
        }
        elseif (in_array($value, ['IS NULL', 'IS NOT NULL', 'IS EMPTY', 'IS NOT EMPTY'])) {
          $formValues[$element] = [$value => 1];
        }
        elseif (!in_array($element, $nullableFields)) {
          // if wildcard is already present return searchString as it is OR append and/or prepend with wildcard
          $isWilcard = strstr($value, '%') ? FALSE : CRM_Core_Config::singleton()->includeWildCardInName;
          $formValues[$element] = ['LIKE' => self::getWildCardedValue($isWilcard, 'LIKE', $value)];
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
   * @param string|null $sortOrder
   *   ASC or DESC
   *
   * @return string
   *   list(string $orderByClause, string $additionalFromClause).
   *
   * @throws \CRM_Core_Exception
   */
  protected function prepareOrderBy($sort, $sortOrder) {
    $orderBy = '';

    if (CRM_Core_Config::singleton()->includeOrderByClause ||
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

          if ($sortOrder) {
            $orderBy .= " $sortOrder";
          }

          // always add contact_a.id to the ORDER clause
          // so the order is deterministic
          if (!str_contains('contact_a.id', $orderBy)) {
            $orderBy .= ", contact_a.id";
          }
        }
      }
      else {
        $orderBy = " contact_a.sort_name ASC, contact_a.id";
      }
    }
    if (!$orderBy) {
      return NULL;
    }
    // Remove this here & add it at the end for simplicity.
    $order = trim($orderBy);
    $orderByArray = explode(',', $order);

    foreach ($orderByArray as $orderByClause) {
      $orderByClauseParts = explode(' ', trim($orderByClause));
      $field = $orderByClauseParts[0];
      $direction = $orderByClauseParts[1] ?? 'asc';
      $fieldSpec = $this->getMetadataForRealField($field);

      // This is a hacky add-in for primary address joins. Feel free to iterate as it is unit tested.
      // @todo much more cleanup on location handling in addHierarchical elements. Potentially
      // add keys to $this->fields to represent the actual keys for locations.
      if (empty($fieldSpec) && substr($field, 0, 2) === '1-') {
        $fieldSpec = $this->getMetadataForField(substr($field, 2));
        $this->addAddressTable('1-' . str_replace('civicrm_', '', $fieldSpec['table_name']), 'is_primary = 1');
      }

      if ($this->_returnProperties === []) {
        if (!empty($fieldSpec['table_name']) && !isset($this->_tables[$fieldSpec['table_name']])) {
          $this->_tables[$fieldSpec['table_name']] = 1;
          $order = $fieldSpec['where'] . ' ' . $direction;
        }

      }
      $cfID = CRM_Core_BAO_CustomField::getKeyID($field);
      // add to cfIDs array if not present
      if (!empty($cfID) && !array_key_exists($cfID, $this->_cfIDs)) {
        $this->_cfIDs[$cfID] = [];
        $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs, TRUE, $this->_locationSpecificCustomFields);
        $this->_customQuery->query();
        $this->_select = array_merge($this->_select, $this->_customQuery->_select);
        $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
      }

      // By replacing the join to the option value table with the mysql construct
      // ORDER BY field('contribution_status_id', 2,1,4)
      // we can remove a join. In the case of the option value join it is
      /// a join known to cause slow queries.
      // @todo cover other pseudoconstant types. Limited to option group ones  & Foreign keys
      // matching an id+name parrern in the
      // first instance for scope reasons. They require slightly different handling as the column (label)
      // is not declared for them.
      // @todo so far only integer fields are being handled. If we add string fields we need to look at
      // escaping.
      $pseudoConstantMetadata = $fieldSpec['pseudoconstant'] ?? FALSE;
      if (!empty($pseudoConstantMetadata)
      ) {
        if (!empty($pseudoConstantMetadata['optionGroupName'])
          || $this->isPseudoFieldAnFK($fieldSpec)
        ) {
          // dev/core#1305 @todo this is not the right thing to do but for now avoid fatal error
          if (empty($fieldSpec['bao'])) {
            continue;
          }
          $sortedOptions = $fieldSpec['bao']::buildOptions($fieldSpec['name']);
          natcasesort($sortedOptions);
          $fieldIDsInOrder = implode(',', array_keys($sortedOptions));
          // Pretty sure this validation ALSO happens in the order clause & this can't be reached but...
          // this might give some early warning.
          CRM_Utils_Type::validate($fieldIDsInOrder, 'CommaSeparatedIntegers');
          // use where if it's set to fully qualify ambiguous column names
          // i.e. civicrm_contribution.contribution_status_id instead of contribution_status_id
          $pseudoColumnName = $fieldSpec['where'] ?? $fieldSpec['name'];
          $order = str_replace("$field", "field($pseudoColumnName,$fieldIDsInOrder)", $order);
        }
        //CRM-12565 add "`" around $field if it is a pseudo constant
        // This appears to be for 'special' fields like locations with appended numbers or hyphens .. maybe.
        if (!empty($pseudoConstantMetadata['element']) && $pseudoConstantMetadata['element'] == $field) {
          $order = str_replace($field, "`{$field}`", $order);
        }
      }
    }

    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_primaryLocation, $this->_mode, NULL, $this->_onlyDeleted);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_primaryLocation, $this->_mode, NULL, $this->_onlyDeleted);

    // The above code relies on crazy brittle string manipulation of a peculiarly-encoded ORDER BY
    // clause. But this magic helper which forgivingly reescapes ORDER BY.
    if ($order) {
      $order = CRM_Utils_Type::escape($order, 'MysqlOrderBy');
      return ' ORDER BY ' . $order;
    }
  }

  /**
   * Convert a string of group IDs to a string of group labels.
   *
   * The original string may include duplicates and groups the user does not have
   * permission to see.
   *
   * @param CRM_Core_DAO $dao
   * @param string $val
   *
   * @return string
   */
  public function convertGroupIDStringToLabelString(&$dao, $val) {
    $groupIDs = explode(',', $val);
    // Note that groups that the user does not have permission to will be excluded (good).
    $groups = array_intersect_key(CRM_Core_PseudoConstant::group(), array_flip($groupIDs));
    return implode(', ', $groups);

  }

  /**
   * Set the qill and where properties for a field.
   *
   * This function is intended as a short-term function to encourage refactoring
   * & re-use - but really we should just have less special-casing.
   *
   * @param string $name
   * @param string $op
   * @param string|array $value
   * @param string $grouping
   * @param array $field
   *
   * @throws \CRM_Core_Exception
   */
  public function setQillAndWhere($name, $op, $value, $grouping, $field) {
    $this->_where[$grouping][] = self::buildClause("contact_a.{$name}", $op, $value);
    [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $name, $value, $op);
    $this->_qill[$grouping][] = ts("%1 %2 %3", [
      1 => $field['title'],
      2 => $qillop,
      3 => $qillVal,
    ]);
  }

  /**
   * Has the pseudoconstant of the field been requested.
   *
   * For example if the field is payment_instrument_id then it
   * has been requested if either payment_instrument_id or payment_instrument
   * have been requested. Payment_instrument is the option groun name field value.
   *
   * @param array $field
   * @param string $fieldName
   *   The unique name of the field - ie. the one it will be aliased to in the query.
   *
   * @return bool
   */
  private function pseudoConstantNameIsInReturnProperties($field, $fieldName = NULL) {
    $realField = $this->getMetadataForRealField($fieldName);
    if (!isset($realField['pseudoconstant'])) {
      return FALSE;
    }
    $pseudoConstant = $realField['pseudoconstant'];

    if (empty($pseudoConstant['optionGroupName']) && ((($pseudoConstant['prefetch'] ?? NULL) === 'disabled') || !empty($pseudoConstant['abbrColumn']))) {
      // We are increasing our pseudoconstant handling - but still very cautiously,
      return FALSE;
    }

    if (!empty($pseudoConstant['optionGroupName']) && !empty($this->_returnProperties[$pseudoConstant['optionGroupName']])) {
      return TRUE;
    }
    if (!empty($this->_returnProperties[$fieldName])) {
      return TRUE;
    }
    // Is this still required - the above goes off the unique name. Test with things like
    // communication_preferences & prefix_id.
    if (!empty($this->_returnProperties[$field['name']])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get Select Clause.
   *
   * @return string
   */
  public function getSelect() {
    $select = 'SELECT ';
    if (isset($this->_distinctComponentClause)) {
      $select .= "{$this->_distinctComponentClause}, ";
    }
    $select .= implode(', ', $this->_select);
    return $select;
  }

  /**
   * Add basic statistics to the summary.
   *
   * @param array $summary
   * @param string $where
   * @param string $from
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function addBasicStatsToSummary(&$summary, $where, $from) {
    $summary['total']['count'] = 0;
    $summary['total']['amount'] = $summary['total']['avg'] = [];

    $query = "
      SELECT COUNT( conts.total_amount ) as total_count,
        SUM(   conts.total_amount ) as total_amount,
        AVG(   conts.total_amount ) as total_avg,
        conts.currency              as currency
      FROM (
        SELECT civicrm_contribution.total_amount, COUNT(civicrm_contribution.total_amount) as civicrm_contribution_total_amount_count,
        civicrm_contribution.currency
        $from
        $where AND civicrm_contribution.contribution_status_id = 1
      GROUP BY civicrm_contribution.id
    ) as conts
    GROUP BY currency";

    $dao = CRM_Core_DAO::executeQuery($query);

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
    return $summary;
  }

  /**
   * Add basic soft credit statistics to summary array.
   *
   * @param array $summary
   * @param string $where
   * @param string $from
   *
   * @throws \CRM_Core_Exception
   */
  protected function addBasicSoftCreditStatsToStats(&$summary, $where, $from) {
    $query = "
      SELECT COUNT( conts.total_amount ) as total_count,
      SUM(   conts.total_amount ) as total_amount,
      AVG(   conts.total_amount ) as total_avg,
      conts.currency as currency
      FROM (
        SELECT civicrm_contribution_soft.amount as total_amount, civicrm_contribution_soft.currency
        $from
        $where AND civicrm_contribution.contribution_status_id = 1 AND civicrm_contribution_soft.id IS NOT NULL
        GROUP BY civicrm_contribution_soft.id
      ) as conts
      GROUP BY currency";

    $dao = CRM_Core_DAO::executeQuery($query);
    $summary['soft_credit']['count'] = 0;
    $summary['soft_credit']['amount'] = $summary['soft_credit']['avg'] = [];
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

  /**
   * Add basic stats about cancelled contributions to the summary.
   *
   * @param array $summary
   * @param string $where
   * @param string $from
   *
   * @throws \CRM_Core_Exception
   */
  protected function addBasicCancelStatsToSummary(&$summary, $where, $from) {
    $query = "
      SELECT COUNT( conts.total_amount ) as cancel_count,
       SUM(   conts.total_amount ) as cancel_amount,
       AVG(   conts.total_amount ) as cancel_avg,
       conts.currency              as currency
        FROM (
      SELECT civicrm_contribution.total_amount, civicrm_contribution.currency
      $from
      $where  AND civicrm_contribution.cancel_date IS NOT NULL
      GROUP BY civicrm_contribution.id
    ) as conts
    GROUP BY currency";

    $dao = CRM_Core_DAO::executeQuery($query);
    $summary['cancel'] = ['count' => 0, 'amount' => 0, 'avg' => 0];
    if ($dao->N <= 1) {
      if ($dao->fetch()) {
        $summary['cancel']['count'] = $dao->cancel_count;
        $summary['cancel']['amount'] = CRM_Utils_Money::format($dao->cancel_amount, $dao->currency);
        $summary['cancel']['avg'] = CRM_Utils_Money::format($dao->cancel_avg, $dao->currency);
      }
    }
    else {
      $summary['cancel']['count'] = 0;
      $summary['cancel']['amount'] = $summary['cancel']['avg'] = [];
      while ($dao->fetch()) {
        $summary['cancel']['count'] += $dao->cancel_count;
        $summary['cancel']['amount'][] = CRM_Utils_Money::format($dao->cancel_amount, $dao->currency);
        $summary['cancel']['avg'][] = CRM_Utils_Money::format($dao->cancel_avg, $dao->currency);
      }
      $summary['cancel']['amount'] = implode(',&nbsp;', $summary['cancel']['amount']);
      $summary['cancel']['avg'] = implode(',&nbsp;', $summary['cancel']['avg']);
    }
  }

  /**
   * Create the sql query for an contact search.
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
   * @param string $additionalWhereClause
   *   If the caller wants to further restrict the search (used for components).
   * @param null $sortOrder
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  public function getSearchSQL(
    $offset = 0, $rowCount = 0, $sort = NULL,
    $count = FALSE, $includeContactIds = FALSE,
    $sortByChar = FALSE, $groupContacts = FALSE,
    $additionalWhereClause = NULL, $sortOrder = NULL,
    $additionalFromClause = NULL) {

    $sqlParts = $this->getSearchSQLParts($offset, $rowCount, $sort, $count, $includeContactIds, $sortByChar, $groupContacts, $additionalWhereClause, $sortOrder, $additionalFromClause);
    return "{$sqlParts['select']} {$sqlParts['from']} {$sqlParts['where']} {$sqlParts['having']} {$sqlParts['group_by']} {$sqlParts['order_by']} {$sqlParts['limit']}";
  }

  /**
   * Get the component parts of the search query as an array.
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
   * @param string $additionalWhereClause
   *   If the caller wants to further restrict the search (used for components).
   * @param null $sortOrder
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getSearchSQLParts($offset = 0, $rowCount = 0, $sort = NULL,
    $count = FALSE, $includeContactIds = FALSE,
    $sortByChar = FALSE, $groupContacts = FALSE,
    $additionalWhereClause = NULL, $sortOrder = NULL,
    $additionalFromClause = NULL) {
    if ($includeContactIds) {
      $this->_includeContactIds = TRUE;
      $this->_whereClause = $this->whereClause();
    }
    $onlyDeleted = in_array([
      'deleted_contacts',
      '=',
      '1',
      '0',
      '0',
    ], $this->_params);

    // if we’re explicitly looking for a certain contact’s contribs, events, etc.
    // and that contact happens to be deleted, set $onlyDeleted to true
    foreach ($this->_params as $values) {
      $name = $values[0] ?? NULL;
      $op = $values[1] ?? NULL;
      $value = $values[2] ?? NULL;
      if ($name === 'contact_id' and $op === '=') {
        if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'is_deleted')) {
          $onlyDeleted = TRUE;
        }
        break;
      }
    }

    // building the query string
    $groupBy = $groupByCols = NULL;
    if (!$count) {
      if (isset($this->_groupByComponentClause)) {
        $groupByCols = preg_replace('/^GROUP BY /', '', trim($this->_groupByComponentClause));
        $groupByCols = explode(', ', $groupByCols);
      }
      elseif ($this->_useGroupBy) {
        $groupByCols = ['contact_a.id'];
      }
    }
    if ($this->_mode & CRM_Contact_BAO_Query::MODE_ACTIVITY && (!$count)) {
      $groupByCols = ['civicrm_activity.id'];
    }
    if (!empty($groupByCols)) {
      $groupBy = " GROUP BY " . implode(', ', $groupByCols);
    }

    $order = $orderBy = '';
    if (!$count) {
      if (!$sortByChar) {
        $order = $this->prepareOrderBy($sort, $sortOrder);
      }
    }
    // Cases where we are disabling FGB (FULL_GROUP_BY_MODE):
    //   1. When GROUP BY columns are present then disable FGB otherwise it demands to add ORDER BY columns in GROUP BY and eventually in SELECT
    //     clause. This will impact the search query output.
    $disableFullGroupByMode = (!empty($groupBy) || $groupContacts);

    if ($disableFullGroupByMode) {
      CRM_Core_DAO::disableFullGroupByMode();
    }

    // CRM-15231
    $this->_sort = $sort;

    //CRM-15967
    $this->includePseudoFieldsJoin($sort);

    [$select, $from, $where, $having] = $this->query($count, $sortByChar, $groupContacts, $onlyDeleted);

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
    $limit = (!$count && $rowCount) ? " LIMIT " . CRM_Utils_Type::escape($offset, 'Int') . ", " . CRM_Utils_Type::escape($rowCount, 'Int') : '';

    return [
      'select' => $select,
      'from' => $from,
      'where' => $where,
      'order_by' => $order,
      'group_by' => $groupBy,
      'having' => $having,
      'limit' => $limit,
    ];
  }

  /**
   * Get the metadata for a given field.
   *
   * @param string $fieldName
   *
   * @return array
   */
  protected function getMetadataForField($fieldName) {
    if ($fieldName === 'contact_a.id') {
      // This seems to be the only anomaly.
      $fieldName = 'id';
    }
    $pseudoField = $this->_pseudoConstantsSelect[$fieldName] ?? [];
    $field = $this->_fields[$fieldName] ?? $pseudoField;
    $field = array_merge($field, $pseudoField);
    if (!empty($field) && empty($field['name'])) {
      // standardising field formatting here - over time we can phase out variants.
      // all paths using this currently unit tested
      $field['name'] = $field['field_name'] ?? $field['idCol'] ?? $fieldName;
    }
    return $field;
  }

  /**
   * Get the metadata for a given field, returning the 'real field' if it is a pseudofield.
   *
   * @param string $fieldName
   *
   * @return array
   */
  public function getMetadataForRealField($fieldName) {
    $field = $this->getMetadataForField($fieldName);
    if (!empty($field['is_pseudofield_for'])) {
      $field = $this->getMetadataForField($field['is_pseudofield_for']);
      $field['pseudofield_name'] = $fieldName;
    }
    elseif (!empty($field['pseudoconstant'])) {
      if (!empty($field['pseudoconstant']['optionGroupName'])) {
        $field['pseudofield_name'] = $field['pseudoconstant']['optionGroupName'];
        if (empty($field['table_name'])) {
          if (!empty($field['where'])) {
            $field['table_name'] = explode('.', $field['where'])[0];
          }
          else {
            $field['table_name'] = 'civicrm_contact';
          }
        }
      }
    }
    return $field;
  }

  /**
   * Get the field datatype, using the type in the database rather than the pseudofield, if a pseudofield.
   *
   * @param string $fieldName
   *
   * @return string
   */
  public function getDataTypeForRealField($fieldName) {
    return CRM_Utils_Type::typeToString($this->getMetadataForRealField($fieldName)['type']);
  }

  /**
   * If we have a field that is better rendered via the pseudoconstant handled them here.
   *
   * Rather than joining in the additional table we render the option value on output.
   *
   * @todo - so far this applies to a narrow range of pseudocontants. We are adding them
   * carefully with test coverage but aim to extend.
   *
   * @param string $name
   */
  protected function addPseudoconstantFieldToSelect($name) {
    $field = $this->getMetadataForRealField($name);
    $realFieldName = $field['name'];
    $pseudoFieldName = $field['pseudofield_name'] ?? NULL;
    if ($pseudoFieldName) {
      // @todo - we don't really need to build this array now we have metadata more available with getMetadataForField fn.
      $this->_pseudoConstantsSelect[$pseudoFieldName] = [
        'pseudoField' => $pseudoFieldName,
        'idCol' => $realFieldName,
        'field_name' => $field['name'],
        'bao' => $field['bao'],
        'pseudoconstant' => $field['pseudoconstant'],
      ];
    }

    $this->_tables[$field['table_name']] = 1;
    $this->_element[$realFieldName] = 1;
    $this->_select[$field['name']] = str_replace('civicrm_contact.', 'contact_a.', "{$field['where']} as `$realFieldName`");
  }

  /**
   * Is this pseudofield a foreign key constraint.
   *
   * We are trying to cautiously expand our pseudoconstant handling. This check allows us
   * to extend to a narrowly defined type (and then only if the pseudofield is in the fields
   * array which is done for contributions which are mostly handled as pseudoconstants.
   *
   * @param $fieldSpec
   *
   * @return bool
   */
  protected function isPseudoFieldAnFK($fieldSpec) {
    if (empty($fieldSpec['FKClassName'])
      || ($fieldSpec['pseudoconstant']['keyColumn'] ?? NULL) !== 'id') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Is the field a relative date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isARelativeDateField($fieldName) {
    if (substr($fieldName, -9, 9) !== '_relative') {
      return FALSE;
    }
    $realField = substr($fieldName, 0, strlen($fieldName) - 9);
    return isset($this->_fields[$realField]);
  }

  /**
   * Get the specifications for the field, if available.
   *
   * @param string $fieldName
   *   Fieldname as displayed on the form.
   *
   * @return array
   */
  public function getFieldSpec($fieldName) {
    if (isset($this->_fields[$fieldName])) {
      $fieldSpec = $this->_fields[$fieldName];
      if (!empty($fieldSpec['is_pseudofield_for'])) {
        $fieldSpec = array_merge($this->_fields[$fieldSpec['is_pseudofield_for']], $this->_fields[$fieldName]);
      }
      return $fieldSpec;
    }
    $lowFieldName = str_replace('_low', '', $fieldName);
    if (isset($this->_fields[$lowFieldName])) {
      return array_merge($this->_fields[$lowFieldName], ['field_name' => $lowFieldName]);
    }
    $highFieldName = str_replace('_high', '', $fieldName);
    if (isset($this->_fields[$highFieldName])) {
      return array_merge($this->_fields[$highFieldName], ['field_name' => $highFieldName]);
    }
    return [];
  }

  public function buildWhereForDate() {

  }

  /**
   * Is the field a relative date field.
   *
   * @param string $fieldName
   *
   * @return bool
   */
  protected function isADateRangeField($fieldName) {
    if (substr($fieldName, -4, 4) !== '_low' && substr($fieldName, -5, 5) !== '_high') {
      return FALSE;
    }
    return !empty($this->getFieldSpec($fieldName));
  }

  /**
   * @param $values
   */
  protected function buildRelativeDateQuery(&$values) {
    $value = $values[2] ?? NULL;
    if (empty($value)) {
      return;
    }
    $fieldName = substr($values[0], 0, strlen($values[0]) - 9);
    $fieldSpec = $this->_fields[$fieldName];
    $tableName = $fieldSpec['table_name'];
    $filters = CRM_Core_OptionGroup::values('relative_date_filters');
    $grouping = $values[3] ?? NULL;
    // If the table value is already set for a custom field it will be more nuanced than just '1'.
    $this->_tables[$tableName] ??= 1;
    $this->_whereTables[$tableName] ??= 1;

    $dates = CRM_Utils_Date::getFromTo($value, NULL, NULL);
    // Where end would be populated only if we are handling one of the weird ones with different from & to fields.
    $secondWhere = $fieldSpec['where_end'] ?? $fieldSpec['where'];

    $where = $fieldSpec['where'];
    if ($fieldSpec['table_name'] === 'civicrm_contact') {
      // Special handling for contact table as it has a known alias in advanced search.
      $where = str_replace('civicrm_contact.', 'contact_a.', $where);
      $secondWhere = str_replace('civicrm_contact.', 'contact_a.', $secondWhere);
    }

    $this->_qill[$grouping][] = $this->getQillForRelativeDateRange($dates[0], $dates[1], $fieldSpec['title'], $filters[$value]);
    if ($fieldName === 'relation_active_period_date') {
      // Hack this to fix regression https://lab.civicrm.org/dev/core/issues/1592
      // Not sure the  'right' fix.
      $this->_where[$grouping] = [self::getRelationshipActivePeriodClauses($dates[0], $dates[1], TRUE)];
      return;
    }

    if (empty($dates[0])) {
      // ie. no start date we only have end date
      $this->_where[$grouping][] = $secondWhere . " <= '{$dates[1]}'";
    }
    elseif (empty($dates[1])) {

      // ie. no end date we only have start date
      $this->_where[$grouping][] = $where . " >= '{$dates[0]}'";
    }
    else {
      // we have start and end dates.
      if ($secondWhere !== $where) {
        $this->_where[$grouping][] = $where . ">=  '{$dates[0]}' AND $secondWhere <='{$dates[1]}'";
      }
      else {
        $this->_where[$grouping][] = $where . " BETWEEN '{$dates[0]}' AND '{$dates[1]}'";
      }
    }
  }

  /**
   * Build the query for a date field if it is a _high or _low field.
   *
   * @param $values
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function buildDateRangeQuery($values) {
    if ($this->isADateRangeField($values[0])) {
      $fieldSpec = $this->getFieldSpec($values[0]);
      $title = empty($fieldSpec['unique_title']) ? $fieldSpec['title'] : $fieldSpec['unique_title'];
      $this->dateQueryBuilder($values, $fieldSpec['table_name'], $fieldSpec['field_name'], $fieldSpec['name'], $title);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add the address table into the query.
   *
   * @param string $tableKey
   * @param string $joinCondition
   *
   * @return array
   *   - alias name
   *   - address join.
   */
  protected function addAddressTable($tableKey, $joinCondition) {
    $tName = "$tableKey-address";
    $aName = "`$tableKey-address`";
    $this->_select["{$tName}_id"] = "`$tName`.id as `{$tName}_id`";
    $this->_element["{$tName}_id"] = 1;
    $addressJoin = "\nLEFT JOIN civicrm_address $aName ON ($aName.contact_id = contact_a.id AND $aName.$joinCondition)";
    $this->_tables[$tName] = $addressJoin;

    return [
      $aName,
      $addressJoin,
    ];
  }

  /**
   * Get the clause for group status.
   *
   * @param int $grouping
   *
   * @return string
   */
  protected function getGroupStatusClause($grouping) {
    $statuses = $this->getSelectedGroupStatuses($grouping);
    return "status IN (" . implode(', ', $statuses) . ")";
  }

  /**
   * Get an array of the statuses that have been selected.
   *
   * @param string $grouping
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getSelectedGroupStatuses($grouping) {
    $statuses = [];
    $gcsValues = $this->getWhereValues('group_contact_status', $grouping);
    if ($gcsValues &&
      is_array($gcsValues[2])
    ) {
      foreach ($gcsValues[2] as $k => $v) {
        if ($v) {
          $statuses[] = "'" . CRM_Utils_Type::escape($k, 'String') . "'";
        }
      }
    }
    else {
      $statuses[] = "'Added'";
    }
    return $statuses;
  }

  /**
   * Get the qill value for the field.
   *
   * @param string $name
   * @param array|int|string $value
   * @param string $op
   * @param array $fieldSpec
   * @param string $labelOverride
   *   Label override, if required.
   *
   * @return string
   */
  public function getQillForField($name, $value, $op, $fieldSpec = [], $labelOverride = NULL): string {
    [$qillop, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue($fieldSpec['bao'] ?? NULL, $name, $value, $op);
    return (string) ts("%1 %2 %3", [
      1 => $labelOverride ?? $fieldSpec['title'],
      2 => $qillop,
      3 => $qillVal,
    ]);
  }

  /**
   * Where handling for any field with adequately defined metadata.
   *
   * @param array $fieldSpec
   * @param string $name
   * @param string|array|int $value
   * @param string $op
   * @param string|int $grouping
   *
   * @throws \CRM_Core_Exception
   */
  public function handleWhereFromMetadata($fieldSpec, $name, $value, $op, $grouping = 0) {
    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldSpec['where'], $op, $value, CRM_Utils_Type::typeToString($fieldSpec['type']));
    $this->_qill[$grouping][] = $this->getQillForField($name, $value, $op, $fieldSpec);
    if (!isset($this->_tables[$fieldSpec['table_name']])) {
      $this->_tables[$fieldSpec['table_name']] = 1;
    }
    if (!isset($this->_whereTables[$fieldSpec['table_name']])) {
      $this->_whereTables[$fieldSpec['table_name']] = 1;
    }
  }

  /**
   * Get the qill for the relative date range.
   *
   * @param string|null $from
   * @param string|null $to
   * @param string $fieldTitle
   * @param string $relativeRange
   *
   * @return string
   */
  protected function getQillForRelativeDateRange($from, $to, string $fieldTitle, string $relativeRange): string {
    if (!$from) {
      return ts('%1 is ', [$fieldTitle]) . $relativeRange . ' (' . ts('to %1', [CRM_Utils_Date::customFormat($to)]) . ')';
    }
    if (!$to) {
      return ts('%1 is ', [$fieldTitle]) . $relativeRange . ' (' . ts('from %1', [CRM_Utils_Date::customFormat($from)]) . ')';
    }
    return ts('%1 is ', [$fieldTitle]) . $relativeRange . ' (' . ts('between %1 and %2', [
      CRM_Utils_Date::customFormat($from),
      CRM_Utils_Date::customFormat($to),
    ]) . ')';
  }

}
