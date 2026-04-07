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
class CRM_Dedupe_Finder {

  /**
   * Return a contact_id-keyed array of arrays of possible dupes
   * (of the key contact_id) - limited to dupes of $cids if provided.
   *
   * @deprecated avoid calling this function directly as it is likely to change.
   *
   * @param int $rgid
   *   Rule group id.
   * @param array $cids
   *   Contact ids to limit the search to.
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   *
   * @return array
   *   Array of (cid1, cid2, weight) dupe triples
   *
   * @throws \CRM_Core_Exception
   */
  public static function dupes($rgid, $cids = [], $checkPermissions = TRUE) {
    $rgBao = new CRM_Dedupe_BAO_DedupeRuleGroup();
    $rgBao->id = $rgid;
    if (!$rgBao->find(TRUE)) {
      throw new CRM_Core_Exception('Dedupe rule not found for selected contacts');
    }

    $dupes = [];

    // Note the table will be dropped when the variable is destroyed (at the end of this function)
    // due to the `__destruct` function on the class.
    $table = $cids ? self::getIdTable($cids) : NULL;
    $tableName = $table ? $table->getName() : NULL;
    CRM_Utils_Hook::findExistingDuplicates($dupes, [$rgid], $tableName, $checkPermissions);
    foreach ($dupes as &$dupe) {
      // re-format to original
      $dupe = [$dupe['entity_id_1'], $dupe['entity_id_2'], $dupe['weight']];
    }
    return $dupes;
  }

  private static function getIdTable($ids): CRM_Utils_SQL_TempTable {
    $table = CRM_Utils_SQL_TempTable::build()
      ->setAutodrop()
      ->setDurable()
      ->setCategory('dedupetable')
      ->createWithColumns('id int(10)');
    CRM_Core_DAO::executeQuery('ALTER TABLE ' . $table->getName() . ' ADD index(id)');
    $insert = ' INSERT INTO ' . $table->getName() . ' (`id`)
      VALUES (' . implode('), (', $ids) . ')';
    CRM_Core_DAO::executeQuery($insert);
    return $table;
  }

  /**
   * Return an array of possible dupes, based on the provided array of
   * params, using the default rule group for the given contact type and
   * usage.
   *
   * check_permission is a boolean flag to indicate if permission should be considered.
   * default is to always check permissioning but public pages for example might not want
   * permission to be checked for anonymous users. Refer CRM-6211. We might be breaking
   * Multi-Site dedupe for public pages.
   *
   * @param array $params
   *   Array of params of the form $params[$table][$field] == $value.
   * @param string $ctype
   *   Contact type to match against.
   * @param string $used
   *   Dedupe rule group usage ('Unsupervised' or 'Supervised' or 'General').
   * @param array $except
   *   Array of contacts that shouldn't be considered dupes.
   * @param int $ruleGroupID
   *   The id of the dedupe rule we should be using.
   *
   * @deprecated since 6.0 will be removed around 6.24 (long deprecation as there are many non-core callers)
   *
   * @return array
   *   matching contact ids
   * @throws \CRM_Core_Exception
   */
  public static function dupesByParams(
    $params,
    $ctype,
    $used = 'Unsupervised',
    $except = [],
    $ruleGroupID = NULL
  ) {
    $checkPermission = $params['check_permission'] ?? TRUE;

    $foundByID = FALSE;
    if ($ruleGroupID) {
      $rgBao = new CRM_Dedupe_BAO_DedupeRuleGroup();
      $rgBao->id = $ruleGroupID;
      $rgBao->contact_type = $ctype;
      if ($rgBao->find(TRUE)) {
        $foundByID = TRUE;
      }
    }

    if (!$foundByID) {
      // @todo - add noisy deprecation - this is already set
      // unless this function is called directly, rather than via
      // a supported api. No core uses do this - they all go through
      // `BAO_Contact::getDuplicateContacts()`. Extensions should use
      // the v3 Contact.duplicatecheck or (preferably) v4 api
      // Contact.getDuplicates().
      $rgBao = new CRM_Dedupe_BAO_DedupeRuleGroup();
      $rgBao->contact_type = $ctype;
      $rgBao->used = $used;
      if (!$rgBao->find(TRUE)) {
        throw new CRM_Core_Exception("$used rule for $ctype does not exist");
      }
    }
    $finderParams = [
      'rule_group_id' => $rgBao->id,
      'contact_type' => $ctype,
      'check_permission' => $checkPermission,
      'excluded_contact_ids' => $except,
      'match_params' => $params,
      'rule' => $used,
    ];
    return CRM_Contact_BAO_Contact::findDuplicates($finderParams, ['is_legacy_usage' => TRUE]);
  }

