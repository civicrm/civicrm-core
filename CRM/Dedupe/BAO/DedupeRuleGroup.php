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
 * The CiviCRM duplicate discovery engine is based on an
 * algorithm designed by David Strauss <david@fourkitchens.com>.
 */
class CRM_Dedupe_BAO_DedupeRuleGroup extends CRM_Dedupe_DAO_DedupeRuleGroup {

  /**
   * @var array
   *
   * Ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   *
   * @internal
   */
  public $contactIds = [];

  /**
   * Set the contact IDs to restrict the dedupe to.
   *
   * @param array $contactIds
   */
  public function setContactIds($contactIds) {
    CRM_Core_Error::deprecatedWarning('unused');
    $this->contactIds = $contactIds;
  }

  /**
   * Params to dedupe against (queries against the whole contact set otherwise)
   * @var array
   */
  public $params = [];

  /**
   * If there are no rules in rule group.
   *
   * @var bool
   *
   * @deprecated this was introduced in https://github.com/civicrm/civicrm-svn/commit/15136b07013b3477d601ebe5f7aa4f99f801beda
   * as an awkward way to avoid fatalling on an invalid rule set with no rules.
   *
   * Passing around a property is a bad way to do that check & we will work to remove.
   */
  public $noRules = FALSE;

  protected $temporaryTables = [];

  /**
   * Return a structure holding the supported tables, fields and their titles
   *
   * @param string $requestedType
   *   The requested contact type.
   *
   * @return array
   *   a table-keyed array of field-keyed arrays holding supported fields'
   *   titles
   * @throws \CRM_Core_Exception
   */
  public static function supportedFields(string $requestedType): array {
    if (!isset(Civi::$statics[__CLASS__]['supportedFields'])) {
      $genericFields = $fields = [];
      // We have a hard-coded list of entities - as we always have
      // but if that were to get restrictive we could declare whether dedupe fields are supported
      // in the entity metadata and maybe get rid of the hook at the end of this function?
      foreach (['Address', 'Email', 'Phone', 'Website', 'OpenID', 'IM', 'Note'] as $entity) {
        $entityFields = civicrm_api4($entity, 'getFields', [
          'where' => [['usage', 'CONTAINS', 'duplicate_matching']],
          'orderBy' => ['title'],
          'checkPermissions' => FALSE,
          // The action is a bit arguable - if not set it would default to 'get'.
          // At the moment it makes no difference but if someone where to add a
          // pseudo-field and set duplicate matching to 'true' then it would probably be a
          // field used when creating/updating & de-duping while saving a contact - so
          // save feels like a safer guess at future requirements than get.
          'action' => 'save',
        ], 'name');
        foreach ($entityFields as $entityField) {
          $genericFields[$entityField['table_name']][$entityField['column_name']] = $entityField['input_attrs']['label'] ?? $entityField['title'];
        }
      }

      foreach (CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
        $fields[$contactType] = $genericFields;
        $contactFields = civicrm_api4('Contact', 'getFields', [
          'where' => [['usage', 'CONTAINS', 'duplicate_matching']],
          'orderBy' => ['title'],
          'values' => [
            'contact_type' => $contactType,
          ],
          'checkPermissions' => FALSE,
          // The action is a bit arguable - if not set it would default to 'get'.
          // At the moment it makes no difference but if someone where to add a
          // pseudo-field and set duplicate matching to 'true' then it would probably be a
          // field used when creating/updating & de-duping while saving a contact - so
          // save feels like a safer guess at future requirements than get.
          'action' => 'save',
        ], 'name');
        // take the table.field pairs and their titles from importableFields() if the table is supported
        foreach ($contactFields as $entityField) {
          $fields[$contactType][$entityField['table_name']][$entityField['column_name']] = $entityField['input_attrs']['label'] ?? $entityField['title'];
        }
      }
      //Does this have to run outside of cache?
      CRM_Utils_Hook::dupeQuery(NULL, 'supportedFields', $fields);
      Civi::$statics[__CLASS__]['supportedFields'] = $fields;
    }

    return Civi::$statics[__CLASS__]['supportedFields'][$requestedType] ?? [];

  }

