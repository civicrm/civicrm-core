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
class CRM_Dedupe_BAO_DedupeRule extends CRM_Dedupe_DAO_DedupeRule {

  /**
   * Return the SQL query for the given rule - either for finding matching
   * pairs of contacts, or for matching against the $params variable (if set).
   *
   * @param array|null $params
   *   Params to dedupe against (queries against the whole contact set otherwise)
   * @param array $contactIDs
   *   Ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   * @param array $rule
   *
   * @return string
   *   SQL query performing the search
   *   or NULL if params is present and doesn't have and for a field.
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated since 5.73 will be removed around 5.80
   * @internal do not call from outside tested core code. No universe uses Feb 2024.
   */
  public static function sql($params, $contactIDs, array $rule): ?string {
    CRM_Core_Error::deprecatedFunctionWarning('unsed, no alternative');
    if ($params &&
      (!array_key_exists($rule['rule_table'], $params) ||
        !array_key_exists($rule['rule_field'], $params[$rule['rule_table']])
      )
    ) {
      // if params is present and doesn't have an entry for a field, don't construct the clause.
      return NULL;
    }

    // we need to initialise WHERE, ON and USING here, as some table types
    // extend them; $where is an array of required conditions, $on and
    // $using are arrays of required field matchings (for substring and
    // full matches, respectively)
    $where = [];
    $on = ["SUBSTR(t1.{$rule['rule_field']}, 1, {$rule['rule_length']}) = SUBSTR(t2.{$rule['rule_field']}, 1, {$rule['rule_length']})"];

    $innerJoinClauses = [
      "t1.{$rule['rule_field']} IS NOT NULL",
      "t2.{$rule['rule_field']} IS NOT NULL",
      "t1.{$rule['rule_field']} = t2.{$rule['rule_field']}",
    ];

    if (in_array(CRM_Dedupe_BAO_DedupeRule::getFieldType($rule['rule_field'], $rule['rule_table']), CRM_Utils_Type::getTextTypes(), TRUE)) {
      $innerJoinClauses[] = "t1.{$rule['rule_field']} <> ''";
      $innerJoinClauses[] = "t2.{$rule['rule_field']} <> ''";
    }

    $cidRefs = CRM_Core_DAO::getReferencesToContactTable();
    $eidRefs = CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact');

    switch ($rule['rule_table']) {
      case 'civicrm_contact':
        $id = 'id';
        //we should restrict by contact type in the first step
        $sql = "SELECT contact_type FROM civicrm_dedupe_rule_group WHERE id = {$rule['dedupe_rule_group_id']};";
        $ct = CRM_Core_DAO::singleValueQuery($sql);
        if ($params) {
          $where[] = "t1.contact_type = '{$ct}'";
        }
        else {
          $where[] = "t1.contact_type = '{$ct}'";
          $where[] = "t2.contact_type = '{$ct}'";
        }
        break;

      default:
        if (array_key_exists($rule['rule_table'], $eidRefs)) {
          $id = $eidRefs[$rule['rule_table']][0];
          $entity_table = $eidRefs[$rule['rule_table']][1];
          if ($params) {
            $where[] = "t1.$entity_table = 'civicrm_contact'";
          }
          else {
            $where[] = "t1.$entity_table = 'civicrm_contact'";
            $where[] = "t2.$entity_table = 'civicrm_contact'";
          }
        }
        elseif (array_key_exists($rule['rule_table'], $cidRefs)) {
          $id = $cidRefs[$rule['rule_table']][0];
        }
        else {
          throw new CRM_Core_Exception("Unsupported rule_table for civicrm_dedupe_rule.id of {$rule['id']}");
        }
        break;
    }

    // build SELECT based on the field names containing contact ids
    // if there are params provided, id1 should be 0
    if ($params) {
      $select = "t1.$id id1, {$rule['rule_weight']} weight";
      $subSelect = 'id1, weight';
    }
    else {
      $select = "t1.$id id1, t2.$id id2, {$rule['rule_weight']} weight";
      $subSelect = 'id1, id2, weight';
    }

    // build FROM (and WHERE, if it's a parametrised search)
    // based on whether the rule is about substrings or not
    if ($params) {
      $from = "{$rule['rule_table']} t1";
      $str = 'NULL';
      if (isset($params[$rule['rule_table']][$rule['rule_field']])) {
        $str = trim(CRM_Utils_Type::escape($params[$rule['rule_table']][$rule['rule_field']], 'String'));
      }
      if ($rule['rule_length']) {
        $where[] = "SUBSTR(t1.{$rule['rule_field']}, 1, {$rule['rule_length']}) = SUBSTR('$str', 1, {$rule['rule_length']})";
        $where[] = "t1.{$rule['rule_field']} IS NOT NULL";
      }
      else {
        $where[] = "t1.{$rule['rule_field']} = '$str'";
      }
    }
    else {
      if ($rule['rule_length']) {
        $from = "{$rule['rule_table']} t1 INNER JOIN {$rule['rule_table']} t2 ON (" . implode(' AND ', $on) . ")";
      }
      else {
        $from = "{$rule['rule_table']} t1 INNER JOIN {$rule['rule_table']} t2 ON (" . implode(' AND ', $innerJoinClauses) . ")";
      }
    }

    // finish building WHERE, also limit the results if requested
    if (!$params) {
      $where[] = "t1.$id < t2.$id";
    }
    $query = "SELECT $select FROM $from WHERE " . implode(' AND ', $where);
    if ($contactIDs) {
      $cids = [];
      foreach ($contactIDs as $cid) {
        $cids[] = CRM_Utils_Type::escape($cid, 'Integer');
      }
      if (count($cids) == 1) {
        $query .= " AND (t1.$id = {$cids[0]}) UNION $query AND t2.$id = {$cids[0]}";
      }
      else {
        $query .= " AND t1.$id IN (" . implode(',', $cids) . ")
        UNION $query AND  t2.$id IN (" . implode(',', $cids) . ")";
      }
      // The `weight` is ambiguous in the context of the union; put the whole
      // thing in a subquery.
      $query = "SELECT $subSelect FROM ($query) subunion";
    }

    return $query;
  }

