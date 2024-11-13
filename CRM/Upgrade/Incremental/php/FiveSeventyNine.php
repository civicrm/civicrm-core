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
 * Upgrade logic for the 5.79.x series.
 *
 * Each minor version in the series is handled by either a `5.79.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_79_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyNine extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$message, $rev, $current = NULL) {
    if ($rev == '5.79.alpha1') {
      $tokenForms = static::findAfformsWithMsgToken();
      if (!empty($tokenForms)) {
        $formList = implode(', ', array_map(fn($name) => '<em>"' . htmlentities($name) . '"</em>', $tokenForms));
        $message .= '<p>' . ts('Some custom forms (%1) support authenticated email links. Please review the <strong><a %2>CiviCRM 5.79 Form-Token Notice</a></strong>.', [
          1 => $formList,
          2 => 'href="https://lab.civicrm.org/dev/core/-/wikis/CiviCRM-v5.79-Form-Token-Notice" target="_blank"',
        ]) . '</p>';
      }
    }
  }

  public static function findAfformsWithMsgToken(): array {
    if (!class_exists('CRM_Afform_AfformScanner')) {
      return [];
    }
    $scanner = new CRM_Afform_AfformScanner(
      new CRM_Utils_Cache_ArrayCache([])
    );
    $matches = [];
    foreach ($scanner->getMetas() as $name => $meta) {
      if (in_array('msg_token', $meta['placement'] ?? [])) {
        $matches[] = $name;
      }
    }
    return $matches;
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_79_alpha1($rev): void {
    $this->addTask('Add Financial Type.label field', 'addColumn', 'civicrm_financial_type', 'label', "varchar(64) NOT NULL COMMENT 'User-facing financial type label' AFTER `name`", TRUE);
    $this->addTask('Add Financial Account.label field', 'addColumn', 'civicrm_financial_account', 'label', "varchar(64) NOT NULL COMMENT 'User-facing financial account label' AFTER `name`", TRUE);
    $this->addTask('Populate financial labels', 'populateFinancialLabels');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update "Website Type" options', 'updateWebsiteType');

    Civi::queue(CRM_Upgrade_Form::QUEUE_NAME)->createItem(
      new CRM_Queue_Task([static::CLASS, 'checkEnableLoginTokens']),
      ['weight' => 2001]
    );
  }

  public static function checkEnableLoginTokens(CRM_Queue_TaskContext $ctx) {
    if (static::findAfformsWithMsgToken()) {
      $ctx->queue->createItem(
        new CRM_Queue_Task([static::CLASS, 'enableExtension'], [['afform_login_token']], 'Enable Form Core Login Tokens'),
        ['weight' => 2000]
      );
    }
    return TRUE;
  }

  /**
   * Even though we just added the label field, if the upgrade is rerun later
   * we don't want to clobber any changes, so only update if blank.
   */
  public static function populateFinancialLabels() {
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("UPDATE `civicrm_financial_type` SET label_{$locale} = `name` WHERE label_{$locale} = ''", [], TRUE, NULL, FALSE, FALSE);
        CRM_Core_DAO::executeQuery("UPDATE `civicrm_financial_account` SET label_{$locale} = `name` WHERE label_{$locale} = ''", [], TRUE, NULL, FALSE, FALSE);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_financial_type` SET  `label` = `name` WHERE label = ''", [], TRUE, NULL, FALSE, FALSE);
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_financial_account` SET  `label` = `name` WHERE label = ''", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Delete branded website-type options that are not in use.
   * Add new "Social" option.
   */
  public static function updateWebsiteType() {
    $query = CRM_Core_DAO::executeQuery("SELECT `id`, `value` FROM `civicrm_option_value`
      WHERE `option_group_id` = (SELECT `id` FROM `civicrm_option_group` WHERE `name` = 'website_type')
      AND `name` NOT IN ('Work', 'Main', 'Social')
      AND `value` NOT IN (SELECT `website_type_id` FROM `civicrm_website`)");
    $types = $query->fetchMap('value', 'id');
    if ($types) {
      CRM_Core_DAO::executeQuery('DELETE FROM `civicrm_option_value` WHERE `id` IN (' . implode(', ', $types) . ')');
    }
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'website_type',
      'name' => 'Social',
      'label' => ts('Social'),
    ]);
    return TRUE;
  }

}
