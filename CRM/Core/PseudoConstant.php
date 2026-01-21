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
 * Stores all constants and pseudo constants for CRM application.
 *
 * examples of constants are "Contact Type" which will always be either
 * 'Individual', 'Household', 'Organization'.
 *
 * pseudo constants are entities from the database whose values rarely
 * change. examples are list of countries, states, location types,
 * relationship types.
 *
 * currently we're getting the data from the underlying database. this
 * will be reworked to use caching.
 *
 * Note: All pseudoconstants should be uninitialized or default to NULL.
 * This provides greater consistency/predictability after flushing.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_PseudoConstant {

  /**
   * Static cache for pseudoconstant arrays.
   * @var array
   */
  private static $cache;

  /**
   * States, provinces
   * @var array
   */
  private static $stateProvince;

  /**
   * Counties.
   * @var array
   */
  private static $county;

  /**
   * States/provinces abbreviations
   * @var array
   */
  private static $stateProvinceAbbreviation = [];

  /**
   * Country.
   * @var array
   */
  private static $country;

  /**
   * group
   * @var array
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   */
  private static $group;

  /**
   * RelationshipType
   * @var array
   */
  private static $relationshipType = [];

  /**
   * Civicrm groups that are not smart groups
   * @var array
   */
  private static $staticGroup;

  /**
   * Currency codes
   * @var array
   */
  private static $currencyCode;

  /**
   * Extensions of type module
   * @var array
   */
  private static $extensions;

  /**
   * Financial Account Type
   * @var array
   */
  private static $accountOptionValues;

  /**
   * Legacy option getter.
   *
   * @deprecated in favor of `Civi::entity()->getOptions()`
   *
   * @param string $daoName
   * @param string $fieldName
   * @param array $params
   * - name       string  name of the option group
   * - flip       DEPRECATED
   * - grouping   boolean if true, return the value in 'grouping' column (currently unsupported for tables other than option_value)
   * - localize   boolean if true, localize the results before returning
   * - condition  string|array add condition(s) to the sql query - will be concatenated using 'AND'
   * - keyColumn  string the column to use for 'id'
   * - labelColumn string the column to use for 'label'
   * - orderColumn string the column to use for sorting, defaults to 'weight' column if one exists, else defaults to labelColumn
   * - onlyActive boolean return only the action option values
   * - fresh      boolean ignore cache entries and go back to DB
   * @param string $context : Context string
   * @see CRM_Core_DAO::buildOptionsContext
   *
   * @return array|bool
   *   array on success, FALSE on error.
   *
   */
  public static function get($daoName, $fieldName, $params = [], $context = NULL) {
    CRM_Core_DAO::buildOptionsContext($context);
    $flip = !empty($params['flip']);
    // Historically this was 'false' but according to the notes in
    // CRM_Core_DAO::buildOptionsContext it should be context dependent.
    // timidly changing for 'search' only to fix world_region in search options.
    $localizeDefault = in_array($context, ['search']);
    // Merge params with defaults
    $params += [
      'grouping' => FALSE,
      'localize' => $localizeDefault,
      'onlyActive' => !($context == 'validate' || $context == 'get'),
      'fresh' => FALSE,
      'context' => $context,
      'condition' => [],
      'values' => [],
    ];
    $entity = CRM_Core_DAO_AllCoreTables::getEntityNameForClass($daoName);

    // Custom fields are not in the schema
    if (str_starts_with($fieldName, 'custom_') && is_numeric($fieldName[7])) {
      $customField = new CRM_Core_BAO_CustomField();
      $customField->id = (int) substr($fieldName, 7);
      $options = $customField->getOptions($context);
      if ($options && $flip) {
        $options = array_flip($options);
      }
      return $options;
    }

    // Core field: load schema
    if (class_exists($daoName)) {
      $dao = new $daoName();
      $fieldSpec = $dao->getFieldSpec($fieldName);
    }

    // Return false if field doesn't exist.
    if (empty($fieldSpec)) {
      return FALSE;
    }

    // Ensure we have the canonical name for this field
    $fieldName = $fieldSpec['name'] ?? $fieldName;

    if (!empty($fieldSpec['pseudoconstant'])) {
      $pseudoconstant = $fieldSpec['pseudoconstant'];

      // if callback is specified..
      if (!empty($pseudoconstant['callback'])) {
        $fieldOptions = call_user_func(Civi\Core\Resolver::singleton()->get($pseudoconstant['callback']), $fieldName, $params);
        $fieldOptions = self::formatArrayOptions($context, $fieldOptions);
        //CRM-18223: Allow additions to field options via hook.
        CRM_Utils_Hook::fieldOptions($entity, $fieldName, $fieldOptions, $params);
        return $fieldOptions;
      }

      // Merge params with schema defaults
      $params += [
        'keyColumn' => $pseudoconstant['keyColumn'] ?? NULL,
        'labelColumn' => $pseudoconstant['labelColumn'] ?? NULL,
      ];
      if (!empty($pseudoconstant['condition'])) {
        $params['condition'] = array_merge((array) $pseudoconstant['condition'], (array) $params['condition']);
      }

      // Fetch option group from option_value table
      if (!empty($pseudoconstant['optionGroupName'])) {
        if ($context == 'validate') {
          $params['labelColumn'] = 'name';
        }
        if ($context == 'match') {
          $params['keyColumn'] = 'name';
        }
        // Call our generic fn for retrieving from the option_value table
        $options = CRM_Core_OptionGroup::values(
          $pseudoconstant['optionGroupName'],
          $flip,
          $params['grouping'],
          $params['localize'],
          $params['condition'] ? ' AND ' . implode(' AND ', (array) $params['condition']) : NULL,
          $params['labelColumn'] ?: 'label',
          $params['onlyActive'],
          $params['fresh'],
          $params['keyColumn'] ?: 'value',
          !empty($params['orderColumn']) ? $params['orderColumn'] : 'weight'
        );
        CRM_Utils_Hook::fieldOptions($entity, $fieldName, $options, $params);
        return $options;
      }

      // Fetch options from other tables
      if (!empty($pseudoconstant['table'])) {
        CRM_Utils_Array::remove($params, 'flip', 'fresh');
        // Normalize params so the serialized cache string will be consistent.
        ksort($params);
        $cacheKey = $daoName . $fieldName . serialize($params);
        // Retrieve cached options
        if (isset(\Civi::$statics[__CLASS__][$cacheKey]) && empty($params['fresh'])) {
          $output = \Civi::$statics[__CLASS__][$cacheKey];
        }
        else {
          $output = self::renderOptionsFromTablePseudoconstant($pseudoconstant, $params, ($fieldSpec['localize_context'] ?? NULL), $context);
          CRM_Utils_Hook::fieldOptions($entity, $fieldName, $output, $params);
          \Civi::$statics[__CLASS__][$cacheKey] = $output;
        }
        return $flip ? array_flip($output) : $output;
      }
    }

    // Return "Yes" and "No" for boolean fields
    elseif (($fieldSpec['type'] ?? NULL) === CRM_Utils_Type::T_BOOLEAN) {
      $output = $context == 'validate' ? [0, 1] : CRM_Core_SelectValues::boolean();
      CRM_Utils_Hook::fieldOptions($entity, $fieldName, $output, $params);
      return $flip ? array_flip($output) : $output;
    }
    // If we're still here, it's an error. Return FALSE.
    return FALSE;
  }

  /**
   * Fetch the translated label for a field given its key.
   *
   * @param string $baoName
   * @param string $fieldName
   * @param string|int $key
   *
   * TODO: Accept multivalued input?
   *
   * @return bool|null|string
   *   FALSE if the given field has no associated option list
   *   NULL if the given key has no corresponding option
   *   String if label is found
   */
  public static function getLabel($baoName, $fieldName, $key) {
    $values = $baoName::buildOptions($fieldName, 'get');
    if ($values === FALSE) {
      return FALSE;
    }
    return $values[$key] ?? NULL;
  }

  /**
   * Fetch the machine name for a field given its key.
   *
   * @param string $baoName
   * @param string $fieldName
   * @param string|int $key
   *
   * @return bool|null|string
   *   FALSE if the given field has no associated option list
   *   NULL if the given key has no corresponding option
   *   String if label is found
   */
  public static function getName($baoName, $fieldName, $key) {
    $values = $baoName::buildOptions($fieldName, 'validate');
    if ($values === FALSE) {
      return FALSE;
    }
    return $values[$key ?? ''] ?? NULL;
  }

  /**
   * Fetch the key for a field option given its name.
   *
   * @param string $baoName
   * @param string $fieldName
   * @param string|int $value
   *
   * @return bool|null|string|int
   *   FALSE if the given field has no associated option list
   *   NULL if the given key has no corresponding option
   *   String|Number if key is found
   */
  public static function getKey($baoName, $fieldName, $value) {
    $values = $baoName::buildOptions($fieldName, 'validate');
    if ($values === FALSE) {
      return FALSE;
    }
    return CRM_Utils_Array::key($value, $values);
  }

  /**
   * Lookup the admin page at which a field's option list can be edited
   * @param $fieldSpec
   * @return string|null
   */
  public static function getOptionEditUrl($fieldSpec) {
    // If it's an option group, that's easy
    if (!empty($fieldSpec['pseudoconstant']['optionGroupName'])) {
      return 'civicrm/admin/options/' . $fieldSpec['pseudoconstant']['optionGroupName'];
    }
    // For everything else...
    elseif (!empty($fieldSpec['pseudoconstant']['table'])) {
      $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($fieldSpec['pseudoconstant']['table']);
      if (!$daoName) {
        return NULL;
      }

      $dao = new $daoName();
      $path = $dao::getEntityPaths()['browse'] ?? NULL;

      if (!$path) {
        // We don't have good mapping so have to do a bit of guesswork from the menu
        // @todo Get rid of this! It's unreliable and doesn't work if the path is replaced by
        // an afform one because the callback changes to CRM_Afform_Page_AfformBase
        [, $parent, , $child] = explode('_', $daoName);
        $sql = "SELECT path FROM civicrm_menu
        WHERE page_callback LIKE '%CRM_Admin_Page_$child%' OR page_callback LIKE '%CRM_{$parent}_Page_$child%'
        ORDER BY page_callback
        LIMIT 1";
        $path = CRM_Core_DAO::singleValueQuery($sql);
      }
      return $path;
    }
    return NULL;
  }

  /**
   * @deprecated generic populate method.
   * All pseudoconstant functions that use this method are also @deprecated
   *
   * The static array $var is populated from the db
   * using the <b>$name DAO</b>.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   * @param array $var
   *   The associative array we will fill.
   * @param string $name
   *   The name of the DAO.
   * @param bool $all
   *   Get all objects. default is to get only active ones.
   * @param string $retrieve
   *   The field that we are interested in (normally name, differs in some objects).
   * @param string $filter
   *   The field that we want to filter the result set with.
   * @param string $condition
   *   The condition that gets passed to the final query as the WHERE clause.
   *
   * @param bool $orderby
   * @param string $key
   * @param bool $force
   *
   * @return array
   */
  public static function populate(
    &$var,
    $name,
    $all = FALSE,
    $retrieve = 'name',
    $filter = 'is_active',
    $condition = NULL,
    $orderby = NULL,
    $key = 'id',
    $force = NULL
  ) {
    $cacheKey = CRM_Utils_Cache::cleanKey("CRM_PC_{$name}_{$all}_{$key}_{$retrieve}_{$filter}_{$condition}_{$orderby}");
    $cache = CRM_Utils_Cache::singleton();
    $var = $cache->get($cacheKey);
    if ($var !== NULL && empty($force)) {
      return $var;
    }

    /** @var CRM_Core_DAO $object */
    $object = new $name();

    $object->selectAdd();
    $object->selectAdd("$key, $retrieve");
    if ($condition) {
      $object->whereAdd($condition);
    }

    if (!$orderby) {
      $object->orderBy($retrieve);
    }
    else {
      $object->orderBy($orderby);
    }

    if (!$all) {
      $object->$filter = 1;
      $aclClauses = array_filter($name::getSelectWhereClause());
      foreach ($aclClauses as $clause) {
        $object->whereAdd($clause);
      }
    }

    $object->find();
    $var = [];
    while ($object->fetch()) {
      $var[$object->$key] = $object->$retrieve;
    }

    $cache->set($cacheKey, $var);
  }

  /**
   * Flush static array cache.
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
    if ($name == 'cache') {
      CRM_Core_OptionGroup::flushAll();
      if (isset(\Civi::$statics[__CLASS__])) {
        unset(\Civi::$statics[__CLASS__]);
      }
    }
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all Activity types.
   *
   * The static array activityType is returned
   *
   *
   * @return array
   *   array reference of all activity types.
   */
  public static function activityType() {
    $args = func_get_args();
    $all = $args[0] ?? TRUE;
    $includeCaseActivities = $args[1] ?? FALSE;
    $reset = $args[2] ?? FALSE;
    $returnColumn = $args[3] ?? 'label';
    $includeCampaignActivities = $args[4] ?? FALSE;
    $onlyComponentActivities = $args[5] ?? FALSE;
    $index = (int) $all . '_' . $returnColumn . '_' . (int) $includeCaseActivities;
    $index .= '_' . (int) $includeCampaignActivities;
    $index .= '_' . (int) $onlyComponentActivities;
    if (!isset(\Civi::$statics[__CLASS__]['activityType'])) {
      \Civi::$statics[__CLASS__]['activityType'] = [];
    }
    $activityTypes = &\Civi::$statics[__CLASS__]['activityType'];

    if (!isset($activityTypes[$index]) || $reset) {
      $condition = NULL;
      if (!$all) {
        $condition = 'AND filter = 0';
      }
      $componentClause = " v.component_id IS NULL";
      if ($onlyComponentActivities) {
        $componentClause = " v.component_id IS NOT NULL";
      }

      $componentIds = [];
      $compInfo = CRM_Core_Component::getEnabledComponents();

      // build filter for listing activity types only if their
      // respective components are enabled
      foreach ($compInfo as $compName => $compObj) {
        if ($compName == 'CiviCase') {
          if ($includeCaseActivities) {
            $componentIds[] = $compObj->componentID;
          }
        }
        elseif ($compName == 'CiviCampaign') {
          if ($includeCampaignActivities) {
            $componentIds[] = $compObj->componentID;
          }
        }
        else {
          $componentIds[] = $compObj->componentID;
        }
      }

      if (count($componentIds)) {
        $componentIds = implode(',', $componentIds);
        $componentClause = " ($componentClause OR v.component_id IN ($componentIds))";
        if ($onlyComponentActivities) {
          $componentClause = " ( v.component_id IN ($componentIds ) )";
        }
      }
      $condition .= ' AND ' . $componentClause;

      $activityTypes[$index] = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $condition, $returnColumn);
    }
    return $activityTypes[$index];
  }

  /**
   * Get all the State/Province from database.
   *
   * The static array stateProvince is returned, and if it's
   * called the first time, the <b>State Province DAO</b> is used
   * to get all the States.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   *
   * @param bool|int $id - Optional id to return
   *
   * @param bool $limit
   *
   * @return array
   *   array reference of all State/Provinces.
   */
  public static function &stateProvince($id = FALSE, $limit = TRUE) {
    if (($id && empty(self::$stateProvince[$id])) || !self::$stateProvince || !$id) {
      $whereClause = FALSE;
      if ($limit) {
        $countryIsoCodes = self::countryIsoCode();
        $limitCodes = CRM_Core_BAO_Country::provinceLimit();
        $limitIds = [];
        foreach ($limitCodes as $code) {
          $limitIds = array_merge($limitIds, array_keys($countryIsoCodes, $code));
        }
        if (!empty($limitIds)) {
          $whereClause = 'country_id IN (' . implode(', ', $limitIds) . ')';
        }
        else {
          $whereClause = FALSE;
        }
      }
      self::populate(self::$stateProvince, 'CRM_Core_DAO_StateProvince', TRUE, 'name', 'is_active', $whereClause);

      // localise the province names if in an non-en_US locale
      $tsLocale = CRM_Core_I18n::getLocale();
      if ($tsLocale != '' and $tsLocale != 'en_US') {
        $i18n = CRM_Core_I18n::singleton();
        $i18n->localizeArray(self::$stateProvince, [
          'context' => 'province',
        ]);
        self::$stateProvince = CRM_Utils_Array::asort(self::$stateProvince);
      }
    }
    if ($id) {
      if (array_key_exists($id, self::$stateProvince)) {
        return self::$stateProvince[$id];
      }
      else {
        $result = NULL;
        return $result;
      }
    }
    return self::$stateProvince;
  }

  /**
   * Get all the State/Province abbreviations from the database.
   *
   * Same as above, except gets the abbreviations instead of the names.
   *
   *
   * @param bool|int $id - Optional id to return
   *
   * @param bool $limit
   *
   * @return array
   *   array reference of all State/Province abbreviations.
   */
  public static function stateProvinceAbbreviation($id = FALSE, $limit = TRUE) {
    if ($id && is_numeric($id)) {
      if (!array_key_exists($id, (array) self::$stateProvinceAbbreviation)) {
        $query = "SELECT abbreviation
FROM   civicrm_state_province
WHERE  id = %1";
        $params = [
          1 => [
            $id,
            'Integer',
          ],
        ];
        self::$stateProvinceAbbreviation[$id] = CRM_Core_DAO::singleValueQuery($query, $params);
      }
      return self::$stateProvinceAbbreviation[$id];
    }
    else {
      $whereClause = FALSE;

      if ($limit) {
        $countryIsoCodes = self::countryIsoCode();
        $limitCodes = CRM_Core_BAO_Country::provinceLimit();
        $limitIds = [];
        foreach ($limitCodes as $code) {
          $tmpArray = array_keys($countryIsoCodes, $code);

          if (!empty($tmpArray)) {
            $limitIds[] = array_shift($tmpArray);
          }
        }
        if (!empty($limitIds)) {
          $whereClause = 'country_id IN (' . implode(', ', $limitIds) . ')';
        }
      }
      self::populate(self::$stateProvinceAbbreviation, 'CRM_Core_DAO_StateProvince', TRUE, 'abbreviation', 'is_active', $whereClause);
    }

    return self::$stateProvinceAbbreviation;
  }

  /**
   * Get all the State/Province abbreviations from the database for the specified country.
   * @deprecated
   *
   * @param int $countryID
   *
   * @return array
   *   array of all State/Province abbreviations for the given country.
   */
  public static function stateProvinceAbbreviationForCountry($countryID) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    $abbrs = [];
    self::populate($abbrs, 'CRM_Core_DAO_StateProvince', TRUE, 'abbreviation', 'is_active', "country_id = " . (int) $countryID, 'abbreviation');
    return $abbrs;
  }

  /**
   * Get all the State/Province abbreviations from the database for the default country.
   * @deprecated
   *
   * @return array
   *   array of all State/Province abbreviations for the given country.
   */
  public static function stateProvinceAbbreviationForDefaultCountry() {
    $countryID = Civi::settings()->get('defaultContactCountry');
    $abbrs = [];
    self::populate($abbrs, 'CRM_Core_DAO_StateProvince', TRUE, 'abbreviation', 'is_active', "country_id = " . (int) $countryID, 'abbreviation');
    return $abbrs;
  }

  /**
   * Get all the countries from database.
   *
   * The static array country is returned, and if it's
   * called the first time, the <b>Country DAO</b> is used
   * to get all the countries.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   *
   * @param bool|int $id - Optional id to return
   *
   * @param bool $applyLimit
   *
   * @return array|null
   *   array reference of all countries.
   */
  public static function country($id = FALSE, $applyLimit = TRUE) {
    if (($id && empty(self::$country[$id])) || !self::$country || !$id) {

      $config = CRM_Core_Config::singleton();
      $limitCodes = [];

      if ($applyLimit) {
        // limit the country list to the countries specified in CIVICRM_COUNTRY_LIMIT
        // (ensuring it's a subset of the legal values)
        // K/P: We need to fix this, i dont think it works with new setting files
        $limitCodes = CRM_Core_BAO_Country::countryLimit();
        if (!is_array($limitCodes)) {
          $limitCodes = [
            $config->countryLimit => 1,
          ];
        }

        $limitCodes = array_intersect(self::countryIsoCode(), $limitCodes);
      }

      if (count($limitCodes)) {
        $whereClause = "iso_code IN ('" . implode("', '", $limitCodes) . "')";
      }
      else {
        $whereClause = NULL;
      }

      self::populate(self::$country, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active', $whereClause);

      self::$country = CRM_Core_BAO_Country::_defaultContactCountries(self::$country);
    }
    if ($id) {
      if (array_key_exists($id, self::$country)) {
        return self::$country[$id];
      }
      else {
        return NULL;
      }
    }
    return self::$country;
  }

  /**
   * Get all the country ISO Code abbreviations from the database.
   *
   * @param bool $id
   *
   * @return array
   */
  public static function countryIsoCode($id = FALSE) {
    $values = [];
    self::populate($values, 'CRM_Core_DAO_Country', TRUE, 'iso_code');

    if ($id) {
      return $values[$id] ?? NULL;
    }
    return $values;
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all groups from database
   *
   * The static array group is returned, and if it's
   * called the first time, the <b>Group DAO</b> is used
   * to get all the groups.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   * @param string $groupType
   *   Type of group(Access/Mailing).
   * @param bool $excludeHidden
   *   Exclude hidden groups.
   *
   *
   * @return array
   *   array reference of all groups.
   */
  public static function allGroup($groupType = NULL, $excludeHidden = TRUE) {
    $condition = CRM_Contact_BAO_Group::groupTypeCondition($groupType, $excludeHidden);
    $values = [];
    self::populate($values, 'CRM_Contact_DAO_Group', FALSE, 'title', 'is_active', $condition);
    return $values;
  }

  /**
   * Get all permissioned groups from database.
   *
   * The static array group is returned, and if it's
   * called the first time, the <b>Group DAO</b> is used
   * to get all the groups.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   * @param string $groupType
   *   Type of group(Access/Mailing).
   * @param bool $excludeHidden
   *   Exclude hidden groups.
   *
   *
   * @return array
   *   array reference of all groups.
   */
  public static function group($groupType = NULL, $excludeHidden = TRUE) {
    return CRM_Core_Permission::group($groupType, $excludeHidden);
  }

  /**
   * Fetch groups in a nested format suitable for use in select form element.
   * @param bool $checkPermissions
   * @param string|null $groupType
   * @param bool $excludeHidden
   * @return array
   */
  public static function nestedGroup(bool $checkPermissions = TRUE, $groupType = NULL, bool $excludeHidden = TRUE) {
    $groups = $checkPermissions ? self::group($groupType, $excludeHidden) : self::allGroup($groupType, $excludeHidden);
    return CRM_Contact_BAO_Group::getGroupsHierarchy($groups, NULL, '&nbsp;&nbsp;', TRUE);
  }

  /**
   * Get all permissioned groups from database.
   *
   * The static array group is returned, and if it's
   * called the first time, the <b>Group DAO</b> is used
   * to get all the groups.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   *
   * @param bool $onlyPublic
   * @param null $groupType
   * @param bool $excludeHidden
   *
   * @return array
   *   array reference of all groups.
   */
  public static function &staticGroup($onlyPublic = FALSE, $groupType = NULL, $excludeHidden = TRUE) {
    if (!self::$staticGroup) {
      $condition = 'saved_search_id = 0 OR saved_search_id IS NULL';
      if ($onlyPublic) {
        $condition .= " AND visibility != 'User and User Admin Only'";
      }

      if ($groupType) {
        $condition .= ' AND ' . CRM_Contact_BAO_Group::groupTypeCondition($groupType);
      }

      if ($excludeHidden) {
        $condition .= ' AND is_hidden != 1 ';
      }

      self::populate(self::$staticGroup, 'CRM_Contact_DAO_Group', FALSE, 'title', 'is_active', $condition, 'title');
    }

    return self::$staticGroup;
  }

  /**
   * Get all Relationship Types  from database.
   *
   * The static array group is returned, and if it's
   * called the first time, the <b>RelationshipType DAO</b> is used
   * to get all the relationship types.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   * @param string $valueColumnName
   *   Db column name/label.
   * @param bool $reset
   *   Reset relationship types if true.
   * @param bool $isActive
   *   Filter by is_active. NULL to disable.
   *
   * @return array
   *   array reference of all relationship types.
   */
  public static function relationshipType($valueColumnName = 'label', $reset = FALSE, $isActive = 1) {
    $cacheKey = $valueColumnName . '::' . $isActive;
    if (!isset(self::$relationshipType[$cacheKey]) || $reset) {
      self::$relationshipType[$cacheKey] = [];

      //now we have name/label columns CRM-3336
      $column_a_b = "{$valueColumnName}_a_b";
      $column_b_a = "{$valueColumnName}_b_a";

      $relationshipTypeDAO = new CRM_Contact_DAO_RelationshipType();
      $relationshipTypeDAO->selectAdd();
      $relationshipTypeDAO->selectAdd("id, {$column_a_b}, {$column_b_a}, contact_type_a, contact_type_b, contact_sub_type_a, contact_sub_type_b");
      if ($isActive !== NULL) {
        $relationshipTypeDAO->is_active = $isActive;
      }
      $relationshipTypeDAO->find();
      while ($relationshipTypeDAO->fetch()) {

        self::$relationshipType[$cacheKey][$relationshipTypeDAO->id] = [
          'id' => $relationshipTypeDAO->id,
          $column_a_b => $relationshipTypeDAO->$column_a_b,
          $column_b_a => $relationshipTypeDAO->$column_b_a,
          'contact_type_a' => "$relationshipTypeDAO->contact_type_a",
          'contact_type_b' => "$relationshipTypeDAO->contact_type_b",
          'contact_sub_type_a' => "$relationshipTypeDAO->contact_sub_type_a",
          'contact_sub_type_b' => "$relationshipTypeDAO->contact_sub_type_b",
        ];
      }
    }

    return self::$relationshipType[$cacheKey];
  }

  /**
   * Name => Label pairs for all relationship types
   *
   * @return array
   */
  public static function relationshipTypeOptions($fieldName = NULL, $options = []) {
    $relationshipTypes = [];
    $onlyActive = empty($options['include_disabled']) ? 1 : NULL;
    $relationshipLabels = self::relationshipType('label', FALSE, $onlyActive);
    $relationshipNames = self::relationshipType('name', FALSE, $onlyActive);
    foreach ($relationshipNames as $id => $type) {
      $relationshipTypes[$type['name_a_b']] = $relationshipLabels[$id]['label_a_b'];
      if ($type['name_b_a'] && $type['name_b_a'] != $type['name_a_b']) {
        $relationshipTypes[$type['name_b_a']] = $relationshipLabels[$id]['label_b_a'];
      }
    }
    return $relationshipTypes;
  }

  /**
   * Get all the ISO 4217 currency codes
   *
   * @return array
   *   array reference of all currency codes
   */
  public static function &currencyCode() {
    if (!self::$currencyCode) {

      $query = "SELECT name FROM civicrm_currency";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        self::$currencyCode[] = $dao->name;
      }
    }
    return self::$currencyCode;
  }

  /**
   * Get all the County from database.
   *
   * The static array county is returned, and if it's
   * called the first time, the <b>County DAO</b> is used
   * to get all the Counties.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   *
   * @param bool|int $id - Optional id to return
   *
   * @return array
   *   array reference of all Counties
   */
  public static function &county($id = FALSE) {
    if (!self::$county) {

      $config = CRM_Core_Config::singleton();
      // order by id so users who populate civicrm_county can have more control over sort by the order they load the counties
      self::populate(self::$county, 'CRM_Core_DAO_County', TRUE, 'name', NULL, NULL, 'id');
    }
    if ($id) {
      if (array_key_exists($id, self::$county)) {
        return self::$county[$id];
      }
      else {
        return CRM_Core_DAO::$_nullObject;
      }
    }
    return self::$county;
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   * Get all active payment processors
   *
   * The static array paymentProcessor is returned
   *
   *
   * @param bool $all
   *   Get payment processors - default is to get only active ones.
   * @param bool $test
   *   Get test payment processors.
   *
   * @param null $additionalCond
   *
   * @return array
   *   array of all payment processors
   */
  public static function paymentProcessor($all = FALSE, $test = FALSE, $additionalCond = NULL) {
    $condition = 'is_test = ' . ($test ? '1' : '0');

    if ($additionalCond) {
      $condition .= " AND ( $additionalCond ) ";
    }

    // CRM-7178. Make sure we only include payment processors valid in this
    // domain
    $condition .= " AND domain_id = " . CRM_Core_Config::domainID();
    $values = [];
    self::populate($values, 'CRM_Financial_DAO_PaymentProcessor', $all, 'name', 'is_active', $condition, 'is_default desc, name');

    return $values;
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * The static array paymentProcessorType is returned
   *
   *
   * @param bool $all
   *   Get payment processors - default is to get only active ones.
   *
   * @param int $id
   * @param string $return
   *
   * @return array
   *   array of all payment processor types
   */
  public static function &paymentProcessorType($all = FALSE, $id = NULL, $return = 'title') {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    $values = [];
    self::populate($values, 'CRM_Financial_DAO_PaymentProcessorType', $all, $return, 'is_active', NULL, "is_default, $return", 'id');
    // This is incredibly stupid, but the whole function is deprecated anyway...
    if ($id && !empty($values[$id])) {
      return $values[$id];
    }
    return $values;
  }

  /**
   * Get all the World Regions from Database.
   *
   * @param bool $id
   *
   * @return array
   *   array reference of all World Regions
   */
  public static function worldRegion($id = FALSE) {
    $values = [];
    self::populate($values, 'CRM_Core_DAO_Worldregion', TRUE, 'name', NULL, NULL, 'id');

    if ($id) {
      return $values[$id] ?? NULL;
    }
    return $values;
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all Activity Statuses.
   *
   * @param string $column
   *
   * @return array
   */
  public static function &activityStatus($column = 'label') {
    return CRM_Core_OptionGroup::values('activity_status', FALSE, FALSE, FALSE, NULL, $column);
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all Visibility levels.
   *
   * The static array visibility is returned
   *
   *
   * @param string $column
   *
   * @return array
   *   array reference of all Visibility levels.
   */
  public static function visibility($column = 'label') {
    return CRM_Core_OptionGroup::values('visibility', FALSE, FALSE, FALSE, NULL, $column);
  }

  /**
   * @param int $countryID
   * @param string $field
   *
   * @return array
   */
  public static function &stateProvinceForCountry($countryID, $field = 'name') {
    static $_cache = NULL;

    $cacheKey = "{$countryID}_{$field}";
    if (!$_cache) {
      $_cache = [];
    }

    if (!empty($_cache[$cacheKey])) {
      return $_cache[$cacheKey];
    }

    $query = "
SELECT civicrm_state_province.{$field} name, civicrm_state_province.id id
  FROM civicrm_state_province
WHERE country_id = %1
ORDER BY name";
    $params = [
      1 => [
        $countryID,
        'Integer',
      ],
    ];

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $result = [];
    while ($dao->fetch()) {
      $result[$dao->id] = $dao->name;
    }

    // localise the stateProvince names if in an non-en_US locale
    $config = CRM_Core_Config::singleton();
    $tsLocale = CRM_Core_I18n::getLocale();
    if ($tsLocale != '' and $tsLocale != 'en_US') {
      $i18n = CRM_Core_I18n::singleton();
      $i18n->localizeArray($result, [
        'context' => 'province',
      ]);
      $result = CRM_Utils_Array::asort($result);
    }

    $_cache[$cacheKey] = $result;

    CRM_Utils_Hook::buildStateProvinceForCountry($countryID, $result);

    return $result;
  }

  /**
   * @param int $stateID
   *
   * @return array
   */
  public static function &countyForState($stateID) {
    if (is_array($stateID)) {
      $states = implode(", ", $stateID);
      $query = "
    SELECT civicrm_county.name name, civicrm_county.id id, civicrm_state_province.abbreviation abbreviation
      FROM civicrm_county
      LEFT JOIN civicrm_state_province ON civicrm_county.state_province_id = civicrm_state_province.id
    WHERE civicrm_county.state_province_id in ( $states )
    ORDER BY civicrm_state_province.abbreviation, civicrm_county.name";

      $dao = CRM_Core_DAO::executeQuery($query);

      $result = [];
      while ($dao->fetch()) {
        $result[$dao->id] = $dao->abbreviation . ': ' . $dao->name;
      }
    }
    else {

      static $_cache = NULL;

      $cacheKey = "{$stateID}_name";
      if (!$_cache) {
        $_cache = [];
      }

      if (!empty($_cache[$cacheKey])) {
        return $_cache[$cacheKey];
      }

      $query = "
    SELECT civicrm_county.name name, civicrm_county.id id
      FROM civicrm_county
    WHERE state_province_id = %1
    ORDER BY name";
      $params = [
        1 => [
          $stateID,
          'Integer',
        ],
      ];

      $dao = CRM_Core_DAO::executeQuery($query, $params);

      $result = [];
      while ($dao->fetch()) {
        $result[$dao->id] = $dao->name;
      }
    }

    return $result;
  }

  /**
   * Given a state ID return the country ID, this allows
   * us to populate forms and values for downstream code
   *
   * @param int $stateID
   *
   * @return int|null
   *   the country id that the state belongs to
   */
  public static function countryIDForStateID($stateID) {
    if (empty($stateID)) {
      return NULL;
    }

    $query = "
SELECT country_id
FROM   civicrm_state_province
WHERE  id = %1
";
    $params = [1 => [$stateID, 'Integer']];

    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Get all types of Greetings.
   *
   * The static array of greeting is returned
   *
   *
   * @param $filter
   *   Get All Email Greetings - default is to get only active ones.
   *
   * @param string $columnName
   *
   * @return array
   *   array reference of all greetings.
   */
  public static function greeting($filter, $columnName = 'label') {
    if (!isset(Civi::$statics[__CLASS__]['greeting'])) {
      Civi::$statics[__CLASS__]['greeting'] = [];
    }

    $index = $filter['greeting_type'] . '_' . $columnName;

    // also add contactType to the array
    $contactType = $filter['contact_type'] ?? NULL;
    if ($contactType) {
      $index .= '_' . $contactType;
    }

    if (empty(Civi::$statics[__CLASS__]['greeting'][$index])) {
      $filterCondition = NULL;
      if ($contactType) {
        $filterVal = 'v.filter =';
        switch ($contactType) {
          case 'Individual':
            $filterVal .= "1";
            break;

          case 'Household':
            $filterVal .= "2";
            break;

          case 'Organization':
            $filterVal .= "3";
            break;
        }
        $filterCondition .= "AND (v.filter = 0 OR {$filterVal}) ";
      }

      Civi::$statics[__CLASS__]['greeting'][$index] = CRM_Core_OptionGroup::values($filter['greeting_type'], NULL, NULL, NULL, $filterCondition, $columnName);
    }

    return Civi::$statics[__CLASS__]['greeting'][$index];
  }

  /**
   * Get all extensions.
   *
   * The static array extensions
   *
   * FIXME: This is called by civix but not by any core code. We
   * should provide an API call which civix can use instead.
   *
   *
   * @return array
   *   array($fullyQualifiedName => $label) list of extensions
   */
  public static function &getExtensions() {
    if (!self::$extensions) {
      $compat = CRM_Extension_System::getCompatibilityInfo();
      self::$extensions = [];
      $sql = '
        SELECT full_name, label
        FROM civicrm_extension
        WHERE is_active = 1
      ';
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        if (!empty($compat[$dao->full_name]['force-uninstall'])) {
          continue;
        }
        self::$extensions[$dao->full_name] = $dao->label;
      }
    }

    return self::$extensions;
  }

  /**
   * Get all options values.
   *
   * The static array option values is returned
   *
   *
   * @param string $optionGroupName
   *   Name of option group
   *
   * @param int $id
   * @param string $condition
   * @param string $column
   *   Whether to return 'name' or 'label'
   *
   * @return array
   *   array reference of all Option Values
   */
  public static function accountOptionValues($optionGroupName, $id = NULL, $condition = NULL, $column = 'label') {
    $cacheKey = $optionGroupName . '_' . $condition . '_' . $column;
    if (empty(self::$accountOptionValues[$cacheKey])) {
      self::$accountOptionValues[$cacheKey] = CRM_Core_OptionGroup::values($optionGroupName, FALSE, FALSE, FALSE, $condition, $column);
    }
    if ($id) {
      return self::$accountOptionValues[$cacheKey][$id] ?? NULL;
    }

    return self::$accountOptionValues[$cacheKey];
  }

  /**
   * Fetch the list of active extensions of type 'module'
   *
   * @param bool $fresh
   *   Whether to forcibly reload extensions list from canonical store.
   *
   * @return array
   *   array(array('prefix' => $, 'file' => $))
   */
  public static function getModuleExtensions($fresh = FALSE) {
    return CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles($fresh);
  }

  /**
   * Get all tax rates.
   *
   * The static array tax rates is returned
   *
   * @return array
   *   array list of tax rates with the financial type
   */
  public static function getTaxRates() {
    if (!isset(Civi::$statics[__CLASS__]['taxRates'])) {
      Civi::$statics[__CLASS__]['taxRates'] = [];
      $option = civicrm_api3('option_value', 'get', [
        'sequential' => 1,
        'option_group_id' => 'account_relationship',
        'name' => 'Sales Tax Account is',
      ]);
      $value = [];
      if ($option['count'] !== 0) {
        if ($option['count'] > 1) {
          foreach ($option['values'] as $opt) {
            $value[] = $opt['value'];
          }
        }
        else {
          $value[] = $option['values'][0]['value'];
        }
        $where = 'AND efa.account_relationship IN (' . implode(', ', $value) . ' )';
      }
      else {
        $where = '';
      }
      $sql = "
        SELECT fa.tax_rate, efa.entity_id
        FROM civicrm_entity_financial_account efa
        INNER JOIN civicrm_financial_account fa ON fa.id = efa.financial_account_id
        WHERE efa.entity_table = 'civicrm_financial_type'
        {$where}
        AND fa.is_active = 1";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        Civi::$statics[__CLASS__]['taxRates'][$dao->entity_id] = $dao->tax_rate;
      }
    }

    return Civi::$statics[__CLASS__]['taxRates'];
  }

  /**
   * Get participant status class options.
   *
   * @return array
   */
  public static function emailOnHoldOptions() {
    return [
      '0' => ts('No'),
      '1' => ts('On Hold Bounce'),
      '2' => ts('On Hold Opt Out'),
    ];
  }

  /**
   * Render the field options from the available pseudoconstant.
   *
   * Do not call this function directly or from untested code. Further cleanup is likely.
   *
   * @param array $pseudoconstant
   * @param array $params
   * @param string|null $localizeContext
   * @param string $context
   *
   * @return array|bool|mixed
   */
  public static function renderOptionsFromTablePseudoconstant($pseudoconstant, &$params = [], $localizeContext = NULL, $context = '') {
    $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($pseudoconstant['table']);
    if (!class_exists($daoName)) {
      return FALSE;
    }
    // Get list of fields for the option table
    /** @var CRM_Core_DAO $dao * */
    $dao = new $daoName();
    $availableFields = array_keys($dao->fieldKeys());

    $select = 'SELECT %1 AS id, %2 AS label';
    $from = 'FROM %3';
    $wheres = [];
    $order = 'ORDER BY %2';
    if (in_array('id', $availableFields, TRUE)) {
      // Example: 'ORDER BY abbreviation, id' because `abbreviation`s are not unique.
      $order .= ', id';
    }

    // Use machine name in certain contexts
    if ($context === 'validate' || $context === 'match') {
      $nameField = $context === 'validate' ? 'labelColumn' : 'keyColumn';
      if (!empty($pseudoconstant['nameColumn'])) {
        $params[$nameField] = $pseudoconstant['nameColumn'];
      }
      elseif (in_array('name', $availableFields)) {
        $params[$nameField] = 'name';
      }
    }

    // Use abbrColum if context is abbreviate
    if ($context === 'abbreviate' && !empty($pseudoconstant['abbrColumn'])) {
      $params['labelColumn'] = $pseudoconstant['abbrColumn'];
    }

    // Condition param can be passed as an sql clause string or an array of clauses
    if (!empty($params['condition'])) {
      $wheres[] = implode(' AND ', (array) $params['condition']);
    }
    // onlyActive param will automatically filter on common flags
    if (!empty($params['onlyActive'])) {
      foreach (['is_active' => 1, 'is_deleted' => 0, 'is_test' => 0, 'is_hidden' => 0] as $flag => $val) {
        if (in_array($flag, $availableFields)) {
          $wheres[] = "$flag = $val";
        }
      }
    }
    // Filter domain specific options
    if (in_array('domain_id', $availableFields)) {
      $wheres[] = '(domain_id = ' . CRM_Core_Config::domainID() . ' OR  domain_id is NULL)';
    }
    $queryParams = [
      1 => [$params['keyColumn'], 'MysqlColumnNameOrAlias'],
      2 => [$params['labelColumn'], 'MysqlColumnNameOrAlias'],
      3 => [$pseudoconstant['table'], 'MysqlColumnNameOrAlias'],
    ];
    // Add orderColumn param
    if (!empty($params['orderColumn'])) {
      $queryParams[4] = [$params['orderColumn'], 'MysqlOrderBy'];
      $order = 'ORDER BY %4';
    }
    // Support no sorting if $params[orderColumn] is FALSE
    elseif (isset($params['orderColumn']) && $params['orderColumn'] === FALSE) {
      $order = '';
    }
    // Default to 'weight' if that column exists
    elseif (in_array('weight', $availableFields)) {
      $order = "ORDER BY weight";
    }

    $output = [];
    $query = "$select $from";
    if ($wheres) {
      $query .= " WHERE " . implode(' AND ', $wheres);
    }
    $query .= ' ' . $order;
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $output[$dao->id] = $dao->label;
    }
    // Localize results
    if (!empty($params['localize']) || $pseudoconstant['table'] === 'civicrm_country' || $pseudoconstant['table'] === 'civicrm_state_province') {
      if ($pseudoconstant['table'] === 'civicrm_country') {
        $output = CRM_Core_BAO_Country::_defaultContactCountries($output);
        // avoid further sorting
        $order = '';
      }
      else {
        $I18nParams = [];
        if ($localizeContext) {
          $I18nParams['context'] = $localizeContext;
        }
        $i18n = CRM_Core_I18n::singleton();
        $i18n->localizeArray($output, $I18nParams);
      }
      // Maintain sort by label
      if ($order === 'ORDER BY %2') {
        $output = CRM_Utils_Array::asort($output);
      }
    }

    return $output;
  }

  /**
   * Convert multidimensional option list to flat array, if necessary
   *
   * Detect if an array of options is simple key/value pairs or a multidimensional array.
   * If the latter, convert to a flat array, as determined by $context.
   *
   * @param string|null $context
   *   See https://docs.civicrm.org/dev/en/latest/framework/pseudoconstant/#context
   * @param array $options
   *   List of options, each as a record of id+name+label.
   *   Ex: [['id' => 123, 'name' => 'foo_bar', 'label' => 'Foo Bar']]
   */
  public static function formatArrayOptions(?string $context, array $options): array {
    // Already flat; return keys/values according to context
    if (!isset($options[0]) || !is_array($options[0])) {
      // For validate context, machine names are expected in place of labels.
      // A flat array has no names so use the ids for both key and value.
      return $context === 'validate' ?
        array_combine(array_keys($options), array_keys($options)) :
        $options;
    }
    $result = [];
    $key = ($context === 'match') ? 'name' : 'id';
    $value = ($context === 'validate') ? 'name' : (($context === 'abbreviate') ? 'abbr' : 'label');
    foreach ($options as $option) {
      // Some fallbacks in case the array is missing a 'name' or 'label' or 'abbr'
      $id = $option[$key] ?? $option['id'] ?? $option['name'];
      $result[$id] = $option[$value] ?? $option['label'] ?? $option['name'] ?? $option['id'];
    }
    return $result;
  }

}