  /**
   * find fields related to a rule group.
   *
   * @param array $params contains the rule group property to identify rule group
   *
   * @return array
   *   rule fields array associated to rule group
   *
   * @internal do not call from outside tested core code. No universe uses Feb 2024.
   */
  public static function dedupeRuleFields(array $params) {
    $rgBao = new CRM_Dedupe_BAO_DedupeRuleGroup();
    $rgBao->used = $params['used'];
    $rgBao->contact_type = $params['contact_type'];
    $rgBao->find(TRUE);

    $ruleBao = new CRM_Dedupe_BAO_DedupeRule();
    $ruleBao->dedupe_rule_group_id = $rgBao->id;
    $ruleBao->find();
    $ruleFields = [];
    while ($ruleBao->fetch()) {
      $field_name = $ruleBao->rule_field;
      if ($field_name === 'phone_numeric') {
        $field_name = 'phone';
      }
      $ruleFields[] = $field_name;
    }
    return $ruleFields;
  }

  /**
   * @param int $cid
   * @param int $oid
   *
   * @return bool
   *
   * @internal do not call from outside tested core code. No universe uses Feb 2024.
   */
  public static function validateContacts($cid, $oid) {
    if (!$cid || !$oid) {
      return NULL;
    }
    $exception = new CRM_Dedupe_DAO_DedupeException();
    $exception->contact_id1 = $cid;
    $exception->contact_id2 = $oid;
    //make sure contact2 > contact1.
    if ($cid > $oid) {
      $exception->contact_id1 = $oid;
      $exception->contact_id2 = $cid;
    }

    return !$exception->find(TRUE);
  }

  /**
   * Get the specification for the given field.
   *
   * @param string $fieldName
   * @param string $ruleTable
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal function has only ever been available from the class & will be moved.
   */
  public static function getFieldType(string $fieldName, string $ruleTable) {
    $entity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($ruleTable);
    if (!$entity) {
      // This means we have stored a custom field rather than an entity name in rule_table, figure out the entity.
      $customGroup = CRM_Core_BAO_CustomGroup::getGroup(['table_name' => $ruleTable]);
      if (!$customGroup) {
        throw new CRM_Core_Exception('Unknown dedupeRule field');
      }
      $entity = $customGroup['extends'];
      if (in_array($entity, CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE)) {
        $entity = 'Contact';
      }
      $fieldIds = array_column($customGroup['fields'], 'id', 'column_name');
      $fieldName = 'custom_' . $fieldIds[$fieldName];
    }
    $fields = civicrm_api3($entity, 'getfields', ['action' => 'create'])['values'];
    return $fields[$fieldName]['type'];
  }

}
