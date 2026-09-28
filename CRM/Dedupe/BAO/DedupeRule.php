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
