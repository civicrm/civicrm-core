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
 * Upgrade logic for the 6.11.x series.
 *
 * Each minor version in the series is handled by either a `6.11.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_11_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixEleven extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_11_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Set preferred language to default if undefined', 'setPreferredLanguageToDefaultIfNull');
  }

  /**
   * CiviCRM had the option to leave the preferred language to undefined.
   * This was confusing to admins because most multi-lingual organisations
   * are either careful about collecting the language, or they operate in a
   * main language and assume that any undefined are equivalent to the default
   * site language. For example, they might have "French subscribers" and
   * "English and any other language subscribers". Since "undefined" is not
   * a language, having it undefined is de facto like having it to the default
   * language.
   */
  public static function setPreferredLanguageToDefaultIfNull() {
    // If the default contact language was undefined, change it to the
    // default site language
    $default = Civi::settings()->get('contact_default_language');
    if ($default == 'undefined') {
      Civi::settings()->set('contact_default_language', 'current_site_language');
    }
    // Update all contacts who were previously undefined
    // The above $default might be "current_site_language" and needs resolving
    $language = CRM_Core_I18n::getContactDefaultLanguage();
    CRM_Core_DAO::executeQuery('UPDATE civicrm_contact SET preferred_language = %1 WHERE preferred_language IS NULL OR preferred_language = ""', [
      1 => [$language, 'String'],
    ]);
    return TRUE;
  }

}
