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
 * $Id$
 *
 */

/**
 * The CiviCRM duplicate discovery engine is based on an
 * algorithm designed by David Strauss <david@fourkitchens.com>.
 */
class CRM_Dedupe_BAO_Rule extends CRM_Dedupe_DAO_Rule {

  /**
   * Ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   * @var array
   */
  public $contactIds = [];

  /**
   * Params to dedupe against (queries against the whole contact set otherwise)
   * @var array
   */
  public $params = [];

  /**
   * Return the SQL query for the given rule - either for finding matching
   * pairs of contacts, or for matching against the $params variable (if set).
   *
   * @return string
   *   SQL query performing the search
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function sql() {
    if ($this->params &&
      (!array_key_exists($this->rule_table, $this->params) ||
        !array_key_exists($this->rule_field, $this->params[$this->rule_table])
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
    $on = ["SUBSTR(t1.{$this->rule_field}, 1, {$this->rule_length}) = SUBSTR(t2.{$this->rule_field}, 1, {$this->rule_length})"];

    $innerJoinClauses = [
      "t1.{$this->rule_field} IS NOT NULL",
      "t2.{$this->rule_field} IS NOT NULL",
      "t1.{$this->rule_field} = t2.{$this->rule_field}",
    ];
    if ($this->getFieldType($this->rule_field) === CRM_Utils_Type::T_DATE) {
      $innerJoinClauses[] = "t1.{$this->rule_field} > '1000-01-01'";
      $innerJoinClauses[] = "t2.{$this->rule_field} > '1000-01-01'";
    }
    else {
      $innerJoinClauses[] = "t1.{$this->rule_field} <> ''";
      $innerJoinClauses[] = "t2.{$this->rule_field} <> ''";
    }

    switch ($this->rule_table) {
      case 'civicrm_contact':
        $id = 'id';
        //we should restrict by contact type in the first step
        $sql = "SELECT contact_type FROM civicrm_dedupe_rule_group WHERE id = {$this->dedupe_rule_group_id};";
        $ct = CRM_Core_DAO::singleValueQuery($sql);
        if ($this->params) {
          $where[] = "t1.contact_type = '{$ct}'";
        }
        else {
          $where[] = "t1.contact_type = '{$ct}'";
          $where[] = "t2.contact_type = '{$ct}'";
        }
        break;

      case 'civicrm_address':
        $id = 'contact_id';
        $on[] = 't1.location_type_id = t2.location_type_id';
        $innerJoinClauses[] = 't1.location_type_id = t2.location_type_id';
        if (!empty($this->params['civicrm_address']['location_type_id'])) {
          $locTypeId = CRM_Utils_Type::escape($this->params['civicrm_address']['location_type_id'], 'Integer', FALSE);
          if ($locTypeId) {
            $where[] = "t1.location_type_id = $locTypeId";
          }
        }
        break;

      case 'civicrm_email':
      case 'civicrm_im':
      case 'civicrm_openid':
      case 'civicrm_phone':
        $id = 'contact_id';
        break;

      case 'civicrm_note':
        $id = 'entity_id';
        if ($this->params) {
          $where[] = "t1.entity_table = 'civicrm_contact'";
        }
        else {
          $where[] = "t1.entity_table = 'civicrm_contact'";
          $where[] = "t2.entity_table = 'civicrm_contact'";
        }
        break;

      default:
        // custom data tables
        if (preg_match('/^civicrm_value_/', $this->rule_table) || preg_match('/^custom_value_/', $this->rule_table)) {
          $id = 'entity_id';
        }
        else {
          throw new CRM_Core_Exception("Unsupported rule_table for civicrm_dedupe_rule.id of {$this->id}");
        }
        break;
    }

    // build SELECT based on the field names containing contact ids
    // if there are params provided, id1 should be 0
    if ($this->params) {
      $select = "t1.$id id1, {$this->rule_weight} weight";
      $subSelect = 'id1, weight';
    }
    else {
      $select = "t1.$id id1, t2.$id id2, {$this->rule_weight} weight";
      $subSelect = 'id1, id2, weight';
    }

    // build FROM (and WHERE, if it's a parametrised search)
    // based on whether the rule is about substrings or not
    if ($this->params) {
      $from = "{$this->rule_table} t1";
      $str = 'NULL';
      if (isset($this->params[$this->rule_table][$this->rule_field])) {
        $str = CRM_Utils_Type::escape($this->params[$this->rule_table][$this->rule_field], 'String');
      }
      if ($this->rule_length) {
        $where[] = "SUBSTR(t1.{$this->rule_field}, 1, {$this->rule_length}) = SUBSTR('$str', 1, {$this->rule_length})";
        $where[] = "t1.{$this->rule_field} IS NOT NULL";
      }
      else {
        $where[] = "t1.{$this->rule_field} = '$str'";
      }
    }
    else {
      if ($this->rule_length) {
        $from = "{$this->rule_table} t1 JOIN {$this->rule_table} t2 ON (" . implode(' AND ', $on) . ")";
      }
      else {
        $from = "{$this->rule_table} t1 INNER JOIN {$this->rule_table} t2 ON (" . implode(' AND ', $innerJoinClauses) . ")";
      }
    }

    // finish building WHERE, also limit the results if requested
    if (!$this->params) {
      $where[] = "t1.$id < t2.$id";
    }
    $query = "SELECT $select FROM $from WHERE " . implode(' AND ', $where);
    if ($this->contactIds) {
      $cids = [];
      foreach ($this->contactIds as $cid) {
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
   */
  public static function dedupeRuleFields($params) {
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->used = $params['used'];
    $rgBao->contact_type = $params['contact_type'];
    $rgBao->find(TRUE);

    $ruleBao = new CRM_Dedupe_BAO_Rule();
    $ruleBao->dedupe_rule_group_id = $rgBao->id;
    $ruleBao->find();
    $ruleFields = [];
    while ($ruleBao->fetch()) {
      $field_name = $ruleBao->rule_field;
      if ($field_name == 'phone_numeric') {
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
   */
  public static function validateContacts($cid, $oid) {
    if (!$cid || !$oid) {
      return NULL;
    }
    $exception = new CRM_Dedupe_DAO_Exception();
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
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function getFieldType($fieldName) {
    $entity = CRM_Core_DAO_AllCoreTables::getBriefName(CRM_Core_DAO_AllCoreTables::getClassForTable($this->rule_table));
    if (!$entity) {
      // This means we have stored a custom field rather than an entity name in rule_table, figure out the entity.
      $entity = civicrm_api3('CustomGroup', 'getvalue', ['table_name' => $this->rule_table, 'return' => 'extends']);
      if (in_array($entity, ['Individual', 'Household', 'Organization'])) {
        $entity = 'Contact';
      }
      $fieldName = 'custom_' . civicrm_api3('CustomField', 'getvalue', ['column_name' => $fieldName, 'return' => 'id']);
    }
    $fields = civicrm_api3($entity, 'getfields', ['action' => 'create'])['values'];
    return $fields[$fieldName]['type'];
  }

}
