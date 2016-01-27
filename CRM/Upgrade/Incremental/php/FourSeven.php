<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
    $this->addTask('Add Index to civicrm_contribution creditnote_id field', 'addIndexContributionCreditNoteID');
  }

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
  public function migrateSettings(CRM_Queue_TaskContext $ctx) {
    // Tip: If there are problems with adding the new uniqueness index, try inspecting:
    // SELECT name, domain_id, contact_id, count(*) AS dupes FROM civicrm_setting cs GROUP BY name, domain_id, contact_id HAVING dupes > 1;

    // Nav records are expendable. https://forum.civicrm.org/index.php?topic=36933.0
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_setting WHERE contact_id IS NOT NULL AND name = "navigation"');

    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_setting DROP INDEX index_group_name');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_setting DROP COLUMN group_name');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_setting ADD UNIQUE INDEX index_domain_contact_name (domain_id, contact_id, name)');

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
  public function addGettingStartedDashlet(CRM_Queue_TaskContext $ctx) {
    $sql = "SELECT count(*) FROM civicrm_dashboard WHERE name='gettingStarted'";
    $res = CRM_Core_DAO::singleValueQuery($sql);
    $domainId = CRM_Core_Config::domainID();
    if ($res <= 0) {
      $sql = "INSERT INTO `civicrm_dashboard`
    ( `domain_id`, `name`, `label`, `url`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `is_active`, `weight`, `fullscreen_url`, `is_fullscreen`, `is_reserved`) VALUES ( {$domainId}, 'getting-started', 'Getting Started', 'civicrm/dashlet/getting-started?reset=1&snippet=5', 'access CiviCRM', NULL, 0, 0, 1, 0, 'civicrm/dashlet/getting-started?reset=1&snippet=5&context=dashletFullscreen', 1, 1)";
      CRM_Core_DAO::executeQuery($sql);
      // Add default position for Getting Started Dashlet ( left column)
      $sql = "INSERT INTO `civicrm_dashboard_contact` (dashboard_id, contact_id, column_no, is_active)
SELECT (SELECT MAX(id) FROM `civicrm_dashboard`), contact_id, 0, IF (SUM(is_active) > 0, 1, 0)
FROM `civicrm_dashboard_contact` WHERE 1 GROUP BY contact_id";
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

    $forOrgColums = array();
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
   * CRM-11782 - Get rid of VALUE_SEPARATOR character in saved search form values
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public function fixContactTypeInSmartGroups(CRM_Queue_TaskContext $ctx) {
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
  public function deleteVersionCheckCacheFile(CRM_Queue_TaskContext $ctx) {
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
  public function disableFlexibleJobsExtension(CRM_Queue_TaskContext $ctx) {
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
  public function addIndexFinancialTrxnTrxnID(CRM_Queue_TaskContext $ctx) {
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
  public function addIndexContributionCreditNoteID(CRM_Queue_TaskContext $ctx) {
    $tables = array('civicrm_contribution' => array('creditnote_id'));
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    return TRUE;
  }

}
