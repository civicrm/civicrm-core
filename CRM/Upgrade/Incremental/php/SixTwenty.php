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
 * Upgrade logic for the 6.20.x series.
 *
 * Each minor version in the series is handled by either a `6.20.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_20_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwenty extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_20_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install Order Completion Metadata entity', 'createEntityTable', '6.20.alpha1.OrderCompletionMetadata.entityType.php');
    $this->addTask('Change case start date from date to datetime', 'alterSchemaField', 'Case', 'start_date', [
      'title' => ts('Case Start Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Date on which given case starts.'),
      'default' => 'CURRENT_TIMESTAMP',
      'add' => '1.8',
    ]);

    $this->addTask('Add WordReplacement.language', 'alterSchemaField', 'WordReplacement', 'language', [
      'title' => ts('Language'),
      'sql_type' => 'varchar(5)',
      'input_type' => 'Select',
      'description' => ts('Word Replacement Language'),
      'add' => '6.20',
      'default_callback' => ['CRM_Core_I18n', 'getLocale'],
      'pseudoconstant' => [
        'option_group_name' => 'languages',
        'key_column' => 'name',
      ],
    ]);
    $this->addTask('Update WordReplacement index and backfill language', 'updateWordReplacements');

    $this->addTask('Add time to existing cases based on time of open case activity ', 'backFillCaseStartTime');

    $this->addTask('Add ContributionPage.thankyou_mode', 'alterSchemaField', 'ContributionPage', 'thankyou_mode', [
      'title' => ts('Thank-you Mode'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Radio',
      'description' => ts('Choose between a thank you page or redirect'),
      'default' => 'page',
    ], 'AFTER `goal_amount`');

    $this->addTask('Add ContributionPage.thankyou_redirect_url', 'alterSchemaField', 'ContributionPage', 'thankyou_redirect_url', [
      'title' => ts('Thank-you Redirect URL'),
      'sql_type' => 'text',
      'input_type' => 'Url',
      'description' => ts('Set a URL to redirect users to after completion, instead of generating a thank you page'),
    ], 'AFTER `thankyou_mode`');

    $this->addTask('Register Events as taggable', 'registerEventTagUsedFor');
    $this->addTask('Add index on test column on civicrm_activity', 'addIndex', 'civicrm_activity', 'is_test');
  }

  /**
   * Allow Events to be tagged.
   */
  public static function registerEventTagUsedFor(): bool {
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'tag_used_for',
      'name' => 'Event',
      'label' => ts('Events'),
      'value' => 'civicrm_event',
    ]);
    return TRUE;
  }

  /**
   * Backfill case start time.
   *
   * We are changing the case start_date from a date to a datetime field. This
   * query uses the time from any open case activities to back fill the time
   * for previously created cases, but only if the date matches.
   *
   */
  public static function backFillCaseStartTime(CRM_Queue_TaskContext $ctx): bool {
    $sql = "SELECT ov.value AS value
      FROM
        civicrm_option_value ov INNER JOIN civicrm_option_group og ON (ov.option_group_id = og.id AND og.name = 'activity_type')
      WHERE ov.name = 'Open Case'";

    $dao = \CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $activityTypeId = $dao->value;
    if (!$activityTypeId) {
      // Maybe cases are not enabled?
      return TRUE;
    }

    $sql = "
      UPDATE civicrm_case c
        JOIN civicrm_case_activity ca ON c.id = ca.case_id
        JOIN civicrm_activity a ON ca.activity_id = a.id
      SET
        c.start_date = a.activity_date_time
      WHERE
        DATE(a.activity_date_time) = DATE(c.start_date) AND
        a.is_deleted = 0 AND
        a.activity_type_id = %0
    ";
    $params = [0 => [$activityTypeId, "Integer"]];
    \CRM_Core_DAO::executeQuery($sql, $params);

    return TRUE;
  }

  /**
   * Update WordReplacement index and backfill language.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function updateWordReplacements(CRM_Queue_TaskContext $ctx): bool {
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_word_replacement', 'temp_domain_id')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_word_replacement ADD INDEX temp_domain_id (domain_id)');
    }
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_word_replacement', 'UI_domain_find');

    // Backfill language on existing rows from domain's default language
    $domains = CRM_Core_DAO::executeQuery('SELECT id, locale_custom_strings FROM civicrm_domain');
    while ($domains->fetch()) {
      $domainId = (int) $domains->id;
      $lang = CRM_Core_DAO::singleValueQuery("SELECT v.value FROM civicrm_setting v WHERE v.name = 'lcMessages' AND v.domain_id = %1", [
        1 => [$domainId, 'Integer'],
      ]);
      $lang = $lang ? CRM_Utils_String::unserialize($lang) : 'en_US';
      if (!$lang) {
        $lang = 'en_US';
      }

      CRM_Core_DAO::executeQuery("UPDATE civicrm_word_replacement SET language = %1 WHERE domain_id = %2 AND (language IS NULL OR language = '')", [
        1 => [$lang, 'String'],
        2 => [$domainId, 'Integer'],
      ]);

      // Migrate any serialized locale_custom_strings
      if (!empty($domains->locale_custom_strings)) {
        $lcs = CRM_Utils_String::unserialize($domains->locale_custom_strings);
        if (is_array($lcs)) {
          foreach ($lcs as $locale => $statuses) {
            if (!is_array($statuses)) {
              continue;
            }
            foreach ($statuses as $status => $matchTypes) {
              if (!is_array($matchTypes)) {
                continue;
              }
              $isActive = ($status === 'enabled') ? 1 : 0;
              foreach ($matchTypes as $matchType => $words) {
                if (!is_array($words)) {
                  continue;
                }
                foreach ($words as $findWord => $replaceWord) {
                  if ($findWord === '' || $replaceWord === '') {
                    continue;
                  }
                  $exists = CRM_Core_DAO::singleValueQuery("
                    SELECT id FROM civicrm_word_replacement
                    WHERE domain_id = %1 AND find_word = %2 AND language = %3
                  ", [
                    1 => [$domainId, 'Integer'],
                    2 => [$findWord, 'String'],
                    3 => [$locale, 'String'],
                  ]);
                  if (!$exists) {
                    CRM_Core_DAO::executeQuery("
                      INSERT INTO civicrm_word_replacement (domain_id, find_word, replace_word, is_active, match_type, language)
                      VALUES (%1, %2, %3, %4, %5, %6)
                    ", [
                      1 => [$domainId, 'Integer'],
                      2 => [$findWord, 'String'],
                      3 => [$replaceWord, 'String'],
                      4 => [$isActive, 'Integer'],
                      5 => [$matchType, 'String'],
                      6 => [$locale, 'String'],
                    ]);
                  }
                }
              }
            }
          }
        }
      }
    }

    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_word_replacement', 'UI_domain_find')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_word_replacement ADD UNIQUE KEY UI_domain_find (domain_id, find_word, language)');
    }
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_word_replacement', 'temp_domain_id');

    return TRUE;
  }

}
