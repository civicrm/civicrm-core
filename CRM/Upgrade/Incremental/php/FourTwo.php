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
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for 4.2
 */
class CRM_Upgrade_Incremental_php_FourTwo extends CRM_Upgrade_Incremental_Base {
  const SETTINGS_SNIPPET_PATTERN = '/CRM_Core_ClassLoader::singleton\(\)-\>register/';
  const SETTINGS_SNIPPET = "\nrequire_once 'CRM/Core/ClassLoader.php';\nCRM_Core_ClassLoader::singleton()->register();\n";

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.2.alpha1', '4.2.beta3', '4.2.0'.
   * @param null $currentVer
   *
   * @return bool
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '4.2.alpha1') {
      $tables = array('civicrm_contribution_page', 'civicrm_event', 'civicrm_group', 'civicrm_contact');
      if (!CRM_Core_DAO::schemaRequiresRebuilding($tables)) {
        $errors = "The upgrade has identified some schema integrity issues in the database. It seems some of your constraints are missing. You will have to rebuild your schema before re-trying the upgrade. Please refer to " . CRM_Utils_System::docURL2("Ensuring Schema Integrity on Upgrades", FALSE, "Ensuring Schema Integrity on Upgrades", NULL, NULL, "wiki");
        CRM_Core_Error::fatal($errors);
        return FALSE;
      }

      // CRM-10613, CRM-11120
      $query = "
SELECT mp.contribution_id, mp.membership_id, mem.membership_type_id, mem.start_date, mem.end_date, mem.status_id, mem.contact_id
FROM civicrm_membership_payment mp
INNER JOIN ( SELECT cmp.contribution_id
              FROM civicrm_membership_payment cmp
              LEFT JOIN civicrm_line_item cli ON cmp.contribution_id=cli.entity_id and cli.entity_table = 'civicrm_contribution'
              WHERE cli.entity_id IS NULL
              GROUP BY cmp.contribution_id
              HAVING COUNT(cmp.membership_id) > 1) submp ON submp.contribution_id = mp.contribution_id
INNER JOIN civicrm_membership mem ON mem.id = mp.membership_id
ORDER BY mp.contribution_id, mp.membership_id";
      $invalidData = CRM_Core_DAO::executeQuery($query);
      if ($invalidData->N) {
        $invalidDataMessage = "<br /><strong>" . 'The upgrade is being aborted due to data integrity issues in your database. There are multiple membership records linked to the same contribution record. This is unexpected, and some of the membership records may be duplicates. The problem record sets are listed below. Refer to <a href="http://wiki.civicrm.org/confluence/display/CRMDOC42/Repair+database+script+for+4.2+upgrades">this wiki page for instructions on repairing your database</a> so that you can run the upgrade successfully.' . "</strong>";
        $membershipType = CRM_Member_PseudoConstant::membershipType();
        $membershipStatus = CRM_Member_PseudoConstant::membershipStatus();
        $invalidDataMessage .= "<table border=1><tr><th>Contact-ID</th><th>Contribution-ID</th><th>Membership-ID</th><th>Membership Type</th><th>Start Date</th><th>End Date</th><th>Membership Status</th></tr>";
        while ($invalidData->fetch()) {
          $invalidDataMessage .= "<tr>";
          $invalidDataMessage .= "<td>{$invalidData->contact_id}</td>";
          $invalidDataMessage .= "<td>{$invalidData->contribution_id}</td>";
          $invalidDataMessage .= "<td>{$invalidData->membership_id}</td>";
          $invalidDataMessage .= "<td>" . CRM_Utils_Array::value($invalidData->membership_type_id, $membershipType) . "</td>";
          $invalidDataMessage .= "<td>{$invalidData->start_date}</td>";
          $invalidDataMessage .= "<td>{$invalidData->end_date}</td>";
          $invalidDataMessage .= "<td>" . CRM_Utils_Array::value($invalidData->status_id, $membershipStatus) . "</td>";
          $invalidDataMessage .= "</tr>";
        }
        $clickHere = CRM_Utils_System::url('civicrm/upgrade/cleanup425', 'reset=1');
        $invalidDataMessage .= "</table><p>If you have reviewed the cleanup script documentation on the wiki and you are ready to run the cleanup now - <a href='$clickHere'>click here</a>.</p>";
        CRM_Core_Error::fatal($invalidDataMessage);
        return FALSE;
      }
    }

    if ($rev == '4.2.beta2') {
      // note: error conditions are also checked in upgrade_4_2_beta2()
      if (!defined('CIVICRM_SETTINGS_PATH')) {
        $preUpgradeMessage .= '<br />' . ts('Could not determine path to civicrm.settings.php. Please manually locate it and add these lines at the bottom: <pre>%1</pre>', array(
            1 => self::SETTINGS_SNIPPET,
          ));
      }
      elseif (preg_match(self::SETTINGS_SNIPPET_PATTERN, file_get_contents(CIVICRM_SETTINGS_PATH))) {
        // OK, nothing to do
      }
      elseif (!is_writable(CIVICRM_SETTINGS_PATH)) {
        $preUpgradeMessage .= '<br />' . ts('The settings file (%1) must be updated. Please make it writable or manually add these lines:<pre>%2</pre>', array(
            1 => CIVICRM_SETTINGS_PATH,
            2 => self::SETTINGS_SNIPPET,
          ));
      }
    }
    if ($rev == '4.2.2' && version_compare($currentVer, '3.3.alpha1') >= 0) {
      $query = " SELECT cli.id
FROM `civicrm_line_item` cli
INNER JOIN civicrm_membership_payment cmp ON cmp.contribution_id = cli.entity_id AND cli.entity_table = 'civicrm_contribution'
INNER JOIN civicrm_price_field_value cpfv ON cpfv.id = cli.price_field_value_id
INNER JOIN civicrm_price_field cpf ON cpf.id = cpfv.price_field_id and cpf.id != cli.price_field_id
INNER JOIN civicrm_price_set cps ON cps.id = cpf.price_set_id AND cps.name <>'default_membership_type_amount' ";
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->N) {
        $preUpgradeMessage .= "<br /><strong>We have identified extraneous data in your database that a previous upgrade likely introduced. We STRONGLY recommend making a backup of your site before continuing. We also STRONGLY suggest fixing this issue with unneeded records BEFORE you upgrade. You can find more information about this issue and the way to fix it by visiting <a href='http://forum.civicrm.org/index.php/topic,26181.0.html'>http://forum.civicrm.org/index.php/topic,26181.0.html</a>.</strong>";
      }
    }

    if (version_compare($rev, '4.2.9') >= 0) {
      //CRM-11980
      $sql = "SELECT id FROM civicrm_option_group WHERE name LIKE 'civicrm_price_field.amount.%' LIMIT 1";
      $dao = CRM_Core_DAO::executeQuery($sql);
      if ($dao->fetch()) {
        $errors = "We found unexpected data values from an older version of CiviCRM in your database. The upgrade can not be run until this condition is corrected.<br /><br />Details: One or more rows are present in the civicrm_option_group with name like 'civicrm_price_field.amount.%'. <a href='http://forum.civicrm.org/index.php/topic,27744.msg118748.html#msg118748'>Check here for information on diagnosing and correcting this problem.</a>";
        CRM_Core_Error::fatal($errors);
        return FALSE;
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
    if ($rev == '4.2.beta5') {
      $config = CRM_Core_Config::singleton();
      if (!empty($config->extensionsDir)) {
        $postUpgradeMessage .= '<br />' . ts('Please <a href="%1" target="_blank">configure the Extension Resource URL</a>.', array(
            1 => CRM_Utils_System::url('civicrm/admin/setting/url', 'reset=1'),
          ));
      }
    }
    if ($rev == '4.2.7') {
      $postUpgradeMessage .= '<br />' . ts('If you have configured a report instance to allow anonymous access, you will need to reset the permission to Everyone for that instance (under the Report Settings pane).');
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_alpha1($rev) {
    //checking whether the foreign key exists before dropping it
    //drop foreign key queries of CRM-9850
    $params = array();
    $tables = array(
      'civicrm_contribution_page' => 'FK_civicrm_contribution_page_payment_processor_id',
      'civicrm_event' => 'FK_civicrm_event_payment_processor_id',
      'civicrm_group' => 'FK_civicrm_group_saved_search_id',
    );
    foreach ($tables as $tableName => $fKey) {
      $foreignKeyExists = CRM_Core_DAO::checkConstraintExists($tableName, $fKey);
      if ($foreignKeyExists) {
        CRM_Core_DAO::executeQuery("ALTER TABLE {$tableName} DROP FOREIGN KEY {$fKey}", $params, TRUE, NULL, FALSE, FALSE);
        CRM_Core_DAO::executeQuery("ALTER TABLE {$tableName} DROP INDEX {$fKey}", $params, TRUE, NULL, FALSE, FALSE);
      }
    }
    // Drop index UI_title for civicrm_price_set
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      foreach ($locales as $locale) {
        $query = "SHOW KEYS FROM `civicrm_price_set` WHERE key_name = 'UI_title_{$locale}'";
        $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
        if ($dao->N) {
          CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_price_set` DROP INDEX `UI_title_{$locale}`", $params, TRUE, NULL, FALSE, FALSE);
        }
      }
    }
    else {
      $query = "SHOW KEYS FROM `civicrm_price_set` WHERE key_name = 'UI_title'";
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->N) {
        CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_price_set` DROP INDEX `UI_title`");
      }
    }

    // Some steps take a long time, so we break them up into separate
    // tasks and enqueue them separately.
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.2.alpha1')), 'runSql', $rev);
    $this->addTask('Upgrade DB to 4.2.alpha1: Price Sets', 'task_4_2_alpha1_createPriceSets', $rev);
    self::convertContribution();
    $this->addTask('Upgrade DB to 4.2.alpha1: Event Profile', 'task_4_2_alpha1_eventProfile');
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_beta2($rev) {
    // note: error conditions are also checked in setPreUpgradeMessage()
    if (defined('CIVICRM_SETTINGS_PATH')) {
      if (!preg_match(self::SETTINGS_SNIPPET_PATTERN, file_get_contents(CIVICRM_SETTINGS_PATH))) {
        if (is_writable(CIVICRM_SETTINGS_PATH)) {
          file_put_contents(CIVICRM_SETTINGS_PATH, self::SETTINGS_SNIPPET, FILE_APPEND);
        }
      }
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_beta3($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.2.beta3')), 'runSql', $rev);
    $minParticipantId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_participant');
    $maxParticipantId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_participant');

    for ($startId = $minParticipantId; $startId <= $maxParticipantId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = "Upgrade DB to 4.2.alpha1: Participant ($startId => $endId)";
      $this->addTask($title, 'task_4_2_alpha1_convertParticipants', $startId, $endId);
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_beta5($rev) {
    // CRM-10629 Create a setting for extension URLs
    // For some reason, this isn't working when placed in the .sql file
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_setting(group_name,name,value,domain_id,is_domain)
      VALUES ('URL Preferences', 'extensionsURL',NULL,1,1);
    ");
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_0($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.2.0')), 'runSql', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_2($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.2.2')), 'runSql', $rev);
    //create line items for memberships and participants for api/import
    self::convertContribution();

    // CRM-10937 Fix the title on civicrm_dedupe_rule_group
    $upgrade = new CRM_Upgrade_Form();
    if ($upgrade->multilingual) {
      // Check if the 'title' field exists
      $query = "SELECT column_name
                  FROM information_schema.COLUMNS
                 WHERE table_name = 'civicrm_dedupe_rule_group'
                   AND table_schema = DATABASE()
                   AND column_name = 'title'";

      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->N) {
        $domain = new CRM_Core_DAO_Domain();
        $domain->find(TRUE);

        if ($domain->locales) {
          $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
          $locale = array_shift($locales);

          // Use the first language (they should all have the same value)
          CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_dedupe_rule_group` CHANGE `title_{$locale}` `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label of the rule group'", $params, TRUE, NULL, FALSE, FALSE);

          // Drop remaining the column for the remaining languages
          foreach ($locales as $locale) {
            CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_dedupe_rule_group` DROP `title_{$locale}`", $params, TRUE, NULL, FALSE, FALSE);
          }
        }
      }
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_3($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.2.3')), 'runSql', $rev);
    // CRM-10953 Remove duplicate activity type for 'Reminder Sent' which is mistakenly inserted by 4.2.alpha1 upgrade script
    $queryMin = "
SELECT coalesce(min(value),0) from civicrm_option_value ov
WHERE ov.option_group_id =
  (SELECT id from civicrm_option_group og WHERE og.name = 'activity_type') AND
ov.name = 'Reminder Sent'";

    $minReminderSent = CRM_Core_DAO::singleValueQuery($queryMin);

    $queryMax = "
SELECT coalesce(max(value),0) from civicrm_option_value ov
WHERE ov.option_group_id =
  (SELECT id from civicrm_option_group og WHERE og.name = 'activity_type') AND
ov.name = 'Reminder Sent'";

    $maxReminderSent = CRM_Core_DAO::singleValueQuery($queryMax);

    // If we have two different values, replace new value with original in any activities
    if ($maxReminderSent > $minReminderSent) {
      $query = "
UPDATE civicrm_activity
SET activity_type_id = {$minReminderSent}
WHERE activity_type_id = {$maxReminderSent}";

      CRM_Core_DAO::executeQuery($query);

      // Then delete the newer (duplicate) option_value row
      $query = "
DELETE from civicrm_option_value
  WHERE option_group_id =
    (SELECT id from civicrm_option_group og WHERE og.name = 'activity_type') AND
  value = '{$maxReminderSent}'";

      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_2_5($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.2.5')), 'runSql', $rev);
    //CRM-11077
    $sql = " SELECT cpse.entity_id, cpse.price_set_id
FROM `civicrm_price_set_entity` cpse
LEFT JOIN civicrm_price_set cps ON cps.id = cpse.price_set_id
LEFT JOIN civicrm_price_set_entity cpse1 ON cpse1.price_set_id = cpse.price_set_id
WHERE cpse.entity_table = 'civicrm_event'  AND cps.is_quick_config = 1
GROUP BY cpse.id
HAVING COUNT(cpse.price_set_id) > 1 AND MIN(cpse1.id) <> cpse.id ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if ($dao->price_set_id) {
        $copyPriceSet = &CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set::copy($dao->price_set_id);
        CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set::addTo('civicrm_event', $dao->entity_id, $copyPriceSet->id);
      }
    }
  }

  public function convertContribution() {
    $minContributionId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxContributionId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minContributionId; $startId <= $maxContributionId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = "Upgrade DB to 4.2.alpha1: Contributions ($startId => $endId)";
      $this->addTask($title, 'task_4_2_alpha1_convertContributions', $startId, $endId);
    }
    $minParticipantId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_participant');
    $maxParticipantId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_participant');

    for ($startId = $minParticipantId; $startId <= $maxParticipantId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = "Upgrade DB to 4.2.alpha1: Participant ($startId => $endId)";
      $this->addTask($title, 'task_4_2_alpha1_convertParticipants', $startId, $endId);
    }
  }

  /**
   * (Queue Task Callback)
   *
   * Upgrade code to create priceset for contribution pages and events
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $rev
   *
   * @return bool
   */
  public static function task_4_2_alpha1_createPriceSets(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $daoName = array(
      'civicrm_contribution_page' => array(
        'CRM_Contribute_BAO_ContributionPage',
        CRM_Core_Component::getComponentID('CiviContribute'),
      ),
      'civicrm_event' => array(
        'CRM_Event_BAO_Event',
        CRM_Core_Component::getComponentID('CiviEvent'),
      ),
    );

    // get all option group used for event and contribution page
    $query = "
SELECT id, name
FROM   civicrm_option_group
WHERE  name LIKE '%.amount.%' ";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $addTo = explode('.', $dao->name);
      if (!empty($addTo[2])) {
        $options = array('optionGroup' => $dao->name);
        self::createPriceSet($daoName, $addTo, $options);
      }
      CRM_Core_OptionGroup::deleteAssoc($dao->name);
    }

    //create pricesets for contribution with only other amount
    $query = "
SELECT    ccp.id as contribution_page_id, ccp.is_allow_other_amount, cmb.id as membership_block_id
FROM      civicrm_contribution_page ccp
LEFT JOIN civicrm_membership_block cmb ON  cmb.entity_id = ccp.id AND cmb.entity_table = 'civicrm_contribution_page'
LEFT JOIN civicrm_price_set_entity cpse ON cpse.entity_id = ccp.id and cpse.entity_table = 'civicrm_contribution_page'
WHERE     cpse.price_set_id IS NULL";
    $dao = CRM_Core_DAO::executeQuery($query);
    $addTo = array('civicrm_contribution_page');
    while ($dao->fetch()) {
      $addTo[2] = $dao->contribution_page_id;
      $options = array(
        'otherAmount' => $dao->is_allow_other_amount,
        'membership' => $dao->membership_block_id,
      );
      self::createPriceSet($daoName, $addTo, $options);
    }

    return TRUE;
  }

  /**
   * Create price sets.
   *
   * @param string $daoName
   * @param string $addTo
   * @param array $options
   */
  public static function createPriceSet($daoName, $addTo, $options = array()) {
    $query = "SELECT title FROM {$addTo[0]} where id =%1";
    $setParams['title'] = CRM_Core_DAO::singleValueQuery($query,
      array(1 => array($addTo[2], 'Integer'))
    );
    $pageTitle = strtolower(CRM_Utils_String::munge($setParams['title'], '_', 245));

    // an event or contrib page has been deleted but left the option group behind - (this may be fixed in later versions?)
    // we should probably delete the option group - but at least early exit here as the code following it does not fatal
    // CRM-10298
    if (empty($pageTitle)) {
      return;
    }

    $optionValue = array();
    if (!empty($options['optionGroup'])) {
      CRM_Core_OptionGroup::getAssoc($options['optionGroup'], $optionValue);
      if (empty($optionValue)) {
        return;
      }
    }
    elseif (empty($options['otherAmount']) && empty($options['membership'])) {
      //CRM-12273
      //if options group, otherAmount, membersip is empty then return, contribution should be default price set
      return;
    }

    if (!CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set', $pageTitle, 'id', 'name', TRUE)) {
      $setParams['name'] = $pageTitle;
    }
    else {
      $timeSec = explode(".", microtime(TRUE));
      $setParams['name'] = $pageTitle . '_' . date('is', $timeSec[0]) . $timeSec[1];
    }
    $setParams['extends'] = $daoName[$addTo[0]][1];
    $setParams['is_quick_config'] = 1;
    $priceSet = CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set::create($setParams);
    CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set::addTo($addTo[0], $addTo[2], $priceSet->id, 1);

    $fieldParams['price_set_id'] = $priceSet->id;
    if (!empty($options['optionGroup'])) {
      $fieldParams['html_type'] = 'Radio';
      $fieldParams['is_required'] = 1;
      if ($addTo[0] == 'civicrm_event') {
        $query = "SELECT fee_label FROM civicrm_event where id =%1";
        $fieldParams['name'] = $fieldParams['label'] = CRM_Core_DAO::singleValueQuery($query,
          array(1 => array($addTo[2], 'Integer'))
        );
        $defaultAmountColumn = 'default_fee_id';
      }
      else {
        $options['membership'] = 1;
        $fieldParams['name'] = strtolower(CRM_Utils_String::munge("Contribution Amount", '_', 245));
        $fieldParams['label'] = "Contribution Amount";
        $defaultAmountColumn = 'default_amount_id';
        $options['otherAmount'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $addTo[2], 'is_allow_other_amount');
        if (!empty($options['otherAmount'])) {
          $fieldParams['is_required'] = 0;
        }
      }
      $fieldParams['option_label'] = $optionValue['label'];
      $fieldParams['option_amount'] = $optionValue['value'];
      $fieldParams['option_weight'] = $optionValue['weight'];
      $fieldParams['is_quick_config'] = $setParams['is_quick_config'];
      if ($defaultAmount = CRM_Core_DAO::getFieldValue($daoName[$addTo[0]][0], $addTo[2], $defaultAmountColumn)) {
        $fieldParams['default_option'] = array_search($defaultAmount, $optionValue['amount_id']);
      }
      $priceField = CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::create($fieldParams);

    }
    if (!empty($options['membership'])) {
      $dao = new CRM_Member_DAO_MembershipBlock();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $addTo[2];

      if ($dao->find(TRUE)) {
        if ($dao->membership_types) {
          $fieldParams = array(
            'name' => strtolower(CRM_Utils_String::munge("Membership Amount", '_', 245)),
            'label' => "Membership Amount",
            'is_required' => $dao->is_required,
            'is_display_amounts' => $dao->display_min_fee,
            'is_active' => $dao->is_active,
            'price_set_id' => $priceSet->id,
            'html_type' => 'Radio',
            'weight' => 1,
          );
          $membershipTypes = unserialize($dao->membership_types);
          $rowcount = 0;
          foreach ($membershipTypes as $membershipType => $autoRenew) {
            $membershipTypeDetail = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membershipType);
            $rowcount++;
            $fieldParams['option_label'][$rowcount] = $membershipTypeDetail['name'];
            $fieldParams['option_amount'][$rowcount] = $membershipTypeDetail['minimum_fee'];
            $fieldParams['option_weight'][$rowcount] = $rowcount;
            $fieldParams['membership_type_id'][$rowcount] = $membershipType;
            if ($membershipType == $dao->membership_type_default) {
              $fieldParams['default_option'] = $rowcount;
            }
          }
          $priceField = CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::create($fieldParams);

          $setParams = array(
            'id' => $priceSet->id,
            'extends' => CRM_Core_Component::getComponentID('CiviMember'),
            'contribution_type_id' => CRM_Core_DAO::getFieldValue($daoName[$addTo[0]][0], $addTo[2], 'contribution_type_id'),
          );
          CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set::create($setParams);
        }
      }
    }
    if (!empty($options['otherAmount'])) {

      $fieldParams = array(
        'name' => strtolower(CRM_Utils_String::munge("Other Amount", '_', 245)),
        'label' => "Other Amount",
        'is_required' => 0,
        'is_display_amounts' => 0,
        'is_active' => 1,
        'price_set_id' => $priceSet->id,
        'html_type' => 'Text',
        'weight' => 3,
      );
      $fieldParams['option_label'][1] = "Other Amount";
      $fieldParams['option_amount'][1] = 1;
      $fieldParams['option_weight'][1] = 1;
      $priceField = CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::create($fieldParams);
    }
  }

  /**
   * (Queue Task Callback)
   *
   * Find any contribution records and create corresponding line-item
   * records.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param int $startId
   *   the first/lowest contribution ID to convert.
   * @param int $endId
   *   the last/highest contribution ID to convert.
   *
   * @return bool
   */
  public static function task_4_2_alpha1_convertContributions(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    $upgrade = new CRM_Upgrade_Form();
    $query = "
 INSERT INTO civicrm_line_item(`entity_table` ,`entity_id` ,`price_field_id` ,`label` , `qty` ,`unit_price` ,`line_total` ,`participant_count` ,`price_field_value_id`)
 SELECT 'civicrm_contribution',cc.id, cpf.id as price_field_id, cpfv.label, 1, cc.total_amount, cc.total_amount line_total, 0, cpfv.id as price_field_value
 FROM civicrm_membership_payment cmp
 LEFT JOIN `civicrm_contribution` cc ON cc.id = cmp.contribution_id
 LEFT JOIN civicrm_line_item cli ON cc.id=cli.entity_id and cli.entity_table = 'civicrm_contribution'
 LEFT JOIN civicrm_membership cm ON cm.id=cmp.membership_id
 LEFT JOIN civicrm_membership_type cmt ON cmt.id = cm.membership_type_id
 LEFT JOIN civicrm_price_field cpf ON BINARY cpf.name = cmt.member_of_contact_id
 LEFT JOIN civicrm_price_field_value cpfv ON cpfv.membership_type_id = cm.membership_type_id AND cpf.id = cpfv.price_field_id
 WHERE (cc.id BETWEEN %1 AND %2) AND cli.entity_id IS NULL ;
 ";
    $sqlParams = array(
      1 => array($startId, 'Integer'),
      2 => array($endId, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $sqlParams);

    // create lineitems for contribution done for membership
    $sql = "
SELECT    cc.id, cmp.membership_id, cpse.price_set_id, cc.total_amount
FROM      civicrm_contribution cc
LEFT JOIN civicrm_line_item cli ON cc.id=cli.entity_id AND cli.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_membership_payment cmp ON cc.id = cmp.contribution_id
LEFT JOIN civicrm_participant_payment cpp ON cc.id = cpp.contribution_id
LEFT JOIN civicrm_price_set_entity cpse on cpse.entity_table = 'civicrm_contribution_page' AND cpse.entity_id = cc.contribution_page_id
WHERE     (cc.id BETWEEN %1 AND %2)
AND       cli.entity_id IS NULL AND cc.contribution_page_id IS NOT NULL AND cpp.contribution_id IS NULL
GROUP BY  cc.id, cmp.membership_id
";
    $result = CRM_Core_DAO::executeQuery($sql, $sqlParams);

    while ($result->fetch()) {
      $sql = "
SELECT    cpf.id, cpfv.id as price_field_value_id, cpfv.label, cpfv.amount, cpfv.count
FROM      civicrm_price_field cpf
LEFT JOIN civicrm_price_field_value cpfv ON cpf.id = cpfv.price_field_id
WHERE     cpf.price_set_id = %1
";
      $lineParams = array(
        'entity_table' => 'civicrm_contribution',
        'entity_id' => $result->id,
      );
      if ($result->membership_id) {
        $sql .= " AND cpf.name = %2 AND cpfv.membership_type_id = %3 ";
        $params = array(
          '1' => array($result->price_set_id, 'Integer'),
          '2' => array('membership_amount', 'String'),
          '3' => array(
            CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $result->membership_id, 'membership_type_id'),
            'Integer',
          ),
        );
        $res = CRM_Core_DAO::executeQuery($sql, $params);
        if ($res->fetch()) {
          $lineParams += array(
            'price_field_id' => $res->id,
            'label' => $res->label,
            'qty' => 1,
            'unit_price' => $res->amount,
            'line_total' => $res->amount,
            'participant_count' => $res->count ? $res->count : 0,
            'price_field_value_id' => $res->price_field_value_id,
          );
        }
        else {
          $lineParams['price_field_id'] = CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field', $result->price_set_id, 'id', 'price_set_id');
          $lineParams['label'] = 'Membership Amount';
          $lineParams['qty'] = 1;
          $lineParams['unit_price'] = $lineParams['line_total'] = $result->total_amount;
          $lineParams['participant_count'] = 0;
        }
      }
      else {
        $sql .= "AND cpfv.amount = %2";

        //CRM-12273
        //check if price_set_id is exist, if not use the default contribution amount
        if (isset($result->price_set_id)) {
          $priceSetId = $result->price_set_id;
        }
        else {
          $defaultPriceSets = CRM_Price_BAO_PriceSet::getDefaultPriceSet();
          foreach ($defaultPriceSets as $key => $pSet) {
            if ($pSet['name'] == 'contribution_amount') {
              $priceSetId = $pSet['setID'];
            }
          }
        }

        $params = array(
          '1' => array($priceSetId, 'Integer'),
          '2' => array($result->total_amount, 'String'),
        );
        $res = CRM_Core_DAO::executeQuery($sql, $params);
        if ($res->fetch()) {
          $lineParams += array(
            'price_field_id' => $res->id,
            'label' => $res->label,
            'qty' => 1,
            'unit_price' => $res->amount,
            'line_total' => $res->amount,
            'participant_count' => $res->count ? $res->count : 0,
            'price_field_value_id' => $res->price_field_value_id,
          );
        }
        else {
          $params = array(
            'price_set_id' => $priceSetId,
            'name' => 'other_amount',
          );
          $defaults = array();
          CRM_Upgrade_Snapshot_V4p2_Price_BAO_Field::retrieve($params, $defaults);
          if (!empty($defaults)) {
            $lineParams['price_field_id'] = $defaults['id'];
            $lineParams['label'] = $defaults['label'];
            $lineParams['price_field_value_id']
              = CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue', $defaults['id'], 'id', 'price_field_id');
          }
          else {
            $lineParams['price_field_id']
              = CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field', $priceSetId, 'id', 'price_set_id');
            $lineParams['label'] = 'Contribution Amount';
          }
          $lineParams['qty'] = 1;
          $lineParams['participant_count'] = 0;
          $lineParams['unit_price'] = $lineParams['line_total'] = $result->total_amount;
        }
      }
      CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::create($lineParams);
    }

    return TRUE;
  }

  /**
   * (Queue Task Callback)
   *
   * Find any participant records and create corresponding line-item
   * records.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param int $startId
   *   the first/lowest participant ID to convert.
   * @param int $endId
   *   the last/highest participant ID to convert.
   *
   * @return bool
   */
  public static function task_4_2_alpha1_convertParticipants(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    $upgrade = new CRM_Upgrade_Form();
    //create lineitems for participant in edge cases using default price set for contribution.
    $query = "
SELECT    cp.id as participant_id, cp.fee_amount, cp.fee_level,ce.is_monetary,
          cpse.price_set_id, cpf.id as price_field_id, cpfv.id as price_field_value_id
FROM      civicrm_participant cp
LEFT JOIN civicrm_line_item cli ON cli.entity_id=cp.id and cli.entity_table = 'civicrm_participant'
LEFT JOIN civicrm_event ce ON ce.id=cp.event_id
LEFT JOIN civicrm_price_set_entity cpse ON cp.event_id = cpse.entity_id and cpse.entity_table = 'civicrm_event'
LEFT JOIN civicrm_price_field cpf ON cpf.price_set_id = cpse.price_set_id
LEFT JOIN civicrm_price_field_value cpfv ON cpfv.price_field_id = cpf.id AND cpfv.label = cp.fee_level
WHERE     (cp.id BETWEEN %1 AND %2)
AND       cli.entity_id IS NULL AND cp.fee_amount IS NOT NULL";
    $sqlParams = array(
      1 => array($startId, 'Integer'),
      2 => array($endId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    if ($dao->N) {
      $defaultPriceSetId = CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_Set', 'default_contribution_amount', 'id', 'name');
      $priceSets = current(CRM_Upgrade_Snapshot_V4p2_Price_BAO_Set::getSetDetail($defaultPriceSetId));
      $fieldID = key($priceSets['fields']);
    }

    while ($dao->fetch()) {
      $lineParams = array(
        'entity_table' => 'civicrm_participant',
        'entity_id' => $dao->participant_id,
        'label' => $dao->fee_level ? $dao->fee_level : ts('Default'),
        'qty' => 1,
        'unit_price' => $dao->fee_amount,
        'line_total' => $dao->fee_amount,
        'participant_count' => 1,
      );
      if ($dao->is_monetary && $dao->price_field_id) {
        $lineParams += array(
          'price_field_id' => $dao->price_field_id,
          'price_field_value_id' => $dao->price_field_value_id,
        );
        $priceSetId = $dao->price_set_id;
      }
      else {
        $lineParams['price_field_id'] = $fieldID;
        $priceSetId = $defaultPriceSetId;
      }
      CRM_Upgrade_Snapshot_V4p2_Price_BAO_LineItem::create($lineParams);
    }
    return TRUE;
  }

  /**
   * (Queue Task Callback)
   *
   * Create an event registration profile with a single email field CRM-9587
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function task_4_2_alpha1_eventProfile(CRM_Queue_TaskContext $ctx) {
    $upgrade = new CRM_Upgrade_Form();
    $profileTitle = ts('Your Registration Info');

    $sql = "
INSERT INTO civicrm_uf_group
  (is_active, group_type, title, help_pre, help_post, limit_listings_group_id, post_URL, add_to_group_id, add_captcha, is_map, is_edit_link, is_uf_link, is_update_dupe, cancel_URL, is_cms_user, notify, is_reserved, name, created_id, created_date, is_proximity_search)
VALUES
  (1, 'Individual, Contact', %1, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, 'event_registration', NULL, NULL, 0);
";

    $params = array(
      1 => array($profileTitle, 'String'),
    );

    CRM_Core_DAO::executeQuery($sql, $params);

    $eventRegistrationId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    $sql = "
INSERT INTO civicrm_uf_field
  (uf_group_id, field_name, is_active, is_view, is_required, weight, help_post, help_pre, visibility, in_selector, is_searchable, location_type_id, phone_type_id, label, field_type, is_reserved)
VALUES
  ({$eventRegistrationId}, 'email', 1, 0, 1, 1, NULL, NULL, 'User and User Admin Only', 0, 0, NULL, NULL, 'Email Address', 'Contact', 0);
";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "SELECT * FROM civicrm_event WHERE is_online_registration = 1;";
    $events = CRM_Core_DAO::executeQuery($sql);
    while ($events->fetch()) {
      // Get next weights for the event registration profile
      $nextMainWeight = $nextAdditionalWeight = 1;
      $sql = "
SELECT   weight
FROM     civicrm_uf_join
WHERE    entity_id = {$events->id} AND module = 'CiviEvent'
ORDER BY weight DESC LIMIT 1";
      $weights = CRM_Core_DAO::executeQuery($sql);
      $weights->fetch();
      if (isset($weights->weight)) {
        $nextMainWeight += $weights->weight;
      }
      $sql = "
SELECT   weight
FROM     civicrm_uf_join
WHERE    entity_id = {$events->id} AND module = 'CiviEvent_Additional'
ORDER BY weight DESC LIMIT 1";
      $weights = CRM_Core_DAO::executeQuery($sql);
      $weights->fetch();
      if (isset($weights->weight)) {
        $nextAdditionalWeight += $weights->weight;
      }
      // Add an event registration profile to the event
      $sql = "
INSERT INTO civicrm_uf_join
  (is_active, module, entity_table, entity_id, weight, uf_group_id)
VALUES
  (1, 'CiviEvent', 'civicrm_event', {$events->id}, {$nextMainWeight}, {$eventRegistrationId});
";
      CRM_Core_DAO::executeQuery($sql);
      $sql = "
INSERT INTO civicrm_uf_join
  (is_active, module, entity_table, entity_id, weight, uf_group_id)
VALUES
  (1, 'CiviEvent_Additional', 'civicrm_event', {$events->id}, {$nextAdditionalWeight}, {$eventRegistrationId});";
      CRM_Core_DAO::executeQuery($sql);
    }
    return TRUE;
  }

  /**
   * @return array
   */
  public static function deleteInvalidPairs() {
    require_once 'CRM/Member/PseudoConstant.php';
    require_once 'CRM/Contribute/PseudoConstant.php';
    $processedRecords = array();

    $tempTableName1 = CRM_Core_DAO::createTempTableName();
    // 1. collect all duplicates
    $sql = "
  CREATE TEMPORARY TABLE {$tempTableName1} SELECT mp.id as payment_id, mp.contribution_id, mp.membership_id, mem.membership_type_id, mem.start_date, mem.end_date, mem.status_id, mem.contact_id, con.contribution_status_id
  FROM civicrm_membership_payment mp
  INNER JOIN ( SELECT cmp.contribution_id
                FROM civicrm_membership_payment cmp
                LEFT JOIN civicrm_line_item cli ON cmp.contribution_id=cli.entity_id and cli.entity_table = 'civicrm_contribution'
                WHERE cli.entity_id IS NULL
                GROUP BY cmp.contribution_id
                HAVING COUNT(cmp.membership_id) > 1) submp ON submp.contribution_id = mp.contribution_id
  INNER JOIN civicrm_membership mem ON mem.id = mp.membership_id
  INNER JOIN civicrm_contribution con ON con.id = mp.contribution_id
  ORDER BY mp.contribution_id, mp.membership_id";
    $dao = CRM_Core_DAO::executeQuery($sql);

    $tempTableName2 = CRM_Core_DAO::createTempTableName();
    // 2. collect all records that are going to be retained
    $sql = "
  CREATE TEMPORARY TABLE {$tempTableName2}
  SELECT MAX(payment_id) as payment_id FROM {$tempTableName1} GROUP BY contribution_id HAVING COUNT(*) > 1";
    CRM_Core_DAO::executeQuery($sql);

    // 3. do the un-linking
    $sql = "
  DELETE cmp.*
  FROM   civicrm_membership_payment cmp
  INNER JOIN $tempTableName1 temp1 ON temp1.payment_id = cmp.id
  LEFT JOIN  $tempTableName2 temp2 ON temp1.payment_id = temp2.payment_id
  WHERE temp2.payment_id IS NULL";
    CRM_Core_DAO::executeQuery($sql);

    // 4. show all records that were Processed, i.e Retained vs Un-linked
    $sql = "
  SELECT temp1.contact_id, temp1.contribution_id, temp1.contribution_status_id, temp1.membership_id, temp1.membership_type_id, temp1.start_date, temp1.end_date, temp1.status_id, temp2.payment_id as retain_id
  FROM $tempTableName1 temp1
  LEFT JOIN  $tempTableName2 temp2 ON temp1.payment_id = temp2.payment_id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->N) {
      $membershipType = CRM_Member_PseudoConstant::membershipType();
      $membershipStatus = CRM_Member_PseudoConstant::membershipStatus();
      $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
      while ($dao->fetch()) {
        $status = $dao->retain_id ? 'Retained' : 'Un-linked';
        $memType = CRM_Utils_Array::value($dao->membership_type_id, $membershipType);
        $memStatus = CRM_Utils_Array::value($dao->status_id, $membershipStatus);
        $contribStatus = CRM_Utils_Array::value($dao->contribution_status_id, $contributionStatus);
        $processedRecords[] = array(
          $dao->contact_id,
          $dao->contribution_id,
          $contribStatus,
          $dao->membership_id,
          $memType,
          $dao->start_date,
          $dao->end_date,
          $memStatus,
          $status,
        );
      }
    }

    if (!empty($processedRecords)) {
      CRM_Core_Error::debug_log_message("deleteInvalidPairs() - The following records have been processed. Membership records with action:");
      CRM_Core_Error::debug_log_message("Contact ID, ContributionID, Contribution Status, MembershipID, Membership Type, Start Date, End Date, Membership Status, Action");
      foreach ($processedRecords as $record) {
        CRM_Core_Error::debug_log_message(implode(', ', $record));
      }
    }
    else {
      CRM_Core_Error::debug_log_message("deleteInvalidPairs() - Could not find any records to process.");
    }
    return $processedRecords;
  }

}
