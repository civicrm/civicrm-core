<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for 4.7
 */
class CRM_Upgrade_Incremental_php_FourSeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '4.7.alpha1') {
      // CRM-16478 Remove custom fatal error template path option
      $config = CRM_Core_Config::singleton();
      if (!empty($config->fatalErrorTemplate) && $config->fatalErrorTemplate != 'CRM/common/fatal.tpl') {
        $preUpgradeMessage .= '<p>' . ts('The custom fatal error template setting will be removed during the upgrade. You are currently using this custom template: %1 . Following the upgrade you will need to use the standard approach to overriding template files, as described in the documentation.', array(1 => $config->fatalErrorTemplate)) . '</p>';
      }
    }
    if ($rev == '4.7.alpha4') {
      // CRM-17004 Warn of Moneris removal
      $count = 1;
      // Query only works in 4.3+
      if (version_compare($currentVer, "4.3.0") > 0) {
        $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(id) FROM civicrm_payment_processor WHERE payment_processor_type_id IN (SELECT id FROM civicrm_payment_processor_type WHERE name = 'Moneris')");
      }
      if ($count && !function_exists('moneris_civicrm_managed')) {
        $preUpgradeMessage .= '<p>' . ts('The %1 payment processor is no longer bundled with CiviCRM. After upgrading you will need to install the extension to continue using it.', array(1 => 'Moneris')) . '</p>';
      }
    }
    if ($rev == '4.7.13') {
      $preUpgradeMessage .= '<p>' . ts('A new permission has been added called %1 This Permission is now used to control access to the Manage Tags screen', array(1 => 'manage tags')) . '</p>';
    }
    if ($rev == '4.7.22') {
      // Based on support inquiries for 4.7.21, show message during 4.7.22.
      // For affected users, this issue prevents loading the regular status screen.
      if (!$this->checkImageUploadDir()) {
        $preUpgradeMessage .= '<p>' . ts('There appears to be an inconsistency in the configuration of "Image Upload URL" and "Image Upload Directory".') . '</p>'
          . '<p>'
          . ts('Further advice will be displayed at the end of the upgrade.')
          . '</p>';
      }
    }
    if ($rev == '4.7.27') {
      $params = array(
        1 => 'Close accounting batches created by user',
        2 => 'Close all accounting batches',
        3 => 'Reopen accounting batches created by user',
        4 => 'Reopen all accounting batches',
        5 => 'https://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles',
      );
      $preUpgradeMessage .= '<p>' . ts('A new set of batch permissions has been added called "%1", "%2", "%3" and "%4". These permissions are now used to control access to the Accounting Batches tasks. If your users need to be able to Reopen or Close batches you may need to give them additional permissions. <a href=%5>Read more</a>', $params) . '</p>';
    }
    if ($rev == '4.7.32') {
      $preUpgradeMessage .= '<p>' . ts('A new %1 permission has been added. It is not granted by default. If you use SMS, you may wish to review your permissions.', array(1 => 'send SMS')) . '</p>';
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.7.alpha1') {
      $config = CRM_Core_Config::singleton();
      // FIXME: Performing an upgrade step during postUpgrade message phase is probably bad
      $editor_id = self::updateWysiwyg();
      $msg = NULL;
      $ext_href = 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1') . '"';
      $dsp_href = 'href="' . CRM_Utils_System::url('civicrm/admin/setting/preferences/display', 'reset=1') . '"';
      $blog_href = 'href="https://civicrm.org/blogs/colemanw/big-changes-wysiwyg-editing-47"';
      switch ($editor_id) {
        // TinyMCE
        case 1:
          $msg = ts('Your configured editor "TinyMCE" is no longer part of the main CiviCRM download. To continue using it, visit the <a %1>Manage Extensions</a> page to download and install the TinyMCE extension.', array(1 => $ext_href));
          break;

        // Drupal/Joomla editor
        case 3:
        case 4:
          $msg = ts('CiviCRM no longer integrates with the "%1 Default Editor." Your wysiwyg setting has been reset to the built-in CKEditor. <a %2>Learn more...</a>', array(1 => $config->userFramework, 2 => $blog_href));
          break;
      }
      if ($msg) {
        $postUpgradeMessage .= '<p>' . $msg . '</p>';
      }
      $postUpgradeMessage .= '<p>' . ts('CiviCRM now includes the easy-to-use CKEditor Configurator. To customize the features and display of your wysiwyg editor, visit the <a %1>Display Preferences</a> page. <a %2>Learn more...</a>', array(1 => $dsp_href, 2 => $blog_href)) . '</p>';

      $postUpgradeMessage .= '<br /><br />' . ts('Default version of the following System Workflow Message Templates have been modified: <ul><li>Personal Campaign Pages - Owner Notification</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');

      $postUpgradeMessage .= '<p>' . ts('The custom fatal error template setting has been removed.') . '</p>';
    }
    //if ($rev == '4.7.11') {
    //  $postUpgradeMessage .= '<br /><br />' . ts("WARNING: For increased security, profile submissions embedded in remote sites are no longer allowed to create or edit data by default. If you need to allow users to submit profiles from external sites, you can restore this at Administer > System Settings > Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.) > 'Accept profile submissions from external sites'");
    //}
    if ($rev == '4.7.11') {
      $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    }
    if ($rev == '4.7.14') {
      $ck_href = 'href="' . CRM_Utils_System::url('civicrm/admin/ckeditor') . '"';
      $postUpgradeMessage .= '<p>' . ts('CiviMail no longer forces CKEditor to add html/head/body tags to email content because some sites place these in the message header/footer. This was added in 4.7.5 and is now disabled by default.')
        . '<br />' . ts('You can re-enable it by visiting the <a %1>CKEditor Config</a> screen and setting "fullPage = true" under the Advanced Options of the CiviMail preset.', array(1 => $ck_href))
        . '</p>';
    }
    if ($rev == '4.7.19') {
      $postUpgradeMessage .= '<br /><br />' . ts('Default version of the following System Workflow Message Templates have been modified: <ul><li>Additional Payment Receipt or Refund Notification</li><li>Contribution Invoice</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
      $check = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_domain");
      $smsCheck = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_sms_provider");
      if ($check > 1 && (bool) $smsCheck) {
        $postUpgradeMessage .= '<p>civicrm_sms_provider ' . ts('has now had a domain id column added. As there is more than 1 domains in this install you need to manually set the domain id for the providers in this install') . '</p>';
      }
    }
    if ($rev == '4.7.22') {
      // Based on support inquiries for 4.7.21, show message during 4.7.22.
      // For affected users, this issue prevents loading the regular status screen.
      if (!$this->checkImageUploadDir()) {
        $config = CRM_Core_Config::singleton();
        $postUpgradeMessage .=
          '<h3>' . ts('Warning') . '</h3>'
          . '<p>' . ts('There appears to be an inconsistency in the configuration of "Image Upload URL" and "Image Upload Directory".') . '</p>'
          . sprintf("<ul><li><b>imageUploadDir</b>: <code>%s</code></li><li><b>imageUploadURL</b>: <code>%s</code></li></ul>", htmlentities($config->imageUploadDir), htmlentities($config->imageUploadURL))
          . '<p>'
          . ts('You may need to check that: <ul><li>(a) the path and URL match,</li><li> (b) the httpd/htaccess policy allows requests for files inside this folder,</li><li>and (c) the web domain matches the normal web domain.</ul>')
          . '</p>'
          . '<p><em>'
          . ts('(Note: Although files should be readable, it is best if they are not listable or browseable.)')
          . '</em></p>'
          . '<p>'
          . ts('If this remains unresolved, then some important screens may fail to load.')
          . '</p>';
      }
    }
    if ($rev == '4.7.23') {
      $postUpgradeMessage .= '<br /><br />' . ts('Default version of the following System Workflow Message Templates have been modified: <ul><li>Contribution Invoice</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_alpha1($rev) {
    $this->addTask('Drop action scheudle mapping foreign key', 'dropActionScheudleMappingForeignKey');
    $this->addTask('Migrate \'on behalf of\' information to module_data', 'migrateOnBehalfOfInfo');
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask(ts('Migrate Settings to %1', array(1 => $rev)), 'migrateSettings', $rev);
    $this->addTask('Add Getting Started dashlet', 'addGettingStartedDashlet', $rev);
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_alpha4($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask(ts('Remove %1', array(1 => 'Moneris')), 'removePaymentProcessorType', 'Moneris');
    $this->addTask('Update Smart Groups', 'fixContactTypeInSmartGroups');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_beta2($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Delete unused file', 'deleteVersionCheckCacheFile');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_beta6($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Disable flexible jobs extension', 'disableFlexibleJobsExtension');
    $this->addTask('Add Index to financial_trxn trxn_id field', 'addIndexFinancialTrxnTrxnID');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add Index to civicrm_contribution creditnote_id field', 'addIndexContributionCreditNoteID');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_2($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Fix Index on civicrm_financial_item combined entity_id + entity_table', 'addCombinedIndexFinancialItemEntityIDEntityType');
    $this->addTask('enable financial account relationships for chargeback & refund', 'addRefundAndChargeBackAccountsIfNotExist');
    $this->addTask('Add Index to civicrm_contribution.source', 'addIndexContributionSource');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_3($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add Index to civicrm_contribution.total_amount', 'addIndexContributionAmount');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_4($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add Contact Deleted by Merge Activity Type', 'addDeletedByMergeActivityType');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_7($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    // https://issues.civicrm.org/jira/browse/CRM-18006
    if (CRM_Core_DAO::checkTableExists('civicrm_install_canary')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_install_canary ENGINE=InnoDB');
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_8($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Upgrade mailing foreign key constraints', 'upgradeMailingFKs');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_10($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Upgrade Add Help Pre and Post Fields to price value table', 'addHelpPreAndHelpPostFieldsPriceFieldValue');
    $this->addTask('Alter index and type for image URL', 'alterIndexAndTypeForImageURL');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_11($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Dashboard schema updates', 'dashboardSchemaUpdate');
    $this->addTask('Fill in setting "remote_profile_submissions"', 'migrateRemoteSubmissionsSetting');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_12($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add Data Type column to civicrm_option_group', 'addDataTypeColumnToOptionGroupTable');
  }
  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_13($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('CRM-19372 - Add column to allow for payment processors to set what card types are accepted', 'addColumn',
      'civicrm_payment_processor', 'accepted_credit_cards', "text DEFAULT NULL COMMENT 'array of accepted credit card types'");
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_14($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add WYSIWYG Editor Presets', 'addWysiwygPresets');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_15($rev) {
    $this->addTask('CRM-19626 - Add min_amount column to civicrm_price_set', 'addColumn',
      'civicrm_price_set', 'min_amount', "INT(10) UNSIGNED DEFAULT '0' COMMENT 'Minimum Amount required for this set.'");
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_16($rev) {
    $this->addTask('CRM-19723 - Add icon column to civicrm_option_value', 'addColumn',
      'civicrm_option_value', 'icon', "varchar(255) COMMENT 'crm-i icon class' DEFAULT NULL");
    $this->addTask('CRM-19769 - Add color column to civicrm_tag', 'addColumn',
      'civicrm_tag', 'color', "varchar(255) COMMENT 'Hex color value e.g. #ffffff' DEFAULT NULL");
    $this->addTask('CRM-19779 - Add color column to civicrm_option_value', 'addColumn',
      'civicrm_option_value', 'color', "varchar(255) COMMENT 'Hex color value e.g. #ffffff' DEFAULT NULL");
    $this->addTask('Add new CiviMail fields', 'addMailingTemplateType');
    $this->addTask('CRM-19770 - Add is_star column to civicrm_activity', 'addColumn',
      'civicrm_activity', 'is_star', "tinyint DEFAULT '0' COMMENT 'Activity marked as favorite.'");
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_18($rev) {
    $this->addTask('Update Kenyan Provinces', 'updateKenyanProvinces');
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_19($rev) {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_financial_account', 'opening_balance')) {
      $query = "SELECT id FROM civicrm_financial_account WHERE opening_balance <> 0 OR current_period_opening_balance <> 0";
      $result = CRM_Core_DAO::executeQuery($query);
      if (!$result->N) {
        $this->addTask('Drop Column current_period_opening_balance From civicrm_financial_account table.', 'dropColumn', 'civicrm_financial_account', 'current_period_opening_balance');
        $this->addTask('Drop Column opening_balance From civicrm_financial_account table.', 'dropColumn', 'civicrm_financial_account', 'opening_balance');
      }
    }
    $this->addTask('CRM-19961 - Add domain_id column to civicrm_sms_provider', 'addColumn',
      'civicrm_sms_provider', 'domain_id', "int(10) unsigned COMMENT 'Which Domain is this sms provier for'");
    $this->addTask('CRM-19961 - Populate domain id table and perhaps add foreign key', 'populateSMSProviderDomainId');
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('CRM-16633 - Add "Change Case Subject" activity', 'addChangeCaseSubjectActivityType');
    $this->addTask('Add is_public column to civicrm_custom_group', 'addColumn',
      'civicrm_custom_group', 'is_public', "boolean DEFAULT '1' COMMENT 'Is this property public?'");
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_20($rev) {
    $this->addtask('Fix Schema on civicrm_action_schedule', 'fixSchemaOnCiviCRMActionSchedule');
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add activity_status column to civicrm_mail_settings', 'addColumn',
      'civicrm_mail_settings', 'activity_status', "varchar (255) DEFAULT NULL COMMENT 'Name of status to use when creating email to activity.'");
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_23($rev) {
    $this->addTask('CRM-20387 - Add invoice_number column to civicrm_contribution', 'addColumn',
      'civicrm_contribution', 'invoice_number', "varchar(255) COMMENT 'Human readable invoice number' DEFAULT NULL");
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_25($rev) {
    $this->addTask("CRM-20927 - Add column to 'civicrm_menu' for additional metadata", 'addColumn',
      'civicrm_menu', 'module_data', "text COMMENT 'All other menu metadata not stored in other fields'");
    $this->addTask('CRM-21052 - Determine activity revision policy', 'pickActivityRevisionPolicy');
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Add cancel button text column to civicrm_uf_group', 'addColumn',
      'civicrm_uf_group', 'cancel_button_text', "varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Custom Text to display on the cancel button when used in create or edit mode'", TRUE);
    $this->addTask('Add Submit button text column to civicrm_uf_group', 'addColumn',
      'civicrm_uf_group', 'submit_button_text', "varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Custom Text to display on the submit button on profile edit/create screens'", TRUE);

    $this->addTask('CRM-20958 - Add created_date to civicrm_activity', 'addColumn',
      'civicrm_activity', 'created_date', "timestamp NULL  DEFAULT NULL COMMENT 'When was the activity was created.'");
    $this->addTask('CRM-20958 - Add modified_date to civicrm_activity', 'addColumn',
      'civicrm_activity', 'modified_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the activity (or closely related entity) was created or modified or deleted.'");
    $this->addTask('CRM-20958 - Add created_date to civicrm_case', 'addColumn',
      'civicrm_case', 'created_date', "timestamp NULL  DEFAULT NULL COMMENT 'When was the case was created.'");
    $this->addTask('CRM-20958 - Add modified_date to civicrm_case', 'addColumn',
      'civicrm_case', 'modified_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the case (or closely related entity) was created or modified or deleted.'");
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_27($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('CRM-20892 Change created_date to default to NULL', 'civiMailingCreatedDateNull');
    $this->addTask('CRM-21234 Missing subdivisions of Tajikistan', 'tajikistanMissingSubdivisions');
    $this->addTask('CRM-20892 - Add modified_date to civicrm_mailing', 'addColumn',
      'civicrm_mailing', 'modified_date', "timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'");
    $this->addTask('CRM-21195 - Add icon field to civicrm_navigation', 'addColumn',
      'civicrm_navigation', 'icon', "varchar(255) NULL DEFAULT NULL COMMENT 'CSS class name for an icon'");
    $this->addTask('CRM-12167 - Add visibility column to civicrm_price_field_value', 'addColumn',
      'civicrm_price_field_value', 'visibility_id', 'int(10) unsigned DEFAULT 1 COMMENT "Implicit FK to civicrm_option_group with name = \'visibility\'"');
    $this->addTask('Remove broken Contribution_logging reports', 'removeContributionLoggingReports');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_28($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('CRM-20572: Fix date fields in save search criteria of Contrib Sybunt custom search ', 'fixDateFieldsInSmartGroups');
    // CRM-20868 : Update invoice_numbers (in batch) with value in [invoice prefix][contribution id] format
    if ($invoicePrefix = CRM_Contribute_BAO_Contribution::checkContributeSettings('invoice_prefix', TRUE)) {
      list($minId, $maxId) = CRM_Core_DAO::executeQuery("SELECT coalesce(min(id),0), coalesce(max(id),0)
        FROM civicrm_contribution ")->getDatabaseResult()->fetchRow();
      for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
        $endId = $startId + self::BATCH_SIZE - 1;
        $title = ts("Upgrade DB to %1: Update Contribution Invoice number (%2 => %3)", array(
          1 => $rev,
          2 => $startId,
          3 => $endId,
        ));
        $this->addTask($title, 'updateContributionInvoiceNumber', $startId, $endId, $invoicePrefix);
      }
    }

  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_31($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('CRM-21225: Add display title field to civicrm_uf_group', 'addColumn', 'civicrm_uf_group', 'frontend_title',
      "VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'Profile Form Public title'", TRUE);
    $this->addTask('Rebuild Multilingual Schema', 'rebuildMultilingalSchema');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_32($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);

    $this->addTask('CRM-21733: Add status_override_end_date field to civicrm_membership table', 'addColumn', 'civicrm_membership', 'status_override_end_date',
      "date DEFAULT NULL COMMENT 'The end date of membership status override if (Override until selected date) override type is selected.'");
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  //  /**
  //   * Upgrade function.
  //   *
  //   * @param string $rev
  //   */
  //  public function upgrade_4_7_x($rev) {
  //    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  /**
   * CRM-16354
   *
   * @return int
   */
  public static function updateWysiwyg() {
    $editorID = Civi::settings()->get('editor_id');
    // Previously a numeric value indicated one of 4 wysiwyg editors shipped in core, and no value indicated 'Textarea'
    // Now the options are "Textarea", "CKEditor", and the rest have been dropped from core.
    $newEditor = $editorID ? "CKEditor" : "Textarea";
    Civi::settings()->set('editor_id', $newEditor);

    return $editorID;
  }

  /**
   * Migrate any last remaining options from `civicrm_domain.config_backend` to `civicrm_setting`.
   * Cleanup setting schema.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function migrateSettings(CRM_Queue_TaskContext $ctx) {
    // Tip: If there are problems with adding the new uniqueness index, try inspecting:
    // SELECT name, domain_id, contact_id, count(*) AS dupes FROM civicrm_setting cs GROUP BY name, domain_id, contact_id HAVING dupes > 1;

    // Nav records are expendable. https://forum.civicrm.org/index.php?topic=36933.0
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_setting WHERE contact_id IS NOT NULL AND name = "navigation"');

    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_setting DROP INDEX index_group_name');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_setting DROP COLUMN group_name');

    // Handle Strange activity_tab_filter settings.
    CRM_Core_DAO::executeQuery('CREATE TABLE civicrm_activity_setting LIKE civicrm_setting');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_activity_setting ADD UNIQUE INDEX index_domain_contact_name (domain_id, contact_id, name)');
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_activity_setting (name, contact_id, domain_id, value)
     SELECT DISTINCT name, contact_id, domain_id, value
     FROM civicrm_setting
     WHERE name = "activity_tab_filter"
     AND value is not NULL');
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_setting WHERE name = "activity_tab_filter"');

    $date = CRM_Utils_Time::getTime('Y-m-d H:i:s');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_setting ADD UNIQUE INDEX index_domain_contact_name (domain_id, contact_id, name)');
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_setting (name, contact_id, domain_id, value, is_domain, created_id, created_date)
     SELECT name, contact_id, domain_id, value, 0, contact_id,'$date'
     FROM civicrm_activity_setting
     WHERE name = 'activity_tab_filter'
     AND value is not NULL"
    );
    CRM_Core_DAO::executeQuery('DROP TABLE civicrm_activity_setting');

    $domainDao = CRM_Core_DAO::executeQuery('SELECT id, config_backend FROM civicrm_domain');
    while ($domainDao->fetch()) {
      $settings = CRM_Upgrade_Incremental_php_FourSeven::convertBackendToSettings($domainDao->id, $domainDao->config_backend);
      CRM_Core_Error::debug_var('convertBackendToSettings', array(
        'domainId' => $domainDao->id,
        'backend' => $domainDao->config_backend,
        'settings' => $settings,
      ));

      foreach ($settings as $name => $value) {
        $rowParams = array(
          1 => array($domainDao->id, 'Positive'),
          2 => array($name, 'String'),
          3 => array(serialize($value), 'String'),
        );
        $settingId = CRM_Core_DAO::singleValueQuery(
          'SELECT id FROM civicrm_setting WHERE domain_id = %1 AND name = %2',
          $rowParams);
        if (!$settingId) {
          CRM_Core_DAO::executeQuery(
            'INSERT INTO civicrm_setting (domain_id, name, value, is_domain) VALUES (%1,%2,%3,1)',
            $rowParams);
        }
      }
    }

    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_domain DROP COLUMN config_backend');

    return TRUE;
  }

  /**
   * Take a config_backend blob and produce an equivalent list of settings.
   *
   * @param int $domainId
   *   Domain ID.
   * @param string $config_backend
   *   Serialized blob.
   * @return array
   */
  public static function convertBackendToSettings($domainId, $config_backend) {
    if (!$config_backend) {
      return array();
    }

    $backend = unserialize($config_backend);
    if (!$backend) {
      return array();
    }

    $mappings = \CRM_Core_Config_MagicMerge::getPropertyMap();
    $settings = array();
    foreach ($backend as $propertyName => $propertyValue) {
      if (isset($mappings[$propertyName][0]) && preg_match('/^setting/', $mappings[$propertyName][0])) {
        // $mapping format: $propertyName => Array(0 => $type, 1 => $setting|NULL).
        $settingName = isset($mappings[$propertyName][1]) ? $mappings[$propertyName][1] : $propertyName;
        $settings[$settingName] = $propertyValue;
      }
    }

    return $settings;
  }

  /**
   * Update Invoice number for all completed contribution.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateContributionInvoiceNumber(CRM_Queue_TaskContext $ctx, $startID, $endID, $invoicePrefix) {
    CRM_Core_DAO::executeQuery("
      UPDATE `civicrm_contribution` SET `invoice_number` = CONCAT(%1, `id`)
       WHERE `id` >= %2 AND `id` <= %3 AND `invoice_number` IS NOT NULL",
      array(
        1 => array($invoicePrefix, 'String'),
        2 => array($startID, 'Integer'),
        3 => array($endID, 'Integer'),
      )
    );

    return TRUE;
  }

  /**
   * Add Getting Started dashlet to dashboard
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addGettingStartedDashlet(CRM_Queue_TaskContext $ctx) {
    $sql = "SELECT count(*) FROM civicrm_dashboard WHERE name='getting-started'";
    $res = CRM_Core_DAO::singleValueQuery($sql);
    $domainId = CRM_Core_Config::domainID();
    if ($res <= 0) {
      $sql = "INSERT INTO `civicrm_dashboard`
    ( `domain_id`, `name`, `label`, `url`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `is_active`, `weight`, `fullscreen_url`, `is_fullscreen`, `is_reserved`) VALUES ( {$domainId}, 'getting-started', 'Getting Started', 'civicrm/dashlet/getting-started?reset=1&snippet=5', 'access CiviCRM', NULL, 0, 0, 1, 0, 'civicrm/dashlet/getting-started?reset=1&snippet=5&context=dashletFullscreen', 1, 1)";
      CRM_Core_DAO::executeQuery($sql);
      // Add default position for Getting Started Dashlet ( left column)
      $sql = "INSERT INTO `civicrm_dashboard_contact` (dashboard_id, contact_id, column_no, is_active)
SELECT (SELECT MAX(id) FROM `civicrm_dashboard`), contact_id, 0, IF (SUM(is_active) > 0, 1, 0)
FROM `civicrm_dashboard_contact` JOIN `civicrm_contact` WHERE civicrm_dashboard_contact.contact_id = civicrm_contact.id GROUP BY contact_id";
      CRM_Core_DAO::executeQuery($sql);
    }
    return TRUE;
  }

  /**
   * Migrate on-behalf information to uf_join.module_data as on-behalf columns will be dropped
   * on DB upgrade
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   */
  public static function migrateOnBehalfOfInfo(CRM_Queue_TaskContext $ctx) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);

    // fetch onBehalf entry in UFJoin table
    $ufGroupDAO = new CRM_Core_DAO_UFJoin();
    $ufGroupDAO->module = 'OnBehalf';
    $ufGroupDAO->find(TRUE);

    $forOrgColums = array('is_for_organization');
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      foreach ($locales as $locale) {
        $forOrgColums[] = "for_organization_{$locale}";
      }
    }
    else {
      $forOrgColums[] = "for_organization";
    }

    $query = "
      SELECT " . implode(", ", $forOrgColums) . ", uj.id as join_id, uj.uf_group_id as uf_group_id
      FROM civicrm_contribution_page cp
       INNER JOIN civicrm_uf_join uj ON uj.entity_id = cp.id AND uj.module = 'OnBehalf'";
    $dao = CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);

    if ($dao->N) {
      while ($dao->fetch()) {
        $onBehalfParams['on_behalf'] = array('is_for_organization' => $dao->is_for_organization);
        if ($domain->locales) {
          foreach ($locales as $locale) {
            $for_organization = "for_organization_{$locale}";
            $onBehalfParams['on_behalf'] += array(
              $locale => array(
                'for_organization' => $dao->$for_organization,
              ),
            );
          }
        }
        else {
          $onBehalfParams['on_behalf'] += array(
            'default' => array(
              'for_organization' => $dao->for_organization,
            ),
          );
        }
        $ufJoinParam = array(
          'id' => $dao->join_id,
          'module' => 'on_behalf',
          'uf_group_id' => $dao->uf_group_id,
          'module_data' => json_encode($onBehalfParams),
        );
        CRM_Core_BAO_UFJoin::create($ufJoinParam);
      }
    }

    return TRUE;
  }

  /**
   * v4.7.11 adds a new setting "remote_profile_submissions". This is
   * long-standing feature that existing sites may be using; however, it's
   * a bit prone to abuse. For new sites, the default is to disable it
   * (since that is more secure). For existing sites, the default is to
   * enable it (since that is more compatible).
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function migrateRemoteSubmissionsSetting(CRM_Queue_TaskContext $ctx) {
    $domains = CRM_Core_DAO::executeQuery("SELECT DISTINCT d.id FROM civicrm_domain d LEFT JOIN civicrm_setting s ON d.id=s.domain_id AND s.name = 'remote_profile_submissions' WHERE s.id IS NULL");
    while ($domains->fetch()) {
      CRM_Core_DAO::executeQuery(
        "INSERT INTO civicrm_setting (`name`, `value`, `domain_id`, `is_domain`, `contact_id`, `component_id`, `created_date`, `created_id`)
          VALUES (%2, %3, %4, %5, NULL, NULL, %6, NULL)",
        array(
          2 => array('remote_profile_submissions', 'String'),
          3 => array('s:1:"1";', 'String'),
          4 => array($domains->id, 'Integer'),
          5 => array(1, 'Integer'),
          6 => array(date('Y-m-d H:i:s'), 'String'),
        )
      );
    }
    return TRUE;
  }

  /**
   * CRM-11782 - Get rid of VALUE_SEPARATOR character in saved search form values
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function fixContactTypeInSmartGroups(CRM_Queue_TaskContext $ctx) {
    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    $dao = CRM_Core_DAO::executeQuery("SELECT id, form_values FROM civicrm_saved_search WHERE form_values LIKE '%$sep%'");
    while ($dao->fetch()) {
      $formValues = unserialize($dao->form_values);
      if (isset($formValues['contact_type']) && is_array($formValues['contact_type'])) {
        $newVals = array();
        foreach ($formValues['contact_type'] as $key => $val) {
          $newVals[str_replace($sep, '__', $key)] = is_string($val) ? str_replace($sep, '__', $val) : $val;
        }
        $formValues['contact_type'] = $newVals;
      }
      CRM_Core_DAO::executeQuery("UPDATE civicrm_saved_search SET form_values = %1 WHERE id = {$dao->id}", array(1 => array(serialize($formValues), 'String')));
    }

    return TRUE;
  }

  /**
   * CRM-17637 - Ths file location has been moved; delete the old one
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function deleteVersionCheckCacheFile(CRM_Queue_TaskContext $ctx) {
    $config = CRM_Core_Config::singleton();
    $cacheFile = $config->uploadDir . 'version-info-cache.json';
    if (file_exists($cacheFile)) {
      unlink($cacheFile);
    }
    return TRUE;
  }

  /**
   * CRM-17669 and CRM-17686, make scheduled jobs more flexible, disable the 4.6 extension if installed
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function disableFlexibleJobsExtension(CRM_Queue_TaskContext $ctx) {
    try {
      civicrm_api3('Extension', 'disable', array('key' => 'com.klangsoft.flexiblejobs'));
    }
    catch (CiviCRM_API3_Exception $e) {
      // just ignore if the extension isn't installed
    }

    return TRUE;
  }

  /**
   * CRM-17752 add index to civicrm_financial_trxn.trxn_id (deliberately non-unique).
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addIndexFinancialTrxnTrxnID(CRM_Queue_TaskContext $ctx) {
    $tables = array('civicrm_financial_trxn' => array('trxn_id'));
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    return TRUE;
  }

  /**
   * CRM-17882 Add index to civicrm_contribution.credit_note_id.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addIndexContributionCreditNoteID(CRM_Queue_TaskContext $ctx) {
    $tables = array('civicrm_contribution' => array('creditnote_id'));
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    return TRUE;
  }

  /**
   * CRM-17775 Add correct index for table civicrm_financial_item.
   *
   * Note that the entity ID should always precede the entity_table as
   * it is more unique. This is better for performance and does not cause fallback
   * to no index if table it omitted.
   *
   * @return bool
   */
  public static function addCombinedIndexFinancialItemEntityIDEntityType() {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_financial_item', 'UI_id');
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_financial_item', 'IX_Entity');
    CRM_Core_BAO_SchemaHandler::createIndexes(array(
      'civicrm_financial_item' => array(array('entity_id', 'entity_table')),
    ));
    return TRUE;
  }

  /**
   * CRM-17951 Add accounts option values for refund and chargeback.
   *
   * Add Chargeback contribution status and Chargeback and Contra account relationships,
   * checking first if one exists.
   */
  public static function addRefundAndChargeBackAccountsIfNotExist() {
    // First we enable and edit the record for Credit contra - this exists but is disabled for most sites.
    // Using the ensure function (below) will not enabled a disabled option (by design).
    CRM_Core_DAO::executeQuery("UPDATE civicrm_option_value v
     INNER JOIN civicrm_option_group g on v.option_group_id=g.id and g.name='account_relationship'
     SET v.is_active=1, v.label='Credit/Contra Revenue Account is', v.name='Credit/Contra Revenue Account is', v.description='Credit/Contra Revenue Account is'
     WHERE v.name = 'Credit/Contra Account is';");

    CRM_Core_BAO_OptionValue::ensureOptionValueExists(array(
      'option_group_id' => 'account_relationship',
      'name' => 'Chargeback Account is',
      'label' => ts('Chargeback Account is'),
      'is_active' => TRUE,
      'component_id' => 'CiviContribute',
    ));

    CRM_Core_BAO_OptionValue::ensureOptionValueExists(array(
      'option_group_id' => 'contribution_status',
      'name' => 'Chargeback',
      'label' => ts('Chargeback'),
      'is_active' => TRUE,
      'component_id' => 'CiviContribute',
    ));
    return TRUE;
  }

  /**
   * CRM-17999 Add index to civicrm_contribution.source.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addIndexContributionSource(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::createIndexes(array('civicrm_contribution' => array('source')));
    return TRUE;
  }

  /**
   * CRM-18124 Add index to civicrm_contribution.total_amount.
   *
   * Note that I made this a combined index with receive_date because the issue included
   * both criteria and they seemed likely to be used in conjunction to me in other cases.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addIndexContributionAmount(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::createIndexes(array(
      'civicrm_contribution' => array(array('total_amount', 'receive_date')),
    ));
    return TRUE;
  }

  /**
   * CRM-18124 Add index to civicrm_contribution.total_amount.
   *
   * Note that I made this a combined index with receive_date because the issue included
   * both criteria and they seemed likely to be used in conjunction to me in other cases.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addDeletedByMergeActivityType(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(array(
      'option_group_id' => 'activity_type',
      'name' => 'Contact Deleted by Merge',
      'label' => ts('Contact Deleted by Merge'),
      'description' => ts('Contact was merged into another contact'),
      'is_active' => TRUE,
      'filter' => 1,
    ));
    return TRUE;
  }

  /**
   * CRM-12252 Add Help Pre and Help Post Fields for Price Field Value Table.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addHelpPreAndHelpPostFieldsPriceFieldValue(CRM_Queue_TaskContext $ctx) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      foreach ($locales as $locale) {
        if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists("civicrm_price_field_value", "help_pre_{$locale}")) {
          CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_price_field_value`
            ADD COLUMN `help_pre_{$locale}` text COLLATE utf8_unicode_ci COMMENT 'Price field option pre help text.'", array(), TRUE, NULL, FALSE, FALSE);
        }
        if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists("civicrm_price_field_value", "help_post_{$locale}")) {
          CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_price_field_value`
            ADD COLUMN `help_post_{$locale}` text COLLATE utf8_unicode_ci COMMENT 'Price field option post help text.'", array(), TRUE, NULL, FALSE, FALSE);
        }
      }
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
    }
    else {
      if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_price_field_value', 'help_pre')) {
        CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_price_field_value`
          ADD COLUMN `help_pre` text COLLATE utf8_unicode_ci COMMENT 'Price field option pre help text.'");
      }
      if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_price_field_value', 'help_post')) {
        CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_price_field_value`
          ADD COLUMN `help_post` text COLLATE utf8_unicode_ci COMMENT 'Price field option post help text.'");
      }
    }
    return TRUE;
  }

  /**
   * CRM-18464 Check if Foreign key exists and also drop any index of same name accidentially created.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function dropActionScheudleMappingForeignKey(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_action_schedule', 'FK_civicrm_action_schedule_mapping_id');
    return TRUE;
  }

  /**
   * CRM-18345 Don't delete mailing data on email/phone deletion
   * Implemented here in CRM-18526
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function upgradeMailingFKs(CRM_Queue_TaskContext $ctx) {

    // Safely drop the foreign keys
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mailing_event_queue', 'FK_civicrm_mailing_event_queue_email_id');
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mailing_event_queue', 'FK_civicrm_mailing_event_queue_phone_id');
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mailing_recipients', 'FK_civicrm_mailing_recipients_email_id');
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mailing_recipients', 'FK_civicrm_mailing_recipients_phone_id');

    // Set up the new foreign keys
    CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 0;");

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_mailing_event_queue`
        ADD CONSTRAINT `FK_civicrm_mailing_event_queue_email_id`
        FOREIGN KEY (`email_id`)
        REFERENCES `civicrm_email`(`id`)
        ON DELETE SET NULL
        ON UPDATE RESTRICT;
    ");

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_mailing_event_queue`
        ADD CONSTRAINT `FK_civicrm_mailing_event_queue_phone_id`
        FOREIGN KEY (`phone_id`)
        REFERENCES `civicrm_phone`(`id`)
        ON DELETE SET NULL
        ON UPDATE RESTRICT;
    ");

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_mailing_recipients`
        ADD CONSTRAINT `FK_civicrm_mailing_recipients_email_id`
        FOREIGN KEY (`email_id`)
        REFERENCES `civicrm_email`(`id`)
        ON DELETE SET NULL
        ON UPDATE RESTRICT;
    ");

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_mailing_recipients`
        ADD CONSTRAINT `FK_civicrm_mailing_recipients_phone_id`
        FOREIGN KEY (`phone_id`)
        REFERENCES `civicrm_phone`(`id`)
        ON DELETE SET NULL
        ON UPDATE RESTRICT;
    ");

    CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 1;");

    return TRUE;
  }

  /**
   * CRM-17663 - Dashboard schema changes
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function dashboardSchemaUpdate(CRM_Queue_TaskContext $ctx) {
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_dashboard_contact', 'index_dashboard_id_contact_id')) {
      // Delete any stray duplicate rows and add unique index to prevent new dupes and enable INSERT/UPDATE combo query
      CRM_Core_DAO::executeQuery('DELETE c1 FROM civicrm_dashboard_contact c1, civicrm_dashboard_contact c2 WHERE c1.contact_id = c2.contact_id AND c1.dashboard_id = c2.dashboard_id AND c1.id > c2.id');
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_dashboard_contact ADD UNIQUE INDEX index_dashboard_id_contact_id (dashboard_id, contact_id);');
    }
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'content', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'is_minimized', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'is_fullscreen', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'created_date', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'is_fullscreen', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'is_minimized', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'column_no', FALSE, TRUE);
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'weight', FALSE, TRUE);

    CRM_Core_DAO::executeQuery('UPDATE civicrm_dashboard SET url = REPLACE(url, "&snippet=5", ""), fullscreen_url = REPLACE(fullscreen_url, "&snippet=5", "")');

    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_dashboard', 'cache_minutes')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_dashboard ADD COLUMN cache_minutes int unsigned NOT NULL DEFAULT 60 COMMENT "Number of minutes to cache dashlet content in browser localStorage."',
         array(), TRUE, NULL, FALSE, FALSE);
    }
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
    }

    CRM_Core_DAO::executeQuery('UPDATE civicrm_dashboard SET cache_minutes = 1440 WHERE name = "blog"');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_dashboard SET cache_minutes = 7200 WHERE name IN ("activity","getting-started")');
    return TRUE;
  }

  /**
   * CRM-19100 - Alter Index and Type for Image URL
   * @return bool
   */
  public static function alterIndexAndTypeForImageURL() {
    $length = array();
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_contact', 'index_image_url');
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_contact` CHANGE `image_URL` `image_URL` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'optional URL for preferred image (photo, logo, etc.) to display for this contact.'");

    $length['civicrm_contact']['image_URL'] = 128;
    CRM_Core_BAO_SchemaHandler::createIndexes(array('civicrm_contact' => array('image_URL')), 'index', $length);

    return TRUE;
  }

  /**
   * Add mailing template type.
   *
   * @return bool
   */
  public static function addMailingTemplateType() {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_mailing', 'template_type', FALSE)) {
      CRM_Core_DAO::executeQuery('
        ALTER TABLE civicrm_mailing
        ADD COLUMN `template_type` varchar(64)  NOT NULL DEFAULT \'traditional\' COMMENT \'The language/processing system used for email templates.\',
        ADD COLUMN `template_options` longtext  COMMENT \'Advanced options used by the email templating system. (JSON encoded)\'
      ');
    }
    return TRUE;
  }

  /**
   * CRM-18651 Add DataType column to Option Group Table
   * @return bool
   */
  public static function addDataTypeColumnToOptionGroupTable() {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_option_group', 'data_type')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_option_group` ADD COLUMN `data_type` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL comment 'Data Type of Option Group.'",
         array(), TRUE, NULL, FALSE, FALSE);
    }
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
    }

    CRM_Core_DAO::executeQuery("UPDATE `civicrm_option_group` SET `data_type` = 'Integer'
      WHERE name IN ('activity_type', 'gender', 'payment_instrument', 'participant_role', 'event_type')");
    return TRUE;
  }

  /**
   * CRM-19372 Add field to store accepted credit credit cards for a payment processor.
   * @return bool
   */
  public static function addWysiwygPresets() {
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(array(
      'name' => 'wysiwyg_presets',
      'title' => ts('WYSIWYG Editor Presets'),
      'is_reserved' => 1,
    ));
    $values = array(
      'default' => array('label' => ts('Default'), 'is_default' => 1),
      'civimail' => array('label' => ts('CiviMail'), 'component_id' => 'CiviMail'),
      'civievent' => array('label' => ts('CiviEvent'), 'component_id' => 'CiviEvent'),
    );
    foreach ($values as $name => $value) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists($value + array(
        'name' => $name,
        'option_group_id' => 'wysiwyg_presets',
      ));
    }
    $fileName = Civi::paths()->getPath('[civicrm.files]/persist/crm-ckeditor-config.js');
    // Ensure the config file contains the allowedContent setting
    if (file_exists($fileName)) {
      $config = file_get_contents($fileName);
      $pos = strrpos($config, '};');
      $setting = "\n\tconfig.allowedContent = true;\n";
      $config = substr_replace($config, $setting, $pos, 0);
      unlink($fileName);
      $newFileName = Civi::paths()->getPath('[civicrm.files]/persist/crm-ckeditor-default.js');
      file_put_contents($newFileName, $config);
    }
    return TRUE;
  }

  /**
   * Update Kenyan Provinces to reflect changes per CRM-20062
   *
   * @param \CRM_Queue_TaskContext $ctx
   */
  public static function updateKenyanProvinces(CRM_Queue_TaskContext $ctx) {
    $kenyaCountryID = CRM_Core_DAO::singleValueQuery('SELECT max(id) from civicrm_country where iso_code = "KE"');
    $oldProvinces = array(
      'Nairobi Municipality',
      'Coast',
      'North-Eastern Kaskazini Mashariki',
      'Rift Valley',
      'Western Magharibi',
    );
    self::deprecateStateProvinces($kenyaCountryID, $oldProvinces);
    return TRUE;
  }

  /**
   * Deprecate provinces that no longer exist.
   *
   * @param int $countryID
   * @param array $provinces
   */
  public static function deprecateStateProvinces($countryID, $provinces) {
    foreach ($provinces as $province) {
      $existingStateID = CRM_Core_DAO::singleValueQuery("
        SELECT id FROM civicrm_state_province
        WHERE country_id = %1
        AND name = %2
      ",
      array(1 => array($countryID, 'Int'), 2 => array($province, 'String')));

      if (!$existingStateID) {
        continue;
      }
      if (!CRM_Core_DAO::singleValueQuery("
       SELECT count(*) FROM civicrm_address
       WHERE state_province_id = %1
       ", array(1 => array($existingStateID, 'Int')))
      ) {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_state_province WHERE id = %1", array(1 => array($existingStateID, 'Int')));
      }
      else {
        $params = array('1' => array(ts("Former - $province"), 'String'));
        CRM_Core_DAO::executeQuery("
          UPDATE civicrm_state_province SET name = %1 WHERE id = $existingStateID
        ", $params);
      }
    }
  }

  /**
   * CRM-19961
   * Poputate newly added domain id column and add foriegn key onto table.
   */
  public static function populateSMSProviderDomainId() {
    $count = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_domain");
    if ($count == 1) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_sms_provider SET domain_id = (SELECT id FROM civicrm_domain)");
    }
    if (!parent::checkFKExists('civicrm_sms_provider', 'FK_civicrm_sms_provider_domain_id')) {
      CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 0;");
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_sms_provider`
        ADD CONSTRAINT FK_civicrm_sms_provider_domain_id
        FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain`(`id`)
        ON DELETE SET NULL");

      CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 1;");
    }
    return TRUE;
  }

  /**
   * CRM-16633 - Add activity type for Change Case Status
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addChangeCaseSubjectActivityType(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(array(
      'option_group_id' => 'activity_type',
      'name' => 'Change Case Subject',
      'label' => ts('Change Case Subject'),
      'is_active' => TRUE,
      'component_id' => 'CiviCase',
      'icon' => 'fa-pencil-square-o',
    ));
    return TRUE;
  }

  /**
   * CRM-19986 fix schema differnces in civicrm_action_schedule
   */
  public static function fixSchemaOnCiviCRMActionSchedule() {
    if (!parent::checkFKExists('civicrm_action_schedule', 'FK_civicrm_action_schedule_sms_template_id')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_action_schedule`
        ADD CONSTRAINT FK_civicrm_action_schedule_sms_template_id
        FOREIGN KEY (`sms_template_id`)  REFERENCES `civicrm_msg_template`(`id`)
        ON DELETE SET NULL");
    }
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_action_schedule`
      CHANGE `mapping_id` `mapping_id` varchar(64) COLLATE
      utf8_unicode_ci DEFAULT NULL COMMENT 'Name/ID of the mapping to use on this table'");
    return TRUE;
  }

  public static function pickActivityRevisionPolicy(CRM_Queue_TaskContext $ctx) {
    // CRM-21052 - If site is using activity revisions, continue doing so. Otherwise, switch out.
    $count = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_activity WHERE is_current_revision = 0 OR original_id IS NOT NULL');
    Civi::settings()->set('civicaseActivityRevisions', $count > 0);
    return TRUE;
  }

  /**
   * Add in missing Tajikistan Subdivisions
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function tajikistanMissingSubdivisions(CRM_Queue_TaskContext $ctx) {
    $sql = 'INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES';
    $updates = array();
    if (!CRM_Core_DAO::singleValueQuery("Select id FROM civicrm_state_province WHERE country_id = 1209 AND name = 'Dushanbe'")) {
      $updates[] = '(NULL, 1209, "DU", "Dushanbe")';
    }
    if (!CRM_Core_DAO::singleValueQuery("Select id FROM civicrm_state_province WHERE country_id = 1209 AND name = 'Nohiyahoi Tobei Jumhurí'")) {
      $updates[] = '(NULL, 1209, "RA", "Nohiyahoi Tobei Jumhurí")';
    }
    if (!empty($updates)) {
      CRM_Core_DAO::executeQuery($sql . implode(', ', $updates));
    }
    return TRUE;
  }

  /**
   * Remove the contribution logging reports which have been broken for a very long time.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function removeContributionLoggingReports(CRM_Queue_TaskContext $ctx) {
    if (class_exists('CRM_Report_Form_Contribute_LoggingDetail') || class_exists('CRM_Report_Form_Contribute_LoggingSummary')) {
      // Perhaps the site has overridden these classes. The core ones are broken but they
      // may have functional ones.
      return TRUE;
    }
    $options = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'report_template', 'options' => array('limit' => 0)));
    foreach ($options['values'] as $option) {
      if ($option['name'] === 'CRM_Report_Form_Contribute_LoggingDetail' || $option['name'] === 'CRM_Report_Form_Contribute_LoggingSummary') {
        $instances = civicrm_api3('ReportInstance', 'get', array('report_id' => $option['value']));
        if ($instances['count']) {
          foreach ($instances['values'] as $instance) {
            if ($instance['navigation_id']) {
              civicrm_api3('Navigation', 'delete', array('id' => $instance['navigation_id']));
            }
            civicrm_api3('ReportInstance', 'delete', array('id' => $instance['id']));
          }
        }
        civicrm_api3('OptionValue', 'delete', array('id' => $option['id']));
      }
    }
    return TRUE;
  }

  /**
   * @return bool
   */
  protected function checkImageUploadDir() {
    $config = CRM_Core_Config::singleton();
    $check = new CRM_Utils_Check_Component_Security();
    return $config->imageUploadDir && $config->imageUploadURL && $check->isDirAccessible($config->imageUploadDir, $config->imageUploadURL);
  }

  /**
   * CRM-20572 - Format date fields in Contrib Sybunt custom search's saved criteria.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function fixDateFieldsInSmartGroups(CRM_Queue_TaskContext $ctx) {
    $dao = CRM_Core_DAO::executeQuery("SELECT id, form_values FROM civicrm_saved_search WHERE form_values LIKE '%CRM_Contact_Form_Search_Custom_ContribSYBNT%'");
    while ($dao->fetch()) {
      $formValues = unserialize($dao->form_values);
      CRM_Contact_Form_Search_Custom_ContribSYBNT::formatSavedSearchFields($formValues);
      CRM_Core_DAO::executeQuery("UPDATE civicrm_saved_search SET form_values = %1 WHERE id = {$dao->id}", array(1 => array(serialize($formValues), 'String')));
    }
    return TRUE;
  }

  /**
   * CRM-20892 Convert default of created_date in civicrm_mailing table to NULL
   * @return bool
   */
  public static function civiMailingCreatedDateNull(CRM_Queue_TaskContext $ctx) {
    $dataType = 'timestamp';
    if (CRM_Utils_Check_Component_Timestamps::isFieldType('civicrm_mailing', 'created_date', 'datetime')) {
      $dataType = 'datetime';
    }
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_mailing CHANGE created_date created_date {$dataType} NULL DEFAULT NULL COMMENT 'Date and time this mailing was created.'");
    return TRUE;
  }

}
