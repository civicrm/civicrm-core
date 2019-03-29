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
 */
class CRM_Dedupe_Merger {

  /**
   * FIXME: consider creating a common structure with cidRefs() and eidRefs()
   * FIXME: the sub-pages references by the URLs should
   * be loaded dynamically on the merge form instead
   * @return array
   */
  public static function relTables() {

    if (!isset(Civi::$statics[__CLASS__]['relTables'])) {

      // Setting these merely prevents enotices - but it may be more appropriate not to add the user table below
      // if the url can't be retrieved. A more standardised way to retrieve them is.
      // CRM_Core_Config::singleton()->userSystem->getUserRecordUrl() - however that function takes a contact_id &
      // we may need a different function when it is not known.
      $title = $userRecordUrl = '';

      $config = CRM_Core_Config::singleton();
      if ($config->userSystem->is_drupal) {
        $userRecordUrl = CRM_Utils_System::url('user/%ufid');
        $title = ts('%1 User: %2; user id: %3', [
          1 => $config->userFramework,
          2 => '$ufname',
          3 => '$ufid',
        ]);
      }
      elseif ($config->userFramework == 'Joomla') {
        $userRecordUrl = $config->userSystem->getVersion() > 1.5 ? $config->userFrameworkBaseURL . "index.php?option=com_users&view=user&task=user.edit&id=" . '%ufid' : $config->userFrameworkBaseURL . "index2.php?option=com_users&view=user&task=edit&id[]=" . '%ufid';
        $title = ts('%1 User: %2; user id: %3', [
          1 => $config->userFramework,
          2 => '$ufname',
          3 => '$ufid',
        ]);
      }

      $relTables = [
        'rel_table_contributions' => [
          'title' => ts('Contributions'),
          'tables' => [
            'civicrm_contribution',
            'civicrm_contribution_recur',
            'civicrm_contribution_soft',
          ],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=contribute'),
        ],
        'rel_table_contribution_page' => [
          'title' => ts('Contribution Pages'),
          'tables' => ['civicrm_contribution_page'],
          'url' => CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1&cid=$cid'),
        ],
        'rel_table_memberships' => [
          'title' => ts('Memberships'),
          'tables' => [
            'civicrm_membership',
            'civicrm_membership_log',
            'civicrm_membership_type',
          ],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=member'),
        ],
        'rel_table_participants' => [
          'title' => ts('Participants'),
          'tables' => ['civicrm_participant'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=participant'),
        ],
        'rel_table_events' => [
          'title' => ts('Events'),
          'tables' => ['civicrm_event'],
          'url' => CRM_Utils_System::url('civicrm/event/manage', 'reset=1&cid=$cid'),
        ],
        'rel_table_activities' => [
          'title' => ts('Activities'),
          'tables' => ['civicrm_activity', 'civicrm_activity_contact'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=activity'),
        ],
        'rel_table_relationships' => [
          'title' => ts('Relationships'),
          'tables' => ['civicrm_relationship'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=rel'),
        ],
        'rel_table_custom_groups' => [
          'title' => ts('Custom Groups'),
          'tables' => ['civicrm_custom_group'],
          'url' => CRM_Utils_System::url('civicrm/admin/custom/group', 'reset=1'),
        ],
        'rel_table_uf_groups' => [
          'title' => ts('Profiles'),
          'tables' => ['civicrm_uf_group'],
          'url' => CRM_Utils_System::url('civicrm/admin/uf/group', 'reset=1'),
        ],
        'rel_table_groups' => [
          'title' => ts('Groups'),
          'tables' => ['civicrm_group_contact'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=group'),
        ],
        'rel_table_notes' => [
          'title' => ts('Notes'),
          'tables' => ['civicrm_note'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=note'),
        ],
        'rel_table_tags' => [
          'title' => ts('Tags'),
          'tables' => ['civicrm_entity_tag'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=tag'),
        ],
        'rel_table_mailings' => [
          'title' => ts('Mailings'),
          'tables' => [
            'civicrm_mailing',
            'civicrm_mailing_event_queue',
            'civicrm_mailing_event_subscribe',
          ],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=mailing'),
        ],
        'rel_table_cases' => [
          'title' => ts('Cases'),
          'tables' => ['civicrm_case_contact'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=case'),
        ],
        'rel_table_grants' => [
          'title' => ts('Grants'),
          'tables' => ['civicrm_grant'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=grant'),
        ],
        'rel_table_pcp' => [
          'title' => ts('PCPs'),
          'tables' => ['civicrm_pcp'],
          'url' => CRM_Utils_System::url('civicrm/contribute/pcp/manage', 'reset=1'),
        ],
        'rel_table_pledges' => [
          'title' => ts('Pledges'),
          'tables' => ['civicrm_pledge', 'civicrm_pledge_payment'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=pledge'),
        ],
        'rel_table_users' => [
          'title' => $title,
          'tables' => ['civicrm_uf_match'],
          'url' => $userRecordUrl,
        ],
      ];

      $relTables += self::getMultiValueCustomSets('relTables');

      // Allow hook_civicrm_merge() to adjust $relTables
      CRM_Utils_Hook::merge('relTables', $relTables);

      // Cache the results in a static variable
      Civi::$statics[__CLASS__]['relTables'] = $relTables;
    }

    return Civi::$statics[__CLASS__]['relTables'];
  }

  /**
   * Returns the related tables groups for which a contact has any info entered.
   *
   * @param int $cid
   *
   * @return array
   */
  public static function getActiveRelTables($cid) {
    $cid = (int) $cid;
    $groups = [];

    $relTables = self::relTables();
    $cidRefs = self::cidRefs();
    $eidRefs = self::eidRefs();
    foreach ($relTables as $group => $params) {
      $sqls = [];
      foreach ($params['tables'] as $table) {
        if (isset($cidRefs[$table])) {
          foreach ($cidRefs[$table] as $field) {
            $sqls[] = "SELECT COUNT(*) AS count FROM $table WHERE $field = $cid";
          }
        }
        if (isset($eidRefs[$table])) {
          foreach ($eidRefs[$table] as $entityTable => $entityId) {
            $sqls[] = "SELECT COUNT(*) AS count FROM $table WHERE $entityId = $cid AND $entityTable = 'civicrm_contact'";
          }
        }
        foreach ($sqls as $sql) {
          if (CRM_Core_DAO::singleValueQuery($sql) > 0) {
            $groups[] = $group;
          }
        }
      }
    }
    return array_unique($groups);
  }

  /**
   * Get array tables and fields that reference civicrm_contact.id.
   *
   * This function calls the merge hook and only exists to wrap the DAO function to support that deprecated call.
   * The entityTypes hook is the recommended way to add tables to this result.
   *
   * I thought about adding another hook to alter tableReferences but decided it was unclear if there
   * are use cases not covered by entityTables and instead we should wait & see.
   */
  public static function cidRefs() {
    if (isset(\Civi::$statics[__CLASS__]) && isset(\Civi::$statics[__CLASS__]['contact_references'])) {
      return \Civi::$statics[__CLASS__]['contact_references'];
    }

    $contactReferences = $coreReferences = CRM_Core_DAO::getReferencesToContactTable();

    CRM_Utils_Hook::merge('cidRefs', $contactReferences);
    if ($contactReferences !== $coreReferences) {
      Civi::log()
        ->warning("Deprecated hook ::merge in context of 'cidRefs. Use entityTypes instead.", ['civi.tag' => 'deprecated']);
    }
    \Civi::$statics[__CLASS__]['contact_references'] = $contactReferences;
    return \Civi::$statics[__CLASS__]['contact_references'];
  }

  /**
   * Return tables and their fields referencing civicrm_contact.contact_id with entity_id
   */
  public static function eidRefs() {
    static $eidRefs;
    if (!$eidRefs) {
      // FIXME: this should be generated dynamically from the schema
      // tables that reference contacts with entity_{id,table}
      $eidRefs = [
        'civicrm_acl' => ['entity_table' => 'entity_id'],
        'civicrm_acl_entity_role' => ['entity_table' => 'entity_id'],
        'civicrm_entity_file' => ['entity_table' => 'entity_id'],
        'civicrm_log' => ['entity_table' => 'entity_id'],
        'civicrm_mailing_group' => ['entity_table' => 'entity_id'],
        'civicrm_note' => ['entity_table' => 'entity_id'],
      ];

      // Allow hook_civicrm_merge() to adjust $eidRefs
      CRM_Utils_Hook::merge('eidRefs', $eidRefs);
    }
    return $eidRefs;
  }

  /**
   * Return tables using locations.
   */
  public static function locTables() {
    static $locTables;
    if (!$locTables) {
      $locTables = ['civicrm_email', 'civicrm_address', 'civicrm_phone'];

      // Allow hook_civicrm_merge() to adjust $locTables
      CRM_Utils_Hook::merge('locTables', $locTables);
    }
    return $locTables;
  }

  /**
   * We treat multi-valued custom sets as "related tables" similar to activities, contributions, etc.
   * @param string $request
   *   'relTables' or 'cidRefs'.
   * @return array
   * @see CRM-13836
   */
  public static function getMultiValueCustomSets($request) {

    if (!isset(Civi::$statics[__CLASS__]['multiValueCustomSets'])) {
      $data = [
        'relTables' => [],
        'cidRefs' => [],
      ];
      $result = civicrm_api3('custom_group', 'get', [
        'is_multiple' => 1,
        'extends' => [
          'IN' => [
            'Individual',
            'Organization',
            'Household',
            'Contact',
          ],
        ],
        'return' => ['id', 'title', 'table_name', 'style'],
      ]);
      foreach ($result['values'] as $custom) {
        $data['cidRefs'][$custom['table_name']] = ['entity_id'];
        $urlSuffix = $custom['style'] == 'Tab' ? '&selectedChild=custom_' . $custom['id'] : '';
        $data['relTables']['rel_table_custom_' . $custom['id']] = [
          'title' => $custom['title'],
          'tables' => [$custom['table_name']],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid' . $urlSuffix),
        ];
      }

      // Store the result in a static variable cache
      Civi::$statics[__CLASS__]['multiValueCustomSets'] = $data;
    }

    return Civi::$statics[__CLASS__]['multiValueCustomSets'][$request];
  }

  /**
   * Tables which require custom processing should declare functions to call here.
   * Doing so will override normal processing.
   */
  public static function cpTables() {
    static $tables;
    if (!$tables) {
      $tables = [
        'civicrm_case_contact' => ['CRM_Case_BAO_Case' => 'mergeContacts'],
        'civicrm_group_contact' => ['CRM_Contact_BAO_GroupContact' => 'mergeGroupContact'],
        // Empty array == do nothing - this table is handled by mergeGroupContact
        'civicrm_subscription_history' => [],
        'civicrm_relationship' => ['CRM_Contact_BAO_Relationship' => 'mergeRelationships'],
        'civicrm_membership' => ['CRM_Member_BAO_Membership' => 'mergeMemberships'],
      ];
    }
    return $tables;
  }

  /**
   * Return payment related table.
   */
  public static function paymentTables() {
    static $tables;
    if (!$tables) {
      $tables = ['civicrm_pledge', 'civicrm_membership', 'civicrm_participant'];
    }
    return $tables;
  }

  /**
   * Return payment update Query.
   *
   * @param string $tableName
   * @param int $mainContactId
   * @param int $otherContactId
   *
   * @return array
   */
  public static function paymentSql($tableName, $mainContactId, $otherContactId) {
    $sqls = [];
    if (!$tableName || !$mainContactId || !$otherContactId) {
      return $sqls;
    }

    $paymentTables = self::paymentTables();
    if (!in_array($tableName, $paymentTables)) {
      return $sqls;
    }

    switch ($tableName) {
      case 'civicrm_pledge':
        $sqls[] = "
    UPDATE  IGNORE  civicrm_contribution contribution
INNER JOIN  civicrm_pledge_payment payment ON ( payment.contribution_id = contribution.id )
INNER JOIN  civicrm_pledge pledge ON ( pledge.id = payment.pledge_id )
       SET  contribution.contact_id = $mainContactId
     WHERE  pledge.contact_id = $otherContactId";
        break;

      case 'civicrm_membership':
        $sqls[] = "
    UPDATE  IGNORE  civicrm_contribution contribution
INNER JOIN  civicrm_membership_payment payment ON ( payment.contribution_id = contribution.id )
INNER JOIN  civicrm_membership membership ON ( membership.id = payment.membership_id )
       SET  contribution.contact_id = $mainContactId
     WHERE  membership.contact_id = $otherContactId";
        break;

      case 'civicrm_participant':
        $sqls[] = "
    UPDATE  IGNORE  civicrm_contribution contribution
INNER JOIN  civicrm_participant_payment payment ON ( payment.contribution_id = contribution.id )
INNER JOIN  civicrm_participant participant ON ( participant.id = payment.participant_id )
       SET  contribution.contact_id = $mainContactId
     WHERE  participant.contact_id = $otherContactId";
        break;
    }

    return $sqls;
  }

  /**
   * @param int $mainId
   * @param int $otherId
   * @param string $tableName
   * @param array $tableOperations
   * @param string $mode
   *
   * @return array
   */
  public static function operationSql($mainId, $otherId, $tableName, $tableOperations = [], $mode = 'add') {
    $sqls = [];
    if (!$tableName || !$mainId || !$otherId) {
      return $sqls;
    }

    switch ($tableName) {
      case 'civicrm_membership':
        if (array_key_exists($tableName, $tableOperations) && $tableOperations[$tableName]['add']) {
          break;
        }
        if ($mode == 'add') {
          $sqls[] = "
DELETE membership1.* FROM civicrm_membership membership1
 INNER JOIN  civicrm_membership membership2 ON membership1.membership_type_id = membership2.membership_type_id
             AND membership1.contact_id = {$mainId}
             AND membership2.contact_id = {$otherId} ";
        }
        if ($mode == 'payment') {
          $sqls[] = "
DELETE contribution.* FROM civicrm_contribution contribution
INNER JOIN  civicrm_membership_payment payment ON payment.contribution_id = contribution.id
INNER JOIN  civicrm_membership membership1 ON membership1.id = payment.membership_id
            AND membership1.contact_id = {$mainId}
INNER JOIN  civicrm_membership membership2 ON membership1.membership_type_id = membership2.membership_type_id
            AND membership2.contact_id = {$otherId}";
        }
        break;

      case 'civicrm_uf_match':
        // normal queries won't work for uf_match since that will lead to violation of unique constraint,
        // failing to meet intended result. Therefore we introduce this additional query:
        $sqls[] = "DELETE FROM civicrm_uf_match WHERE contact_id = {$mainId}";
        break;
    }

    return $sqls;
  }

  /**
   * Based on the provided two contact_ids and a set of tables, remove the
   * belongings of the other contact and of their relations.
   *
   * @param int $otherID
   * @param bool $tables
   */
  public static function removeContactBelongings($otherID, $tables) {
    // CRM-20421: Removing Inherited memberships when memberships of parent are not migrated to new contact.
    if (in_array("civicrm_membership", $tables)) {
      $membershipIDs = CRM_Utils_Array::collect('id',
        CRM_Utils_Array::value('values',
          civicrm_api3("Membership", "get", [
              "contact_id" => $otherID,
              "return" => "id",
            ]
          )
        ));

      if (!empty($membershipIDs)) {
        civicrm_api3("Membership", "get", [
          'owner_membership_id' => ['IN' => $membershipIDs],
          'api.Membership.delete' => ['id' => '$value.id'],
        ]);
      }
    }
  }

  /**
   * Based on the provided two contact_ids and a set of tables, move the
   * belongings of the other contact to the main one.
   *
   * @param int $mainId
   * @param int $otherId
   * @param bool $tables
   * @param array $tableOperations
   * @param array $customTableToCopyFrom
   */
  public static function moveContactBelongings($mainId, $otherId, $tables = FALSE, $tableOperations = [], $customTableToCopyFrom = NULL) {
    $cidRefs = self::cidRefs();
    $eidRefs = self::eidRefs();
    $cpTables = self::cpTables();
    $paymentTables = self::paymentTables();

    // getting all custom tables
    $customTables = [];
    if ($customTableToCopyFrom !== NULL) {
      // @todo this duplicates cidRefs?
      CRM_Core_DAO::appendCustomTablesExtendingContacts($customTables);
      $customTables = array_keys($customTables);
    }

    $affected = array_merge(array_keys($cidRefs), array_keys($eidRefs));

    // if there aren't any specific tables, don't affect the ones handled by relTables()
    // also don't affect tables in locTables() CRM-15658
    $relTables = self::relTables();
    $handled = self::locTables();

    foreach ($relTables as $params) {
      $handled = array_merge($handled, $params['tables']);
    }
    $affected = array_diff($affected, $handled);
    $affected = array_unique(array_merge($affected, $tables));

    $mainId = (int) $mainId;
    $otherId = (int) $otherId;
    $multi_value_tables = array_keys(CRM_Dedupe_Merger::getMultiValueCustomSets('cidRefs'));

    $sqls = [];
    foreach ($affected as $table) {
      // skipping non selected single-value custom table's value migration
      if (!in_array($table, $multi_value_tables)) {
        if ($customTableToCopyFrom !== NULL && in_array($table, $customTables) && !in_array($table, $customTableToCopyFrom)) {
          continue;
        }
      }

      // Call custom processing function for objects that require it
      if (isset($cpTables[$table])) {
        foreach ($cpTables[$table] as $className => $fnName) {
          $className::$fnName($mainId, $otherId, $sqls, $tables, $tableOperations);
        }
        // Skip normal processing
        continue;
      }

      // use UPDATE IGNORE + DELETE query pair to skip on situations when
      // there's a UNIQUE restriction on ($field, some_other_field) pair
      if (isset($cidRefs[$table])) {
        foreach ($cidRefs[$table] as $field) {
          // carry related contributions CRM-5359
          if (in_array($table, $paymentTables)) {
            $paymentSqls = self::paymentSql($table, $mainId, $otherId);
            $sqls = array_merge($sqls, $paymentSqls);

            if (!empty($tables) && !in_array('civicrm_contribution', $tables)) {
              $payOprSqls = self::operationSql($mainId, $otherId, $table, $tableOperations, 'payment');
              $sqls = array_merge($sqls, $payOprSqls);
            }
          }

          $preOperationSqls = self::operationSql($mainId, $otherId, $table, $tableOperations);
          $sqls = array_merge($sqls, $preOperationSqls);

          if ($customTableToCopyFrom !== NULL && in_array($table, $customTableToCopyFrom) && !self::customRecordExists($mainId, $table, $field)) {
            $sqls[] = "INSERT INTO $table ($field) VALUES ($mainId)";
          }
          $sqls[] = "UPDATE IGNORE $table SET $field = $mainId WHERE $field = $otherId";
          $sqls[] = "DELETE FROM $table WHERE $field = $otherId";
        }
      }

      if (isset($eidRefs[$table])) {
        foreach ($eidRefs[$table] as $entityTable => $entityId) {
          $sqls[] = "UPDATE IGNORE $table SET $entityId = $mainId WHERE $entityId = $otherId AND $entityTable = 'civicrm_contact'";
          $sqls[] = "DELETE FROM $table WHERE $entityId = $otherId AND $entityTable = 'civicrm_contact'";
        }
      }
    }

    // Allow hook_civicrm_merge() to add SQL statements for the merge operation.
    CRM_Utils_Hook::merge('sqls', $sqls, $mainId, $otherId, $tables);

    // call the SQL queries in one transaction
    $transaction = new CRM_Core_Transaction();
    foreach ($sqls as $sql) {
      CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, TRUE);
    }
    CRM_Dedupe_Merger::addMembershipToRealtedContacts($mainId);
    $transaction->commit();
  }

  /**
   * Given a contact ID, will check if a record exists in given table.
   *
   * @param $contactID
   * @param $table
   * @param $idField
   *   Field where the contact's ID is stored in the table
   *
   * @return bool
   *   True if a record is found for the given contact ID, false otherwise
   */
  private static function customRecordExists($contactID, $table, $idField) {
    $sql = "
      SELECT COUNT(*) AS count
      FROM $table
      WHERE $idField = $contactID
    ";
    $dbResult = CRM_Core_DAO::executeQuery($sql);
    $dbResult->fetch();

    if ($dbResult->count > 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Load all non-empty fields for the contacts
   *
   * @param array $main
   *   Contact details.
   * @param array $other
   *   Contact details.
   *
   * @return array
   */
  public static function retrieveFields($main, $other) {
    $result = [
      'contact' => [],
      'custom' => [],
    ];
    foreach (self::getContactFields() as $validField) {
      // CRM-17556 Get all non-empty fields, to make comparison easier
      if (!empty($main[$validField]) || !empty($other[$validField])) {
        $result['contact'][] = $validField;
      }
    }

    $mainEvs = CRM_Core_BAO_CustomValueTable::getEntityValues($main['id']);
    $otherEvs = CRM_Core_BAO_CustomValueTable::getEntityValues($other['id']);
    $keys = array_unique(array_merge(array_keys($mainEvs), array_keys($otherEvs)));
    foreach ($keys as $key) {
      // Exclude multi-value fields CRM-13836
      if (strpos($key, '_')) {
        continue;
      }
      $key1 = CRM_Utils_Array::value($key, $mainEvs);
      $key2 = CRM_Utils_Array::value($key, $otherEvs);
      // We wish to retain '0' as it has a different meaning than NULL on a checkbox.
      // However I can't think of a case where an empty string is more meaningful than null
      // or where it would be FALSE or something else nullish.
      $valuesToIgnore = [NULL, '', []];
      if (!in_array($key1, $valuesToIgnore, TRUE) || !in_array($key2, $valuesToIgnore, TRUE)) {
        $result['custom'][] = $key;
      }
    }
    return $result;
  }

  /**
   * Batch merge a set of contacts based on rule-group and group.
   *
   * @param int $rgid
   *   Rule group id.
   * @param int $gid
   *   Group id.
   * @param string $mode
   *   Helps decide how to behave when there are conflicts.
   *   A 'safe' value skips the merge if there are any un-resolved conflicts, wheras 'aggressive'
   *   mode does a force merge.
   * @param int $batchLimit number of merges to carry out in one batch.
   * @param int $isSelected if records with is_selected column needs to be processed.
   *   Note the option of '2' is only used in conjunction with $redirectForPerformance
   *   to determine when to reload the cache (!). The use of anything other than a boolean is being grandfathered
   *   out in favour of explicitly passing in $reloadCacheIfEmpty
   *
   * @param array $criteria
   *   Criteria to use in the filter.
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   * @param bool|NULL $reloadCacheIfEmpty
   *  If not set explicitly this is calculated but it is preferred that it be set
   *  per comments on isSelected above.
   *
   * @return array|bool
   */
  public static function batchMerge($rgid, $gid = NULL, $mode = 'safe', $batchLimit = 1, $isSelected = 2, $criteria = [], $checkPermissions = TRUE, $reloadCacheIfEmpty = NULL) {
    $redirectForPerformance = ($batchLimit > 1) ? TRUE : FALSE;

    if (!isset($reloadCacheIfEmpty)) {
      $reloadCacheIfEmpty = (!$redirectForPerformance && $isSelected == 2);
    }
    if ($isSelected !== 0 && $isSelected !== 1) {
      // explicitly set to NULL if not 1 or 0 as part of grandfathering out the mystical '2' value.
      $isSelected = NULL;
    }
    $dupePairs = self::getDuplicatePairs($rgid, $gid, $reloadCacheIfEmpty, $batchLimit, $isSelected, '', ($mode == 'aggressive'), $criteria, $checkPermissions);

    $cacheParams = [
      'cache_key_string' => self::getMergeCacheKeyString($rgid, $gid, $criteria, $checkPermissions),
      // @todo stop passing these parameters in & instead calculate them in the merge function based
      // on the 'real' params like $isRespectExclusions $batchLimit and $isSelected.
      'join' => self::getJoinOnDedupeTable(),
      'where' => self::getWhereString($isSelected),
      'limit' => (int) $batchLimit,
    ];
    return CRM_Dedupe_Merger::merge($dupePairs, $cacheParams, $mode, $redirectForPerformance, $checkPermissions);
  }

  /**
   * Get the string to join the prevnext cache to the dedupe table.
   *
   * @return string
   *   The join string to join prevnext cache on the dedupe table.
   */
  public static function getJoinOnDedupeTable() {
    return "
      LEFT JOIN civicrm_dedupe_exception de
        ON (
          pn.entity_id1 = de.contact_id1
          AND pn.entity_id2 = de.contact_id2 )
       ";
  }

  /**
   * Get where string for dedupe join.
   *
   * @param bool $isSelected
   *
   * @return string
   */
  protected static function getWhereString($isSelected) {
    $where = "de.id IS NULL";
    if ($isSelected === 0 || $isSelected === 1) {
      $where .= " AND pn.is_selected = {$isSelected}";
    }
    return $where;
  }

  /**
   * Update the statistics for the merge set.
   *
   * @param string $cacheKeyString
   * @param array $result
   */
  public static function updateMergeStats($cacheKeyString, $result = []) {
    // gather latest stats
    $merged = count($result['merged']);
    $skipped = count($result['skipped']);

    if ($merged <= 0 && $skipped <= 0) {
      return;
    }

    // get previous stats
    $previousStats = CRM_Core_BAO_PrevNextCache::retrieve("{$cacheKeyString}_stats");
    if (!empty($previousStats)) {
      if ($previousStats[0]['merged']) {
        $merged = $merged + $previousStats[0]['merged'];
      }
      if ($previousStats[0]['skipped']) {
        $skipped = $skipped + $previousStats[0]['skipped'];
      }
    }

    // delete old stats
    CRM_Dedupe_Merger::resetMergeStats($cacheKeyString);

    // store the updated stats
    $data = [
      'merged' => $merged,
      'skipped' => $skipped,
    ];
    $data = CRM_Core_DAO::escapeString(serialize($data));

    $values = [];
    $values[] = " ( 'civicrm_contact', 0, 0, '{$cacheKeyString}_stats', '$data' ) ";
    CRM_Core_BAO_PrevNextCache::setItem($values);
  }

  /**
   * Delete information about merges for the given string.
   *
   * @param $cacheKeyString
   */
  public static function resetMergeStats($cacheKeyString) {
    CRM_Core_BAO_PrevNextCache::deleteItem(NULL, "{$cacheKeyString}_stats");
  }

  /**
   * Get merge outcome statistics.
   *
   * @param string $cacheKeyString
   *
   * @return array
   *   Array of how many were merged and how many were skipped.
   */
  public static function getMergeStats($cacheKeyString) {
    $stats = CRM_Core_BAO_PrevNextCache::retrieve("{$cacheKeyString}_stats");
    if (!empty($stats)) {
      $stats = $stats[0];
    }
    return $stats;
  }

  /**
   * Get merge statistics message.
   *
   * @param array $stats
   *
   * @return string
   */
  public static function getMergeStatsMsg($stats) {
    $msg = '';
    if (!empty($stats['merged'])) {
      $msg = '<p>' . ts('One contact merged.', [
          'count' => $stats['merged'],
          'plural' => '%count contacts merged.',
        ]) . '</p>';
    }
    if (!empty($stats['skipped'])) {
      $msg .= '<p>' . ts('One contact was skipped.', [
          'count' => $stats['skipped'],
          'plural' => '%count contacts were skipped.',
        ]) . '</p>';
    }
    return $msg;
  }

  /**
   * Merge given set of contacts. Performs core operation.
   *
   * @param array $dupePairs
   *   Set of pair of contacts for whom merge is to be done.
   * @param array $cacheParams
   *   Prev-next-cache params based on which next pair of contacts are computed.
   *                              Generally used with batch-merge.
   * @param string $mode
   *   Helps decide how to behave when there are conflicts.
   *                             A 'safe' value skips the merge if there are any un-resolved conflicts.
   *                             Does a force merge otherwise (aggressive mode).
   *
   * @param bool $redirectForPerformance
   *   Redirect to a url for batch processing.
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   *
   * @return array|bool
   */
  public static function merge($dupePairs = [], $cacheParams = [], $mode = 'safe',
                               $redirectForPerformance = FALSE, $checkPermissions = TRUE
  ) {
    $cacheKeyString = CRM_Utils_Array::value('cache_key_string', $cacheParams);
    $resultStats = ['merged' => [], 'skipped' => []];

    // we don't want dupe caching to get reset after every-merge, and therefore set the
    CRM_Core_Config::setPermitCacheFlushMode(FALSE);
    $deletedContacts = [];

    while (!empty($dupePairs)) {
      foreach ($dupePairs as $index => $dupes) {
        if (in_array($dupes['dstID'], $deletedContacts) || in_array($dupes['srcID'], $deletedContacts)) {
          unset($dupePairs[$index]);
          continue;
        }
        CRM_Utils_Hook::merge('flip', $dupes, $dupes['dstID'], $dupes['srcID']);
        $mainId = $dupes['dstID'];
        $otherId = $dupes['srcID'];

        if (!$mainId || !$otherId) {
          // return error
          return FALSE;
        }
        // Generate var $migrationInfo. The variable structure is exactly same as
        // $formValues submitted during a UI merge for a pair of contacts.
        $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($mainId, $otherId, $checkPermissions);
        // add additional details that we might need to resolve conflicts
        $rowsElementsAndInfo['migration_info']['main_details'] = &$rowsElementsAndInfo['main_details'];
        $rowsElementsAndInfo['migration_info']['other_details'] = &$rowsElementsAndInfo['other_details'];
        $rowsElementsAndInfo['migration_info']['rows'] = &$rowsElementsAndInfo['rows'];

        self::dedupePair($rowsElementsAndInfo['migration_info'], $resultStats, $deletedContacts, $mode, $checkPermissions, $mainId, $otherId, $cacheKeyString);
      }

      if ($cacheKeyString && !$redirectForPerformance) {
        // retrieve next pair of dupes
        // @todo call getDuplicatePairs.
        $dupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString,
          $cacheParams['join'],
          $cacheParams['where'],
          0,
          $cacheParams['limit'],
          [],
          '',
          FALSE
        );
      }
      else {
        // do not proceed. Terminate the loop
        unset($dupePairs);
      }
    }

    CRM_Dedupe_Merger::updateMergeStats($cacheKeyString, $resultStats);
    return $resultStats;
  }

  /**
   * A function which uses various rules / algorithms for choosing which contact to bias to
   * when there's a conflict (to handle "gotchas"). Plus the safest route to merge.
   *
   * @param int $mainId
   *   Main contact with whom merge has to happen.
   * @param int $otherId
   *   Duplicate contact which would be deleted after merge operation.
   * @param array $migrationInfo
   *   Array of information about which elements to merge.
   * @param string $mode
   *   Helps decide how to behave when there are conflicts.
   *   -  A 'safe' value skips the merge if there are any un-resolved conflicts.
   *   -  Does a force merge otherwise (aggressive mode).
   *
   * @param array $conflicts
   *
   * @return bool
   */
  public static function skipMerge($mainId, $otherId, &$migrationInfo, $mode = 'safe', &$conflicts = []) {

    $originalMigrationInfo = $migrationInfo;
    foreach ($migrationInfo as $key => $val) {
      if ($val === "null") {
        // Rule: Never overwrite with an empty value (in any mode)
        unset($migrationInfo[$key]);
        continue;
      }
      elseif ((in_array(substr($key, 5), CRM_Dedupe_Merger::getContactFields()) or
          substr($key, 0, 12) == 'move_custom_'
        ) and $val != NULL
      ) {
        // Rule: If both main-contact, and other-contact have a field with a
        // different value, then let $mode decide if to merge it or not
        if (
          (!empty($migrationInfo['rows'][$key]['main'])
            // For custom fields a 0 (e.g in an int field) could be a true conflict. This
            // is probably true for other fields too - e.g. 'do_not_email' but
            // leaving that investigation as a @todo - until tests can be written.
            // Note the handling of this has test coverage - although the data-typing
            // of '0' feels flakey we have insurance.
            || ($migrationInfo['rows'][$key]['main'] === '0' && substr($key, 0, 12) == 'move_custom_')
          )
          && $migrationInfo['rows'][$key]['main'] != $migrationInfo['rows'][$key]['other']
        ) {

          // note it down & lets wait for response from the hook.
          // For no response $mode will decide if to skip this merge
          $conflicts[$key] = NULL;
        }
      }
      elseif (substr($key, 0, 14) == 'move_location_' and $val != NULL) {
        $locField = explode('_', $key);
        $fieldName = $locField[2];
        $fieldCount = $locField[3];

        // Rule: Catch address conflicts (same address type on both contacts)
        if (
          isset($migrationInfo['main_details']['location_blocks'][$fieldName]) &&
          !empty($migrationInfo['main_details']['location_blocks'][$fieldName])
        ) {

          // Load the address we're inspecting from the 'other' contact
          $addressRecord = $migrationInfo['other_details']['location_blocks'][$fieldName][$fieldCount];
          $addressRecordLocTypeId = CRM_Utils_Array::value('location_type_id', $addressRecord);

          // If it exists on the 'main' contact already, skip it. Otherwise
          // if the location type exists already, log a conflict.
          foreach ($migrationInfo['main_details']['location_blocks'][$fieldName] as $mainAddressKey => $mainAddressRecord) {
            if (self::locationIsSame($addressRecord, $mainAddressRecord)) {
              unset($migrationInfo[$key]);
              break;
            }
            elseif ($addressRecordLocTypeId == $mainAddressRecord['location_type_id']) {
              $conflicts[$key] = NULL;
              break;
            }
          }
        }

        // For other locations, don't merge/add if the values are the same
        elseif (CRM_Utils_Array::value('main', $migrationInfo['rows'][$key]) == $migrationInfo['rows'][$key]['other']) {
          unset($migrationInfo[$key]);
        }
      }
    }

    // A hook to implement other algorithms for choosing which contact to bias to when
    // there's a conflict (to handle "gotchas"). fields_in_conflict could be modified here
    // merge happens with new values filled in here. For a particular field / row not to be merged
    // field should be unset from fields_in_conflict.
    $migrationData = [
      'old_migration_info' => $originalMigrationInfo,
      'mode' => $mode,
      'fields_in_conflict' => $conflicts,
      'merge_mode' => $mode,
      'migration_info' => $migrationInfo,
    ];
    CRM_Utils_Hook::merge('batch', $migrationData, $mainId, $otherId);
    $conflicts = $migrationData['fields_in_conflict'];
    // allow hook to override / manipulate migrationInfo as well
    $migrationInfo = $migrationData['migration_info'];

    if (!empty($conflicts)) {
      foreach ($conflicts as $key => $val) {
        if ($val === NULL and $mode == 'safe') {
          // un-resolved conflicts still present. Lets skip this merge after saving the conflict / reason.
          return TRUE;
        }
        else {
          // copy over the resolved values
          $migrationInfo[$key] = $val;
        }
      }
      // if there are conflicts and mode is aggressive, allow hooks to decide if to skip merges
      if (array_key_exists('skip_merge', $migrationData)) {
        return (bool) $migrationData['skip_merge'];
      }
    }
    return FALSE;
  }

  /**
   * Compare 2 addresses to see if they are the same.
   *
   * @param array $mainAddress
   * @param array $comparisonAddress
   *
   * @return bool
   */
  static public function locationIsSame($mainAddress, $comparisonAddress) {
    $keysToIgnore = [
      'id',
      'is_primary',
      'is_billing',
      'manual_geo_code',
      'contact_id',
      'reset_date',
      'hold_date',
    ];
    foreach ($comparisonAddress as $field => $value) {
      if (in_array($field, $keysToIgnore)) {
        continue;
      }
      if ((!empty($value) || $value === '0') && isset($mainAddress[$field]) && $mainAddress[$field] != $value) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * A function to build an array of information about location blocks that is
   * required when merging location fields
   *
   * @return array
   */
  public static function getLocationBlockInfo() {
    $locationBlocks = [
      'address' => [
        'label' => 'Address',
        'displayField' => 'display',
        'sortString' => 'location_type_id',
        'hasLocation' => TRUE,
        'hasType' => FALSE,
      ],
      'email' => [
        'label' => 'Email',
        'displayField' => 'display',
        'sortString' => 'location_type_id',
        'hasLocation' => TRUE,
        'hasType' => FALSE,
      ],
      'im' => [
        'label' => 'IM',
        'displayField' => 'name',
        'sortString' => 'location_type_id,provider_id',
        'hasLocation' => TRUE,
        'hasType' => 'provider_id',
      ],
      'phone' => [
        'label' => 'Phone',
        'displayField' => 'phone',
        'sortString' => 'location_type_id,phone_type_id',
        'hasLocation' => TRUE,
        'hasType' => 'phone_type_id',
      ],
      'website' => [
        'label' => 'Website',
        'displayField' => 'url',
        'sortString' => 'website_type_id',
        'hasLocation' => FALSE,
        'hasType' => 'website_type_id',
      ],
    ];
    return $locationBlocks;
  }

  /**
   * A function to build an array of information required by merge function and the merge UI.
   *
   * @param int $mainId
   *   Main contact with whom merge has to happen.
   * @param int $otherId
   *   Duplicate contact which would be deleted after merge operation.
   * @param bool $checkPermissions
   *   Should the logged in user's permissions be ignore. Setting this to false is
   *   highly risky as it could cause data to be lost due to conflicts not showing up.
   *   OTOH there is a risk a merger might view custom data they do not have permission to.
   *   Hence for now only making this really explicit and making it reflect perms in
   *   an api call.
   *
   * @todo review permissions issue!
   *
   * @return array|bool|int
   *
   *   rows => An array of arrays, each is row of merge information for the table
   *   Format: move_fieldname, eg: move_contact_type
   *     main => Value associated with the main contact
   *     other => Value associated with the other contact
   *     title => The title of the field to display in the merge table
   *
   *   elements => An array of form elements for the merge UI
   *
   *   rel_table_elements => An array of form elements for the merge UI for
   *     entities related to the contact (eg: checkbox to move 'mailings')
   *
   *   rel_tables => Stores the tables that have related entities for the contact
   *     for example mailings, groups
   *
   *   main_details => An array of core contact field values, eg: first_name, etc.
   *     location_blocks => An array of location block data for the main contact
   *       stored as the 'result' of an API call.
   *       eg: main_details['location_blocks']['address'][0]['id']
   *       eg: main_details['location_blocks']['email'][1]['id']
   *
   *   other_details => As above, but for the 'other' contact
   *
   *   migration_info => Stores the 'default' merge actions for each field which
   *     is used when programatically merging contacts. It contains instructions
   *     to move all fields from the 'other' contact to the 'main' contact, as
   *     though the form had been submitted with those options.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public static function getRowsElementsAndInfo($mainId, $otherId, $checkPermissions = TRUE) {
    $qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';
    $fields = self::getMergeFieldsMetadata();

    $main = self::getMergeContactDetails($mainId);
    $other = self::getMergeContactDetails($otherId);
    $specialValues['main'] = self::getSpecialValues($main);
    $specialValues['other'] = self::getSpecialValues($other);

    $compareFields = self::retrieveFields($main, $other);

    $rows = $elements = $relTableElements = $migrationInfo = [];

    foreach ($compareFields['contact'] as $field) {
      if ($field == 'contact_sub_type') {
        // CRM-15681 don't display sub-types in UI
        continue;
      }
      foreach (['main', 'other'] as $moniker) {
        $contact = &$$moniker;
        $value = CRM_Utils_Array::value($field, $contact);
        if (isset($specialValues[$moniker][$field]) && is_string($specialValues[$moniker][$field])) {
          $value = CRM_Core_DAO::VALUE_SEPARATOR . trim($specialValues[$moniker][$field], CRM_Core_DAO::VALUE_SEPARATOR) . CRM_Core_DAO::VALUE_SEPARATOR;
        }
        $label = isset($specialValues[$moniker]["{$field}_display"]) ? $specialValues[$moniker]["{$field}_display"] : $value;
        if (!empty($fields[$field]['type']) && $fields[$field]['type'] == CRM_Utils_Type::T_DATE) {
          if ($value) {
            $value = str_replace('-', '', $value);
            $label = CRM_Utils_Date::customFormat($label);
          }
          else {
            $value = "null";
          }
        }
        elseif (!empty($fields[$field]['type']) && $fields[$field]['type'] == CRM_Utils_Type::T_BOOLEAN) {
          if ($label === '0') {
            $label = ts('[ ]');
          }
          if ($label === '1') {
            $label = ts('[x]');
          }
        }
        elseif ($field == 'prefix_id') {
          $label = CRM_Utils_Array::value('individual_prefix', $contact);
        }
        elseif ($field == 'suffix_id') {
          $label = CRM_Utils_Array::value('individual_suffix', $contact);
        }
        elseif ($field == 'gender_id' && !empty($value)) {
          $genderOptions = civicrm_api3('contact', 'getoptions', ['field' => 'gender_id']);
          $label = $genderOptions['values'][$value];
        }
        elseif ($field == 'current_employer_id' && !empty($value)) {
          $label = "$value (" . CRM_Contact_BAO_Contact::displayName($value) . ")";
        }
        $rows["move_$field"][$moniker] = $label;
        if ($moniker == 'other') {
          //CRM-14334
          if ($value === NULL || $value == '') {
            $value = 'null';
          }
          if ($value === 0 or $value === '0') {
            $value = $qfZeroBug;
          }
          if (is_array($value) && empty($value[1])) {
            $value[1] = NULL;
          }

          // Display a checkbox to migrate, only if the values are different
          if ($value != $main[$field]) {
            $elements[] = [
              'advcheckbox',
              "move_$field",
              NULL,
              NULL,
              NULL,
              $value,
            ];
          }

          $migrationInfo["move_$field"] = $value;
        }
      }
      $rows["move_$field"]['title'] = $fields[$field]['title'];
    }

    // Handle location blocks.
    // @todo OpenID not in API yet, so is not supported here.

    // Set up useful information about the location blocks
    $locationBlocks = self::getLocationBlockInfo();

    $locations = [
      'main' => [],
      'other' => [],
    ];

    // @todo This could probably be defined and used earlier
    $mergeTargets = [
      'main' => $mainId,
      'other' => $otherId,
    ];

    foreach ($locationBlocks as $blockName => $blockInfo) {

      // Collect existing fields from both 'main' and 'other' contacts first
      // This allows us to match up location/types when building the table rows
      foreach ($mergeTargets as $moniker => $cid) {
        $searchParams = [
          'contact_id' => $cid,
          // CRM-17556 Order by field-specific criteria
          'options' => [
            'sort' => $blockInfo['sortString'],
          ],
        ];
        $values = civicrm_api3($blockName, 'get', $searchParams);
        if ($values['count']) {
          $cnt = 0;
          foreach ($values['values'] as $value) {
            $locations[$moniker][$blockName][$cnt] = $value;
            // Fix address display
            if ($blockName == 'address') {
              CRM_Core_BAO_Address::fixAddress($value);
              $locations[$moniker][$blockName][$cnt]['display'] = CRM_Utils_Address::format($value);
            }
            // Fix email display
            elseif ($blockName == 'email') {
              $locations[$moniker][$blockName][$cnt]['display'] = CRM_Utils_Mail::format($value);
            }

            $cnt++;
          }
        }
      }

      // Now, build the table rows appropriately, based off the information on
      // the 'other' contact
      if (!empty($locations['other']) && !empty($locations['other'][$blockName])) {
        foreach ($locations['other'][$blockName] as $count => $value) {

          $displayValue = $value[$blockInfo['displayField']];

          // Add this value to the table rows
          $rows["move_location_{$blockName}_{$count}"]['other'] = $displayValue;

          // CRM-17556 Only display 'main' contact value if it's the same location + type
          // Look it up from main values...

          $lookupLocation = FALSE;
          if ($blockInfo['hasLocation']) {
            $lookupLocation = $value['location_type_id'];
          }

          $lookupType = FALSE;
          if ($blockInfo['hasType']) {
            $lookupType = CRM_Utils_Array::value($blockInfo['hasType'], $value);
          }

          // Hold ID of main contact's matching block
          $mainContactBlockId = 0;

          if (!empty($locations['main'][$blockName])) {
            foreach ($locations['main'][$blockName] as $mainValueCheck) {
              // No location/type, or matching location and type
              if (
                (empty($lookupLocation) || $lookupLocation == $mainValueCheck['location_type_id'])
                && (empty($lookupType) || $lookupType == $mainValueCheck[$blockInfo['hasType']])
              ) {
                // Set this value as the default against the 'other' contact value
                $rows["move_location_{$blockName}_{$count}"]['main'] = $mainValueCheck[$blockInfo['displayField']];
                $rows["move_location_{$blockName}_{$count}"]['main_is_primary'] = $mainValueCheck['is_primary'];
                $rows["move_location_{$blockName}_{$count}"]['location_entity'] = $blockName;
                $mainContactBlockId = $mainValueCheck['id'];
                break;
              }
            }
          }

          // Add checkbox to migrate data from 'other' to 'main'
          $elements[] = ['advcheckbox', "move_location_{$blockName}_{$count}"];

          // Add checkbox to set the 'other' location as primary
          $elements[] = [
            'advcheckbox',
            "location_blocks[$blockName][$count][set_other_primary]",
            NULL,
            ts('Set as primary'),
          ];

          // Flag up this field to skipMerge function (@todo: do we need to?)
          $migrationInfo["move_location_{$blockName}_{$count}"] = 1;

          // Add a hidden field to store the ID of the target main contact block
          $elements[] = [
            'hidden',
            "location_blocks[$blockName][$count][mainContactBlockId]",
            $mainContactBlockId,
          ];

          // Setup variables
          $thisTypeId = FALSE;
          $thisLocId = FALSE;

          // Provide a select drop-down for the location's location type
          // eg: Home, Work...

          if ($blockInfo['hasLocation']) {

            // Load the location options for this entity
            $locationOptions = civicrm_api3($blockName, 'getoptions', ['field' => 'location_type_id']);

            $thisLocId = $value['location_type_id'];

            // Put this field's location type at the top of the list
            $tmpIdList = $locationOptions['values'];
            $defaultLocId = [$thisLocId => $tmpIdList[$thisLocId]];
            unset($tmpIdList[$thisLocId]);

            // Add the element
            $elements[] = [
              'select',
              "location_blocks[$blockName][$count][locTypeId]",
              NULL,
              $defaultLocId + $tmpIdList,
            ];

            // Add the relevant information to the $migrationInfo
            // Keep location-type-id same as that of other-contact
            // @todo Check this logic out
            $migrationInfo['location_blocks'][$blockName][$count]['locTypeId'] = $thisLocId;
            if ($blockName != 'address') {
              $elements[] = [
                'advcheckbox',
                "location_blocks[{$blockName}][$count][operation]",
                NULL,
                ts('Add new'),
              ];
              // always use add operation
              $migrationInfo['location_blocks'][$blockName][$count]['operation'] = 1;
            }

          }

          // Provide a select drop-down for the location's type/provider
          // eg websites: Google+, Facebook...

          if ($blockInfo['hasType']) {

            // Load the type options for this entity
            $typeOptions = civicrm_api3($blockName, 'getoptions', ['field' => $blockInfo['hasType']]);

            $thisTypeId = CRM_Utils_Array::value($blockInfo['hasType'], $value);

            // Put this field's location type at the top of the list
            $tmpIdList = $typeOptions['values'];
            $defaultTypeId = [$thisTypeId => CRM_Utils_Array::value($thisTypeId, $tmpIdList)];
            unset($tmpIdList[$thisTypeId]);

            // Add the element
            $elements[] = [
              'select',
              "location_blocks[$blockName][$count][typeTypeId]",
              NULL,
              $defaultTypeId + $tmpIdList,
            ];

            // Add the information to the migrationInfo
            $migrationInfo['location_blocks'][$blockName][$count]['typeTypeId'] = $thisTypeId;

          }

          // Set the label for this row
          $rowTitle = $blockInfo['label'] . ' ' . ($count + 1);
          if (!empty($thisLocId)) {
            $rowTitle .= ' (' . $locationOptions['values'][$thisLocId] . ')';
          }
          if (!empty($thisTypeId)) {
            $rowTitle .= ' (' . $typeOptions['values'][$thisTypeId] . ')';
          }
          $rows["move_location_{$blockName}_$count"]['title'] = $rowTitle;

        } // End loop through 'other' locations of this type

      } // End if 'other' location for this type exists

    } // End loop through each location block entity

    // add the related tables and unset the ones that don't sport any of the duplicate contact's info
    $config = CRM_Core_Config::singleton();
    $mainUfId = CRM_Core_BAO_UFMatch::getUFId($mainId);
    $mainUser = NULL;
    if ($mainUfId) {
      // d6 compatible
      if ($config->userSystem->is_drupal == '1' && function_exists($mainUser)) {
        $mainUser = user_load($mainUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $mainUser = JFactory::getUser($mainUfId);
      }
    }
    $otherUfId = CRM_Core_BAO_UFMatch::getUFId($otherId);
    $otherUser = NULL;
    if ($otherUfId) {
      // d6 compatible
      if ($config->userSystem->is_drupal == '1' && function_exists($mainUser)) {
        $otherUser = user_load($otherUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $otherUser = JFactory::getUser($otherUfId);
      }
    }

    $relTables = CRM_Dedupe_Merger::relTables();
    $activeRelTables = CRM_Dedupe_Merger::getActiveRelTables($otherId);
    $activeMainRelTables = CRM_Dedupe_Merger::getActiveRelTables($mainId);
    foreach ($relTables as $name => $null) {
      if (!in_array($name, $activeRelTables) &&
        !(($name == 'rel_table_users') && in_array($name, $activeMainRelTables))
      ) {
        unset($relTables[$name]);
        continue;
      }

      $relTableElements[] = ['checkbox', "move_$name"];
      $migrationInfo["move_$name"] = 1;

      $relTables[$name]['main_url'] = str_replace('$cid', $mainId, $relTables[$name]['url']);
      $relTables[$name]['other_url'] = str_replace('$cid', $otherId, $relTables[$name]['url']);
      if ($name == 'rel_table_users') {
        $relTables[$name]['main_url'] = str_replace('%ufid', $mainUfId, $relTables[$name]['url']);
        $relTables[$name]['other_url'] = str_replace('%ufid', $otherUfId, $relTables[$name]['url']);
        $find = ['$ufid', '$ufname'];
        if ($mainUser) {
          $replace = [$mainUfId, $mainUser->name];
          $relTables[$name]['main_title'] = str_replace($find, $replace, $relTables[$name]['title']);
        }
        if ($otherUser) {
          $replace = [$otherUfId, $otherUser->name];
          $relTables[$name]['other_title'] = str_replace($find, $replace, $relTables[$name]['title']);
        }
      }
      if ($name == 'rel_table_memberships') {
        //Enable 'add new' checkbox if main contact does not contain any membership similar to duplicate contact.
        $attributes = ['checked' => 'checked'];
        $otherContactMemberships = CRM_Member_BAO_Membership::getAllContactMembership($otherId);
        foreach ($otherContactMemberships as $membership) {
          $mainMembership = CRM_Member_BAO_Membership::getContactMembership($mainId, $membership['membership_type_id'], FALSE);
          if ($mainMembership) {
            $attributes = [];
          }
        }
        $elements[] = [
          'checkbox',
          "operation[move_{$name}][add]",
          NULL,
          ts('add new'),
          $attributes,
        ];
        $migrationInfo["operation"]["move_{$name}"]['add'] = 1;
      }
    }
    foreach ($relTables as $name => $null) {
      $relTables["move_$name"] = $relTables[$name];
      unset($relTables[$name]);
    }

    // handle custom fields
    $mainTree = CRM_Core_BAO_CustomGroup::getTree($main['contact_type'], NULL, $mainId, -1,
      CRM_Utils_Array::value('contact_sub_type', $main), NULL, TRUE, NULL, TRUE, $checkPermissions
    );
    $otherTree = CRM_Core_BAO_CustomGroup::getTree($main['contact_type'], NULL, $otherId, -1,
      CRM_Utils_Array::value('contact_sub_type', $other), NULL, TRUE, NULL, TRUE, $checkPermissions
    );

    foreach ($otherTree as $gid => $group) {
      $foundField = FALSE;
      if (!isset($group['fields'])) {
        continue;
      }

      foreach ($group['fields'] as $fid => $field) {
        if (in_array($fid, $compareFields['custom'])) {
          if (!$foundField) {
            $rows["custom_group_$gid"]['title'] = $group['title'];
            $foundField = TRUE;
          }
          if (!empty($mainTree[$gid]['fields'][$fid]['customValue'])) {
            foreach ($mainTree[$gid]['fields'][$fid]['customValue'] as $valueId => $values) {
              $rows["move_custom_$fid"]['main'] = CRM_Core_BAO_CustomField::displayValue($values['data'], $fid);
            }
          }
          $value = "null";
          if (!empty($otherTree[$gid]['fields'][$fid]['customValue'])) {
            foreach ($otherTree[$gid]['fields'][$fid]['customValue'] as $valueId => $values) {
              $rows["move_custom_$fid"]['other'] = CRM_Core_BAO_CustomField::displayValue($values['data'], $fid);
              if ($values['data'] === 0 || $values['data'] === '0') {
                $values['data'] = $qfZeroBug;
              }
              $value = ($values['data']) ? $values['data'] : $value;
            }
          }
          $rows["move_custom_$fid"]['title'] = $field['label'];

          $elements[] = [
            'advcheckbox',
            "move_custom_$fid",
            NULL,
            NULL,
            NULL,
            $value,
          ];
          $migrationInfo["move_custom_$fid"] = $value;
        }
      }
    }

    $result = [
      'rows' => $rows,
      'elements' => $elements,
      'rel_table_elements' => $relTableElements,
      'rel_tables' => $relTables,
      'main_details' => $main,
      'other_details' => $other,
      'migration_info' => $migrationInfo,
    ];

    $result['main_details']['location_blocks'] = $locations['main'];
    $result['other_details']['location_blocks'] = $locations['other'];

    return $result;
  }

  /**
   * Based on the provided two contact_ids and a set of tables, move the belongings of the
   * other contact to the main one - be it Location / CustomFields or Contact .. related info.
   * A superset of moveContactBelongings() function.
   *
   * @param int $mainId
   *   Main contact with whom merge has to happen.
   * @param int $otherId
   *   Duplicate contact which would be deleted after merge operation.
   *
   * @param $migrationInfo
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   *
   * @return bool
   */
  public static function moveAllBelongings($mainId, $otherId, $migrationInfo, $checkPermissions = TRUE) {
    if (empty($migrationInfo)) {
      return FALSE;
    }

    $qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';
    $relTables = CRM_Dedupe_Merger::relTables();
    $submittedCustomFields = $moveTables = $locationMigrationInfo = $tableOperations = $removeTables = [];

    foreach ($migrationInfo as $key => $value) {
      if ($value == $qfZeroBug) {
        $value = '0';
      }

      if (substr($key, 0, 12) == 'move_custom_' && $value != NULL) {
        $submitted[substr($key, 5)] = $value;
        $submittedCustomFields[] = substr($key, 12);
      }
      elseif (in_array(substr($key, 5), CRM_Dedupe_Merger::getContactFields()) && $value != NULL) {
        $submitted[substr($key, 5)] = $value;
      }
      // Set up initial information for handling migration of location blocks
      elseif (substr($key, 0, 14) == 'move_location_' and $value != NULL) {
        $locationMigrationInfo[$key] = $value;
      }
      elseif (substr($key, 0, 15) == 'move_rel_table_' and $value == '1') {
        $moveTables = array_merge($moveTables, $relTables[substr($key, 5)]['tables']);
        if (array_key_exists('operation', $migrationInfo)) {
          foreach ($relTables[substr($key, 5)]['tables'] as $table) {
            if (array_key_exists($key, $migrationInfo['operation'])) {
              $tableOperations[$table] = $migrationInfo['operation'][$key];
            }
          }
        }
      }
      elseif (substr($key, 0, 15) == 'move_rel_table_' and $value == '0') {
        $removeTables = array_merge($moveTables, $relTables[substr($key, 5)]['tables']);
      }
    }
    self::mergeLocations($mainId, $otherId, $locationMigrationInfo, $migrationInfo);

    // **** Do contact related migrations
    $customTablesToCopyValues = self::getAffectedCustomTables($submittedCustomFields);
    CRM_Dedupe_Merger::moveContactBelongings($mainId, $otherId, $moveTables, $tableOperations, $customTablesToCopyValues);
    unset($moveTables, $tableOperations);

    // **** Do table related removals
    if (!empty($removeTables)) {
      // **** CRM-20421
      CRM_Dedupe_Merger::removeContactBelongings($otherId, $removeTables);
      $removeTables = [];
    }

    // FIXME: fix gender, prefix and postfix, so they're edible by createProfileContact()
    $names['gender'] = ['newName' => 'gender_id', 'groupName' => 'gender'];
    $names['individual_prefix'] = [
      'newName' => 'prefix_id',
      'groupName' => 'individual_prefix',
    ];
    $names['individual_suffix'] = [
      'newName' => 'suffix_id',
      'groupName' => 'individual_suffix',
    ];
    $names['communication_style'] = [
      'newName' => 'communication_style_id',
      'groupName' => 'communication_style',
    ];
    $names['addressee'] = [
      'newName' => 'addressee_id',
      'groupName' => 'addressee',
    ];
    $names['email_greeting'] = [
      'newName' => 'email_greeting_id',
      'groupName' => 'email_greeting',
    ];
    $names['postal_greeting'] = [
      'newName' => 'postal_greeting_id',
      'groupName' => 'postal_greeting',
    ];
    CRM_Core_OptionGroup::lookupValues($submitted, $names, TRUE);

    // fix custom fields so they're edible by createProfileContact()
    $treeCache = [];
    if (!array_key_exists($migrationInfo['main_details']['contact_type'], $treeCache)) {
      $treeCache[$migrationInfo['main_details']['contact_type']] = CRM_Core_BAO_CustomGroup::getTree(
        $migrationInfo['main_details']['contact_type'],
        NULL,
        NULL,
        -1,
        [],
        NULL,
        TRUE,
        NULL,
        FALSE,
        FALSE
      );
    }

    $cFields = [];
    foreach ($treeCache[$migrationInfo['main_details']['contact_type']] as $key => $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      foreach ($group['fields'] as $fid => $field) {
        $cFields[$fid]['attributes'] = $field;
      }
    }

    if (!isset($submitted)) {
      $submitted = [];
    }
    foreach ($submitted as $key => $value) {
      if (substr($key, 0, 7) == 'custom_') {
        $fid = (int) substr($key, 7);
        if (empty($cFields[$fid])) {
          continue;
        }
        $htmlType = $cFields[$fid]['attributes']['html_type'];
        switch ($htmlType) {
          case 'File':
            $customFiles[] = $fid;
            unset($submitted["custom_$fid"]);
            break;

          case 'Select Country':
          case 'Select State/Province':
            $submitted[$key] = CRM_Core_BAO_CustomField::displayValue($value, $fid);
            break;

          case 'Select Date':
            if ($cFields[$fid]['attributes']['is_view']) {
              $submitted[$key] = date('YmdHis', strtotime($submitted[$key]));
            }
            break;

          case 'CheckBox':
          case 'Multi-Select':
          case 'Multi-Select Country':
          case 'Multi-Select State/Province':
            // Merge values from both contacts for multivalue fields, CRM-4385
            // get the existing custom values from db.
            $customParams = ['entityID' => $mainId, $key => TRUE];
            $customfieldValues = CRM_Core_BAO_CustomValueTable::getValues($customParams);
            if (!empty($customfieldValues[$key])) {
              $existingValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $customfieldValues[$key]);
              if (is_array($existingValue) && !empty($existingValue)) {
                $mergeValue = $submittedCustomFields = [];
                if ($value == 'null') {
                  // CRM-19074 if someone has deliberately chosen to overwrite with 'null', respect it.
                  $submitted[$key] = $value;
                }
                else {
                  if ($value) {
                    $submittedCustomFields = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
                  }

                  // CRM-19653: overwrite or add the existing custom field value with dupicate contact's
                  // custom field value stored at $submittedCustomValue.
                  foreach ($submittedCustomFields as $k => $v) {
                    if ($v != '' && !in_array($v, $mergeValue)) {
                      $mergeValue[] = $v;
                    }
                  }

                  //keep state and country as array format.
                  //for checkbox and m-select format w/ VALUE_SEPARATOR
                  if (in_array($htmlType, [
                    'CheckBox',
                    'Multi-Select',
                  ])) {
                    $submitted[$key] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                        $mergeValue
                      ) . CRM_Core_DAO::VALUE_SEPARATOR;
                  }
                  else {
                    $submitted[$key] = $mergeValue;
                  }
                }
              }
            }
            elseif (in_array($htmlType, [
              'Multi-Select Country',
              'Multi-Select State/Province',
            ])) {
              //we require submitted values should be in array format
              if ($value) {
                $mergeValueArray = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
                //hack to remove null values from array.
                $mergeValue = [];
                foreach ($mergeValueArray as $k => $v) {
                  if ($v != '') {
                    $mergeValue[] = $v;
                  }
                }
                $submitted[$key] = $mergeValue;
              }
            }
            break;

          default:
            break;
        }
      }
    }

    // **** Do file custom fields related migrations
    // FIXME: move this someplace else (one of the BAOs) after discussing
    // where to, and whether CRM_Core_BAO_File::deleteFileReferences() shouldn't actually,
    // like, delete a file...

    if (!isset($customFiles)) {
      $customFiles = [];
    }
    foreach ($customFiles as $customId) {
      list($tableName, $columnName, $groupID) = CRM_Core_BAO_CustomField::getTableColumnGroup($customId);

      // get the contact_id -> file_id mapping
      $fileIds = [];
      $sql = "SELECT entity_id, {$columnName} AS file_id FROM {$tableName} WHERE entity_id IN ({$mainId}, {$otherId})";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $fileIds[$dao->entity_id] = $dao->file_id;
        if ($dao->entity_id == $mainId) {
          CRM_Core_BAO_File::deleteFileReferences($fileIds[$mainId], $mainId, $customId);
        }
      }
      $dao->free();

      // move the other contact's file to main contact
      //NYSS need to INSERT or UPDATE depending on whether main contact has an existing record
      if (CRM_Core_DAO::singleValueQuery("SELECT id FROM {$tableName} WHERE entity_id = {$mainId}")) {
        $sql = "UPDATE {$tableName} SET {$columnName} = {$fileIds[$otherId]} WHERE entity_id = {$mainId}";
      }
      else {
        $sql = "INSERT INTO {$tableName} ( entity_id, {$columnName} ) VALUES ( {$mainId}, {$fileIds[$otherId]} )";
      }
      CRM_Core_DAO::executeQuery($sql);

      if (CRM_Core_DAO::singleValueQuery("
        SELECT id
        FROM civicrm_entity_file
        WHERE entity_table = '{$tableName}' AND file_id = {$fileIds[$otherId]}")
      ) {
        $sql = "
          UPDATE civicrm_entity_file
          SET entity_id = {$mainId}
          WHERE entity_table = '{$tableName}' AND file_id = {$fileIds[$otherId]}";
      }
      else {
        $sql = "
          INSERT INTO civicrm_entity_file ( entity_table, entity_id, file_id )
          VALUES ( '{$tableName}', {$mainId}, {$fileIds[$otherId]} )";
      }
      CRM_Core_DAO::executeQuery($sql);
    }

    // move view only custom fields CRM-5362
    $viewOnlyCustomFields = [];
    foreach ($submitted as $key => $value) {
      $fid = CRM_Core_BAO_CustomField::getKeyID($key);
      if ($fid && array_key_exists($fid, $cFields) && !empty($cFields[$fid]['attributes']['is_view'])
      ) {
        $viewOnlyCustomFields[$key] = $value;
      }
    }
    // special case to set values for view only, CRM-5362
    if (!empty($viewOnlyCustomFields)) {
      $viewOnlyCustomFields['entityID'] = $mainId;
      CRM_Core_BAO_CustomValueTable::setValues($viewOnlyCustomFields);
    }

    if (!$checkPermissions || (CRM_Core_Permission::check('merge duplicate contacts') &&
        CRM_Core_Permission::check('delete contacts'))
    ) {
      // if ext id is submitted then set it null for contact to be deleted
      if (!empty($submitted['external_identifier'])) {
        $query = "UPDATE civicrm_contact SET external_identifier = null WHERE id = {$otherId}";
        CRM_Core_DAO::executeQuery($query);
      }
      civicrm_api3('contact', 'delete', ['id' => $otherId]);
    }

    // CRM-15681 merge sub_types
    if ($other_sub_types = CRM_Utils_Array::value('contact_sub_type', $migrationInfo['other_details'])) {
      if ($main_sub_types = CRM_Utils_Array::value('contact_sub_type', $migrationInfo['main_details'])) {
        $submitted['contact_sub_type'] = array_unique(array_merge($main_sub_types, $other_sub_types));
      }
      else {
        $submitted['contact_sub_type'] = $other_sub_types;
      }
    }

    // **** Update contact related info for the main contact
    if (!empty($submitted)) {
      $submitted['contact_id'] = $mainId;

      //update current employer field
      if ($currentEmloyerId = CRM_Utils_Array::value('current_employer_id', $submitted)) {
        if (!CRM_Utils_System::isNull($currentEmloyerId)) {
          $submitted['current_employer'] = $submitted['current_employer_id'];
        }
        else {
          $submitted['current_employer'] = '';
        }
        unset($submitted['current_employer_id']);
      }

      //CRM-14312 include prefix/suffix from mainId if not overridden for proper construction of display/sort name
      if (!isset($submitted['prefix_id']) && !empty($migrationInfo['main_details']['prefix_id'])) {
        $submitted['prefix_id'] = $migrationInfo['main_details']['prefix_id'];
      }
      if (!isset($submitted['suffix_id']) && !empty($migrationInfo['main_details']['suffix_id'])) {
        $submitted['suffix_id'] = $migrationInfo['main_details']['suffix_id'];
      }

      CRM_Contact_BAO_Contact::createProfileContact($submitted, CRM_Core_DAO::$_nullArray, $mainId);
    }

    CRM_Utils_Hook::post('merge', 'Contact', $mainId);
    self::createMergeActivities($mainId, $otherId);

    return TRUE;
  }

  /**
   * Builds an Array of Custom tables for given custom field ID's.
   *
   * @param $customFieldIDs
   *
   * @return array
   *   Array of custom table names
   */
  private static function getAffectedCustomTables($customFieldIDs) {
    $customTableToCopyValues = [];

    foreach ($customFieldIDs as $fieldID) {
      if (!empty($fieldID)) {
        $customField = civicrm_api3('custom_field', 'getsingle', [
          'id' => $fieldID,
          'is_active' => TRUE,
        ]);
        if (!civicrm_error($customField) && !empty($customField['custom_group_id'])) {
          $customGroup = civicrm_api3('custom_group', 'getsingle', [
            'id' => $customField['custom_group_id'],
            'is_active' => TRUE,
          ]);

          if (!civicrm_error($customGroup) && !empty($customGroup['table_name'])) {
            $customTableToCopyValues[] = $customGroup['table_name'];
          }
        }
      }
    }

    return $customTableToCopyValues;
  }

  /**
   * Get fields in the contact table suitable for merging.
   *
   * @return array
   *   Array of field names to be potentially merged.
   */
  public static function getContactFields() {
    $contactFields = CRM_Contact_DAO_Contact::fields();
    $invalidFields = [
      'api_key',
      'created_date',
      'display_name',
      'hash',
      'id',
      'modified_date',
      'primary_contact_id',
      'sort_name',
      'user_unique_id',
    ];
    foreach ($contactFields as $field => $value) {
      if (in_array($field, $invalidFields)) {
        unset($contactFields[$field]);
      }
    }
    return array_keys($contactFields);
  }

  /**
   * Added for CRM-12695
   * Based on the contactID provided
   * add/update membership(s) to related contacts
   *
   * @param int $contactID
   */
  public static function addMembershipToRealtedContacts($contactID) {
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $contactID;
    $dao->is_test = 0;
    $dao->find();

    //checks membership of contact itself
    while ($dao->fetch()) {
      $relationshipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $dao->membership_type_id, 'relationship_type_id', 'id');
      if ($relationshipTypeId) {
        $membershipParams = [
          'id' => $dao->id,
          'contact_id' => $dao->contact_id,
          'membership_type_id' => $dao->membership_type_id,
          'join_date' => CRM_Utils_Date::isoToMysql($dao->join_date),
          'start_date' => CRM_Utils_Date::isoToMysql($dao->start_date),
          'end_date' => CRM_Utils_Date::isoToMysql($dao->end_date),
          'source' => $dao->source,
          'status_id' => $dao->status_id,
        ];
        // create/update membership(s) for related contact(s)
        CRM_Member_BAO_Membership::createRelatedMemberships($membershipParams, $dao);
      } // end of if relationshipTypeId
    }
  }

  /**
   * Create activities tracking the merge on affected contacts.
   *
   * @param int $mainId
   * @param int $otherId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function createMergeActivities($mainId, $otherId) {
    $params = [
      1 => $otherId,
      2 => $mainId,
    ];
    $activity = civicrm_api3('activity', 'create', [
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID() ? CRM_Core_Session::getLoggedInContactID() :
        $mainId,
      'subject' => ts('Contact ID %1 has been merged and deleted.', $params),
      'target_contact_id' => $mainId,
      'activity_type_id' => 'Contact Merged',
      'status_id' => 'Completed',
    ]);
    if (civicrm_api3('Setting', 'getvalue', [
      'name' => 'contact_undelete',
      'group' => 'CiviCRM Preferences',
    ])) {
      civicrm_api3('activity', 'create', [
        'source_contact_id' => CRM_Core_Session::getLoggedInContactID() ? CRM_Core_Session::getLoggedInContactID() :
          $otherId,
        'subject' => ts('Contact ID %1 has been merged into Contact ID %2 and deleted.', $params),
        'target_contact_id' => $otherId,
        'activity_type_id' => 'Contact Deleted by Merge',
        'parent_id' => $activity['id'],
        'status_id' => 'Completed',
      ]);
    }
  }

  /**
   * Get Duplicate Pairs based on a rule for a group.
   *
   * @param int $rule_group_id
   * @param int $group_id
   * @param bool $reloadCacheIfEmpty
   *   Should the cache be reloaded if empty - this must be false when in a dedupe action!
   * @param int $batchLimit
   * @param bool $isSelected
   *   Limit to selected pairs.
   * @param array|string $orderByClause
   * @param bool $includeConflicts
   * @param array $criteria
   *   Additional criteria to narrow down the merge group.
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   *
   * @param int $searchLimit
   *   Limit to searching for matches against this many contacts.
   *
   * @return array
   *    Array of matches meeting the criteria.
   */
  public static function getDuplicatePairs($rule_group_id, $group_id, $reloadCacheIfEmpty, $batchLimit, $isSelected, $orderByClause = '', $includeConflicts = TRUE, $criteria = [], $checkPermissions = TRUE, $searchLimit = 0) {
    $where = self::getWhereString($isSelected);
    $cacheKeyString = self::getMergeCacheKeyString($rule_group_id, $group_id, $criteria, $checkPermissions);
    $join = self::getJoinOnDedupeTable();
    $dupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, $join, $where, 0, $batchLimit, [], $orderByClause, $includeConflicts);
    if (empty($dupePairs) && $reloadCacheIfEmpty) {
      // If we haven't found any dupes, probably cache is empty.
      // Try filling cache and give another try. We don't need to specify include conflicts here are there will not be any
      // until we have done some processing.
      CRM_Core_BAO_PrevNextCache::refillCache($rule_group_id, $group_id, $cacheKeyString, $criteria, $checkPermissions, $searchLimit);
      $dupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, $join, $where, 0, $batchLimit, [], $orderByClause, $includeConflicts);
      return $dupePairs;
    }
    return $dupePairs;
  }

  /**
   * Get the cache key string for the merge action.
   *
   * @param int $rule_group_id
   * @param int $group_id
   * @param array $criteria
   *   Additional criteria to narrow down the merge group.
   *   Currently we are only supporting the key 'contact' within it.
   *
   * @param bool $checkPermissions
   *   Respect the users permissions.
   *
   * @return string
   */
  public static function getMergeCacheKeyString($rule_group_id, $group_id, $criteria = [], $checkPermissions = TRUE) {
    $contactType = CRM_Dedupe_BAO_RuleGroup::getContactTypeForRuleGroup($rule_group_id);
    $cacheKeyString = "merge_{$contactType}";
    $cacheKeyString .= $rule_group_id ? "_{$rule_group_id}" : '_0';
    $cacheKeyString .= $group_id ? "_{$group_id}" : '_0';
    $cacheKeyString .= !empty($criteria) ? md5(serialize($criteria)) : '_0';
    if ($checkPermissions) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
      if (!$contactID) {
        // Distinguish between no permission check & no logged in user.
        $contactID = 'null';
      }
      $cacheKeyString .= '_' . $contactID;
    }
    else {
      $cacheKeyString .= '_0';
    }
    return $cacheKeyString;
  }

  /**
   * @param array $contact
   * @return array
   *   $specialValues
   */
  public static function getSpecialValues($contact) {
    $preferred_communication_method = CRM_Utils_Array::value('preferred_communication_method', $contact);
    $value = empty($preferred_communication_method) ? [] : $preferred_communication_method;
    $specialValues = [
      'preferred_communication_method' => $value,
      'communication_style_id' => $value,
    ];

    if (!empty($contact['preferred_communication_method'])) {
      // api 3 returns pref_comm_method as an array, which breaks the lookup; so we reconstruct
      $prefCommList = is_array($specialValues['preferred_communication_method']) ? implode(CRM_Core_DAO::VALUE_SEPARATOR, $specialValues['preferred_communication_method']) : $specialValues['preferred_communication_method'];
      $specialValues['preferred_communication_method'] = CRM_Core_DAO::VALUE_SEPARATOR . $prefCommList . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    $names = [
      'preferred_communication_method' => [
        'newName' => 'preferred_communication_method_display',
        'groupName' => 'preferred_communication_method',
      ],
    ];
    CRM_Core_OptionGroup::lookupValues($specialValues, $names);

    if (!empty($contact['communication_style'])) {
      $specialValues['communication_style_id_display'] = $contact['communication_style'];
    }
    return $specialValues;
  }

  /**
   * Get the metadata for the merge fields.
   *
   * This is basically the contact metadata, augmented with fields to
   * represent email greeting, postal greeting & addressee.
   *
   * @return array
   */
  public static function getMergeFieldsMetadata() {
    if (isset(\Civi::$statics[__CLASS__]) && isset(\Civi::$statics[__CLASS__]['merge_fields_metadata'])) {
      return \Civi::$statics[__CLASS__]['merge_fields_metadata'];
    }
    $fields = CRM_Contact_DAO_Contact::fields();
    static $optionValueFields = [];
    if (empty($optionValueFields)) {
      $optionValueFields = CRM_Core_OptionValue::getFields();
    }
    foreach ($optionValueFields as $field => $params) {
      $fields[$field]['title'] = $params['title'];
    }
    \Civi::$statics[__CLASS__]['merge_fields_metadata'] = $fields;
    return \Civi::$statics[__CLASS__]['merge_fields_metadata'];
  }

  /**
   * Get the details of the contact to be merged.
   *
   * @param int $contact_id
   *
   * @return array
   *
   * @throws CRM_Core_Exception
   */
  public static function getMergeContactDetails($contact_id) {
    $params = [
      'contact_id' => $contact_id,
      'version' => 3,
      'return' => array_merge(['display_name'], self::getContactFields()),
    ];
    $result = civicrm_api('contact', 'get', $params);

    // CRM-18480: Cancel the process if the contact is already deleted
    if (isset($result['values'][$contact_id]['contact_is_deleted']) && !empty($result['values'][$contact_id]['contact_is_deleted'])) {
      throw new CRM_Core_Exception(ts('Cannot merge because one contact (ID %1) has been deleted.', [
        1 => $contact_id,
      ]));
    }

    return $result['values'][$contact_id];
  }

  /**
   * Merge location.
   *
   * Based on the data in the $locationMigrationInfo merge the locations for 2 contacts.
   *
   * The data is in the format received from the merge form (which is a fairly confusing format).
   *
   * It is converted into an array of DAOs which is passed to the alterLocationMergeData hook
   * before saving or deleting the DAOs. A new hook is added to allow these to be altered after they have
   * been calculated and before saving because
   * - the existing format & hook combo is so confusing it is hard for developers to change & inherently fragile
   * - passing to a hook right before save means calculations only have to be done once
   * - the existing pattern of passing dissimilar data to the same (merge) hook with a different 'type' is just
   *  ugly.
   *
   * The use of the new hook is tested, including the fact it is called before contributions are merged, as this
   * is likely to be significant data in merge hooks.
   *
   * @param int $mainId
   * @param int $otherId
   * @param array $locationMigrationInfo
   *   Portion of the migration_info that holds location migration information.
   *
   * @param array $migrationInfo
   *   Migration info for the merge. This is passed to the hook as informational only.
   */
  public static function mergeLocations($mainId, $otherId, $locationMigrationInfo, $migrationInfo) {
    foreach ($locationMigrationInfo as $key => $value) {
      $locField = explode('_', $key);
      $fieldName = $locField[2];
      $fieldCount = $locField[3];

      // Set up the operation type (add/overwrite)
      // Ignore operation for websites
      // @todo Tidy this up
      $operation = 0;
      if ($fieldName != 'website') {
        $operation = CRM_Utils_Array::value('operation', $migrationInfo['location_blocks'][$fieldName][$fieldCount]);
      }
      // default operation is overwrite.
      if (!$operation) {
        $operation = 2;
      }
      $locBlocks[$fieldName][$fieldCount]['operation'] = $operation;
    }
    $blocksDAO = [];

    // @todo Handle OpenID (not currently in API).
    if (!empty($locBlocks)) {
      $locationBlocks = self::getLocationBlockInfo();

      $primaryBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($mainId, ['is_primary' => 1]);
      $billingBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($mainId, ['is_billing' => 1]);

      foreach ($locBlocks as $name => $block) {
        $blocksDAO[$name] = ['delete' => [], 'update' => []];
        if (!is_array($block) || CRM_Utils_System::isNull($block)) {
          continue;
        }
        $daoName = 'CRM_Core_DAO_' . $locationBlocks[$name]['label'];
        $changePrimary = FALSE;
        $primaryDAOId = (array_key_exists($name, $primaryBlockIds)) ? array_pop($primaryBlockIds[$name]) : NULL;
        $billingDAOId = (array_key_exists($name, $billingBlockIds)) ? array_pop($billingBlockIds[$name]) : NULL;

        foreach ($block as $blkCount => $values) {
          $otherBlockId = CRM_Utils_Array::value('id', $migrationInfo['other_details']['location_blocks'][$name][$blkCount]);
          $mainBlockId = CRM_Utils_Array::value('mainContactBlockId', $migrationInfo['location_blocks'][$name][$blkCount], 0);
          if (!$otherBlockId) {
            continue;
          }

          // For the block which belongs to other-contact, link the location block to main-contact
          $otherBlockDAO = new $daoName();
          $otherBlockDAO->contact_id = $mainId;

          // Get the ID of this block on the 'other' contact, otherwise skip
          $otherBlockDAO->id = $otherBlockId;

          // Add/update location and type information from the form, if applicable
          if ($locationBlocks[$name]['hasLocation']) {
            $locTypeId = CRM_Utils_Array::value('locTypeId', $migrationInfo['location_blocks'][$name][$blkCount]);
            $otherBlockDAO->location_type_id = $locTypeId;
          }
          if ($locationBlocks[$name]['hasType']) {
            $typeTypeId = CRM_Utils_Array::value('typeTypeId', $migrationInfo['location_blocks'][$name][$blkCount]);
            $otherBlockDAO->{$locationBlocks[$name]['hasType']} = $typeTypeId;
          }

          // If we're deliberately setting this as primary then add the flag
          // and remove it from the current primary location (if there is one).
          // But only once for each entity.
          $set_primary = CRM_Utils_Array::value('set_other_primary', $migrationInfo['location_blocks'][$name][$blkCount]);
          if (!$changePrimary && $set_primary == "1") {
            $otherBlockDAO->is_primary = 1;
            if ($primaryDAOId) {
              $removePrimaryDAO = new $daoName();
              $removePrimaryDAO->id = $primaryDAOId;
              $removePrimaryDAO->is_primary = 0;
              $blocksDAO[$name]['update'][$primaryDAOId] = $removePrimaryDAO;
            }
            $changePrimary = TRUE;
          }
          // Otherwise, if main contact already has primary, set it to 0.
          elseif ($primaryDAOId) {
            $otherBlockDAO->is_primary = 0;
          }

          // If the main contact already has a billing location, set this to 0.
          if ($billingDAOId) {
            $otherBlockDAO->is_billing = 0;
          }

          $operation = CRM_Utils_Array::value('operation', $values, 2);
          // overwrite - need to delete block which belongs to main-contact.
          if (!empty($mainBlockId) && ($operation == 2)) {
            $deleteDAO = new $daoName();
            $deleteDAO->id = $mainBlockId;
            $deleteDAO->find(TRUE);

            // if we about to delete a primary / billing block, set the flags for new block
            // that we going to assign to main-contact
            if ($primaryDAOId && ($primaryDAOId == $deleteDAO->id)) {
              $otherBlockDAO->is_primary = 1;
            }
            if ($billingDAOId && ($billingDAOId == $deleteDAO->id)) {
              $otherBlockDAO->is_billing = 1;
            }
            $blocksDAO[$name]['delete'][$deleteDAO->id] = $deleteDAO;
          }
          $blocksDAO[$name]['update'][$otherBlockDAO->id] = $otherBlockDAO;
        }
      }
    }

    CRM_Utils_Hook::alterLocationMergeData($blocksDAO, $mainId, $otherId, $migrationInfo);
    foreach ($blocksDAO as $blockDAOs) {
      if (!empty($blockDAOs['update'])) {
        foreach ($blockDAOs['update'] as $blockDAO) {
          $blockDAO->save();
        }
      }
      if (!empty($blockDAOs['delete'])) {
        foreach ($blockDAOs['delete'] as $blockDAO) {
          $blockDAO->delete();
        }
      }
    }
  }

  /**
   * Dedupe a pair of contacts.
   *
   * @param array $migrationInfo
   * @param array $resultStats
   * @param array $deletedContacts
   * @param string $mode
   * @param bool $checkPermissions
   * @param int $mainId
   * @param int $otherId
   * @param string $cacheKeyString
   */
  protected static function dedupePair(&$migrationInfo, &$resultStats, &$deletedContacts, $mode, $checkPermissions, $mainId, $otherId, $cacheKeyString) {

    // go ahead with merge if there is no conflict
    $conflicts = [];
    if (!CRM_Dedupe_Merger::skipMerge($mainId, $otherId, $migrationInfo, $mode, $conflicts)) {
      CRM_Dedupe_Merger::moveAllBelongings($mainId, $otherId, $migrationInfo, $checkPermissions);
      $resultStats['merged'][] = [
        'main_id' => $mainId,
        'other_id' => $otherId,
      ];
      $deletedContacts[] = $otherId;
    }
    else {
      $resultStats['skipped'][] = [
        'main_id' => $mainId,
        'other_id' => $otherId,
      ];
    }

    // store any conflicts
    if (!empty($conflicts)) {
      foreach ($conflicts as $key => $dnc) {
        $conflicts[$key] = "{$migrationInfo['rows'][$key]['title']}: '{$migrationInfo['rows'][$key]['main']}' vs. '{$migrationInfo['rows'][$key]['other']}'";
      }
      CRM_Core_BAO_PrevNextCache::markConflict($mainId, $otherId, $cacheKeyString, $conflicts);
    }
    else {
      // delete entry from PrevNextCache table so we don't consider the pair next time
      // pair may have been flipped, so make sure we delete using both orders
      CRM_Core_BAO_PrevNextCache::deletePair($mainId, $otherId, $cacheKeyString, TRUE);
    }
  }

}
