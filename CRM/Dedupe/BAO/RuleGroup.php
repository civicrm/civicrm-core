<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * The CiviCRM duplicate discovery engine is based on an
 * algorithm designed by David Strauss <david@fourkitchens.com>.
 */
class CRM_Dedupe_BAO_RuleGroup extends CRM_Dedupe_DAO_RuleGroup {

  /**
   * Ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   */
  var $contactIds = array();

  /**
   * Set the contact IDs to restrict the dedupe to.
   *
   * @param array $contactIds
   */
  public function setContactIds($contactIds) {
    $this->contactIds = $contactIds;
  }

  /**
   * Params to dedupe against (queries against the whole contact set otherwise)
   */
  var $params = array();

  /**
   * If there are no rules in rule group.
   */
  var $noRules = FALSE;

  protected $temporaryTables = [];

  /**
   * Return a structure holding the supported tables, fields and their titles
   *
   * @param string $requestedType
   *   The requested contact type.
   *
   * @return array
   *   a table-keyed array of field-keyed arrays holding supported fields' titles
   */
  public static function &supportedFields($requestedType) {
    static $fields = NULL;
    if (!$fields) {
      // this is needed, as we're piggy-backing importableFields() below
      $replacements = array(
        'civicrm_country.name' => 'civicrm_address.country_id',
        'civicrm_county.name' => 'civicrm_address.county_id',
        'civicrm_state_province.name' => 'civicrm_address.state_province_id',
        'gender.label' => 'civicrm_contact.gender_id',
        'individual_prefix.label' => 'civicrm_contact.prefix_id',
        'individual_suffix.label' => 'civicrm_contact.suffix_id',
        'addressee.label' => 'civicrm_contact.addressee_id',
        'email_greeting.label' => 'civicrm_contact.email_greeting_id',
        'postal_greeting.label' => 'civicrm_contact.postal_greeting_id',
        'civicrm_phone.phone' => 'civicrm_phone.phone_numeric',
      );
      // the table names we support in dedupe rules - a filter for importableFields()
      $supportedTables = array(
        'civicrm_address',
        'civicrm_contact',
        'civicrm_email',
        'civicrm_im',
        'civicrm_note',
        'civicrm_openid',
        'civicrm_phone',
      );

      foreach (array('Individual', 'Organization', 'Household') as $ctype) {
        // take the table.field pairs and their titles from importableFields() if the table is supported
        foreach (CRM_Contact_BAO_Contact::importableFields($ctype) as $iField) {
          if (isset($iField['where'])) {
            $where = $iField['where'];
            if (isset($replacements[$where])) {
              $where = $replacements[$where];
            }
            list($table, $field) = explode('.', $where);
            if (!in_array($table, $supportedTables)) {
              continue;
            }
            $fields[$ctype][$table][$field] = $iField['title'];
          }
        }
        // add custom data fields
        foreach (CRM_Core_BAO_CustomGroup::getTree($ctype, NULL, NULL, -1) as $key => $cg) {
          if (!is_int($key)) {
            continue;
          }
          foreach ($cg['fields'] as $cf) {
            $fields[$ctype][$cg['table_name']][$cf['column_name']] = $cf['label'];
          }
        }
      }
    }
    CRM_Utils_Hook::dupeQuery(CRM_Core_DAO::$_nullObject, 'supportedFields', $fields);
    return $fields[$requestedType];
  }

  /**
   * Return the SQL query for dropping the temporary table.
   */
  public function tableDropQuery() {
    return 'DROP TEMPORARY TABLE IF EXISTS dedupe';
  }

  /**
   * Return a set of SQL queries whose cummulative weights will mark matched
   * records for the RuleGroup::threasholdQuery() to retrieve.
   */
  public function tableQuery() {
    // make sure we've got a fetched dbrecord, not sure if this is enforced
    if (!$this->name == NULL || $this->is_reserved == NULL) {
      $this->find(TRUE);
    }

    // Reserved Rule Groups can optionally get special treatment by
    // implementing an optimization class and returning a query array.
    if ($this->is_reserved &&
      CRM_Utils_File::isIncludable("CRM/Dedupe/BAO/QueryBuilder/{$this->name}.php")
    ) {
      $command = empty($this->params) ? 'internal' : 'record';
      $queries = call_user_func(array("CRM_Dedupe_BAO_QueryBuilder_{$this->name}", $command), $this);
    }
    else {
      // All other rule groups have queries generated by the member dedupe
      // rules defined in the administrative interface.

      // Find all rules contained by this script sorted by weight so that
      // their execution can be short circuited on RuleGroup::fillTable()
      $bao = new CRM_Dedupe_BAO_Rule();
      $bao->dedupe_rule_group_id = $this->id;
      $bao->orderBy('rule_weight DESC');
      $bao->find();

      // Generate a SQL query for each rule in the rule group that is
      // tailored to respect the param and contactId options provided.
      $queries = array();
      while ($bao->fetch()) {
        $bao->contactIds = $this->contactIds;
        $bao->params = $this->params;

        // Skipping empty rules? Empty rules shouldn't exist; why check?
        if ($query = $bao->sql()) {
          $queries["{$bao->rule_table}.{$bao->rule_field}.{$bao->rule_weight}"] = $query;
        }
      }
    }

    // if there are no rules in this rule group
    // add an empty query fulfilling the pattern
    if (!$queries) {
      $this->noRules = TRUE;
      return array();
    }

    return $queries;
  }

