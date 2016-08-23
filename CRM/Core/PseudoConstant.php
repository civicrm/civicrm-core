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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Core_PseudoConstant {

  /**
   * Static cache for pseudoconstant arrays.
   * @var array
   */
  private static $cache;

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * activity type
   * @var array
   */
  private static $activityType;

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
  private static $stateProvinceAbbreviation = array();

  /**
   * Country.
   * @var array
   */
  private static $country;

  /**
   * CountryIsoCode.
   * @var array
   */
  private static $countryIsoCode;

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * group
   * @var array
   */
  private static $group;

  /**
   * RelationshipType
   * @var array
   */
  private static $relationshipType;

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
   * Payment processor
   * @var array
   */
  private static $paymentProcessor;

  /**
   * Payment processor types
   * @var array
   */
  private static $paymentProcessorType;

  /**
   * World Region
   * @var array
   */
  private static $worldRegions;

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * activity status
   * @var array
   */
  private static $activityStatus;

  /**
   * Visibility
   * @var array
   */
  private static $visibility;

  /**
   * Greetings
   * @var array
   */
  private static $greeting;

  /**
   * Default Greetings
   * @var array
   */
  private static $greetingDefaults;

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
   * Low-level option getter, rarely accessed directly.
   * NOTE: Rather than calling this function directly use CRM_*_BAO_*::buildOptions()
   * @see http://wiki.civicrm.org/confluence/display/CRMDOC/Pseudoconstant+%28option+list%29+Reference
   *
   * NOTE: If someone undertakes a refactoring of this, please consider the use-case of
   * the Setting.getoptions API. There is no DAO/field, but it would be nice to use the
   * same 'pseudoconstant' struct in *.settings.php. This means loosening the coupling
   * between $field lookup and the $pseudoconstant evaluation.
   *
   * @param string $daoName
   * @param string $fieldName
   * @param array $params
   * - name       string  name of the option group
   * - flip       boolean results are return in id => label format if false
   *                            if true, the results are reversed
   * - grouping   boolean if true, return the value in 'grouping' column (currently unsupported for tables other than option_value)
   * - localize   boolean if true, localize the results before returning
   * - condition  string|array add condition(s) to the sql query - will be concatenated using 'AND'
   * - keyColumn  string the column to use for 'id'
   * - labelColumn string the column to use for 'label'
   * - orderColumn string the column to use for sorting, defaults to 'weight' column if one exists, else defaults to labelColumn
   * - onlyActive boolean return only the action option values
   * - fresh      boolean ignore cache entries and go back to DB
   * @param string $context : Context string
   *
   * @return array|bool
   *   array on success, FALSE on error.
   *
   */
  public static function get($daoName, $fieldName, $params = array(), $context = NULL) {
    CRM_Core_DAO::buildOptionsContext($context);
    $flip = !empty($params['flip']);
    // Merge params with defaults
    $params += array(
      'grouping' => FALSE,
      'localize' => FALSE,
      'onlyActive' => ($context == 'validate' || $context == 'get') ? FALSE : TRUE,
      'fresh' => FALSE,
      'context' => $context,
    );
    $entity = CRM_Core_DAO_AllCoreTables::getBriefName(CRM_Core_DAO_AllCoreTables::getCanonicalClassName($daoName));

    // Custom fields are not in the schema
    if (strpos($fieldName, 'custom_') === 0 && is_numeric($fieldName[7])) {
      $customField = new CRM_Core_BAO_CustomField();
      $customField->id = (int) substr($fieldName, 7);
      $options = $customField->getOptions();
      if ($options && $flip) {
        $options = array_flip($options);
      }
      $customField->free();
      return $options;
    }

    // Core field: load schema
    $dao = new $daoName();
    $fieldSpec = $dao->getFieldSpec($fieldName);
    $dao->free();

    // Ensure we have the canonical name for this field
    $fieldName = CRM_Utils_Array::value('name', $fieldSpec, $fieldName);

    // Return false if field doesn't exist.
    if (empty($fieldSpec)) {
      return FALSE;
    }

    elseif (!empty($fieldSpec['pseudoconstant'])) {
      $pseudoconstant = $fieldSpec['pseudoconstant'];

      // if callback is specified..
      if (!empty($pseudoconstant['callback'])) {
        $fieldOptions = call_user_func(Civi\Core\Resolver::singleton()->get($pseudoconstant['callback']));
        //CRM-18223: Allow additions to field options via hook.
        CRM_Utils_Hook::fieldOptions($entity, $fieldName, $fieldOptions, $params);
        return $fieldOptions;
      }

      // Merge params with schema defaults
      $params += array(
        'condition' => CRM_Utils_Array::value('condition', $pseudoconstant, array()),
        'keyColumn' => CRM_Utils_Array::value('keyColumn', $pseudoconstant),
        'labelColumn' => CRM_Utils_Array::value('labelColumn', $pseudoconstant),
      );

      if ($context == 'abbreviate') {
        switch ($fieldName) {
          case 'state_province_id':
            $params['labelColumn'] = 'abbreviation';
            break;

          case 'country_id':
            $params['labelColumn'] = 'iso_code';
            break;

          default:
        }
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
          $params['labelColumn'] ? $params['labelColumn'] : 'label',
          $params['onlyActive'],
          $params['fresh'],
          $params['keyColumn'] ? $params['keyColumn'] : 'value',
          !empty($params['orderColumn']) ? $params['orderColumn'] : 'weight'
        );
        CRM_Utils_Hook::fieldOptions($entity, $fieldName, $options, $params);
        return $options;
      }

      // Fetch options from other tables
      if (!empty($pseudoconstant['table'])) {
        // Normalize params so the serialized cache string will be consistent.
        CRM_Utils_Array::remove($params, 'flip', 'fresh');
        ksort($params);
        $cacheKey = $daoName . $fieldName . serialize($params);

        // Retrieve cached options
        if (isset(self::$cache[$cacheKey]) && empty($params['fresh'])) {
          $output = self::$cache[$cacheKey];
        }
        else {
          $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($pseudoconstant['table']);
          if (!class_exists($daoName)) {
            return FALSE;
          }
          // Get list of fields for the option table
          $dao = new $daoName();
          $availableFields = array_keys($dao->fieldKeys());
          $dao->free();

          $select = "SELECT %1 AS id, %2 AS label";
          $from = "FROM %3";
          $wheres = array();
          $order = "ORDER BY %2";

          // Use machine name in certain contexts
          if ($context == 'validate' || $context == 'match') {
            $nameField = $context == 'validate' ? 'labelColumn' : 'keyColumn';
            if (!empty($pseudoconstant['nameColumn'])) {
              $params[$nameField] = $pseudoconstant['nameColumn'];
            }
            elseif (in_array('name', $availableFields)) {
              $params[$nameField] = 'name';
            }
          }
          // Condition param can be passed as an sql clause string or an array of clauses
          if (!empty($params['condition'])) {
            $wheres[] = implode(' AND ', (array) $params['condition']);
          }
          // onlyActive param will automatically filter on common flags
          if (!empty($params['onlyActive'])) {
            foreach (array('is_active' => 1, 'is_deleted' => 0, 'is_test' => 0, 'is_hidden' => 0) as $flag => $val) {
              if (in_array($flag, $availableFields)) {
                $wheres[] = "$flag = $val";
              }
            }
          }
          // Filter domain specific options
          if (in_array('domain_id', $availableFields)) {
            $wheres[] = 'domain_id = ' . CRM_Core_Config::domainID();
          }
          $queryParams = array(
            1 => array($params['keyColumn'], 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES),
            2 => array($params['labelColumn'], 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES),
            3 => array($pseudoconstant['table'], 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES),
          );
          // Add orderColumn param
          if (!empty($params['orderColumn'])) {
            $queryParams[4] = array($params['orderColumn'], 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES);
            $order = "ORDER BY %4";
          }
          // Support no sorting if $params[orderColumn] is FALSE
          elseif (isset($params['orderColumn']) && $params['orderColumn'] === FALSE) {
            $order = '';
          }
          // Default to 'weight' if that column exists
          elseif (in_array('weight', $availableFields)) {
            $order = "ORDER BY weight";
          }

          $output = array();
          $query = "$select $from";
          if ($wheres) {
            $query .= " WHERE " . implode($wheres, ' AND ');
          }
          $query .= ' ' . $order;
          $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
          while ($dao->fetch()) {
            $output[$dao->id] = $dao->label;
          }
          $dao->free();
          // Localize results
          if (!empty($params['localize']) || $pseudoconstant['table'] == 'civicrm_country' || $pseudoconstant['table'] == 'civicrm_state_province') {
            $I18nParams = array();
            if ($pseudoconstant['table'] == 'civicrm_country') {
              $I18nParams['context'] = 'country';
            }
            if ($pseudoconstant['table'] == 'civicrm_state_province') {
              $I18nParams['context'] = 'province';
            }
            $i18n = CRM_Core_I18n::singleton();
            $i18n->localizeArray($output, $I18nParams);
            // Maintain sort by label
            if ($order == "ORDER BY %2") {
              CRM_Utils_Array::asort($output);
            }
          }
          CRM_Utils_Hook::fieldOptions($entity, $fieldName, $output, $params);
          self::$cache[$cacheKey] = $output;
        }
        return $flip ? array_flip($output) : $output;
      }
    }

    // Return "Yes" and "No" for boolean fields
    elseif (CRM_Utils_Array::value('type', $fieldSpec) === CRM_Utils_Type::T_BOOLEAN) {
      $output = $context == 'validate' ? array(0, 1) : CRM_Core_SelectValues::boolean();
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
   * @param string|Int $key
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
    return CRM_Utils_Array::value($key, $values);
  }

  /**
   * Fetch the machine name for a field given its key.
   *
   * @param string $baoName
   * @param string $fieldName
   * @param string|Int $key
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
    return CRM_Utils_Array::value($key, $values);
  }

  /**
   * Fetch the key for a field option given its name.
   *
   * @param string $baoName
   * @param string $fieldName
   * @param string|Int $value
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
      // We don't have good mapping so have to do a bit of guesswork from the menu
      list(, $parent, , $child) = explode('_', $daoName);
      $sql = "SELECT path FROM civicrm_menu
        WHERE page_callback LIKE '%CRM_Admin_Page_$child%' OR page_callback LIKE '%CRM_{$parent}_Page_$child%'
        ORDER BY page_callback
        LIMIT 1";
      return CRM_Core_Dao::singleValueQuery($sql);
    }
    return NULL;
  }

  /**
   * DEPRECATED generic populate method.
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
    $cacheKey = "CRM_PC_{$name}_{$all}_{$key}_{$retrieve}_{$filter}_{$condition}_{$orderby}";
    $cache = CRM_Utils_Cache::singleton();
    $var = $cache->get($cacheKey);
    if ($var && empty($force)) {
      return $var;
    }

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
    }

    $object->find();
    $var = array();
    while ($object->fetch()) {
      $var[$object->$key] = $object->$retrieve;
    }

    $cache->set($cacheKey, $var);
  }

  /**
   * Flush given pseudoconstant so it can be reread from db.
   * nex time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
    if ($name == 'cache') {
      CRM_Core_OptionGroup::flushAll();
    }
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all Activty types.
   *
   * The static array activityType is returned
   *
   *
   * @return array
   *   array reference of all activity types.
   */
  public static function &activityType() {
    $args = func_get_args();
    $all = CRM_Utils_Array::value(0, $args, TRUE);
    $includeCaseActivities = CRM_Utils_Array::value(1, $args, FALSE);
    $reset = CRM_Utils_Array::value(2, $args, FALSE);
    $returnColumn = CRM_Utils_Array::value(3, $args, 'label');
    $includeCampaignActivities = CRM_Utils_Array::value(4, $args, FALSE);
    $onlyComponentActivities = CRM_Utils_Array::value(5, $args, FALSE);
    $index = (int) $all . '_' . $returnColumn . '_' . (int) $includeCaseActivities;
    $index .= '_' . (int) $includeCampaignActivities;
    $index .= '_' . (int) $onlyComponentActivities;

    if (NULL === self::$activityType) {
      self::$activityType = array();
    }

    if (!isset(self::$activityType[$index]) || $reset) {
      $condition = NULL;
      if (!$all) {
        $condition = 'AND filter = 0';
      }
      $componentClause = " v.component_id IS NULL";
      if ($onlyComponentActivities) {
        $componentClause = " v.component_id IS NOT NULL";
      }

      $componentIds = array();
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
      $condition = $condition . ' AND ' . $componentClause;

      self::$activityType[$index] = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $condition, $returnColumn);
    }
    return self::$activityType[$index];
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
    if (($id && !CRM_Utils_Array::value($id, self::$stateProvince)) || !self::$stateProvince || !$id) {
      $whereClause = FALSE;
      if ($limit) {
        $countryIsoCodes = self::countryIsoCode();
        $limitCodes = CRM_Core_BAO_Country::provinceLimit();
        $limitIds = array();
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
        $i18n->localizeArray(self::$stateProvince, array(
          'context' => 'province',
        ));
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
        $params = array(
          1 => array(
            $id,
            'Integer',
          ),
        );
        self::$stateProvinceAbbreviation[$id] = CRM_Core_DAO::singleValueQuery($query, $params);
      }
      return self::$stateProvinceAbbreviation[$id];
    }
    else {
      $whereClause = FALSE;

      if ($limit) {
        $countryIsoCodes = self::countryIsoCode();
        $limitCodes = CRM_Core_BAO_Country::provinceLimit();
        $limitIds = array();
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
   * @return array
   *   array reference of all countries.
   */
  public static function country($id = FALSE, $applyLimit = TRUE) {
    if (($id && !CRM_Utils_Array::value($id, self::$country)) || !self::$country || !$id) {

      $config = CRM_Core_Config::singleton();
      $limitCodes = array();

      if ($applyLimit) {
        // limit the country list to the countries specified in CIVICRM_COUNTRY_LIMIT
        // (ensuring it's a subset of the legal values)
        // K/P: We need to fix this, i dont think it works with new setting files
        $limitCodes = CRM_Core_BAO_Country::countryLimit();
        if (!is_array($limitCodes)) {
          $limitCodes = array(
            $config->countryLimit => 1,
          );
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

      // if default country is set, percolate it to the top
      if ($config->defaultContactCountry()) {
        $countryIsoCodes = self::countryIsoCode();
        $defaultID = array_search($config->defaultContactCountry(), $countryIsoCodes);
        if ($defaultID !== FALSE) {
          $default[$defaultID] = CRM_Utils_Array::value($defaultID, self::$country);
          self::$country = $default + self::$country;
        }
      }

      // localise the country names if in an non-en_US locale
      $tsLocale = CRM_Core_I18n::getLocale();
      if ($tsLocale != '' and $tsLocale != 'en_US') {
        $i18n = CRM_Core_I18n::singleton();
        $i18n->localizeArray(self::$country, array(
          'context' => 'country',
        ));
        self::$country = CRM_Utils_Array::asort(self::$country);
      }
    }
    if ($id) {
      if (array_key_exists($id, self::$country)) {
        return self::$country[$id];
      }
      else {
        return CRM_Core_DAO::$_nullObject;
      }
    }
    return self::$country;
  }

  /**
   * Get all the country ISO Code abbreviations from the database.
   *
   * The static array countryIsoCode is returned, and if it's
   * called the first time, the <b>Country DAO</b> is used
   * to get all the countries' ISO codes.
   *
   * Note: any database errors will be trapped by the DAO.
   *
   *
   * @param bool $id
   *
   * @return array
   *   array reference of all country ISO codes.
   */
  public static function &countryIsoCode($id = FALSE) {
    if (!self::$countryIsoCode) {
      self::populate(self::$countryIsoCode, 'CRM_Core_DAO_Country', TRUE, 'iso_code');
    }
    if ($id) {
      if (array_key_exists($id, self::$countryIsoCode)) {
        return self::$countryIsoCode[$id];
      }
      else {
        return CRM_Core_DAO::$_nullObject;
      }
    }
    return self::$countryIsoCode;
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

    if (!self::$group) {
      self::$group = array();
    }

    $groupKey = ($groupType ? $groupType : 'null') . !empty($excludeHidden);

    if (!isset(self::$group[$groupKey])) {
      self::$group[$groupKey] = NULL;
      self::populate(self::$group[$groupKey], 'CRM_Contact_DAO_Group', FALSE, 'title', 'is_active', $condition);
    }
    return self::$group[$groupKey];
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
  public static function nestedGroup($checkPermissions = TRUE, $groupType = NULL, $excludeHidden = TRUE) {
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
   * @param bool|NULL $isActive
   *   Filter by is_active. NULL to disable.
   *
   * @return array
   *   array reference of all relationship types.
   */
  public static function &relationshipType($valueColumnName = 'label', $reset = FALSE, $isActive = 1) {
    $cacheKey = $valueColumnName . '::' . $isActive;
    if (!CRM_Utils_Array::value($cacheKey, self::$relationshipType) || $reset) {
      self::$relationshipType[$cacheKey] = array();

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

        self::$relationshipType[$cacheKey][$relationshipTypeDAO->id] = array(
          'id' => $relationshipTypeDAO->id,
          $column_a_b => $relationshipTypeDAO->$column_a_b,
          $column_b_a => $relationshipTypeDAO->$column_b_a,
          'contact_type_a' => "$relationshipTypeDAO->contact_type_a",
          'contact_type_b' => "$relationshipTypeDAO->contact_type_b",
          'contact_sub_type_a' => "$relationshipTypeDAO->contact_sub_type_a",
          'contact_sub_type_b' => "$relationshipTypeDAO->contact_sub_type_b",
        );
      }
    }

    return self::$relationshipType[$cacheKey];
  }

  /**
   * Get all the ISO 4217 currency codes
   *
   * so far, we use this for validation only, so there's no point of putting this into the database
   *
   *
   * @return array
   *   array reference of all currency codes
   */
  public static function &currencyCode() {
    if (!self::$currencyCode) {
      self::$currencyCode = array(
        'AFN',
        'ALL',
        'DZD',
        'USD',
        'EUR',
        'AOA',
        'XCD',
        'XCD',
        'ARS',
        'AMD',
        'AWG',
        'AUD',
        'EUR',
        'AZM',
        'BSD',
        'BHD',
        'BDT',
        'BBD',
        'BYR',
        'EUR',
        'BZD',
        'XOF',
        'BMD',
        'INR',
        'BTN',
        'BOB',
        'BOV',
        'BAM',
        'BWP',
        'NOK',
        'BRL',
        'USD',
        'BND',
        'BGN',
        'XOF',
        'BIF',
        'KHR',
        'XAF',
        'CAD',
        'CVE',
        'KYD',
        'XAF',
        'XAF',
        'CLP',
        'CLF',
        'CNY',
        'AUD',
        'AUD',
        'COP',
        'COU',
        'KMF',
        'XAF',
        'CDF',
        'NZD',
        'CRC',
        'XOF',
        'HRK',
        'CUP',
        'CYP',
        'CZK',
        'DKK',
        'DJF',
        'XCD',
        'DOP',
        'USD',
        'EGP',
        'SVC',
        'USD',
        'XAF',
        'ERN',
        'EEK',
        'ETB',
        'FKP',
        'DKK',
        'FJD',
        'EUR',
        'EUR',
        'EUR',
        'XPF',
        'EUR',
        'XAF',
        'GMD',
        'GEL',
        'EUR',
        'GHC',
        'GIP',
        'EUR',
        'DKK',
        'XCD',
        'EUR',
        'USD',
        'GTQ',
        'GNF',
        'GWP',
        'XOF',
        'GYD',
        'HTG',
        'USD',
        'AUD',
        'EUR',
        'HNL',
        'HKD',
        'HUF',
        'ISK',
        'INR',
        'IDR',
        'XDR',
        'IRR',
        'IQD',
        'EUR',
        'ILS',
        'EUR',
        'JMD',
        'JPY',
        'JOD',
        'KZT',
        'KES',
        'AUD',
        'KPW',
        'KRW',
        'KWD',
        'KGS',
        'LAK',
        'LVL',
        'LBP',
        'ZAR',
        'LSL',
        'LRD',
        'LYD',
        'CHF',
        'LTL',
        'EUR',
        'MOP',
        'MKD',
        'MGA',
        'MWK',
        'MYR',
        'MVR',
        'XOF',
        'MTL',
        'USD',
        'EUR',
        'MRO',
        'MUR',
        'EUR',
        'MXN',
        'MXV',
        'USD',
        'MDL',
        'EUR',
        'MNT',
        'XCD',
        'MAD',
        'MZM',
        'MMK',
        'ZAR',
        'NAD',
        'AUD',
        'NPR',
        'EUR',
        'ANG',
        'XPF',
        'NZD',
        'NIO',
        'XOF',
        'NGN',
        'NZD',
        'AUD',
        'USD',
        'NOK',
        'OMR',
        'PKR',
        'USD',
        'PAB',
        'USD',
        'PGK',
        'PYG',
        'PEN',
        'PHP',
        'NZD',
        'PLN',
        'EUR',
        'USD',
        'QAR',
        'EUR',
        'ROL',
        'RON',
        'RUB',
        'RWF',
        'SHP',
        'XCD',
        'XCD',
        'EUR',
        'XCD',
        'WST',
        'EUR',
        'STD',
        'SAR',
        'XOF',
        'CSD',
        'EUR',
        'SCR',
        'SLL',
        'SGD',
        'SKK',
        'SIT',
        'SBD',
        'SOS',
        'ZAR',
        'EUR',
        'LKR',
        'SDD',
        'SRD',
        'NOK',
        'SZL',
        'SEK',
        'CHF',
        'CHW',
        'CHE',
        'SYP',
        'TWD',
        'TJS',
        'TZS',
        'THB',
        'USD',
        'XOF',
        'NZD',
        'TOP',
        'TTD',
        'TND',
        'TRY',
        'TRL',
        'TMM',
        'USD',
        'AUD',
        'UGX',
        'UAH',
        'AED',
        'GBP',
        'USD',
        'USS',
        'USN',
        'USD',
        'UYU',
        'UZS',
        'VUV',
        'VEB',
        'VND',
        'USD',
        'USD',
        'XPF',
        'MAD',
        'YER',
        'ZMK',
        'ZWD',
        'XAU',
        'XBA',
        'XBB',
        'XBC',
        'XBD',
        'XPD',
        'XPT',
        'XAG',
        'XFU',
        'XFO',
        'XTS',
        'XXX',
      );
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
    $condition = "is_test = ";
    $condition .= ($test) ? '1' : '0';

    if ($additionalCond) {
      $condition .= " AND ( $additionalCond ) ";
    }

    // CRM-7178. Make sure we only include payment processors valid in this
    // domain
    $condition .= " AND domain_id = " . CRM_Core_Config::domainID();

    $cacheKey = $condition . '_' . (int) $all;
    if (!isset(self::$paymentProcessor[$cacheKey])) {
      self::populate(self::$paymentProcessor[$cacheKey], 'CRM_Financial_DAO_PaymentProcessor', $all, 'name', 'is_active', $condition, 'is_default desc, name');
    }

    return self::$paymentProcessor[$cacheKey];
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
    $cacheKey = $id . '_' . $return;
    if (empty(self::$paymentProcessorType[$cacheKey])) {
      self::populate(self::$paymentProcessorType[$cacheKey], 'CRM_Financial_DAO_PaymentProcessorType', $all, $return, 'is_active', NULL, "is_default, $return", 'id');
    }
    if ($id && CRM_Utils_Array::value($id, self::$paymentProcessorType[$cacheKey])) {
      return self::$paymentProcessorType[$cacheKey][$id];
    }
    return self::$paymentProcessorType[$cacheKey];
  }

  /**
   * Get all the World Regions from Database.
   *
   *
   * @param bool $id
   *
   * @return array
   *   array reference of all World Regions
   */
  public static function &worldRegion($id = FALSE) {
    if (!self::$worldRegions) {
      self::populate(self::$worldRegions, 'CRM_Core_DAO_Worldregion', TRUE, 'name', NULL, NULL, 'id');
    }

    if ($id) {
      if (array_key_exists($id, self::$worldRegions)) {
        return self::$worldRegions[$id];
      }
      else {
        return CRM_Core_DAO::$_nullObject;
      }
    }

    return self::$worldRegions;
  }

  /**
   * @deprecated Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all Activity Statuses.
   *
   * The static array activityStatus is returned
   *
   *
   * @param string $column
   *
   * @return array
   *   array reference of all activity statuses
   */
  public static function &activityStatus($column = 'label') {
    if (NULL === self::$activityStatus) {
      self::$activityStatus = array();
    }
    if (!array_key_exists($column, self::$activityStatus)) {
      self::$activityStatus[$column] = array();

      self::$activityStatus[$column] = CRM_Core_OptionGroup::values('activity_status', FALSE, FALSE, FALSE, NULL, $column);
    }

    return self::$activityStatus[$column];
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
  public static function &visibility($column = 'label') {
    if (!isset(self::$visibility)) {
      self::$visibility = array();
    }

    if (!isset(self::$visibility[$column])) {
      self::$visibility[$column] = CRM_Core_OptionGroup::values('visibility', FALSE, FALSE, FALSE, NULL, $column);
    }

    return self::$visibility[$column];
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
      $_cache = array();
    }

    if (!empty($_cache[$cacheKey])) {
      return $_cache[$cacheKey];
    }

    $query = "
SELECT civicrm_state_province.{$field} name, civicrm_state_province.id id
  FROM civicrm_state_province
WHERE country_id = %1
ORDER BY name";
    $params = array(
      1 => array(
        $countryID,
        'Integer',
      ),
    );

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $result = array();
    while ($dao->fetch()) {
      $result[$dao->id] = $dao->name;
    }

    // localise the stateProvince names if in an non-en_US locale
    $config = CRM_Core_Config::singleton();
    $tsLocale = CRM_Core_I18n::getLocale();
    if ($tsLocale != '' and $tsLocale != 'en_US') {
      $i18n = CRM_Core_I18n::singleton();
      $i18n->localizeArray($result, array(
        'context' => 'province',
      ));
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

      $result = array();
      while ($dao->fetch()) {
        $result[$dao->id] = $dao->abbreviation . ': ' . $dao->name;
      }
    }
    else {

      static $_cache = NULL;

      $cacheKey = "{$stateID}_name";
      if (!$_cache) {
        $_cache = array();
      }

      if (!empty($_cache[$cacheKey])) {
        return $_cache[$cacheKey];
      }

      $query = "
    SELECT civicrm_county.name name, civicrm_county.id id
      FROM civicrm_county
    WHERE state_province_id = %1
    ORDER BY name";
      $params = array(
        1 => array(
          $stateID,
          'Integer',
        ),
      );

      $dao = CRM_Core_DAO::executeQuery($query, $params);

      $result = array();
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
   * @return int
   *   the country id that the state belongs to
   */
  public static function countryIDForStateID($stateID) {
    if (empty($stateID)) {
      return CRM_Core_DAO::$_nullObject;
    }

    $query = "
SELECT country_id
FROM   civicrm_state_province
WHERE  id = %1
";
    $params = array(1 => array($stateID, 'Integer'));

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
    $index = $filter['greeting_type'] . '_' . $columnName;

    // also add contactType to the array
    $contactType = CRM_Utils_Array::value('contact_type', $filter);
    if ($contactType) {
      $index .= '_' . $contactType;
    }

    if (NULL === self::$greeting) {
      self::$greeting = array();
    }

    if (!CRM_Utils_Array::value($index, self::$greeting)) {
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

      self::$greeting[$index] = CRM_Core_OptionGroup::values($filter['greeting_type'], NULL, NULL, NULL, $filterCondition, $columnName);
    }

    return self::$greeting[$index];
  }

  /**
   * Construct array of default greeting values for contact type.
   *
   *
   * @return array
   *   array reference of default greetings.
   */
  public static function &greetingDefaults() {
    if (!self::$greetingDefaults) {
      $defaultGreetings = array();
      $contactTypes = self::get('CRM_Contact_DAO_Contact', 'contact_type', array(
          'keyColumn' => 'id',
          'labelColumn' => 'name',
        ));

      foreach ($contactTypes as $filter => $contactType) {
        $filterCondition = " AND (v.filter = 0 OR v.filter = $filter) AND v.is_default = 1 ";

        foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
          $tokenVal = CRM_Core_OptionGroup::values($greeting, NULL, NULL, NULL, $filterCondition, 'label');
          $defaultGreetings[$contactType][$greeting] = $tokenVal;
        }
      }

      self::$greetingDefaults = $defaultGreetings;
    }

    return self::$greetingDefaults;
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
      self::$extensions = array();
      $sql = '
        SELECT full_name, label
        FROM civicrm_extension
        WHERE is_active = 1
      ';
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
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
   * @param bool $optionGroupName
   *   Get All Option Group values- default is to get only active ones.
   *
   * @param int $id
   * @param null $condition
   *
   * @return array
   *   array reference of all Option Group Name
   */
  public static function accountOptionValues($optionGroupName, $id = NULL, $condition = NULL) {
    $cacheKey = $optionGroupName . '_' . $condition;
    if (empty(self::$accountOptionValues[$cacheKey])) {
      self::$accountOptionValues[$cacheKey] = CRM_Core_OptionGroup::values($optionGroupName, FALSE, FALSE, FALSE, $condition);
    }
    if ($id) {
      return CRM_Utils_Array::value($id, self::$accountOptionValues[$cacheKey]);
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
      Civi::$statics[__CLASS__]['taxRates'] = array();
      $option = civicrm_api3('option_value', 'get', array(
        'sequential' => 1,
        'option_group_id' => 'account_relationship',
        'name' => 'Sales Tax Account is',
      ));
      $value = array();
      if ($option['count'] !== 0) {
        if ($option['count'] > 1) {
          foreach ($option['values'] as $opt) {
            $value[] = $opt['value'];
          }
        }
        else {
          $value[] = $option['values'][0]['value'];
        }
        $where = 'AND efa.account_relationship IN (' . implode(', ', $value)  . ' )';
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

}
