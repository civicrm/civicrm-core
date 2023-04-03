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
 * Upgrade logic for the 5.63.x series.
 *
 * Each minor version in the series is handled by either a `5.63.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_63_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function upgrade_5_63_alpha1(string $rev): void {
    $this->addTask('Add name column  to civicrm_contribution_page', 'addColumn', 'civicrm_contribution_page',
      'name', "varchar(255) NULL COMMENT 'Unique name for identifying contribution page'");
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $enabledComponents = Civi::settings()->get('enable_components');
    $extensions = array_map(['CRM_Utils_String', 'convertStringToSnakeCase'], $enabledComponents);
    $this->addExtensionTask('Enable component extensions', $extensions);

    $this->addTask('Make ContributionPage.name required', 'alterColumn', 'civicrm_contribution_page', 'name', "varchar(255) NOT NULL COMMENT 'Unique name for identifying contribution page'");
    $this->addTask('Make ContributionPage.title required', 'alterColumn', 'civicrm_contribution_page', 'title', "varchar(255) NOT NULL COMMENT 'Contribution Page title. For top of page display'", TRUE);
    $this->addTask('Make ContributionPage.frontend_title required', 'alterColumn', 'civicrm_contribution_page', 'frontend_title', "varchar(255) NOT NULL COMMENT 'Contribution Page Public title'", TRUE);
  }

}
