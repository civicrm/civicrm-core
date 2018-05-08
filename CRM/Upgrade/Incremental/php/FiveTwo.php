<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * Upgrade logic for FiveTwo */
class CRM_Upgrade_Incremental_php_FiveTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function for version 5.2.beta1. This version adds the category column
   * to case types. The category is an option value that can be Workflow or
   * Vacancy.
   *
   * @param string $rev
   */
  public function upgrade_5_2_beta1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [ 1 => $rev ]), 'runSql', $rev);

    $this->addCategoryColumnToCaseType();
    $this->createCaseTypeCategories();
    $this->setDefaultCategoriesForCaseTypes();
  }

  /**
   * Adds a new category column to the case type entity. This column references
   * option values.
   */
  private function addCategoryColumnToCaseType() {
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_case_type
      ADD COLUMN category INT(10)');
  }

  /**
   * Creates the option group and values for the case type categories. The
   * values can be Vacancy or Workflow types.
   */
  private function createCaseTypeCategories() {
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'case_type_category',
      'title' => ts('Case Type Category'),
      'is_reserved' => 1,
    ]);

    // flush the pseudo constant cache so it includes the newly created option group:
    CRM_Core_PseudoConstant::flush();

    $options = [
      ['name' => 'WORKFLOW', 'label' => ts('Workflow'), 'is_default' => TRUE],
      ['name' => 'VACANCY', 'label' => ts('Vacancy'), 'is_default' => FALSE]
    ];

    foreach ($options as $option) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'case_type_category',
        'name' => $option['name'],
        'label' => $option['label'],
        'is_default' => $option['is_default'],
        'is_active' => TRUE,
        'is_reserved' => TRUE
      ]);
    }
  }

  /**
   * Updates current case types so they have a category assigned. All case types
   * are assigned the Workflow category by default except for the Application case
   * type, which gets the Vacancy category.
   */
  private function setDefaultCategoriesForCaseTypes() {
    $caseTypes = civicrm_api3('CaseType', 'get', [
      'options' => [ 'limit' => 0 ]
    ]);

    foreach ($caseTypes['values'] as $caseType) {
      $category = $caseType['name'] === 'Application' ? 'VACANCY' : 'WORKFLOW';

      civicrm_api3('CaseType', 'create', [
        'id' => $caseType['id'],
        'category' => $category
      ]);
    }
  }

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
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission has been added called %1 This Permission is now used to control access to the Manage Tags screen', array(1 => 'manage tags')) . '</p>';
    // }
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
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
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
  //  public function upgrade_5_0_x($rev) {
  //    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  //    $this->addTask('Do the foo change', 'taskFoo', ...);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

}