  /**
   * Return the SQL query for dropping the temporary table.
   */
  public function tableDropQuery() {
    return 'DROP TEMPORARY TABLE IF EXISTS dedupe';
  }

  /**
   * Fill the dedupe finder table.
   *
   * @internal do not access from outside core.
   *
   * @param int $id
   * @param array $contactIDs
   * @param array $params
   *
   * @return bool
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function fillTable(int $id, array $contactIDs, array $params): bool {
    $this->contactIds = $contactIDs;
    $this->params = $params;
    $this->id = $id;
    // make sure we've got a fetched dbrecord, not sure if this is enforced
    $this->find(TRUE);
    $optimizer = new CRM_Dedupe_FinderQueryOptimizer($this->id, $contactIDs, $params);
    // Reserved Rule Groups can optionally get special treatment by
    // implementing an optimization class and returning a query array.
    if ($optimizer->isUseReservedQuery()) {
      $tableQueries = $optimizer->getReservedQuery();
    }
    else {
      $tableQueries = $optimizer->getRuleQueries();
    }
    // if there are no rules in this rule group
    // add an empty query fulfilling the pattern
    if (!$tableQueries) {
      // Just for the hook.... (which is deprecated).
      $this->noRules = TRUE;
    }
    CRM_Utils_Hook::dupeQuery($this, 'table', $tableQueries);
    if (empty($tableQueries)) {
      return FALSE;
    }

    if ($params) {
      $this->temporaryTables['dedupe'] = CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe')
        ->createWithColumns("id1 int, weight int, UNIQUE UI_id1 (id1)")->getName();
      $dedupeCopyTemporaryTableObject = CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe');
      $this->temporaryTables['dedupe_copy'] = $dedupeCopyTemporaryTableObject->getName();
      $insertClause = "INSERT INTO {$this->temporaryTables['dedupe']}  (id1, weight)";
      $groupByClause = "GROUP BY id1, weight";
      $dupeCopyJoin = " JOIN {$this->temporaryTables['dedupe_copy']} ON {$this->temporaryTables['dedupe_copy']}.id1 = t1.column WHERE ";
    }
    else {
      $this->temporaryTables['dedupe'] = CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe')
        ->createWithColumns("id1 int, id2 int, weight int, UNIQUE UI_id1_id2 (id1, id2)")->getName();
      $dedupeCopyTemporaryTableObject = CRM_Utils_SQL_TempTable::build()
        ->setCategory('dedupe');
      $this->temporaryTables['dedupe_copy'] = $dedupeCopyTemporaryTableObject->getName();
      $insertClause = "INSERT INTO {$this->temporaryTables['dedupe']}  (id1, id2, weight)";
      $groupByClause = "GROUP BY id1, id2, weight";
      $dupeCopyJoin = " JOIN {$this->temporaryTables['dedupe_copy']} ON {$this->temporaryTables['dedupe_copy']}.id1 = t1.column AND {$this->temporaryTables['dedupe_copy']}.id2 = t2.column WHERE ";
    }
    $patternColumn = '/t1.(\w+)/';
    $exclWeightSum = [];

    while (!empty($tableQueries)) {
      [$isInclusive, $isDie] = self::isQuerySetInclusive($tableQueries, $this->threshold, $exclWeightSum);

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
            // drop dedupe_copy table just in case if its already there.
            $dedupeCopyTemporaryTableObject->drop();
            // get prepared to search within already found dupes if $searchWithinDupes flag is set
            $dedupeCopyTemporaryTableObject->createWithQuery("SELECT * FROM {$this->temporaryTables['dedupe']} WHERE weight >= {$weightSum}");

            preg_match($patternColumn, $query, $matches);
            $query = str_replace(' WHERE ', str_replace('column', $matches[1], $dupeCopyJoin), $query);

            // CRM-19612: If there's a union, there will be two WHEREs, and you
            // can't use the temp table twice.
            if (preg_match('/' . $this->temporaryTables['dedupe_copy'] . '[\S\s]*(union)[\S\s]*' . $this->temporaryTables['dedupe_copy'] . '/i', $query, $matches, PREG_OFFSET_CAPTURE)) {
              // Make a second temp table:
              $this->temporaryTables['dedupe_copy_2'] = CRM_Utils_SQL_TempTable::build()
                ->setCategory('dedupe')
                ->createWithQuery("SELECT * FROM {$this->temporaryTables['dedupe']} WHERE weight >= {$weightSum}")
                ->getName();
              // After the union, use that new temp table:
              $part1 = substr($query, 0, $matches[1][1]);
              $query = $part1 . str_replace($this->temporaryTables['dedupe_copy'], $this->temporaryTables['dedupe_copy_2'], substr($query, $matches[1][1]));
            }
          }
          $searchWithinDupes = 1;

          // construct and execute the intermediate query
          $query = "{$insertClause} {$query} {$groupByClause} ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)";
          $dao = CRM_Core_DAO::executeQuery($query);

          // FIXME: we need to be more accurate with affected rows, especially for insert vs duplicate insert.
          // And that will help optimize further.
          $affectedRows = $dao->affectedRows();

          // In an inclusive situation, failure of any query means no further processing -
          if ($affectedRows == 0) {
            // reset to make sure no further execution is done.
            $tableQueries = [];
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
        $dao = CRM_Core_DAO::executeQuery($query);
        if ($dao->affectedRows() >= 1) {
          $exclWeightSum[] = substr($fieldWeight, strrpos($fieldWeight, '.') + 1);
        }
      }
      else {
        // its a die situation
        break;
      }
    }
    return TRUE;
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
  public static function isQuerySetInclusive($tableQueries, $threshold, $exclWeightSum = []) {
    $input = [];
    foreach ($tableQueries as $key => $query) {
      $input[] = substr($key, strrpos($key, '.') + 1);
    }

    if (!empty($exclWeightSum)) {
      $input = array_merge($input, $exclWeightSum);
      rsort($input);
    }

    if (count($input) == 1) {
      return [FALSE, $input[0] < $threshold];
    }

    $totalCombinations = 0;
    for ($i = 0; $i < count($input); $i++) {
      $combination = [$input[$i]];
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
    return [$totalCombinations == 1, $totalCombinations <= 0];
  }

  /**
   * Sort queries by number of records for the table associated with them.
   *
   * @param array $tableQueries
   */
  public static function orderByTableCount(array &$tableQueries): void {
    uksort($tableQueries, [__CLASS__, 'isTableBigger']);
  }

