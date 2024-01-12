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
 * Upgrade logic for the 5.66.x series.
 *
 * Each minor version in the series is handled by either a `5.66.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_66_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtySix extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.66.alpha1') {
      $preUpgradeMessage .= '<p>' . ts('If your site uses custom code to inject tracking fields into messages, it may need updating. See <a %1>this issue for details</a>.',
          [1 => 'href="https://github.com/civicrm/civicrm-core/pull/27233" target="_blank"']) . '</p>';
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_66_alpha1($rev): void {
    // Increase column length before the upgrade sql writes to it
    $this->addTask('Increase ActionSchedule.name length', 'alterColumn', 'civicrm_action_schedule', 'name', "varchar(128) COMMENT 'Name of the scheduled action'");
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    // These run after the sql file
    $this->addTask('Make Contribution.tax_amount required', 'alterColumn', 'civicrm_contribution', 'tax_amount', "decimal(20,2) DEFAULT 0 NOT NULL COMMENT 'Total tax amount of this contribution.'");
    $this->addTask('Make LineItem.tax_amount required', 'alterColumn', 'civicrm_line_item', 'tax_amount', "decimal(20,2) DEFAULT 0 NOT NULL COMMENT 'tax of each item'");
    $this->addTask(ts('Create index %1', [1 => 'civicrm_action_schedule.UI_name']), 'addIndex', 'civicrm_action_schedule', 'name', 'UI');
    $this->addTask('Add fields to civicrm_mail_settings to allow more flexibility for email to activity', 'addMailSettingsFields');
    $this->addTask('Move serialized contents of civicrm_survey.recontact_interval into civicrm_option_value.filter', 'migrateRecontactInterval');
    $this->addTask('Drop column civicrm_survey.recontact_interval', 'dropColumn', 'civicrm_survey', 'recontact_interval');
    $this->addTask('Update afform tab names', 'updateAfformTabs');
    $this->addTask('Add in Client Removed Activity Type', 'addCaseClientRemovedActivity');
    $this->addTask('Update quicksearch options to v4 format', 'updateQuicksearchOptions');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_66_beta1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      // Note these are localizable so need the last param
      $this->addTask('civicrm_location_type.display_name default', 'alterColumn', 'civicrm_location_type', 'display_name', "varchar(64) NOT NULL DEFAULT '' COMMENT 'Location Type Display Name.'", TRUE);
      $this->addTask('civicrm_survey.title default', 'alterColumn', 'civicrm_survey', 'title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Title of the Survey.'", TRUE);
      $this->addTask('civicrm_case_type.title default', 'alterColumn', 'civicrm_case_type', 'title', "varchar(64) NOT NULL DEFAULT '' COMMENT 'Natural language name for Case Type'", TRUE);
      $this->addTask('civicrm_custom_group.title default', 'alterColumn', 'civicrm_custom_group', 'title', "varchar(64) NOT NULL DEFAULT '' COMMENT 'Friendly Name.'", TRUE);
      $this->addTask('civicrm_custom_field.label default', 'alterColumn', 'civicrm_custom_field', 'label', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Text for form field label (also friendly name for administering this custom property).'", TRUE);
      $this->addTask('civicrm_option_value.label default', 'alterColumn', 'civicrm_option_value', 'label', "varchar(512) NOT NULL DEFAULT '' COMMENT 'Option string as displayed to users - e.g. the label in an HTML OPTION tag.'", TRUE);
      $this->addTask('civicrm_group.title default', 'alterColumn', 'civicrm_group', 'title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of Group.'", TRUE);
      $this->addTask('civicrm_group.frontend_title default', 'alterColumn', 'civicrm_group', 'frontend_title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Alternative public title for this Group.'", TRUE);
      $this->addTask('civicrm_contribution_page.title default', 'alterColumn', 'civicrm_contribution_page', 'title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Contribution Page title. For top of page display'", TRUE);
      $this->addTask('civicrm_contribution_page.frontend_title default', 'alterColumn', 'civicrm_contribution_page', 'frontend_title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Contribution Page Public title'", TRUE);
      $this->addTask('civicrm_product.name default', 'alterColumn', 'civicrm_product', 'name', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Required product/premium name'", TRUE);
      $this->addTask('civicrm_payment_processor.title default', 'alterColumn', 'civicrm_payment_processor', 'title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of processor when shown to CiviCRM administrators.'", TRUE);
      $this->addTask('civicrm_payment_processor.frontend_title default', 'alterColumn', 'civicrm_payment_processor', 'frontend_title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of processor when shown to users making a payment.'", TRUE);
      $this->addTask('civicrm_membership_type.name default', 'alterColumn', 'civicrm_membership_type', 'name', "varchar(128) NOT NULL DEFAULT '' COMMENT 'Name of Membership Type'", TRUE);
      $this->addTask('civicrm_price_set.title default', 'alterColumn', 'civicrm_price_set', 'title', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Displayed title for the Price Set.'", TRUE);
      $this->addTask('civicrm_uf_group.title default', 'alterColumn', 'civicrm_uf_group', 'title', "varchar(64) NOT NULL DEFAULT '' COMMENT 'Form title.'", TRUE);
      $this->addTask('civicrm_uf_field.label default', 'alterColumn', 'civicrm_uf_field', 'label', "varchar(255) NOT NULL DEFAULT '' COMMENT 'To save label for fields.'", TRUE);
      $this->addTask('civicrm_price_field.label default', 'alterColumn', 'civicrm_price_field', 'label', "varchar(255) NOT NULL DEFAULT '' COMMENT 'Text for form field label (also friendly name for administering this field).'", TRUE);
      // END localizable field updates
    }
  }

  /**
   * Add fields to civicrm_mail_settings to allow more flexibility for email to activity
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addMailSettingsFields(CRM_Queue_TaskContext $ctx) {
    $ctx->log->info('Adding field is_active');
    self::addColumn($ctx, 'civicrm_mail_settings', 'is_active', 'tinyint NOT NULL DEFAULT 1 COMMENT "Ignored for bounce processing, only for email-to-activity"');
    $ctx->log->info('Adding field activity_type_id');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_type_id', 'int unsigned COMMENT "Implicit FK to civicrm_option_value where option_group = activity_type"');
    $ctx->log->info('Adding field campaign_id');
    self::addColumn($ctx, 'civicrm_mail_settings', 'campaign_id', 'int unsigned DEFAULT NULL COMMENT "Foreign key to the Campaign."');
    $ctx->log->info('Adding field activity_source');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_source', 'varchar(4) COMMENT "Which email recipient to add as the activity source (from, to, cc, bcc)."');
    $ctx->log->info('Adding field activity_targets');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_targets', 'varchar(16) COMMENT "Which email recipients to add as the activity targets (from, to, cc, bcc)."');
    $ctx->log->info('Adding field activity_assignees');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_assignees', 'varchar(16) COMMENT "Which email recipients to add as the activity assignees (from, to, cc, bcc)."');

    $ctx->log->info('Adding FK_civicrm_mail_settings_campaign_id');
    if (!self::checkFKExists('civicrm_mail_settings', 'FK_civicrm_mail_settings_campaign_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_mail_settings`
        ADD CONSTRAINT `FK_civicrm_mail_settings_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `civicrm_campaign`(`id`)
        ON DELETE SET NULL;
      ");
    }

    $ctx->log->info('Setting default activity_source');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_source` = "from" WHERE `activity_source` IS NULL;');
    $ctx->log->info('Setting default activity_targets');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_targets` = "to,cc,bcc" WHERE `activity_targets` IS NULL;');
    $ctx->log->info('Setting default activity_assignees');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_assignees` = "from" WHERE `activity_assignees` IS NULL;');
    $ctx->log->info('Setting default activity_type_id');
    $inboundEmailActivity = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
    if ($inboundEmailActivity) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_type_id` = ' . $inboundEmailActivity . ' WHERE `activity_type_id` IS NULL;');
    }
    return TRUE;
  }

  /**
   * If the ContactLayout extension is installed, update its stored tab names to keep up
   * with core changes to Afform tabs.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/27196
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateAfformTabs(CRM_Queue_TaskContext $ctx) {
    $convert = function($id) {
      if ($id === 'afsearchGrants') {
        return 'grant';
      }
      if (preg_match('#^(afform|afsearch)#i', $id)) {
        return CRM_Utils_String::convertStringToSnakeCase(preg_replace('#^(afformtab|afsearchtab|afform|afsearch)#i', '', $id));
      }
      return $id;
    };

    $setting = \Civi::settings()->get('contactlayout_default_tabs');
    if ($setting && is_array($setting)) {
      foreach ($setting as $index => $tab) {
        $setting[$index]['id'] = $convert($tab['id']);
      }
      \Civi::settings()->set('contactlayout_default_tabs', $setting);
    }
    if (CRM_Core_DAO::checkTableExists('civicrm_contact_layout')) {
      // Can't use the api due to extension loading issues
      $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_contact_layout');
      while ($dao->fetch()) {
        if (!empty($dao->tabs)) {
          $tabs = CRM_Core_DAO::unSerializeField($dao->tabs, CRM_Core_DAO::SERIALIZE_JSON);
          foreach ($tabs as $index => $tab) {
            $tabs[$index]['id'] = $convert($tab['id']);
          }
          CRM_Core_DAO::executeQuery('UPDATE civicrm_contact_layout SET tabs = %1 WHERE id = %2', [
            1 => [CRM_Core_DAO::serializeField($tabs, CRM_Core_DAO::SERIALIZE_JSON), 'String'],
            2 => [$dao->id, 'Integer'],
          ]);
        }
      }
    }
    return TRUE;
  }

  public static function addCaseClientRemovedActivity() {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'activity_type',
      'name' => 'Case Client Removed',
      'label' => ts('Case Client was removed from Case'),
      'description' => ts('Case client was removed from a case'),
      'is_active' => TRUE,
      'component_id' => CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviCase'"),
      'icon' => 'fa-trash',
    ]);
    return TRUE;
  }

  /**
   * Move serialized contents of Survey.recontact_interval into OptionValue.filter
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function migrateRecontactInterval($ctx): bool {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_survey', 'recontact_interval')) {
      // Upgrade has already run, nothing to do.
      return TRUE;
    }
    $surveys = CRM_Core_DAO::executeQuery('SELECT result_id, recontact_interval FROM civicrm_survey')->fetchAll();
    foreach ($surveys as $survey) {
      if (empty($survey['recontact_interval']) || empty($survey['result_id'])) {
        continue;
      }
      foreach (unserialize($survey['recontact_interval']) as $label => $interval) {
        CRM_Core_DAO::executeQuery('UPDATE civicrm_option_value SET filter = %1 WHERE option_group_id = %2 AND label = %3', [
          1 => [$interval, 'Integer'],
          2 => [$survey['result_id'], 'Positive'],
          3 => [$label, 'String'],
        ]);
      }
    }
    return TRUE;
  }

  /**
   * Convert `quicksearch_options` setting to use new APIv4 field names
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function updateQuicksearchOptions($ctx): bool {
    $oldOpts = Civi::settings()->get('quicksearch_options');
    if ($oldOpts) {
      // Map old quicksearch options to new APIv4 format
      $map = [
        'sort_name' => 'sort_name',
        'contact_id' => 'id',
        'external_identifier' => 'external_identifier',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email_primary.email',
        'phone_numeric' => 'phone_primary.phone_numeric',
        'street_address' => 'address_primary.street_address',
        'city' => 'address_primary.city',
        'postal_code' => 'address_primary.postal_code',
        'job_title' => 'job_title',
      ];

      $newOpts = [];
      foreach ($oldOpts as $oldOpt) {
        // Convert custom fields
        if (str_starts_with($oldOpt, 'custom_')) {
          $fieldName = substr($oldOpt, 7);
          try {
            $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldName, 'option_group_id', 'name');
            $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldName, 'custom_group_id', 'name');
            $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'name');
            $newOpts[] = $customGroupName . '.' . $fieldName . ($optionGroupId ? ':label' : '');
          }
          catch (CRM_Core_Exception $e) {
            // Field not found or something... just drop it
          }
        }
        // Core fields. In case the upgrade has run already, check new and old options
        elseif (in_array($oldOpt, $map) || array_key_exists($oldOpt, $map)) {
          $newOpts[] = $map[$oldOpt] ?? $oldOpt;
        }
      }
      Civi::settings()->set('quicksearch_options', $newOpts);
    }
    else {
      Civi::settings()->revert('quicksearch_options');
    }
    return TRUE;
  }

}
