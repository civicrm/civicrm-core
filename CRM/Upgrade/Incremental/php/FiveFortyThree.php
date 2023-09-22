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
 * Upgrade logic for FiveFortyThree
 */
class CRM_Upgrade_Incremental_php_FiveFortyThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.43.alpha1' && CRM_Core_Component::isEnabled('CiviCase')) {
      $preUpgradeMessage .= '<p>' . ts('Minor changes have been made to how the tokens to render case.is_deleted, case.created_date and case.modified_date. See https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/ for more') . '</p>';
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_43_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Fix DB Collation if needed on the relationship cache table', 'fixRelationshipCacheTableCollation');
    $this->addTask('Make mapping field foreign key cascade delete', 'alterMappingFK');
    $this->addTask('Replace legacy displayName smarty token in Online contribution workflow template',
      'updateMessageToken', 'contribution_online_receipt', '$displayName', 'contact.display_name', $rev
    );
    $this->addTask('Replace legacy first_name smarty token in Online contribution workflow template',
      'updateMessageToken', 'contribution_online_receipt', '$first_name', 'contact.first_name', $rev
    );
    $this->addTask('Replace legacy last_name smarty token in Online contribution workflow template',
      'updateMessageToken', 'contribution_online_receipt', '$last_name', 'contact.last_name', $rev
    );
    $this->addTask('Replace membership status token in action schedule',
      'updateActionScheduleToken', 'membership.status', 'membership.status_id:label', $rev
    );
    $this->addTask('Replace membership type token in action schedule',
      'updateActionScheduleToken', 'membership.type', 'membership.membership_type_id:label', $rev
    );
    $this->addTask('Replace event type token in action schedule',
      'updateActionScheduleToken', 'event.event_type_id', 'event.event_type_id:label', $rev
    );
    $this->addTask('Replace event balance in action schedule',
      'updateActionScheduleToken', 'event.balance', 'participant.balance', $rev
    );
    $this->addTask('Replace event fee amount in action schedule',
      'updateActionScheduleToken', 'event.fee_amount', 'participant.fee_amount', $rev
    );
    $this->addTask('Replace contact.preferred_communication_method in action schedule',
      'updateActionScheduleToken', 'contact.preferred_communication_method', 'contact.preferred_communication_method:label', $rev
    );
    $this->addTask('Replace event event_id in action schedule',
      'updateActionScheduleToken', 'event.event_id', 'event.id', $rev
    );
    $this->addTask('Replace duplicate event title token in event badges',
      'updatePrintLabelToken', 'participant.event_title', 'event.title', $rev
    );
    $this->addTask('Replace duplicate event start date token in event badges',
      'updatePrintLabelToken', 'participant.event_start_date', 'event.start_date', $rev
    );
    $this->addTask('Replace duplicate event end date token in event badges',
      'updatePrintLabelToken', 'participant.event_end_date', 'event.end_date', $rev
    );
    $this->addTask('Update participant status id token in event badges',
      'updatePrintLabelToken', 'participant.participant_status_id', 'participant.status_id', $rev
    );
    $this->addTask('Update participant role id token in event badges',
      'updatePrintLabelToken', 'participant.participant_role_id', 'participant.role_id', $rev
    );
    $this->addTask('Update participant role label token in event badges',
      'updatePrintLabelToken', 'participant.participant_role', 'participant.role_id:label', $rev
    );
    $this->addTask('Update participant register date token in event badges',
      'updatePrintLabelToken', 'participant.participant_register_date', 'participant.register_date', $rev
    );
    $this->addTask('Update participant source token in event badges',
      'updatePrintLabelToken', 'participant.participant_source', 'participant.source', $rev
    );
    $this->addTask('Update participant fee level token in event badges',
      'updatePrintLabelToken', 'participant.participant_fee_level', 'participant.fee_level', $rev
    );
    $this->addTask('Update participant fee amount token in event badges',
      'updatePrintLabelToken', 'participant.participant_fee_amount', 'participant.fee_amount', $rev
    );
    $this->addTask('Update participant registered by id token in event badges',
      'updatePrintLabelToken', 'participant.participant_registered_by_id', 'participant.registered_by_id', $rev
    );
    $this->addTask('Update preferred_communication_method token in saved message templates',
      'updateMessageToken', '', 'contact.preferred_communication_method', 'contact.preferred_communication_method:label', $rev
    );
    $this->addTask('Update contribution status token in saved message templates',
      'updateMessageToken', '', 'contribution.contribution_status', 'contribution.contribution_status_id:label', $rev
    );
    $this->addTask('Update campaign token in saved message templates',
      'updateMessageToken', '', 'contribution.campaign', 'contribution.campaign_id:label', $rev);
    $this->addTask('Update start date token in event badges',
      'updatePrintLabelToken', 'event.start_date', 'event.start_date|crmDate:"%B %E%f', $rev
    );
    $this->addTask('Update end date token in event badges',
      'updatePrintLabelToken', 'event.end_date', 'event.end_date|crmDate:"%B %E%f', $rev
    );
    $this->addTask('Update event id token in event badges',
      'updatePrintLabelToken', 'event.event_id', 'participant.event_id', $rev
    );
    //  prefix_id was a short lived variant around civicrm 4.4 - it's unlikely any
    // cases exist, but they will resolve as an integer if not updated
    $this->addTask('Update legacy prefix id token in greetings (use {contact.prefix_id:label})',
      'updateGreetingOptions', 'contact.prefix_id', 'contact.prefix_id:label', $rev
    );
    //  suffix_id was a short lived variant around civicrm 4.4 - it's unlikely any
    //  cases exist, but they will resolve as an integer if not updated
    $this->addTask('Update legacy suffix id token in greetings  (use {contact.suffix_id:label})',
      'updateGreetingOptions', 'contact.suffix_id', 'contact.suffix_id:label', $rev
    );
    //  individual_prefix still works but is now deprecated
    $this->addTask('Update individual_prefix token in greetings (use {contact.prefix_id:label})',
      'updateGreetingOptions', 'contact.individual_prefix', 'contact.prefix_id:label', $rev
    );
    //  individual_prefix still works but is now deprecated
    $this->addTask('Update individual_suffix token in greetings (use {contact.suffix_id:label})',
      'updateGreetingOptions', 'contact.individual_suffix', 'contact.suffix_id:label', $rev
    );

    $this->addTask('Set note_date field to NOT NULL', 'setNoteDateFieldNotNull');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function alterMappingFK(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mapping_field', 'FK_civicrm_mapping_field_mapping_id');
    CRM_Core_DAO::executeQuery('
        ALTER TABLE civicrm_mapping_field
        ADD CONSTRAINT `FK_civicrm_mapping_field_mapping_id`
        FOREIGN KEY (`mapping_id`)
        REFERENCES `civicrm_mapping`(`id`) ON DELETE CASCADE
      ', [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  public static function fixRelationshipCacheTableCollation():bool {
    $contactTableCollation = CRM_Core_BAO_SchemaHandler::getInUseCollation();
    $dao = CRM_Core_DAO::executeQuery('SHOW TABLE STATUS LIKE \'civicrm_relationship_cache\'');
    $dao->fetch();
    $relationshipCacheCollation = $dao->Collation;
    $characterSet = 'utf8';
    if (stripos($contactTableCollation, 'utf8mb4') !== FALSE) {
      $characterSet = 'utf8mb4';
    }
    if ($contactTableCollation !== $relationshipCacheCollation) {
      CRM_Core_BAO_SchemaHandler::migrateUtf8mb4(($characterSet === 'utf8mb4' ? FALSE : TRUE), ['%civicrm_relationship_cache%']);
    }
    return TRUE;
  }

  /**
   * Ensure note_date is NOT NULL
   *
   * Since note_date is exposed in the UI, we want to ensure an empty value
   * results in CURRENT_TIME, not a NULL date.
   */
  public static function setNoteDateFieldNotNull(): bool {
    $i18nRewrite = FALSE;
    $query = "UPDATE civicrm_note SET note_date = created_date WHERE note_date IS NULL";
    CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, $i18nRewrite);
    $query = "ALTER TABLE civicrm_note CHANGE COLUMN `note_date` `note_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Date attached to the note'";
    CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, $i18nRewrite);
    return TRUE;
  }

}