  /**
   * Is the table extracted from the first string larger than the second string.
   *
   * @param string $a
   *   e.g civicrm_contact.first_name
   * @param string $b
   *   e.g civicrm_address.street_address
   *
   * @return int
   */
  private static function isTableBigger(string $a, string $b): int {
    $tableA = explode('.', $a)[0];
    $tableB = explode('.', $b)[0];
    if ($tableA === $tableB) {
      return 0;
    }
    return CRM_Core_BAO_SchemaHandler::getRowCountForTable($tableA) <=> CRM_Core_BAO_SchemaHandler::getRowCountForTable($tableB);
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
    $aclFrom = '';
    $aclWhere = '';

    if ($this->params) {
      if ($checkPermission) {
        [$aclFrom, $aclWhere] = CRM_Contact_BAO_Contact_Permission::cacheClause('civicrm_contact');
        $aclWhere = $aclWhere ? "AND {$aclWhere}" : '';
      }
      $query = "SELECT {$this->temporaryTables['dedupe']}.id1 as id
                FROM {$this->temporaryTables['dedupe']} JOIN civicrm_contact ON {$this->temporaryTables['dedupe']}.id1 = civicrm_contact.id {$aclFrom}
                WHERE contact_type = '{$this->contact_type}' AND is_deleted = 0 $aclWhere
                AND weight >= {$this->threshold}";
    }
    else {
      $aclWhere = '';
      if ($checkPermission) {
        [$aclFrom, $aclWhere] = CRM_Contact_BAO_Contact_Permission::cacheClause(['c1', 'c2']);
        $aclWhere = $aclWhere ? "AND {$aclWhere}" : '';
      }
      $query = "SELECT IF({$this->temporaryTables['dedupe']}.id1 < {$this->temporaryTables['dedupe']}.id2, {$this->temporaryTables['dedupe']}.id1, {$this->temporaryTables['dedupe']}.id2) as id1,
                IF({$this->temporaryTables['dedupe']}.id1 < {$this->temporaryTables['dedupe']}.id2, {$this->temporaryTables['dedupe']}.id2, {$this->temporaryTables['dedupe']}.id1) as id2, {$this->temporaryTables['dedupe']}.weight
                FROM {$this->temporaryTables['dedupe']} JOIN civicrm_contact c1 ON {$this->temporaryTables['dedupe']}.id1 = c1.id
                            JOIN civicrm_contact c2 ON {$this->temporaryTables['dedupe']}.id2 = c2.id {$aclFrom}
                       LEFT JOIN civicrm_dedupe_exception exc ON {$this->temporaryTables['dedupe']}.id1 = exc.contact_id1 AND {$this->temporaryTables['dedupe']}.id2 = exc.contact_id2
                WHERE c1.contact_type = '{$this->contact_type}' AND
                      c2.contact_type = '{$this->contact_type}'
                       AND c1.is_deleted = 0 AND c2.is_deleted = 0
                      {$aclWhere}
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
    $rgBao = new CRM_Dedupe_BAO_DedupeRuleGroup();
    $rgBao->contact_type = $params['contact_type'];
    if (!empty($params['id'])) {
      // accept an ID if provided
      $rgBao->id = $params['id'];
    }
    else {
      $rgBao->used = $params['used'];
    }
    $rgBao->find(TRUE);

    $ruleBao = new CRM_Dedupe_BAO_DedupeRule();
    $ruleBao->dedupe_rule_group_id = $rgBao->id;
    $ruleBao->find();
    $ruleFields = [];
    while ($ruleBao->fetch()) {
      $field_name = $ruleBao->rule_field;
      if ($field_name == 'phone_numeric') {
        $field_name = 'phone';
      }
      $ruleFields[$field_name] = $ruleBao->rule_weight;
    }

    return [$ruleFields, $rgBao->threshold];
  }

  /**
   * Get all of the combinations of fields that would work with a rule.
   *
   * @param array $rgFields
   * @param int $threshold
   * @param array $combos
   * @param array $running
   */
  public static function combos($rgFields, $threshold, &$combos, $running = []) {
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
   * @return array|string[]
   *   id => "nice name" of rule group
   */
  public static function getByType($contactType = NULL): array {
    $dao = new CRM_Dedupe_DAO_DedupeRuleGroup();

    if ($contactType) {
      $dao->contact_type = $contactType;
    }

    $dao->find();
    $result = [];
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
      \Civi::$statics[__CLASS__]['rule_groups'] = [];
    }
    if (empty(\Civi::$statics[__CLASS__]['rule_groups'][$rule_group_id])) {
      \Civi::$statics[__CLASS__]['rule_groups'][$rule_group_id]['contact_type'] = CRM_Core_DAO::getFieldValue(
        'CRM_Dedupe_DAO_DedupeRuleGroup',
        $rule_group_id,
        'contact_type'
      );
    }

    return \Civi::$statics[__CLASS__]['rule_groups'][$rule_group_id]['contact_type'];
  }

}