  /**
   * Return a contact_id-keyed array of arrays of possible dupes in the given group.
   *
   * @param int $rgid
   *   Rule group id.
   * @param int $gid
   *   Contact group id.
   *
   * @param int $searchLimit
   *  Limit for the number of contacts to be used for comparison.
   *  The search methodology finds all matches for the searchedContacts so this limits
   *  the number of searched contacts, not the matches found.
   *
   * @return array
   *   array of (cid1, cid2, weight) dupe triples
   *
   * @throws \CRM_Core_Exception
   */
  public static function dupesInGroup($rgid, $gid, $searchLimit = 0) {
    $cids = array_keys(CRM_Contact_BAO_Group::getMember($gid, TRUE, $searchLimit));
    if (!empty($cids)) {
      return self::dupes($rgid, $cids);
    }
    return [];
  }

  /**
   * @param array $fields
   * @param array $flat
   * @param string $ctype
   *
   * @throws \CRM_Core_Exception
   */
  private static function appendCustomDataFields(array &$fields, array &$flat, string $ctype): void {
    $subTypes = $fields['contact_sub_type'] ?? [];
    // Only return custom for subType + unrestricted or return all custom
    // fields.
    if (!is_array($subTypes)) {
      if (empty($subTypes)) {
        $subTypes = [];
      }
      else {
        $subTypes = (array) $subTypes;
      }
    }
    foreach ($subTypes as $index => $subType) {
      $validatedSubType = self::validateSubTypeByEntity($ctype, $subType);
      if ($subType !== $validatedSubType) {
        // This is a security check rather than a deprecation.
        \Civi::log()->warning('invalid subtype passed to duplicate check {type}', ['type' => $subType]);
        unset($subTypes[$index]);
      }
    }

    $filters = [
      'extends' => $ctype,
      'is_active' => TRUE,
    ];
    if ($subTypes) {
      foreach ($subTypes as $subType) {
        $filters['extends_entity_column_value'][] = $subType;
      }
      $filters['extends_entity_column_value'][] = NULL;
    }

    $customGroups = CRM_Core_BAO_CustomGroup::getAll($filters, CRM_Core_Permission::EDIT);
    $customFields = [];
    foreach ($customGroups as $group) {
      foreach ($group['fields'] as $field) {
        $customFields[$field['id']] = $field;
      }
    }

    foreach ($customFields as $field) {
      $fieldId = $field['id'];
      $serialize = CRM_Core_BAO_CustomField::isSerialized($field);

      // Reset all checkbox, radio and multiselect data
      if ($field['html_type'] === 'Radio' || $serialize) {
        $customFields[$fieldId]['customValue']['data'] = 'NULL';
      }

      $v = NULL;
      foreach ($fields as $key => $val) {
        if (preg_match('/^custom_(\d+)_?(-?\d+)?$/', $key, $match) &&
          $match[1] == $field['id']
        ) {
          $v = $val;
        }
      }

      if (!isset($customFields[$fieldId]['customValue'])) {
        // field exists in db so populate value from "form".
        $customFields[$fieldId]['customValue'] = [];
      }

      // Serialize checkbox and multi-select data (using array keys for checkbox)
      if ($serialize) {
        $v = ($v && $field['html_type'] === 'Checkbox') ? array_keys($v) : $v;
        $v = $v ? CRM_Utils_Array::implodePadded($v) : NULL;
      }

      switch ($field['html_type']) {

        case 'Select Date':
          $date = CRM_Utils_Date::processDate($v);
          $flat[$field['column_name']] = $date;
          break;

        case 'File':
          $flat[$field['column_name']] = NULL;
          break;

        default:
          $flat[$field['column_name']] = $v ?? NULL;
          break;
      }
    }
  }

