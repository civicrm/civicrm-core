<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_alpha1($rev) {
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
    $this->addTask(ts('Upgrade Add Help Pre and Post Fields to price value table'), 'addHelpPreAndHelpPostFieldsPriceFieldValue');
    $this->addTask(ts('Alter index and type for image URL'), 'alterIndexAndTypeForImageURL');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_11($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Dashboard schema updates', 'dashboardSchemaUpdate');
    $this->addTask(ts('Fill in setting "remote_profile_submissions"'), 'migrateRemoteSubmissionsSetting');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_12($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask(ts('Add Data Type column to civicrm_option_group'), 'addDataTypeColumnToOptionGroupTable');
  }

  /*
   * Important! All upgrade functions MUST call the 'runSql' task.
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
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL);
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
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'content');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'is_minimized');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'is_fullscreen');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard_contact', 'created_date');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'is_fullscreen');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'is_minimized');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'column_no');
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_dashboard', 'weight');

    CRM_Core_DAO::executeQuery('UPDATE civicrm_dashboard SET url = REPLACE(url, "&snippet=5", ""), fullscreen_url = REPLACE(fullscreen_url, "&snippet=5", "")');

    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_dashboard', 'cache_minutes')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_dashboard ADD COLUMN cache_minutes int unsigned NOT NULL DEFAULT 60 COMMENT "Number of minutes to cache dashlet content in browser localStorage."',
         array(), TRUE, NULL, FALSE, FALSE);
    }
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL);
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
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL);
    }

    CRM_Core_DAO::executeQuery("UPDATE `civicrm_option_group` SET `data_type` = 'Integer'
      WHERE name IN ('activity_type', 'gender', 'payment_instrument', 'participant_role', 'event_type')");
    return TRUE;
  }

}