  public function fillTable() {
    // get the list of queries handy
    $tableQueries = $this->tableQuery();

    if ($this->params && !$this->noRules) {
      $this->temporaryTables['dedupe'] = CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe')
        ->createWithColumns("id1 int, weight int, UNIQUE UI_id1 (id1)")->getName();
      $insertClause = "INSERT INTO {$this->temporaryTables['dedupe']}  (id1, weight)";
      $groupByClause = "GROUP BY id1, weight";
      $dupeCopyJoin = " JOIN dedupe_copy ON dedupe_copy.id1 = t1.column WHERE ";
    }
    else {
      $this->temporaryTables['dedupe'] = CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe')
        ->createWithColumns("id1 int, id2 int, weight int, UNIQUE UI_id1_id2 (id1, id2)")->getName();
      $insertClause = "INSERT INTO {$this->temporaryTables['dedupe']}  (id1, id2, weight)";
      $groupByClause = "GROUP BY id1, id2, weight";
      $dupeCopyJoin = " JOIN dedupe_copy ON dedupe_copy.id1 = t1.column AND dedupe_copy.id2 = t2.column WHERE ";
    }
    $patternColumn = '/t1.(\w+)/';
    $exclWeightSum = array();

    $dao = new CRM_Core_DAO();
    CRM_Utils_Hook::dupeQuery($this, 'table', $tableQueries);

    while (!empty($tableQueries)) {
      list($isInclusive, $isDie) = self::isQuerySetInclusive($tableQueries, $this->threshold, $exclWeightSum);

      if ($isInclusive) {
        // order queries by table count
        self::orderByTableCount($tableQueries);

        $weightSum = array_sum($exclWeightSum);
        $searchWithinDupes = !empty($exclWeightSum) ? 1 : 0;

        while (!empty($tableQueries)) {
          // extract the next query ( and weight ) to be executed
          $fieldWeight = array_keys($tableQueries);
          $fieldWeight = $fieldWeight[0];
          $query = array_shift($tableQueries);

          if ($searchWithinDupes) {
            // get prepared to search within already found dupes if $searchWithinDupes flag is set
            $dao->query("DROP TEMPORARY TABLE IF EXISTS dedupe_copy");
            $dao->query("CREATE TEMPORARY TABLE dedupe_copy SELECT * FROM {$this->temporaryTables['dedupe']} WHERE weight >= {$weightSum}");
            $dao->free();

            preg_match($patternColumn, $query, $matches);
            $query = str_replace(' WHERE ', str_replace('column', $matches[1], $dupeCopyJoin), $query);

            // CRM-19612: If there's a union, there will be two WHEREs, and you
            // can't use the temp table twice.
            if (preg_match('/dedupe_copy[\S\s]*(union)[\S\s]*dedupe_copy/i', $query, $matches, PREG_OFFSET_CAPTURE)) {
              // Make a second temp table:
              $dao->query("DROP TEMPORARY TABLE IF EXISTS dedupe_copy_2");
              $dao->query("CREATE TEMPORARY TABLE dedupe_copy_2 SELECT * FROM {$this->temporaryTables['dedupe']} WHERE weight >= {$weightSum}");
              $dao->free();
              // After the union, use that new temp table:
              $part1 = substr($query, 0, $matches[1][1]);
              $query = $part1 . str_replace('dedupe_copy', 'dedupe_copy_2', substr($query, $matches[1][1]));
            }
          }
          $searchWithinDupes = 1;

          // construct and execute the intermediate query
          $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
          $dao->query($query);

          // FIXME: we need to be more acurate with affected rows, especially for insert vs duplicate insert.
          // And that will help optimize further.
          $affectedRows = $dao->affectedRows();
          $dao->free();

          // In an inclusive situation, failure of any query means no further processing -
          if ($affectedRows == 0) {
            // reset to make sure no further execution is done.
            $tableQueries = array();
            break;
          }
          $weightSum = substr($fieldWeight, strrpos($fieldWeight, '.') + 1) + $weightSum;
        }
        // An exclusive situation -
      }
      elseif (!$isDie) {
        // since queries are already sorted by weights, we can continue as is
        $fieldWeight = array_keys($tableQueries);
        $fieldWeight = $fieldWeight[0];
        $query = array_shift($tableQueries);
        $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
        $dao->query($query);
        if ($dao->affectedRows() >= 1) {
          $exclWeightSum[] = substr($fieldWeight, strrpos($fieldWeight, '.') + 1);
        }
      }
      else {
        // its a die situation
        break;
      }
    }
  }