  /**
   * A hackish function needed to massage CRM_Contact_Form_$ctype::formRule()
   * object into a valid $params array for dedupe
   *
   * @param array $fields
   *   Contact structure from formRule().
   * @param string $ctype
   *   Contact type of the given contact.
   *
   * @return array
   *   valid $params array for dedupe
   * @throws \CRM_Core_Exception
   */
  public static function formatParams($fields, $ctype) {
    $flat = [];
    CRM_Utils_Array::flatten($fields, $flat);

    // handle {birth,deceased}_date
    foreach (['birth_date', 'deceased_date'] as $date) {
      if (!empty($fields[$date])) {
        $original = $fields[$date];
        $flat[$date] = $fields[$date];
        if (is_array($flat[$date])) {
          $flat[$date] = CRM_Utils_Date::format($flat[$date]);
        }
        $flat[$date] = CRM_Utils_Date::processDate($flat[$date]);
        if ($flat[$date] !== $original) {
          CRM_Core_Error::deprecatedWarning('passing in mis-formatted date values is deprecated');
        }
      }
    }

    if (!empty($flat['contact_source'])) {
      $flat['source'] = $flat['contact_source'];
      unset($flat['contact_source']);
      CRM_Core_Error::deprecatedWarning('passing in mis-named source field is deprecated');
    }

    // handle preferred_communication_method
    if (!empty($fields['preferred_communication_method'])) {
      $methods = array_intersect($fields['preferred_communication_method'], ['1']);
      $methods = array_keys($methods);
      sort($methods);
      if ($methods) {
        $flat['preferred_communication_method'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $methods) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }

    // handle custom data
    self::appendCustomDataFields($fields, $flat, $ctype);

    // if the key is dotted, keep just the last part of it
    foreach ($flat as $key => $value) {
      if (substr_count($key, '.')) {
        $last = explode('.', $key);
        $last = array_pop($last);
        // make sure the first occurrence is kept, not the last
        if (!isset($flat[$last])) {
          $flat[$last] = $value;
        }
        unset($flat[$key]);
      }
    }

    // drop the -digit (and -Primary, for CRM-3902) postfixes (so event registration's $flat['email-5'] becomes $flat['email'])
    // FIXME: CRM-5026 should be fixed here; the below clobbers all address info; we should split off address fields and match
    // the -digit to civicrm_address.location_type_id and -Primary to civicrm_address.is_primary
    foreach ($flat as $key => $value) {
      $matches = [];
      if (preg_match('/(.*)-(Primary-[\d+])$|(.*)-(\d+-\d+)$|(.*)-(\d+|Primary)$/', $key, $matches)) {
        $return = array_values(array_filter($matches));
        // make sure the first occurrence is kept, not the last
        $flat[$return[1]] = empty($flat[$return[1]]) ? $value : $flat[$return[1]];
        unset($flat[$key]);
      }
    }

    $params = [];

    foreach (CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields($ctype) as $table => $fields) {
      if ($table === 'civicrm_address') {
        // for matching on civicrm_address fields, we also need the location_type_id
        $fields['location_type_id'] = '';
        // FIXME: we also need to do some hacking for id and name fields, see CRM-3902’s comments
        $fixes = [
          'address_name' => 'name',
          'country' => 'country_id',
          'state_province' => 'state_province_id',
          'county' => 'county_id',
        ];
        foreach ($fixes as $orig => $target) {
          if (!empty($flat[$orig])) {
            $params[$table][$target] = $flat[$orig];
          }
        }
      }
      if ($table === 'civicrm_phone') {
        if (!empty($flat['phone'])) {
          $flat['phone_numeric'] = preg_replace('/[^\d]/', '', $flat['phone']);
        }
      }
      foreach ($fields as $field => $title) {
        if (!empty($flat[$field])) {
          $params[$table][$field] = $flat[$field];
        }
      }
    }
    return $params;
  }

  /**
   * Validates contact subtypes and event types.
   *
   * Performs case-insensitive matching of strings and outputs the correct case.
   * e.g. an input of "meeting" would output "Meeting".
   *
   * For all other entities, it doesn't validate except to check the subtype is an integer.
   *
   * @param string $entityType
   * @param string $subType
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  private static function validateSubTypeByEntity($entityType, $subType) {

    if (is_numeric($subType)) {
      return $subType;
    }

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypeInfo(TRUE);
    $contactTypes['Contact'] = 1;

    if (!array_key_exists($entityType, $contactTypes)) {
      throw new CRM_Core_Exception('Invalid Entity Filter');
    }

    $subTypes = CRM_Contact_BAO_ContactType::subTypeInfo($entityType, TRUE);
    $subTypes = array_column($subTypes, 'name', 'name');
    // When you create a new contact type it gets saved in mixed case in the database.
    // Eg. "Service User" becomes "Service_User" in civicrm_contact_type.name
    // But that field does not differentiate case (eg. you can't add Service_User and service_user because mysql will report a duplicate error)
    // webform_civicrm and some other integrations pass in the name as lowercase to API3 Contact.duplicatecheck
    // Since we can't actually have two strings with different cases in the database perform a case-insensitive search here:
    $subTypesByName = array_combine($subTypes, $subTypes);
    $subTypesByName = array_change_key_case($subTypesByName, CASE_LOWER);
    $subTypesByKey = array_change_key_case($subTypes, CASE_LOWER);
    $subTypeKey = mb_strtolower($subType);
    if (!array_key_exists($subTypeKey, $subTypesByKey) && !in_array($subTypeKey, $subTypesByName)) {
      \Civi::log()->debug("entityType: {$entityType}; subType: {$subType}");
      throw new CRM_Core_Exception('Invalid Filter');
    }
    return $subTypesByName[$subTypeKey] ?? $subTypesByKey[$subTypeKey];
  }

}
