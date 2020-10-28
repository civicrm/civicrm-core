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

use Civi\Api4\CustomGroup;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Dedupe_Merger {

  /**
   * FIXME: consider creating a common structure with cidRefs() and eidRefs()
   * FIXME: the sub-pages references by the URLs should
   * be loaded dynamically on the merge form instead
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function relTables() {

    if (!isset(Civi::$statics[__CLASS__]['relTables'])) {

      // Setting these merely prevents enotices - but it may be more appropriate not to add the user table below
      // if the url can't be retrieved. A more standardised way to retrieve them is.
      // CRM_Core_Config::singleton()->userSystem->getUserRecordUrl() - however that function takes a contact_id &
      // we may need a different function when it is not known.
      $title = $userRecordUrl = '';

      $config = CRM_Core_Config::singleton();
      // @todo - this user url stuff is only needed for the form layer - move to CRM_Contact_Form_Merge
      if ($config->userSystem->is_drupal) {
        $userRecordUrl = CRM_Utils_System::url('user/%ufid');
        $title = ts('%1 User: %2; user id: %3', [
          1 => $config->userFramework,
          2 => '$ufname',
          3 => '$ufid',
        ]);
      }
      elseif ($config->userFramework === 'Joomla') {
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
   * @throws \CiviCRM_API3_Exception
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
    foreach (['civicrm_group_contact_cache', 'civicrm_acl_cache', 'civicrm_acl_contact_cache'] as $tableName) {
      // Don't merge cache tables. These should be otherwise cleared at some point in the dedupe
      // but they are prone to locking to let's not touch during the dedupe.
      unset($contactReferences[$tableName], $coreReferences[$tableName]);
    }

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
   *
   * @param string $request
   *   'relTables' or 'cidRefs'.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
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
        $urlSuffix = $custom['style'] === 'Tab' ? '&selectedChild=custom_' . $custom['id'] : '';
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
        if ($mode === 'add') {
          $sqls[] = "
DELETE membership1.* FROM civicrm_membership membership1
 INNER JOIN  civicrm_membership membership2 ON membership1.membership_type_id = membership2.membership_type_id
             AND membership1.contact_id = {$mainId}
             AND membership2.contact_id = {$otherId} ";
        }
        if ($mode === 'payment') {
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
   * @param array $tables
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function removeContactBelongings($otherID, $tables) {
    // CRM-20421: Removing Inherited memberships when memberships of parent are not migrated to new contact.
    if (in_array('civicrm_membership', $tables, TRUE)) {
      $membershipIDs = CRM_Utils_Array::collect('id',
        CRM_Utils_Array::value('values',
          civicrm_api3('Membership', "get", [
            'contact_id' => $otherID,
            'return' => 'id',
          ])
        )
      );

      if (!empty($membershipIDs)) {
        civicrm_api3('Membership', 'get', [
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
   * @param CRM_Dedupe_MergeHandler $mergeHandler
   * @param array $tables
   * @param array $tableOperations
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function moveContactBelongings($mergeHandler, $tables, $tableOperations) {
    $mainId = $mergeHandler->getToKeepID();
    $otherId = $mergeHandler->getToRemoveID();
    $cidRefs = self::cidRefs();
    $eidRefs = $mergeHandler->getTablesDynamicallyRelatedToContactTable();
    $dynamicRefs = CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact');
    $cpTables = self::cpTables();
    $paymentTables = self::paymentTables();
    self::filterRowBasedCustomDataFromCustomTables($cidRefs);

    $affected = array_merge(array_keys($cidRefs), array_keys($eidRefs));

    // if there aren't any specific tables, don't affect the ones handled by relTables()
    // also don't affect tables in locTables() CRM-15658
    $relTables = self::relTables();
    // These arrays don't make a lot of sense. For now ensure the tested handling of tags works...
    // it is moved over further down....
    unset($relTables['rel_table_tags']);
    $handled = self::locTables();

    foreach ($relTables as $params) {
      $handled = array_merge($handled, $params['tables']);
    }
    $affected = array_diff($affected, $handled);
    $affected = array_unique(array_merge($affected, $tables));

    $mainId = (int) $mainId;
    $otherId = (int) $otherId;

    $sqls = [];
    foreach ($affected as $table) {
      // Call custom processing function for objects that require it
      if (isset($cpTables[$table])) {
        foreach ($cpTables[$table] as $className => $fnName) {
          $className::$fnName($mainId, $otherId, $sqls, $tables, $tableOperations);
        }
        // Skip normal processing
        continue;
      }

      if ($table === 'civicrm_activity_contact') {
        $sqls[] = "UPDATE IGNORE civicrm_activity_contact SET contact_id = $mainId WHERE contact_id = $otherId";
        $sqls[] = "DELETE FROM civicrm_activity_contact WHERE contact_id = $otherId";
        continue;
      }

      if ($table === 'civicrm_dashboard_contact') {
        $sqls[] = "UPDATE IGNORE civicrm_dashboard_contact SET contact_id = $mainId WHERE contact_id = $otherId";
        $sqls[] = "DELETE FROM civicrm_dashboard_contact WHERE contact_id = $otherId";
        continue;
      }

      if ($table === 'civicrm_dedupe_exception') {
        $sqls[] = "UPDATE IGNORE civicrm_dedupe_exception SET contact_id1 = $mainId WHERE contact_id1 = $otherId";
        $sqls[] = "UPDATE IGNORE civicrm_dedupe_exception SET contact_id2 = $mainId WHERE contact_id2 = $otherId";
        $sqls[] = "DELETE FROM civicrm_dedupe_exception WHERE contact_id1 = $otherId OR contact_id2 = $otherId";
        continue;
      }

      if ($table === 'civicrm_setting') {
        // Per https://lab.civicrm.org/dev/core/-/issues/1934
        // Note this line is not unit tested as yet as a quick-fix for a regression
        // but it would be better to do a SELECT request & only update if needed (as a general rule
        // more selects & less UPDATES will result in less deadlocks while de-duping.
        // Note the delete is not important here - it can stay with the deleted contact on the
        // off chance they get restored.
        $sqls[] = "UPDATE IGNORE civicrm_setting SET contact_id = $mainId WHERE contact_id = $otherId";
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
          $sqls[] = "UPDATE $table SET $field = $mainId WHERE $field = $otherId";
        }
      }

      if (isset($eidRefs[$table])) {
        foreach ($dynamicRefs[$table] as $dynamicRef) {
          $sqls[] = "UPDATE IGNORE $table SET {$dynamicRef[0]}= $mainId WHERE {$dynamicRef[0]} = $otherId AND {$dynamicRef[1]} = 'civicrm_contact'";
          $sqls[] = "DELETE FROM $table WHERE {$dynamicRef[0]} = $otherId AND {$dynamicRef[1]} = 'civicrm_contact'";
        }
      }
    }

    // Allow hook_civicrm_merge() to add SQL statements for the merge operation.
    CRM_Utils_Hook::merge('sqls', $sqls, $mainId, $otherId, $tables);

    foreach ($sqls as $sql) {
      CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, TRUE);
    }
    CRM_Dedupe_Merger::addMembershipToRealtedContacts($mainId);
  }

  /**
   * Filter out custom tables from cidRefs unless they are there due to a contact reference or are a multiple set.
   *
   * The only fields where we want to move the data by sql is where entity reference fields
   * on another contact refer to the contact being merged, or it is a multiple record set.
   * The transference of custom data from one contact to another is done in 2 other places in the dedupe process but should
   * not be done in moveAllContactData.
   *
   * Note it's a bit silly the way we build & then cull cidRefs - however, poor hook placement means that
   * until we fully deprecate calling the hook from cidRefs we are stuck.
   *
   * It was deprecated in code (via deprecation notices if people altered it) in Mar 2019 but in docs only in Apri 2020.
   *
   * @param array $cidRefs
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected static function filterRowBasedCustomDataFromCustomTables(array &$cidRefs) {
    $customTables = (array) CustomGroup::get(FALSE)
      ->setSelect(['table_name'])
      ->addWhere('is_multiple', '=', 0)
      ->addWhere('extends', 'IN', array_merge(['Contact'], CRM_Contact_BAO_ContactType::contactTypes()))
      ->execute()
      ->indexBy('table_name');
    foreach (array_intersect_key($cidRefs, $customTables) as $tableName => $cidSpec) {
      if (in_array('entity_id', $cidSpec, TRUE)) {
        unset($cidRefs[$tableName][array_search('entity_id', $cidSpec, TRUE)]);
      }
      if (empty($cidRefs[$tableName])) {
        unset($cidRefs[$tableName]);
      }
    }
  }

  /**
   * Update the contact with the new parameters.
   *
   * This function is intended as an interim function, with the intent being
   * an apiv4 call.
   *
   * The function was calling the rather-terrifying createProfileContact. I copied all
   * that code into this function and then removed all the parts that have no effect in this scenario.
   *
   * @param int $contactID
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected static function createContact($contactID, $params) {
    // This parameter causes blank fields to be be emptied out.
    // We can probably remove.
    $params['updateBlankLocInfo'] = TRUE;
    list($data) = CRM_Contact_BAO_Contact::formatProfileContactParams($params, [], $contactID);
    CRM_Contact_BAO_Contact::create($data);
  }

  /**
   * Given a contact ID, will check if a record exists in given table.
   *
   * @param int $contactID
   * @param string $table
   * @param string $idField
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
   *
   * @throws \CRM_Core_Exception
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
      $key1 = $mainEvs[$key] ?? NULL;
      $key2 = $otherEvs[$key] ?? NULL;
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
   * @param int $searchLimit
   *   Limit on number of contacts to search for duplicates for.
   *   This means that if the limit is 1000 then only duplicates for the first 1000 contacts
   *   matching criteria will be found and batchMerged (the number of merges could be less than or greater than 100)
   *
   * @return array|bool
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \API_Exception
   */
  public static function batchMerge($rgid, $gid = NULL, $mode = 'safe', $batchLimit = 1, $isSelected = 2, $criteria = [], $checkPermissions = TRUE, $reloadCacheIfEmpty = NULL, $searchLimit = 0) {
    $redirectForPerformance = $batchLimit > 1;
    if ($mode === 'aggressive' && $checkPermissions && !CRM_Core_Permission::check('force merge duplicate contacts')) {
      throw new CRM_Core_Exception(ts('Insufficient permissions for aggressive mode batch merge'));
    }
    if (!isset($reloadCacheIfEmpty)) {
      $reloadCacheIfEmpty = (!$redirectForPerformance && $isSelected == 2);
    }
    if ($isSelected !== 0 && $isSelected !== 1) {
      // explicitly set to NULL if not 1 or 0 as part of grandfathering out the mystical '2' value.
      $isSelected = NULL;
    }
    $dupePairs = self::getDuplicatePairs($rgid, $gid, $reloadCacheIfEmpty, $batchLimit, $isSelected, ($mode === 'aggressive'), $criteria, $checkPermissions, $searchLimit);

    $cacheParams = [
      'cache_key_string' => self::getMergeCacheKeyString($rgid, $gid, $criteria, $checkPermissions, $searchLimit),
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
    return '
      LEFT JOIN civicrm_dedupe_exception de
        ON (
          pn.entity_id1 = de.contact_id1
          AND pn.entity_id2 = de.contact_id2 )
       ';
  }

  /**
   * Get where string for dedupe join.
   *
   * @param bool $isSelected
   *
   * @return string
   */
  protected static function getWhereString($isSelected) {
    $where = 'de.id IS NULL';
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
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateMergeStats($cacheKeyString, $result = []) {
    // gather latest stats
    $merged = count($result['merged']);
    $skipped = count($result['skipped']);

    if ($merged <= 0 && $skipped <= 0) {
      return;
    }

    // get previous stats
    $previousStats = CRM_Dedupe_Merger::getMergeStats($cacheKeyString);
    if (!empty($previousStats)) {
      if ($previousStats['merged']) {
        $merged = $merged + $previousStats['merged'];
      }
      if ($previousStats['skipped']) {
        $skipped = $skipped + $previousStats['skipped'];
      }
    }

    // delete old stats
    CRM_Dedupe_Merger::resetMergeStats($cacheKeyString);

    // store the updated stats
    $data = [
      'merged' => (int) $merged,
      'skipped' => (int) $skipped,
    ];

    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cacheKey, data) VALUES
        ('civicrm_contact', 0, 0, %1, %2)", [1 => [$cacheKeyString . '_stats', 'String'], 2 => [serialize($data), 'String']]);
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
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getMergeStats($cacheKeyString) {
    $stats = civicrm_api3('Dedupe', 'get', ['cachekey' => "{$cacheKeyString}_stats", 'sequential' => 1])['values'];
    if (!empty($stats)) {
      return $stats[0]['data'];
    }
    return [];
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
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function merge($dupePairs = [], $cacheParams = [], $mode = 'safe',
                               $redirectForPerformance = FALSE, $checkPermissions = TRUE
  ) {
    $cacheKeyString = $cacheParams['cache_key_string'] ?? NULL;
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
        if (($result = self::dedupePair((int) $dupes['dstID'], (int) $dupes['srcID'], $mode, $checkPermissions, $cacheKeyString)) === FALSE) {
          unset($dupePairs[$index]);
          continue;
        }
        if (!empty($result['merged'])) {
          $deletedContacts[] = $result['merged'][0]['other_id'];
          $resultStats['merged'][] = ($result['merged'][0]);
        }
        else {
          $resultStats['skipped'][] = ($result['skipped'][0]);
        }
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
   *  An empty array to be filed with conflict information.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \API_Exception
   */
  public static function skipMerge($mainId, $otherId, &$migrationInfo, $mode = 'safe', &$conflicts = []) {

    $conflicts = self::getConflicts($migrationInfo, $mainId, $otherId, $mode)['conflicts'];
    // A hook could have set skip_merge in order to alter merge behaviour.
    // This is a something we might ideally deprecate since they really 'should'
    // mess with the conflicts array instead.
    return (bool) ($migrationData['skip_merge'] ?? !empty($conflicts));
  }

  /**
   * Compare 2 addresses to see if they are the effectively the same.
   *
   * Being the same would mean same location type and any populated fields that describe the locationn match.
   *
   * Metadata fields such as is_primary, on_hold, manual_geocode may differ.
   *
   * @param array $mainAddress
   * @param array $comparisonAddress
   *
   * @return bool
   */
  public static function locationIsSame($mainAddress, $comparisonAddress) {
    $keysToIgnore = self::ignoredFields();
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
   * Does the location array have valid data.
   *
   * While not UI-creatable some sites wind up with email or address rows with no actual email or address
   * through non core-UI processes.
   *
   * @param array $location
   *
   * @return bool
   */
  public static function locationHasData($location) {
    return !empty(self::getLocationDataFields($location));
  }

  /**
   * Get the location data from a location array, filtering out metadata.
   *
   * This returns data like street_address but not metadata like is_primary, on_hold etc.
   *
   * @param array $location
   *
   * @return mixed
   */
  public static function getLocationDataFields($location) {
    $keysToIgnore = array_merge(self::ignoredFields(), ['display', 'location_type_id']);
    foreach ($location as $field => $value) {
      if (in_array($field, $keysToIgnore, TRUE)) {
        unset($location[$field]);
      }
    }
    return $location;
  }

  /**
   * A function to build an array of information about location blocks that is
   * required when merging location fields
   *
   * @return array
   */
  public static function getLocationBlockInfo() {
    return [
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

    $compareFields = self::retrieveFields($main, $other);

    $rows = $elements = $migrationInfo = [];

    foreach ($compareFields['contact'] as $field) {
      if ($field === 'contact_sub_type') {
        // CRM-15681 don't display sub-types in UI
        continue;
      }
      $rows["move_$field"] = [
        'main' => self::getFieldValueAndLabel($field, $main)['label'],
        'other' => self::getFieldValueAndLabel($field, $other)['label'],
        'title' => $fields[$field]['html']['label'] ?? $fields[$field]['title'],
      ];

      $value = self::getFieldValueAndLabel($field, $other)['value'];
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
          0 => 'advcheckbox',
          1 => "move_$field",
          2 => NULL,
          3 => NULL,
          4 => NULL,
          5 => $value,
          'is_checked' => (!isset($main[$field]) || $main[$field] === ''),
        ];
      }

      $migrationInfo["move_$field"] = $value;
    }

    // Handle location blocks.
    // @todo OpenID not in API yet, so is not supported here.

    // Set up useful information about the location blocks
    $locationBlocks = self::getLocationBlockInfo();

    $locations = ['main' => [], 'other' => []];

    foreach ($locationBlocks as $blockName => $blockInfo) {
      list($locations, $rows, $elements, $migrationInfo) = self::addLocationFieldInfo($mainId, $otherId, $blockInfo, $blockName, $locations, $rows, $elements, $migrationInfo);
    } // End loop through each location block entity

    // add the related tables and unset the ones that don't sport any of the duplicate contact's info
    $mergeHandler = new CRM_Dedupe_MergeHandler((int) $mainId, (int) $otherId);
    $relTables = $mergeHandler->getTablesRelatedToTheMergePair();
    foreach ($relTables as $name => $null) {
      $migrationInfo["move_$name"] = 1;

      $relTables[$name]['main_url'] = str_replace('$cid', $mainId, $relTables[$name]['url']);
      $relTables[$name]['other_url'] = str_replace('$cid', $otherId, $relTables[$name]['url']);
      if ($name === 'rel_table_users') {
        // @todo - this user url stuff is only needed for the form layer - move to CRM_Contact_Form_Merge
        $relTables[$name]['main_url'] = str_replace('%ufid', CRM_Core_BAO_UFMatch::getUFId($mainId), $relTables[$name]['url']);
        $relTables[$name]['other_url'] = str_replace('%ufid', CRM_Core_BAO_UFMatch::getUFId($otherId), $relTables[$name]['url']);
      }
      if ($name === 'rel_table_memberships') {
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
      if (!isset($group['fields'])) {
        continue;
      }

      foreach ($group['fields'] as $fid => $field) {
        $mainContactValue = $mainTree[$gid]['fields'][$fid]['customValue'] ?? NULL;
        $otherContactValue = $otherTree[$gid]['fields'][$fid]['customValue'] ?? NULL;
        if (in_array($fid, $compareFields['custom'])) {
          $rows["custom_group_$gid"]['title'] = $rows["custom_group_$gid"]['title'] ?? $group['title'];

          if ($mainContactValue) {
            foreach ($mainContactValue as $valueId => $values) {
              $rows["move_custom_$fid"]['main'] = CRM_Core_BAO_CustomField::displayValue($values['data'], $fid);
            }
          }
          $value = 'null';
          if ($otherContactValue) {
            foreach ($otherContactValue as $valueId => $values) {
              $rows["move_custom_$fid"]['other'] = CRM_Core_BAO_CustomField::displayValue($values['data'], $fid);
              if ($values['data'] === 0 || $values['data'] === '0') {
                $values['data'] = $qfZeroBug;
              }
              $value = ($values['data']) ? $values['data'] : $value;
            }
          }
          $rows["move_custom_$fid"]['title'] = $field['label'];

          $elements[] = [
            0 => 'advcheckbox',
            1 => "move_custom_$fid",
            2 => NULL,
            3 => NULL,
            4 => NULL,
            5 => $value,
            'is_checked' => (!isset($rows["move_custom_$fid"]['main']) || $rows["move_custom_$fid"]['main'] === ''),
          ];
          $migrationInfo["move_custom_$fid"] = $value;
        }
      }
    }

    $result = [
      'rows' => $rows,
      'elements' => $elements,
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
   * @param array $migrationInfo
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   *
   * @return bool
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function moveAllBelongings($mainId, $otherId, $migrationInfo, $checkPermissions = TRUE) {
    if (empty($migrationInfo)) {
      return FALSE;
    }
    // Encapsulate in a transaction to avoid half-merges.
    $transaction = new CRM_Core_Transaction();

    $contactType = $migrationInfo['main_details']['contact_type'];
    $relTables = CRM_Dedupe_Merger::relTables();
    $submittedCustomFields = $moveTables = $tableOperations = $removeTables = [];

    self::swapOutFieldsAffectedByQFZeroBug($migrationInfo);
    foreach ($migrationInfo as $key => $value) {

      if (substr($key, 0, 12) === 'move_custom_' && $value != NULL) {
        $submitted[substr($key, 5)] = $value;
        $submittedCustomFields[] = substr($key, 12);
      }
      elseif (in_array(substr($key, 5), CRM_Dedupe_Merger::getContactFields()) && $value != NULL) {
        $submitted[substr($key, 5)] = $value;
      }
      elseif (substr($key, 0, 15) === 'move_rel_table_' and $value == '1') {
        $moveTables = array_merge($moveTables, $relTables[substr($key, 5)]['tables']);
        if (array_key_exists('operation', $migrationInfo)) {
          foreach ($relTables[substr($key, 5)]['tables'] as $table) {
            if (array_key_exists($key, $migrationInfo['operation'])) {
              $tableOperations[$table] = $migrationInfo['operation'][$key];
            }
          }
        }
      }
      elseif (substr($key, 0, 15) === 'move_rel_table_' and $value == '0') {
        $removeTables = array_merge($moveTables, $relTables[substr($key, 5)]['tables']);
      }
    }
    $mergeHandler = new CRM_Dedupe_MergeHandler((int) $mainId, (int) $otherId);
    $mergeHandler->setMigrationInfo($migrationInfo);
    self::mergeLocations($mergeHandler);

    // **** Do contact related migrations
    // @todo - move all custom field processing to the move class & eventually have an
    // overridable DAO class for it.
    $customFieldBAO = new CRM_Core_BAO_CustomField();
    $customFieldBAO->move($otherId, $mainId, $submittedCustomFields);
    // add the related tables and unset the ones that don't sport any of the duplicate contact's info

    CRM_Dedupe_Merger::moveContactBelongings($mergeHandler, $moveTables, $tableOperations);
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
    $cFields = self::getCustomFieldMetadata($contactType);

    if (!isset($submitted)) {
      $submitted = [];
    }
    foreach ($submitted as $key => $value) {
      list($cFields, $submitted) = self::processCustomFields($mainId, $key, $cFields, $submitted, $value);
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

    // dev/core#996 Ensure that the earliest created date is stored against the kept contact id
    $mainCreatedDate = civicrm_api3('Contact', 'getsingle', [
      'id' => $mainId,
      'return' => ['created_date'],
    ])['created_date'];
    $otherCreatedDate = civicrm_api3('Contact', 'getsingle', [
      'id' => $otherId,
      'return' => ['created_date'],
    ])['created_date'];
    if ($otherCreatedDate < $mainCreatedDate && !empty($otherCreatedDate)) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET created_date = %1 WHERE id = %2", [
        1 => [$otherCreatedDate, 'String'],
        2 => [$mainId, 'Positive'],
      ]);
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
      self::createContact($mainId, $submitted);
    }
    $transaction->commit();
    CRM_Utils_Hook::post('merge', 'Contact', $mainId);
    self::createMergeActivities($mainId, $otherId);

    return TRUE;
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
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
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
   * @param int $isForceNewSearch
   *   Should a new search be forced, bypassing any cache retrieval.
   *
   * @return array
   *   Array of matches meeting the criteria.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function getDuplicatePairs($rule_group_id, $group_id, $reloadCacheIfEmpty, $batchLimit, $isSelected, $includeConflicts = TRUE, $criteria = [], $checkPermissions = TRUE, $searchLimit = 0, $isForceNewSearch = 0) {
    $dupePairs = $isForceNewSearch ? [] : self::getCachedDuplicateMatches($rule_group_id, $group_id, $batchLimit, $isSelected, $includeConflicts, $criteria, $checkPermissions, $searchLimit);
    if (empty($dupePairs) && $reloadCacheIfEmpty) {
      // If we haven't found any dupes, probably cache is empty.
      // Try filling cache and give another try. We don't need to specify include conflicts here are there will not be any
      // until we have done some processing.
      CRM_Core_BAO_PrevNextCache::refillCache($rule_group_id, $group_id, $criteria, $checkPermissions, $searchLimit);
      return self::getCachedDuplicateMatches($rule_group_id, $group_id, $batchLimit, $isSelected, FALSE, $criteria, $checkPermissions, $searchLimit);
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
   * @param bool $checkPermissions
   *   Respect the users permissions.
   * @param int $searchLimit
   *   Number of contacts to seek dupes for (we need this because if
   *   we change it the results won't be refreshed otherwise. Changing the limit
   *   from 100 to 1000 SHOULD result in a new dedupe search).
   *
   * @return string
   */
  public static function getMergeCacheKeyString($rule_group_id, $group_id, $criteria, $checkPermissions, $searchLimit) {
    $contactType = CRM_Dedupe_BAO_RuleGroup::getContactTypeForRuleGroup($rule_group_id);
    $cacheKeyString = "merge_{$contactType}";
    $cacheKeyString .= $rule_group_id ? "_{$rule_group_id}" : '_0';
    $cacheKeyString .= $group_id ? "_{$group_id}" : '_0';
    $cacheKeyString .= '_' . (int) $searchLimit;
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
   * @param \CRM_Dedupe_MergeHandler $mergeHandler
   */
  public static function mergeLocations($mergeHandler) {
    $locBlocks = $mergeHandler->getLocationBlocksToMerge();
    $blocksDAO = [];
    $migrationInfo = $mergeHandler->getMigrationInfo();

    // @todo Handle OpenID (not currently in API).
    if (!empty($locBlocks)) {
      $locationBlocks = self::getLocationBlockInfo();

      $primaryBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($mergeHandler->getToKeepID(), ['is_primary' => 1]);
      $billingBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($mergeHandler->getToKeepID(), ['is_billing' => 1]);

      foreach ($locBlocks as $name => $block) {
        $blocksDAO[$name] = ['delete' => [], 'update' => []];
        $daoName = 'CRM_Core_DAO_' . $locationBlocks[$name]['label'];
        $changePrimary = FALSE;
        $primaryDAOId = (array_key_exists($name, $primaryBlockIds)) ? array_pop($primaryBlockIds[$name]) : NULL;
        $billingDAOId = (array_key_exists($name, $billingBlockIds)) ? array_pop($billingBlockIds[$name]) : NULL;

        foreach ($block as $blkCount => $values) {
          $otherBlockId = $migrationInfo['other_details']['location_blocks'][$name][$blkCount]['id'] ?? NULL;
          $mainBlockId = CRM_Utils_Array::value('mainContactBlockId', $migrationInfo['location_blocks'][$name][$blkCount], 0);
          if (!$otherBlockId) {
            continue;
          }
          $otherBlockDAO = $mergeHandler->copyDataToNewBlockDAO($otherBlockId, $name, $blkCount);

          // If we're deliberately setting this as primary then add the flag
          // and remove it from the current primary location (if there is one).
          // But only once for each entity.
          $set_primary = $migrationInfo['location_blocks'][$name][$blkCount]['set_other_primary'] ?? NULL;
          if (!$changePrimary && $set_primary == "1") {
            $otherBlockDAO->is_primary = 1;
            if ($primaryDAOId) {
              $removePrimaryDAO = $mergeHandler->getDAOForLocationEntity($name);
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

          // overwrite - need to delete block which belongs to main-contact.
          if (!empty($mainBlockId) && $values['is_replace']) {
            $deleteDAO = $mergeHandler->getDAOForLocationEntity($name);
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
        $blocksDAO[$name]['update'] += $mergeHandler->getBlocksToUpdateForDeletedContact($name);
      }
    }

    CRM_Utils_Hook::alterLocationMergeData($blocksDAO, $mergeHandler->getToKeepID(), $mergeHandler->getToRemoveID(), $migrationInfo);
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
   * @param int $mainId Id of contact to keep.
   * @param int $otherId Id of contact to delete.
   * @param string $mode
   * @param bool $checkPermissions
   * @param string $cacheKeyString
   *
   * @return bool|array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception_ResourceConflictException
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected static function dedupePair(int $mainId, int $otherId, $mode = 'safe', $checkPermissions = TRUE, $cacheKeyString = NULL) {
    $resultStats = [];

    $migrationInfo = [];
    $conflicts = [];
    // Try to lock the contacts before we load the data as we don't want it changing under us.
    // https://lab.civicrm.org/dev/core/issues/1355
    $locks = self::getLocksOnContacts([$mainId, $otherId]);
    if (!CRM_Dedupe_Merger::skipMerge($mainId, $otherId, $migrationInfo, $mode, $conflicts)) {
      CRM_Dedupe_Merger::moveAllBelongings($mainId, $otherId, $migrationInfo, $checkPermissions);
      $resultStats['merged'][] = [
        'main_id' => $mainId,
        'other_id' => $otherId,
      ];
    }
    else {
      $resultStats['skipped'][] = [
        'main_id' => $mainId,
        'other_id' => $otherId,
      ];
    }

    // store any conflicts
    if (!empty($conflicts)) {
      CRM_Core_BAO_PrevNextCache::markConflict($mainId, $otherId, $cacheKeyString, $conflicts, $mode);
    }
    else {
      self::deletePairFromPrevNextCache((int) $mainId, (int) $otherId);
    }
    self::releaseLocks($locks);
    return $resultStats;
  }

  /**
   * Delete merged pair from the previous next cache table as the are no longer a merge candidate.
   *
   * It's possible there may be more than one set of merge results cached, with different cache keys.
   * Once we have merged a pair these should all go (even from a different merge search) as they
   * can only be merged once.
   *
   * @param int $contactID1
   * @param int $contactID2
   */
  protected static function deletePairFromPrevNextCache(int $contactID1, int $contactID2) {
    CRM_Core_DAO::executeQuery("
      DELETE FROM civicrm_prevnext_cache
      WHERE  entity_table = 'civicrm_contact'
        AND (entity_id1 = %1 AND entity_id2 = %2) OR (entity_id1 = %2 AND entity_id2 = %1)",
      [1 => [$contactID1, 'Integer'], 2 => [$contactID2, 'Integer']]
    );
  }

  /**
   * Replace the pseudo QFKey with zero if it is present.
   *
   * @todo - on the slim chance this is still relevant it should be moved to the form layer.
   *
   * Details about this bug are somewhat obscured by the move from svn but perhaps JIRA
   * can still help.
   *
   * @param array $migrationInfo
   */
  protected static function swapOutFieldsAffectedByQFZeroBug(&$migrationInfo) {
    $qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';
    foreach ($migrationInfo as $key => &$value) {
      if ($value === $qfZeroBug) {
        $value = '0';
      }
    }
  }

  /**
   * Honestly - what DOES this do - hopefully some refactoring will reveal it's purpose.
   *
   * Update this function formats fields in preparation for them to be submitted to the
   * 'ProfileContactCreate action. This is a lot of code to do this & for
   * - for some fields it fails - e.g Country - per testMergeCustomFields.
   *
   * Goal is to move all custom field handling into 'move' functions on the various BAO
   * with an underlying DAO function. For custom fields it has been started on the BAO.
   *
   * @param int $mainId
   * @param string $key
   * @param array $cFields
   * @param array $submitted
   * @param mixed $value
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function processCustomFields($mainId, $key, $cFields, $submitted, $value) {
    if (substr($key, 0, 7) === 'custom_') {
      $fid = (int) substr($key, 7);
      if (empty($cFields[$fid])) {
        return [$cFields, $submitted];
      }
      $htmlType = $cFields[$fid]['attributes']['html_type'];
      $isSerialized = CRM_Core_BAO_CustomField::isSerialized($cFields[$fid]['attributes']);

      if ($htmlType === 'File') {
        // Handled in CustomField->move(). Tested in testMergeCustomFields.
        unset($submitted["custom_$fid"]);
      }
      elseif (!$isSerialized && ($htmlType === 'Select Country' || $htmlType === 'Select State/Province')) {
        // @todo Test in testMergeCustomFields disabled as this does not work, Handle in CustomField->move().
        $submitted[$key] = CRM_Core_BAO_CustomField::displayValue($value, $fid);
      }
      elseif ($htmlType === 'Select Date') {
        if ($cFields[$fid]['attributes']['is_view']) {
          $submitted[$key] = date('YmdHis', strtotime($submitted[$key]));
        }
      }
      elseif ($isSerialized) {
        // Merge values from both contacts for multivalue fields, CRM-4385
        // get the existing custom values from db.
        $customParams = ['entityID' => $mainId, $key => TRUE];
        $customfieldValues = CRM_Core_BAO_CustomValueTable::getValues($customParams);
        if (!empty($customfieldValues[$key])) {
          $existingValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $customfieldValues[$key]);
          if (is_array($existingValue) && !empty($existingValue)) {
            $mergeValue = $submittedCustomFields = [];
            if ($value === 'null') {
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
              if (in_array($htmlType, ['CheckBox', 'Select'])) {
                $submitted[$key] = CRM_Utils_Array::implodePadded($mergeValue);
              }
              else {
                $submitted[$key] = $mergeValue;
              }
            }
          }
        }
        elseif (in_array($htmlType, ['Select Country', 'Select State/Province'])) {
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
      }
    }
    return [$cFields, $submitted];
  }

  /**
   * Get metadata for the custom fields for the merge.
   *
   * @param string $contactType
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function getCustomFieldMetadata($contactType) {
    $treeCache = [];
    if (!array_key_exists($contactType, $treeCache)) {
      $treeCache[$contactType] = CRM_Core_BAO_CustomGroup::getTree(
        $contactType,
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
    foreach ($treeCache[$contactType] as $key => $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      foreach ($group['fields'] as $fid => $field) {
        $cFields[$fid]['attributes'] = $field;
      }
    }
    return $cFields;
  }

  /**
   * Get conflicts for proposed merge pair.
   *
   * @param array $migrationInfo
   *   This is primarily to inform hooks. The can also modify it which feels
   *   pretty fragile to do it here - but it is historical.
   * @param int $mainId
   *   Main contact with whom merge has to happen.
   * @param int $otherId
   *   Duplicate contact which would be deleted after merge operation.
   * @param string $mode
   *   Helps decide how to behave when there are conflicts.
   *   -  A 'safe' value skips the merge if there are any un-resolved conflicts.
   *   -  Does a force merge otherwise (aggressive mode).
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function getConflicts(&$migrationInfo, $mainId, $otherId, $mode) {
    $conflicts = [];
    // Generate var $migrationInfo. The variable structure is exactly same as
    // $formValues submitted during a UI merge for a pair of contacts.
    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($mainId, $otherId, FALSE);
    // add additional details that we might need to resolve conflicts
    $migrationInfo = $rowsElementsAndInfo['migration_info'];
    $migrationInfo['main_details'] = &$rowsElementsAndInfo['main_details'];
    $migrationInfo['other_details'] = &$rowsElementsAndInfo['other_details'];
    $migrationInfo['rows'] = &$rowsElementsAndInfo['rows'];
    // go ahead with merge if there is no conflict
    $originalMigrationInfo = $migrationInfo;
    foreach ($migrationInfo as $key => $val) {
      if ($val === "null") {
        // Rule: Never overwrite with an empty value (in any mode)
        unset($migrationInfo[$key]);
        continue;
      }
      elseif ((in_array(substr($key, 5), CRM_Dedupe_Merger::getContactFields()) or
          substr($key, 0, 12) === 'move_custom_'
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
            || ($migrationInfo['rows'][$key]['main'] === '0' && substr($key, 0, 12) === 'move_custom_')
          )
          && $migrationInfo['rows'][$key]['main'] != $migrationInfo['rows'][$key]['other']
        ) {

          // note it down & lets wait for response from the hook.
          // For no response $mode will decide if to skip this merge
          $conflicts[$key] = NULL;
        }
      }
      elseif (substr($key, 0, 14) === 'move_location_' and $val != NULL) {
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
          $addressRecordLocTypeId = $addressRecord['location_type_id'] ?? NULL;

          // If it exists on the 'main' contact already, skip it. Otherwise
          // if the location type exists already, log a conflict.
          foreach ($migrationInfo['main_details']['location_blocks'][$fieldName] as $mainAddressKey => $mainAddressRecord) {
            if (!self::locationHasData($mainAddressRecord)) {
              // Go ahead & overwrite the main address - it has no data in it.
              // if it is the primary address then pass that honour to the address that actually has data.
              $migrationInfo['location_blocks'][$fieldName][$mainAddressKey]['set_other_primary'] = $mainAddressRecord['is_primary'];
              continue;
            }
            if (self::locationIsSame($addressRecord, $mainAddressRecord)) {
              unset($migrationInfo[$key]);
              continue;
            }
            if ($addressRecordLocTypeId == $mainAddressRecord['location_type_id']) {
              $conflicts[$key] = NULL;
              continue;
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
    foreach ($conflicts as $key => $val) {
      // Copy over the resolved values. If we are in aggressive mode we update to null
      // so as not to copy over. Why it's different to safe mode is a bit murky.
      // Working theory is it doesn't matter what we do in safe mode here if $val is NULL.
      // as the merge is not gonna happen if $val == NULL
      $migrationInfo[$key] = $val ?? ($mode === 'safe' ? $migrationInfo[$key] : NULL);
    }
    return self::formatConflictArray($conflicts, $migrationInfo['rows'], $migrationInfo['main_details']['location_blocks'], $migrationInfo['other_details']['location_blocks'], $mainId, $otherId, $mode);
  }

  /**
   * @param array $conflicts
   * @param array $migrationInfo
   * @param $toKeepContactLocationBlocks
   * @param $toRemoveContactLocationBlocks
   * @param $toKeepID
   * @param $toRemoveID
   * @param string $mode
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  protected static function formatConflictArray($conflicts, $migrationInfo, $toKeepContactLocationBlocks, $toRemoveContactLocationBlocks, $toKeepID, $toRemoveID, $mode) {
    $return = [];
    $resolved = [];
    foreach ($conflicts as $key => $val) {
      if ($val !== NULL) {
        // copy over the resolved values
        $resolved[$key] = $val;
        unset($conflicts[$key]);
      }
      elseif ($mode === 'aggressive') {
        unset($conflicts[$key]);
        if (strpos($key, 'move_location_') !== 0) {
          // @todo - just handling plain contact fields for now because I think I need a bigger refactor
          // of the below to handle locations & will do as a follow up.
          $resolved['contact'][substr($key, 5)] = $migrationInfo[$key]['main'];
        }
      }
    }
    foreach (array_keys($conflicts) as $index) {
      if (substr($index, 0, 14) === 'move_location_') {
        $parts = explode('_', $index);
        $entity = $parts[2];
        $blockIndex = $parts[3];
        $locationTypeID = $toKeepContactLocationBlocks[$entity][$blockIndex]['location_type_id'];
        $entityConflicts = [
          'location_type_id' => $locationTypeID,
          'title' => $migrationInfo[$index]['title'],
        ];
        foreach ($toKeepContactLocationBlocks[$entity][$blockIndex] as $fieldName => $fieldValue) {
          if (in_array($fieldName, self::ignoredFields())) {
            continue;
          }
          $toRemoveValue = $toRemoveContactLocationBlocks[$entity][$blockIndex][$fieldName] ?? NULL;
          if ($fieldValue !== $toRemoveValue) {
            $entityConflicts[$fieldName] = [
              $toKeepID => $fieldValue,
              $toRemoveID => $toRemoveValue,
            ];
          }
        }
        $return[$entity][] = $entityConflicts;
      }
      elseif (substr($index, 0, 5) === 'move_') {
        $contactFieldsToCompare[] = str_replace('move_', '', $index);
        $return['contact'][str_replace('move_', '', $index)] = [
          'title' => $migrationInfo[$index]['title'],
          $toKeepID => $migrationInfo[$index]['main'],
          $toRemoveID => $migrationInfo[$index]['other'],
        ];
      }
      else {
        // Can't think of why this would be the case but perhaps it's ensuring it isn't as we
        // refactor this.
        throw new CRM_Core_Exception(ts('Unknown parameter') . $index);
      }
    }
    return ['conflicts' => $return, 'resolved' => $resolved];
  }

  /**
   * Get any duplicate merge pairs that have been previously cached.
   *
   * @param int $rule_group_id
   * @param int $group_id
   * @param int $batchLimit
   * @param bool $isSelected
   * @param bool $includeConflicts
   * @param array $criteria
   * @param int $checkPermissions
   * @param int $searchLimit
   *
   * @return array
   */
  protected static function getCachedDuplicateMatches($rule_group_id, $group_id, $batchLimit, $isSelected, $includeConflicts, $criteria, $checkPermissions, $searchLimit = 0) {
    return CRM_Core_BAO_PrevNextCache::retrieve(
      self::getMergeCacheKeyString($rule_group_id, $group_id, $criteria, $checkPermissions, $searchLimit),
      self::getJoinOnDedupeTable(),
      self::getWhereString($isSelected),
      0, $batchLimit,
      [], '',
      $includeConflicts
    );
  }

  /**
   * @return array
   */
  protected static function ignoredFields(): array {
    $keysToIgnore = [
      'id',
      'is_primary',
      'is_billing',
      'manual_geo_code',
      'contact_id',
      'reset_date',
      'hold_date',
    ];
    return $keysToIgnore;
  }

  /**
   * Get the field value & label for the given field.
   *
   * @param $field
   * @param $contact
   *
   * @return array
   * @throws \Exception
   */
  private static function getFieldValueAndLabel($field, $contact): array {
    $fields = self::getMergeFieldsMetadata();
    $value = $label = $contact[$field] ?? NULL;
    $fieldSpec = $fields[$field];
    if (!empty($fieldSpec['serialize']) && is_array($value)) {
      // In practice this only applies to preferred_communication_method as the sub types are skipped above
      // and no others are serialized.
      $labels = [];
      foreach ($value as $individualValue) {
        $labels[] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', $field, $individualValue);
      }
      $label = implode(', ', $labels);
      // We serialize this due to historic handling but it's likely that if we just left it as an
      // array all would be well & we would have less code.
      $value = CRM_Core_DAO::serializeField($value, $fieldSpec['serialize']);
    }
    elseif (!empty($fieldSpec['type']) && $fieldSpec['type'] == CRM_Utils_Type::T_DATE) {
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
    elseif (!empty($fieldSpec['pseudoconstant'])) {
      $label = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', $field, $value);
    }
    elseif ($field == 'current_employer_id' && !empty($value)) {
      $label = "$value (" . CRM_Contact_BAO_Contact::displayName($value) . ")";
    }
    return ['label' => $label, 'value' => $value];
  }

  /**
   * Build up the location block for the contact in dedupe-screen display format.
   *
   * @param integer $cid
   * @param array $blockInfo
   * @param string $blockName
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  private static function buildLocationBlockForContact($cid, $blockInfo, $blockName): array {
    $searchParams = [
      'contact_id' => $cid,
      // CRM-17556 Order by field-specific criteria
      'options' => [
        'sort' => $blockInfo['sortString'],
      ],
    ];
    $locationBlock = [];
    $values = civicrm_api3($blockName, 'get', $searchParams);
    if ($values['count']) {
      $cnt = 0;
      foreach ($values['values'] as $value) {
        $locationBlock[$cnt] = $value;
        // Fix address display
        if ($blockName == 'address') {
          // For performance avoid geocoding while merging https://issues.civicrm.org/jira/browse/CRM-21786
          // we can expect existing geocode values to be retained.
          $value['skip_geocode'] = TRUE;
          CRM_Core_BAO_Address::fixAddress($value);
          unset($value['skip_geocode']);
          $locationBlock[$cnt]['display'] = CRM_Utils_Address::format($value);
        }
        // Fix email display
        elseif ($blockName == 'email') {
          $locationBlock[$cnt]['display'] = CRM_Utils_Mail::format($value);
        }

        $cnt++;
      }
    }
    return $locationBlock;
  }

  /**
   * Get a lock on the given contact.
   *
   * The lock is like a gentleman's agreement between php & mysql. It is reserved at the
   * mysql level so it works across php processes but it doesn't actually lock the database.
   *
   * Instead php can check the lock to see if it has been acquired before taking an action.
   *
   * In this case we really don't want to attempt to dedupe contacts if another process is
   * trying to act on the specific contact as it could result in messy deadlocks & possibly data corruption.
   * In most databases this would be a rare event but if multiple dedupe processes are running
   * at once (for example) or there is also an import process in play there is potential for them to crash.
   * By throwing a specific error the calling process can catch it and determine it is worth trying again later without a lot of
   * noise.
   *
   * As of writing no other processes DO grab contact locks but it would be reasonable to consider
   * grabbing them doing contact edits in general as well as imports etc.
   *
   * @param array $contacts
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception_ResourceConflictException
   */
  protected static function getLocksOnContacts($contacts):array {
    $locks = [];
    if (!CRM_Utils_SQL::supportsMultipleLocks()) {
      return $locks;
    }
    foreach ($contacts as $contactID) {
      $lock = Civi::lockManager()->acquire("data.core.contact.{$contactID}");
      if ($lock->isAcquired()) {
        $locks[] = $lock;
      }
      else {
        self::releaseLocks($locks);
        throw new CRM_Core_Exception_ResourceConflictException(ts('Contact is in Use'), 'contact_lock');
      }
    }
    return $locks;
  }

  /**
   * Release contact locks so another process can alter them if it wants.
   *
   * @param array $locks
   */
  protected static function releaseLocks(array $locks) {
    foreach ($locks as $lock) {
      /* @var Civi\Core\Lock\LockInterface $lock */
      $lock->release();
    }
  }

  /**
   * @param $mainId
   * @param $otherId
   * @param $blockInfo
   * @param $blockName
   * @param array $locations
   * @param array $rows
   * @param array $elements
   * @param array $migrationInfo
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected static function addLocationFieldInfo($mainId, $otherId, $blockInfo, $blockName, array $locations, array $rows, array $elements, array $migrationInfo): array {
    // Collect existing fields from both 'main' and 'other' contacts first
    // This allows us to match up location/types when building the table rows
    $locations['main'][$blockName] = self::buildLocationBlockForContact($mainId, $blockInfo, $blockName);
    $locations['other'][$blockName] = self::buildLocationBlockForContact($otherId, $blockInfo, $blockName);

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
          $lookupType = $value[$blockInfo['hasType']] ?? NULL;
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
        // eg websites: Facebook...

        if ($blockInfo['hasType']) {

          // Load the type options for this entity
          $typeOptions = civicrm_api3($blockName, 'getoptions', ['field' => $blockInfo['hasType']]);

          $thisTypeId = $value[$blockInfo['hasType']] ?? NULL;

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

    }
    return [$locations, $rows, $elements, $migrationInfo];
  }

}