  /**
   * Function to determine if a given query set contains inclusive or exclusive set of weights.
   * The function assumes that the query set is already ordered by weight in desc order.
   * @param $tableQueries
   * @param $threshold
   * @param array $exclWeightSum
   *
   * @return array
   */
  public static function isQuerySetInclusive($tableQueries, $threshold, $exclWeightSum = array()) {
    $input = array();
    foreach ($tableQueries as $key => $query) {
      $input[] = substr($key, strrpos($key, '.') + 1);
    }

    if (!empty($exclWeightSum)) {
      $input = array_merge($input, $exclWeightSum);
      rsort($input);
    }

    if (count($input) == 1) {
      return array(FALSE, $input[0] < $threshold);
    }

    $totalCombinations = 0;
    for ($i = 0; $i < count($input); $i++) {
      $combination = array($input[$i]);
      if (array_sum($combination) >= $threshold) {
        $totalCombinations++;
        continue;
      }
      for ($j = $i + 1; $j < count($input); $j++) {
        $combination[] = $input[$j];
        if (array_sum($combination) >= $threshold) {
          $totalCombinations++;
        }
      }
    }
    return array($totalCombinations == 1, $totalCombinations <= 0);
  }

  /**
   * sort queries by number of records for the table associated with them.
   * @param $tableQueries
   */
  public static function orderByTableCount(&$tableQueries) {
    static $tableCount = array();

    $tempArray = array();
    foreach ($tableQueries as $key => $query) {
      $table = explode(".", $key);
      $table = $table[0];
      if (!array_key_exists($table, $tableCount)) {
        $query = "SELECT COUNT(*) FROM {$table}";
        $tableCount[$table] = CRM_Core_DAO::singleValueQuery($query);
      }
      $tempArray[$key] = $tableCount[$table];
    }

    asort($tempArray);
    foreach ($tempArray as $key => $count) {
      $tempArray[$key] = $tableQueries[$key];
    }
    $tableQueries = $tempArray;
  }

  /**
   * Return the SQL query for getting only the interesting results out of the dedupe table.
   *
   * @$checkPermission boolean $params a flag to indicate if permission should be considered.
   * default is to always check permissioning but public pages for example might not want
   * permission to be checked for anonymous users. Refer CRM-6211. We might be beaking
   * Multi-Site dedupe for public pages.
   *
   * @param bool $checkPermission
   *
   * @return string
   */
  public function thresholdQuery($checkPermission = TRUE) {
    $this->_aclFrom = '';
    // CRM-6603: anonymous dupechecks side-step ACLs
    $this->_aclWhere = ' AND is_deleted = 0 ';

    if ($this->params && !$this->noRules) {
      if ($checkPermission) {
        list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('civicrm_contact');
        $this->_aclWhere = $this->_aclWhere ? "AND {$this->_aclWhere}" : '';
      }
      $query = "SELECT {$this->temporaryTables['dedupe']}.id1 as id
                FROM {$this->temporaryTables['dedupe']} JOIN civicrm_contact ON {$this->temporaryTables['dedupe']}.id1 = civicrm_contact.id {$this->_aclFrom}
                WHERE contact_type = '{$this->contact_type}' {$this->_aclWhere}
                AND weight >= {$this->threshold}";
    }
    else {
      $this->_aclWhere = ' AND c1.is_deleted = 0 AND c2.is_deleted = 0';
      if ($checkPermission) {
        list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause(array('c1', 'c2'));
        $this->_aclWhere = $this->_aclWhere ? "AND {$this->_aclWhere}" : '';
      }
      $query = "SELECT IF({$this->temporaryTables['dedupe']}.id1 < {$this->temporaryTables['dedupe']}.id2, {$this->temporaryTables['dedupe']}.id1, {$this->temporaryTables['dedupe']}.id2) as id1,
                IF({$this->temporaryTables['dedupe']}.id1 < {$this->temporaryTables['dedupe']}.id2, {$this->temporaryTables['dedupe']}.id2, {$this->temporaryTables['dedupe']}.id1) as id2, {$this->temporaryTables['dedupe']}.weight
                FROM {$this->temporaryTables['dedupe']} JOIN civicrm_contact c1 ON {$this->temporaryTables['dedupe']}.id1 = c1.id
                            JOIN civicrm_contact c2 ON {$this->temporaryTables['dedupe']}.id2 = c2.id {$this->_aclFrom}
                       LEFT JOIN civicrm_dedupe_exception exc ON {$this->temporaryTables['dedupe']}.id1 = exc.contact_id1 AND {$this->temporaryTables['dedupe']}.id2 = exc.contact_id2
                WHERE c1.contact_type = '{$this->contact_type}' AND
                      c2.contact_type = '{$this->contact_type}' {$this->_aclWhere}
                      AND weight >= {$this->threshold} AND exc.contact_id1 IS NULL";
    }

    CRM_Utils_Hook::dupeQuery($this, 'threshold', $query);
    return $query;
  }

