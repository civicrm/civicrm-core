<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Upgrade logic for FiveTwenty */
class CRM_Upgrade_Incremental_php_FiveTwenty extends CRM_Upgrade_Incremental_Base {

  /**
   * @var $relationshipTypes array
   *   api call result keyed on relationship_type.id
   */
  protected static $relationshipTypes;

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
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
    if ($rev == '5.20.alpha1') {
      if (CRM_Core_DAO::checkTableExists('civicrm_persistent') && CRM_Core_DAO::checkTableHasData('civicrm_persistent')) {
        $preUpgradeMessage .= '<br/>' . ts("WARNING: The table \"<code>civicrm_persistent</code>\" is flagged for removal because all official records show it being unused. However, the upgrader has detected data in this copy of \"<code>civicrm_persistent</code>\". Please <a href='%1' target='_blank'>report</a> anything you can about the usage of this table. In the mean-time, the data will be preserved.", [
          1 => 'https://civicrm.org/bug-reporting',
        ]);
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

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_20_alpha1($rev) {
    $this->addTask('Add frontend title column to contribution page table', 'addColumn', 'civicrm_contribution_page',
      'frontend_title', "varchar(255) DEFAULT NULL COMMENT 'Contribution Page Public title'", TRUE, '5.20.alpha1');
    $this->addTask('Add is_template field to civicrm_contribution', 'addColumn', 'civicrm_contribution', 'is_template',
      "tinyint(4) DEFAULT '0' COMMENT 'Shows this is a template for recurring contributions.'", FALSE, '5.20.alpha1');
    $this->addTask('Add order_reference field to civicrm_financial_trxn', 'addColumn', 'civicrm_financial_trxn', 'order_reference',
      "varchar(255) COMMENT 'Payment Processor external order reference'", FALSE, '5.20.alpha1');
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviCase', $config->enableComponents)) {
      $this->addTask('Change direction of autoassignees in case type xml', 'changeCaseTypeAutoassignee');
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add "Template" contribution status', 'templateStatus');
    $this->addTask('Update smart groups to rename filters on case_from and case_to to case_start_date and case_end_date', 'updateSmartGroups', [
      'renameField' => [
        ['old' => 'case_from_relative', 'new' => 'case_start_date_relative'],
        ['old' => 'case_from_start_date_high', 'new' => 'case_start_date_high'],
        ['old' => 'case_from_start_date_low', 'new' => 'case_start_date_low'],
        ['old' => 'case_to_relative', 'new' => 'case_end_date_relative'],
        ['old' => 'case_to_end_date_high', 'new' => 'case_end_date_high'],
        ['old' => 'case_to_end_date_low', 'new' => 'case_end_date_low'],
        ['old' => 'mailing_date_relative', 'new' => 'mailing_job_start_date_relative'],
        ['old' => 'mailing_date_high', 'new' => 'mailing_job_start_date_high'],
        ['old' => 'mailing_date_low', 'new' => 'mailing_job_start_date_low'],
        ['old' => 'relation_start_date_low', 'new' => 'relationship_start_date_low'],
        ['old' => 'relation_start_date_high', 'new' => 'relationship_start_date_high'],
        ['old' => 'relation_start_date_relative', 'new' => 'relationship_start_date_relative'],
        ['old' => 'relation_end_date_low', 'new' => 'relationship_end_date_low'],
        ['old' => 'relation_end_date_high', 'new' => 'relationship_end_date_high'],
        ['old' => 'relation_end_date_relative', 'new' => 'relationship_end_date_relative'],
        ['old' => 'event_start_date_low', 'new' => 'event_low'],
        ['old' => 'event_end_date_high', 'new' => 'event_high'],
      ],
    ]);
    $this->addTask('Convert Log date searches to their final names either created date or modified date', 'updateSmartGroups', [
      'renameLogFields' => [],
    ]);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'birth_date',
        'deceased_date',
        'case_start_date',
        'case_end_date',
        'mailing_job_start_date',
        'relationship_start_date',
        'relationship_end_date',
        'event',
        'relation_active_period_date',
        'created_date',
        'modified_date',
      ],
    ]);
    $this->addTask('Clean up unused table "civicrm_persistent"', 'dropTableIfEmpty', 'civicrm_persistent');
    $this->addTask('Convert Custom data based smart groups from jcalendar to datepicker', 'updateSmartGroups', [
      'convertCustomSmartGroups' => NULL,
    ]);
  }

  public static function templateStatus(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'contribution_status',
      'name' => 'Template',
      'label' => ts('Template'),
      'is_active' => TRUE,
      'component_id' => 'CiviContribute',
    ]);
    return TRUE;
  }

  /**
   * Change direction of activity autoassignees in case type xml for
   * bidirectional relationship types if they point the other way. This is
   * mostly a visual issue on the case type edit screen and doesn't affect
   * normal operation, but could lead to confusion and a future mixup.
   * (dev/core#1046)
   * ONLY for ones using database storage - don't want to "fork" case types
   * that aren't currently forked.
   *
   * Earlier iterations of this used the api and array manipulation
   * and then another iteration used SimpleXML manipulation, but both
   * suffered from weirdnesses in how conversion back and forth worked.
   *
   * Here we use SQL and a regex. The thing we're changing is pretty
   * well-defined and unique:
   * <default_assignee_relationship>N_b_a</default_assignee_relationship>
   *
   * @return bool
   */
  public static function changeCaseTypeAutoassignee() {
    self::$relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];

    // Get all case types definitions that are using db storage
    $dao = CRM_Core_DAO::executeQuery("SELECT id, definition FROM civicrm_case_type WHERE definition IS NOT NULL AND definition <> ''");
    while ($dao->fetch()) {
      self::processCaseTypeAutoassignee($dao->id, $dao->definition);
    }
    return TRUE;
  }

  /**
   * Process a single case type
   *
   * @param $caseTypeId int
   * @param $definition string
   *   xml string
   */
  public static function processCaseTypeAutoassignee($caseTypeId, $definition) {
    $isDirty = FALSE;
    // find the autoassignees
    preg_match_all('/<default_assignee_relationship>(.*?)<\/default_assignee_relationship>/', $definition, $matches);
    // $matches[1][n] has the text inside the xml tag, e.g. 2_a_b
    foreach ($matches[1] as $index => $match) {
      if (empty($match)) {
        continue;
      }
      // parse out existing id and direction
      list($relationshipTypeId, $direction1) = explode('_', $match);
      // we only care about ones that are b_a
      if ($direction1 === 'b') {
        // we only care about bidirectional
        if (self::isBidirectionalRelationship($relationshipTypeId)) {
          // flip it to be a_b
          // $matches[0][n] has the whole match including the xml tag
          $definition = str_replace($matches[0][$index], "<default_assignee_relationship>{$relationshipTypeId}_a_b</default_assignee_relationship>", $definition);
          $isDirty = TRUE;
        }
      }
    }

    if ($isDirty) {
      $sqlParams = [
        1 => [$definition, 'String'],
        2 => [$caseTypeId, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery("UPDATE civicrm_case_type SET definition = %1 WHERE id = %2", $sqlParams);
      //echo "UPDATE civicrm_case_type SET definition = '" . CRM_Core_DAO::escapeString($sqlParams[1][0]) . "' WHERE id = {$sqlParams[2][0]}\n";
    }
  }

  /**
   * Check if this is bidirectional, based on label. In the situation where
   * we're using this we don't care too much about the edge case where name
   * might not also be bidirectional.
   *
   * @param $relationshipTypeId int
   *
   * @return bool
   */
  private static function isBidirectionalRelationship($relationshipTypeId) {
    if (isset(self::$relationshipTypes[$relationshipTypeId])) {
      if (self::$relationshipTypes[$relationshipTypeId]['label_a_b'] === self::$relationshipTypes[$relationshipTypeId]['label_b_a']) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