  /**
   * find fields related to a rule group.
   *
   * @param array $params
   *
   * @return array
   *   (rule field => weight) array and threshold associated to rule group
   */
  public static function dedupeRuleFieldsWeight($params) {
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->contact_type = $params['contact_type'];
    if (!empty($params['id'])) {
      // accept an ID if provided
      $rgBao->id = $params['id'];
    }
    else {
      $rgBao->used = $params['used'];
    }
    $rgBao->find(TRUE);

    $ruleBao = new CRM_Dedupe_BAO_Rule();
    $ruleBao->dedupe_rule_group_id = $rgBao->id;
    $ruleBao->find();
    $ruleFields = array();
    while ($ruleBao->fetch()) {
      $ruleFields[$ruleBao->rule_field] = $ruleBao->rule_weight;
    }

    return array($ruleFields, $rgBao->threshold);
  }

  /**
   * Get all of the combinations of fields that would work with a rule.
   *
   * @param array $rgFields
   * @param int $threshold
   * @param array $combos
   * @param array $running
   */
  public static function combos($rgFields, $threshold, &$combos, $running = array()) {
    foreach ($rgFields as $rgField => $weight) {
      unset($rgFields[$rgField]);
      $diff = $threshold - $weight;
      $runningnow = $running;
      $runningnow[] = $rgField;
      if ($diff > 0) {
        self::combos($rgFields, $diff, $combos, $runningnow);
      }
      else {
        $combos[] = $runningnow;
      }
    }
  }

  /**
   * Get an array of rule group id to rule group name
   * for all th groups for that contactType. If contactType
   * not specified, do it for all
   *
   * @param string $contactType
   *   Individual, Household or Organization.
   *
   *
   * @return array
   *   id => "nice name" of rule group
   */
  public static function getByType($contactType = NULL) {
    $dao = new CRM_Dedupe_DAO_RuleGroup();

    if ($contactType) {
      $dao->contact_type = $contactType;
    }

    $dao->find();
    $result = array();
    while ($dao->fetch()) {
      $title = !empty($dao->title) ? $dao->title : (!empty($dao->name) ? $dao->name : $dao->contact_type);

      $name = "$title - {$dao->used}";
      $result[$dao->id] = $name;
    }
    return $result;
  }


  /**
   * Get the cached contact type for a particular rule group.
   *
   * @param int $rule_group_id
   *
   * @return string
   */
  public static function getContactTypeForRuleGroup($rule_group_id) {
    if (!isset(\Civi::$statics[__CLASS__]) || !isset(\Civi::$statics[__CLASS__]['rule_groups'])) {
      \Civi::$statics[__CLASS__]['rule_groups'] = array();
    }
    if (empty(\Civi::$statics[__CLASS__]['rule_groups'][$rule_group_id])) {
      \Civi::$statics[__CLASS__]['rule_groups'][$rule_group_id]['contact_type'] = CRM_Core_DAO::getFieldValue(
        'CRM_Dedupe_DAO_RuleGroup',
        $rule_group_id,
        'contact_type'
      );
    }

    return \Civi::$statics[__CLASS__]['rule_groups'][$rule_group_id]['contact_type'];
  }

}
