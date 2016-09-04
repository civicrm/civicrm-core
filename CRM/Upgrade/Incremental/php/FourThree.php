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
 * Upgrade logic for 4.3
 */
class CRM_Upgrade_Incremental_php_FourThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.3.alpha1', '4.3.beta3', '4.3.0'.
   * @param null $currentVer
   *
   * @return bool
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '4.3.beta3') {
      //CRM-12084
      //sql for checking orphaned contribution records
      $sql = "SELECT COUNT(ct.id) FROM civicrm_contribution ct LEFT JOIN civicrm_contact c ON ct.contact_id = c.id WHERE c.id IS NULL";
      $count = CRM_Core_DAO::singleValueQuery($sql, array(), TRUE, FALSE);

      if ($count > 0) {
        $error = ts("There is a data integrity issue with this CiviCRM database. It contains %1 contribution records which are linked to contact records that have been deleted. You will need to correct this manually before you can run the upgrade. Use the following MySQL query to identify the problem records: %2 These records will need to be deleted or linked to an existing contact record.", array(
          1 => $count,
          2 => '<em>SELECT ct.* FROM civicrm_contribution ct LEFT JOIN civicrm_contact c ON ct.contact_id = c.id WHERE c.id IS NULL;</em>',
        ));
        CRM_Core_Error::fatal($error);
        return FALSE;
      }
    }
    if ($rev == '4.3.beta4' && CRM_Utils_Constant::value('CIVICRM_UF', FALSE) == 'Drupal6') {
      // CRM-11823 - Make sure the D6 HTML HEAD technique will work on
      // upgrade pages ... except when we're in Drush.
      if (!function_exists('drush_main')) {
        theme('item_list', array()); // force-load theme registry
        $theme_registry = theme_get_registry();
        if (!isset($theme_registry['page']['preprocess functions']) || FALSE === array_search('civicrm_preprocess_page_inject', $theme_registry['page']['preprocess functions'])) {
          CRM_Core_Error::fatal('Please reset the Drupal cache (Administer => Site Configuration => Performance => Clear cached data))');
        }
      }
    }

    if ($rev == '4.3.6') {
      $constraintArray = array(
        'civicrm_contact' => 'contact_id',
        'civicrm_payment_processor' => 'payment_processor_id',
      );

      if (version_compare('4.1alpha1', $currentVer) <= 0) {
        $constraintArray['civicrm_campaign'] = 'campaign_id';
      }

      if (version_compare('4.3alpha1', $currentVer) <= 0) {
        $constraintArray['civicrm_financial_type'] = 'financial_type_id';
      }

      foreach ($constraintArray as $key => $value) {
        $query = "SELECT contri_recur.id FROM civicrm_contribution_recur contri_recur LEFT JOIN {$key} ON contri_recur.{$value} = {$key}.id
WHERE {$key}.id IS NULL";
        if ($value != 'contact_id') {
          $query .= " AND contri_recur.{$value} IS NOT NULL ";
        }
        $dao = CRM_Core_DAO::executeQuery($query);
        if ($dao->N) {
          $invalidDataMessage = '<strong>Oops, it looks like you have orphaned recurring contribution records in your database. Before this upgrade can complete they will need to be fixed or deleted. <a href="http://wiki.civicrm.org/confluence/display/CRMDOC/Fixing+Orphaned+Contribution+Recur+Records" target="_blank">You can review steps to correct this situation on the documentation wiki.</a></strong>';
          CRM_Core_Error::fatal($invalidDataMessage);
          return FALSE;
        }
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
   * @return void
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.3.alpha1') {
      // check if CiviMember component is enabled
      $config = CRM_Core_Config::singleton();
      if (in_array('CiviMember', $config->enableComponents)) {
        $postUpgradeMessage .= '<br />' . ts('Membership renewal reminders must now be configured using the Schedule Reminders feature, which supports multiple renewal reminders  (Administer > Communications > Schedule Reminders). The Update Membership Statuses scheduled job will no longer send membershp renewal reminders. You can use your existing renewal reminder message template(s) with the Schedule Reminders feature.');
        $postUpgradeMessage .= '<br />' . ts('The Set Membership Reminder Dates scheduled job has been deleted since membership reminder dates stored in the membership table are no longer in use.');
      }

      //CRM-11636
      //here we do the financial type check and migration
      $isDefaultsModified = self::_checkAndMigrateDefaultFinancialTypes();
      if ($isDefaultsModified) {
        $postUpgradeMessage .= '<br />' . ts('Please review all price set financial type assignments.');
      }
      list($context, $orgName) = self::createDomainContacts();
      if ($context == 'added') {
        $postUpgradeMessage .= '<br />' . ts("A new organization contact has been added as the default domain contact using the information from your Organization Address and Contact Info settings: '%1'.", array(1 => $orgName));
      }
      elseif ($context == 'merged') {
        $postUpgradeMessage .= '<br />' . ts("The existing organization contact record for '%1' has been marked as the default domain contact, and has been updated with information from your Organization Address and Contact Info settings.", array(1 => $orgName));
      }

      $providerExists = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_sms_provider LIMIT 1");
      if ($providerExists) {
        $postUpgradeMessage .= '<br />' . ts('SMS providers were found to setup. Please note Clickatell / Twilio are now shipped as extensions and will require installing them to continue working. Extension could be downloaded and installed from <a href="%1">github</a>.', array(1 => 'https://github.com/civicrm/civicrm-core/tree/master/tools/extensions'));
      }
    }

    if ($rev == '4.3.alpha2') {
      $sql = "
SELECT   title, id
FROM     civicrm_action_schedule
WHERE    entity_value = '' OR entity_value IS NULL
";

      $dao = CRM_Core_DAO::executeQuery($sql);
      $reminder = array();
      $list = '';
      while ($dao->fetch()) {
        $reminder[$dao->id] = $dao->title;
        $list .= "<li>{$dao->title}</li>";
      }
      if (!empty($reminder)) {
        $list = "<br /><ul>" . $list . "</ul>";
        $postUpgradeMessage .= '<br />' . ts("Scheduled Reminders must be linked to one or more 'entities' (Events, Event Templates, Activity Types, Membership Types). The following reminders are not configured properly and will not be run. Please review them and update or delete them: %1", array(1 => $list));
      }
    }
    if ($rev == '4.3.beta2') {
      $postUpgradeMessage .= '<br />' . ts('Default versions of the following System Workflow Message Templates have been modified to handle new functionality: <ul><li>Events - Registration Confirmation and Receipt (on-line)</li><li>Events - Registration Confirmation and Receipt (off-line)</li><li>Pledges - Acknowledgement</li><li>Pledges - Payment Reminder</li><li>Contributions - Receipt (off-line)</li><li>Contributions - Receipt (on-line)</li><li>Memberships - Signup and Renewal Receipts (off-line)</li><li>Memberships - Receipt (on-line)</li><li>Personal Campaign Pages - Admin Notification</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
    }

    if ($rev == '4.3.beta5') {
      $postUpgradeMessage .= '<br />' . ts("If you are interested in trying out the new Accounting Integration features, please review user permissions and assign the new 'manual batch' permissions as appropriate.");

      // CRM-12155
      $query = "
SELECT    ceft.id FROM `civicrm_financial_trxn` cft
LEFT JOIN civicrm_entity_financial_trxn ceft
  ON ceft.financial_trxn_id = cft.id AND ceft.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_contribution cc
  ON cc.id = ceft.entity_id AND ceft.entity_table = 'civicrm_contribution'
WHERE cc.id IS NULL
";

      $dao = CRM_Core_DAO::executeQuery($query);
      $isOrphanData = TRUE;
      if (!$dao->N) {
        $query = "
SELECT    cli.id FROM civicrm_line_item cli
LEFT JOIN civicrm_contribution cc ON cli.entity_id = cc.id AND cli.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_participant cp ON cli.entity_id = cp.id AND cli.entity_table = 'civicrm_participant'
WHERE CASE WHEN cli.entity_table = 'civicrm_contribution'
  THEN cc.id IS NULL
  ELSE  cp.id IS NULL
END
";
        $dao = CRM_Core_DAO::executeQuery($query);
        if (!$dao->N) {
          $revPattern = '/^((\d{1,2})\.\d{1,2})\.(\d{1,2}|\w{4,7})?$/i';
          preg_match($revPattern, $currentVer, $version);
          if ($version[1] >= 4.3) {
            $query = "
SELECT    cfi.id
FROM      civicrm_financial_item cfi
LEFT JOIN civicrm_entity_financial_trxn ceft ON ceft.entity_table = 'civicrm_financial_item' and cfi.id = ceft.entity_id
WHERE     ceft.entity_id IS NULL;
";
            $dao = CRM_Core_DAO::executeQuery($query);
            if (!$dao->N) {
              $isOrphanData = FALSE;
            }
          }
          else {
            $isOrphanData = FALSE;
          }
        }
      }

      if ($isOrphanData) {
        $postUpgradeMessage .= "</br> <strong>" . ts('Your database contains extraneous financial records related to deleted contacts and contributions. These records should not affect the site and will not appear in reports, search results or exports. However you may wish to clean them up. Refer to <a href="%1" target="_blank">this wiki page for details</a>.
        ', array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Clean+up+extraneous+financial+data+-+4.3+upgrades')) . "</strong>";
      }
    }
    if ($rev == '4.3.4') {
      $postUpgradeMessage .= '<br />' . ts('System Administrator Alert: If you are running scheduled jobs using CLI.php, you will need to reconfigure cron tasks to include a password. Scheduled jobs will no longer run if the password is not provided (<a href="%1" target="_blank">learn more</a>).',
          array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Managing+Scheduled+Jobs'));
    }
    if ($rev == '4.3.5') {
      $postUpgradeMessage .= '<br />' . ts('Default versions of the following System Workflow Message Templates have been modified to handle new functionality: <ul><li>Events - Registration Confirmation and Receipt (on-line)</li><li>Events - Registration Confirmation and Receipt (off-line)</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
    }
    if ($rev == '4.3.6') {
      $flag = CRM_Core_DAO::singleValueQuery('SELECT count(ccp.id) FROM civicrm_contribution_product ccp
INNER JOIN civicrm_product cp ON ccp.product_id = cp.id
WHERE ccp.financial_type_id IS NULL and cp.cost > 0');
      if ($flag) {
        $postUpgradeMessage .= '<br />' . ts('Your database contains one or more premiums which have a cost but are not linked to a financial type. If you are exporting transations to an accounting package, this will result in unbalanced transactions. <a href="%1" target="_blank">You can review steps to correct this situation on the wiki.</a>',
            array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Fixing+Issues+Caused+by+Missing+Cost+of+Goods+Account+-+4.3+Upgrades'));
      }
    }
  }

  /**
   * @param $rev
   *
   * @return bool
   */
  public function upgrade_4_3_alpha1($rev) {
    self::task_4_3_alpha1_checkDBConstraints();

    // add indexes for civicrm_entity_financial_trxn
    // CRM-12141
    $this->addTask('Check/Add indexes for civicrm_entity_financial_trxn', 'task_4_3_x_checkIndexes', $rev);
    // task to process sql
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.alpha1')), 'runSql', $rev);

    //CRM-11636
    $this->addTask('Populate financial type values for price records', 'assignFinancialTypeToPriceRecords');
    //CRM-11514 create financial records for contributions
    $this->addTask('Create financial records for contributions', 'createFinancialRecords');

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contact');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contact');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = "Upgrade timestamps ($startId => $endId)";
      $this->addTask($title, 'convertTimestamps', $startId, $endId);
    }

    // CRM-10893
    // fix WP access control
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'WordPress') {
      civicrm_wp_set_capabilities();
    }

    // Update phones CRM-11292.
    $this->addTask('Upgrade Phone Numbers', 'phoneNumeric');

    return TRUE;
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_alpha2($rev) {
    //CRM-11847
    $isColumnPresent = CRM_Core_DAO::checkFieldExists('civicrm_dedupe_rule_group', 'is_default');
    if ($isColumnPresent) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_dedupe_rule_group DROP COLUMN is_default');
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.alpha2')), 'runSql', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_alpha3($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.alpha3')), 'runSql', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_beta2($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.beta2')), 'runSql', $rev);

    // CRM-12002
    if (
      CRM_Core_DAO::checkTableExists('log_civicrm_line_item') &&
      CRM_Core_DAO::checkFieldExists('log_civicrm_line_item', 'label')
    ) {
      CRM_Core_DAO::executeQuery('ALTER TABLE `log_civicrm_line_item` CHANGE `label` `label` VARCHAR(255) NULL DEFAULT NULL');
    }
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_beta3($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.beta3')), 'runSql', $rev);
    // CRM-12065
    $query = "SELECT id, form_values FROM civicrm_report_instance WHERE form_values LIKE '%contribution_type%'";
    $this->addTask('Replace contribution_type to financial_type in table civicrm_report_instance', 'replaceContributionTypeId', $query, 'reportInstance');
    $query = "SELECT * FROM civicrm_saved_search WHERE form_values LIKE '%contribution_type%'";
    $this->addTask('Replace contribution_type to financial_type in table civicrm_saved_search', 'replaceContributionTypeId', $query, 'savedSearch');
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_beta4($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.beta4')), 'runSql', $rev);
    // add indexes for civicrm_entity_financial_trxn
    // CRM-12141
    $this->addTask('Check/Add indexes for civicrm_entity_financial_trxn', 'task_4_3_x_checkIndexes', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_beta5($rev) {
    // CRM-12205
    if (
      CRM_Core_DAO::checkTableExists('log_civicrm_financial_trxn') &&
      CRM_Core_DAO::checkFieldExists('log_civicrm_financial_trxn', 'trxn_id')
    ) {
      CRM_Core_DAO::executeQuery('ALTER TABLE `log_civicrm_financial_trxn` CHANGE `trxn_id` `trxn_id` VARCHAR(255) NULL DEFAULT NULL');
    }
    // CRM-12142 - some sites didn't get this column added yet, and sites which installed 4.3 from scratch will already have it
    // CRM-12367 - add this column to single lingual sites only
    $upgrade = new CRM_Upgrade_Form();
    if (!$upgrade->multilingual &&
      !CRM_Core_DAO::checkFieldExists('civicrm_premiums', 'premiums_nothankyou_label')
    ) {
      $query = "
ALTER TABLE civicrm_premiums
ADD COLUMN   premiums_nothankyou_label varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
  COMMENT 'Label displayed for No Thank-you option in premiums block (e.g. No thank you)'
";
      CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.beta5')), 'runSql', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_4($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.4')), 'runSql', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_5($rev) {
    // CRM-12156
    $config = CRM_Core_Config::singleton();
    $dbname = DB::parseDSN($config->dsn);
    $sql = "SELECT DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_NAME = 'FK_civicrm_financial_item_contact_id'
AND CONSTRAINT_SCHEMA = %1";
    $params = array(1 => array($dbname['database'], 'String'));
    $onDelete = CRM_Core_DAO::singleValueQuery($sql, $params, TRUE, FALSE);

    if ($onDelete != 'CASCADE') {
      $query = "ALTER TABLE `civicrm_financial_item`
DROP FOREIGN KEY FK_civicrm_financial_item_contact_id,
DROP INDEX FK_civicrm_financial_item_contact_id;";
      CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
      $query = "
ALTER TABLE `civicrm_financial_item`
ADD CONSTRAINT `FK_civicrm_financial_item_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
";
      CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.5')), 'runSql', $rev);
  }

  /**
   * @param $rev
   */
  public function upgrade_4_3_6($rev) {
    //CRM-13094
    $this->addTask(ts('Add missing constraints'), 'addMissingConstraints', $rev);
    //CRM-13088
    $this->addTask('Add ON DELETE Options for constraints', 'task_4_3_x_checkConstraints', $rev);
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.3.6')), 'runSql', $rev);
    // CRM-12844
    // update line_item, financial_trxn and financial_item table for recurring contributions
    $this->addTask('Update financial_account_id in financial_trxn table', 'updateFinancialTrxnData', $rev);
    $this->addTask('Update Line Item Data', 'updateLineItemData', $rev);
  }

  /**
   * CRM-11636
   * @return bool
   */
  public function assignFinancialTypeToPriceRecords() {
    $upgrade = new CRM_Upgrade_Form();
    //here we update price set entries
    $sqlFinancialIds = "
SELECT id, LCASE(name) name
FROM   civicrm_financial_type
WHERE name IN ('Donation', 'Event Fee', 'Member Dues');
";
    $daoFinancialIds = CRM_Core_DAO::executeQuery($sqlFinancialIds);
    while ($daoFinancialIds->fetch()) {
      $financialIds[$daoFinancialIds->name] = $daoFinancialIds->id;
    }
    $sqlPriceSetUpdate = "
UPDATE civicrm_price_set ps
SET    ps.financial_type_id =
  CASE
    WHEN ps.extends LIKE '%1%' THEN {$financialIds['event fee']}
    WHEN ps.extends LIKE '2' THEN {$financialIds['donation']}
    WHEN ps.extends LIKE '3' THEN {$financialIds['member dues']}
  END
WHERE  financial_type_id IS NULL
";
    CRM_Core_DAO::executeQuery($sqlPriceSetUpdate);

    //here we update price field value rows
    $sqlPriceFieldValueUpdate = "
UPDATE     civicrm_price_field_value pfv
LEFT JOIN  civicrm_membership_type mt ON (pfv.membership_type_id = mt.id)
INNER JOIN civicrm_price_field pf ON (pfv.price_field_id = pf.id)
INNER JOIN civicrm_price_set ps ON (pf.price_set_id = ps.id)
 SET pfv.financial_type_id =
   CASE
     WHEN pfv.membership_type_id IS NOT NULL THEN mt.financial_type_id
     WHEN pfv.membership_type_id IS NULL THEN ps.financial_type_id
   END
";
    CRM_Core_DAO::executeQuery($sqlPriceFieldValueUpdate);

    return TRUE;
  }

  /**
   * @return bool
   */
  public static function _checkAndMigrateDefaultFinancialTypes() {
    $modifiedDefaults = FALSE;
    //insert types if not exists
    $sqlFetchTypes = "
SELECT id, name
FROM   civicrm_contribution_type
WHERE  name IN ('Donation', 'Event Fee', 'Member Dues') AND is_active =1
";
    $daoFetchTypes = CRM_Core_DAO::executeQuery($sqlFetchTypes);

    if ($daoFetchTypes->N < 3) {
      $modifiedDefaults = TRUE;
      $insertStatments = array(
        'Donation' => "('Donation', 0, 1, 1)",
        'Member' => "('Member Dues', 0, 1, 1)",
        'Event Fee' => "('Event Fee', 0, 1, 0)",
      );
      foreach ($insertStatments as $values) {
        $query = "
INSERT INTO  civicrm_contribution_type  (name, is_reserved, is_active, is_deductible)
VALUES       $values
ON DUPLICATE KEY UPDATE  is_active = 1
";
        CRM_Core_DAO::executeQuery($query);
      }
    }
    return $modifiedDefaults;
  }

  /**
   * @return bool
   */
  public function createFinancialRecords() {
    $upgrade = new CRM_Upgrade_Form();

    // update civicrm_entity_financial_trxn.amount = civicrm_financial_trxn.total_amount
    $query = "
UPDATE    civicrm_entity_financial_trxn ceft
LEFT JOIN civicrm_financial_trxn cft ON cft.id = ceft.financial_trxn_id
SET       ceft.amount = total_amount
WHERE     cft.net_amount IS NOT NULL
AND       ceft.entity_table = 'civicrm_contribution'
";
    CRM_Core_DAO::executeQuery($query);

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $completedStatus = array_search('Completed', $contributionStatus);
    $pendingStatus = array_search('Pending', $contributionStatus);
    $cancelledStatus = array_search('Cancelled', $contributionStatus);
    $queryParams = array(
      1 => array($completedStatus, 'Integer'),
      2 => array($pendingStatus, 'Integer'),
      3 => array($cancelledStatus, 'Integer'),
    );

    $accountType = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name = 'Asset' "));
    $query = "
SELECT id
FROM   civicrm_financial_account
WHERE  is_default = 1
AND    financial_account_type_id = {$accountType}
";
    $financialAccountId = CRM_Core_DAO::singleValueQuery($query);

    $accountRelationsips = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount',
      'account_relationship', CRM_Core_DAO::$_nullArray, 'validate');

    $accountsReceivableAccount = array_search('Accounts Receivable Account is', $accountRelationsips);
    $incomeAccountIs = array_search('Income Account is', $accountRelationsips);
    $assetAccountIs = array_search('Asset Account is', $accountRelationsips);
    $expenseAccountIs = array_search('Expense Account is', $accountRelationsips);

    $financialItemStatus = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialItem', 'status_id',
      CRM_Core_DAO::$_nullArray, 'validate');
    $unpaidStatus = array_search('Unpaid', $financialItemStatus);
    $paidStatus = array_search('Paid', $financialItemStatus);

    $validCurrencyCodes = CRM_Core_PseudoConstant::currencyCode();
    $validCurrencyCodes = implode("','", $validCurrencyCodes);
    $config = CRM_Core_Config::singleton();
    $defaultCurrency = $config->defaultCurrency;
    $now = date('YmdHis');

    //adding financial_trxn records and entity_financial_trxn records related to contribution
    //Add temp column for easy entry in entity_financial_trxn
    $sql = "ALTER TABLE civicrm_financial_trxn ADD COLUMN contribution_id INT DEFAULT NULL";
    CRM_Core_DAO::executeQuery($sql);

    //pending pay later status handling
    $sql = "
INSERT INTO civicrm_financial_trxn
       (contribution_id, payment_instrument_id, currency, total_amount, net_amount, fee_amount, trxn_id, status_id,
       check_number, to_financial_account_id, from_financial_account_id, trxn_date)
SELECT con.id as contribution_id, con.payment_instrument_id,
       IF(con.currency IN ('{$validCurrencyCodes}'), con.currency, '{$defaultCurrency}') as currency,
       con.total_amount, con.net_amount, con.fee_amount, con.trxn_id, con.contribution_status_id,
       con.check_number, efa.financial_account_id as to_financial_account_id, NULL as from_financial_account_id,
        REPLACE(REPLACE(REPLACE(
          CASE
            WHEN con.receive_date IS NOT NULL THEN
                con.receive_date
            WHEN con.receipt_date IS NOT NULL THEN
                con.receipt_date
            ELSE
                {$now}
          END
        , '-', ''), ':', ''), ' ', '') as trxn_date
FROM  civicrm_contribution con
      LEFT JOIN civicrm_entity_financial_account efa
             ON (con.financial_type_id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
                 AND efa.account_relationship = {$accountsReceivableAccount})
WHERE con.is_pay_later = 1
AND   con.contribution_status_id = {$pendingStatus}
";
    CRM_Core_DAO::executeQuery($sql);

    //create a temp table to hold financial account id related to payment instruments
    $tempTableName1 = CRM_Core_DAO::createTempTableName();

    $sql = "
CREATE TEMPORARY TABLE {$tempTableName1}
SELECT     ceft.financial_account_id financial_account_id, cov.value as instrument_id
FROM       civicrm_entity_financial_account ceft
INNER JOIN civicrm_option_value cov ON cov.id = ceft.entity_id AND ceft.entity_table = 'civicrm_option_value'
INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id
WHERE      cog.name = 'payment_instrument'
";
    CRM_Core_DAO::executeQuery($sql);

    //CRM-12141
    $sql = "ALTER TABLE {$tempTableName1} ADD INDEX index_instrument_id (instrument_id(200));";
    CRM_Core_DAO::executeQuery($sql);

    //create temp table to process completed / cancelled contribution
    $tempTableName2 = CRM_Core_DAO::createTempTableName();
    $sql = "
CREATE TEMPORARY TABLE {$tempTableName2}
SELECT con.id as contribution_id, con.payment_instrument_id,
       IF(con.currency IN ('{$validCurrencyCodes}'), con.currency, '{$defaultCurrency}') as currency,
       con.total_amount, con.net_amount, con.fee_amount, con.trxn_id, con.contribution_status_id,
       con.check_number, NULL as from_financial_account_id,
       REPLACE(REPLACE(REPLACE(
          CASE
            WHEN con.receive_date IS NOT NULL THEN
                con.receive_date
            WHEN con.receipt_date IS NOT NULL THEN
                con.receipt_date
            ELSE
                {$now}
          END
       , '-', ''), ':', ''), ' ', '') as trxn_date,
       CASE
         WHEN con.payment_instrument_id IS NULL THEN
              {$financialAccountId}
         WHEN con.payment_instrument_id IS NOT NULL THEN
              tpi.financial_account_id
       END as to_financial_account_id,
       IF(eft.financial_trxn_id IS NULL, 'insert', eft.financial_trxn_id) as action
FROM      civicrm_contribution con
LEFT JOIN civicrm_entity_financial_trxn eft
       ON (eft.entity_table = 'civicrm_contribution' AND eft.entity_id = con.id)
LEFT JOIN {$tempTableName1} tpi
       ON con.payment_instrument_id = tpi.instrument_id
WHERE     con.contribution_status_id IN ({$completedStatus}, {$cancelledStatus})
";
    CRM_Core_DAO::executeQuery($sql);

    // CRM-12141
    $sql = "ALTER TABLE {$tempTableName2} ADD INDEX index_action (action);";
    CRM_Core_DAO::executeQuery($sql);

    //handling for completed contribution and cancelled contribution
    //insertion of new records
    $sql = "
INSERT INTO civicrm_financial_trxn
            (contribution_id, payment_instrument_id, currency, total_amount, net_amount, fee_amount, trxn_id, status_id, check_number,
            to_financial_account_id, from_financial_account_id, trxn_date)
SELECT   tempI.contribution_id, tempI.payment_instrument_id, tempI.currency, tempI.total_amount, tempI.net_amount,
         tempI.fee_amount,    tempI.trxn_id,   tempI.contribution_status_id, tempI.check_number,
         tempI.to_financial_account_id, tempI.from_financial_account_id, tempI.trxn_date
FROM {$tempTableName2} tempI
WHERE tempI.action = 'insert'
";
    CRM_Core_DAO::executeQuery($sql);

    //update of existing records
    $sql = "
UPDATE civicrm_financial_trxn ft
       INNER JOIN {$tempTableName2} tempU
               ON (tempU.action != 'insert' AND ft.id = tempU.action)
SET   ft.from_financial_account_id  = NULL,
      ft.to_financial_account_id    = tempU.to_financial_account_id,
      ft.status_id                  = tempU.contribution_status_id,
      ft.payment_instrument_id      = tempU.payment_instrument_id,
      ft.check_number               = tempU.check_number,
      ft.contribution_id            = tempU.contribution_id;";
    CRM_Core_DAO::executeQuery($sql);

    //insert the -ve transaction rows for cancelled contributions
    $sql = "
INSERT INTO civicrm_financial_trxn
            (contribution_id, payment_instrument_id, currency, total_amount,     net_amount, fee_amount, trxn_id, status_id,
            check_number,    to_financial_account_id, from_financial_account_id, trxn_date)
SELECT ft.contribution_id, ft.payment_instrument_id, ft.currency, -ft.total_amount, ft.net_amount, ft.fee_amount, ft.trxn_id,
       ft.status_id, ft.check_number, ft.to_financial_account_id, ft.from_financial_account_id, ft.trxn_date
FROM   civicrm_financial_trxn ft
WHERE  ft.status_id = {$cancelledStatus};";
    CRM_Core_DAO::executeQuery($sql);

    //inserting entity financial trxn entries if its not present in entity_financial_trxn for completed and pending contribution statuses
    //this also handles +ve and -ve both transaction entries for a cancelled contribution
    $sql = "
INSERT INTO civicrm_entity_financial_trxn (entity_table, entity_id, financial_trxn_id, amount)
SELECT 'civicrm_contribution', ft.contribution_id, ft.id, ft.total_amount as amount
FROM   civicrm_financial_trxn ft
WHERE  contribution_id IS NOT NULL AND
       ft.id NOT IN (SELECT financial_trxn_id
                     FROM civicrm_entity_financial_trxn
                     WHERE entity_table = 'civicrm_contribution'
                     AND entity_id      = ft.contribution_id)";
    CRM_Core_DAO::executeQuery($sql);
    //end of adding financial_trxn records and entity_financial_trxn records related to contribution

    //update all linked line_item rows
    // set line_item.financial_type_id = contribution.financial_type_id if contribution page id is null and not participant line item
    // set line_item.financial_type_id = price_field_value.financial_type_id if contribution page id is set and not participant line item
    // set line_item.financial_type_id = event.financial_type_id if its participant line item and line_item.price_field_value_id is null
    // set line_item.financial_type_id = price_field_value.financial_type_id if its participant line item and line_item.price_field_value_id is set
    $updateLineItemSql = "
UPDATE civicrm_line_item li
       LEFT JOIN civicrm_contribution con
              ON (li.entity_id = con.id AND li.entity_table = 'civicrm_contribution')
       LEFT JOIN civicrm_price_field_value cpfv
              ON li.price_field_value_id = cpfv.id
       LEFT JOIN civicrm_participant cp
              ON (li.entity_id = cp.id AND li.entity_table = 'civicrm_participant')
       LEFT JOIN civicrm_event ce
              ON ce.id = cp.event_id
SET    li.financial_type_id = CASE
         WHEN (con.contribution_page_id IS NULL || li.price_field_value_id IS NULL) AND cp.id IS NULL THEN
           con.financial_type_id
         WHEN (con.contribution_page_id IS NOT NULL AND cp.id IS NULL) || (cp.id IS NOT NULL AND  li.price_field_value_id IS NOT NULL) THEN
           cpfv.financial_type_id
         WHEN cp.id IS NOT NULL AND  li.price_field_value_id IS NULL THEN
           ce.financial_type_id
       END";
    CRM_Core_DAO::executeQuery($updateLineItemSql, $queryParams);

    //add the financial_item entries
    //add a temp column so that inserting entity_financial_trxn entries gets easy
    $sql = "ALTER TABLE civicrm_financial_item ADD COLUMN f_trxn_id INT DEFAULT NULL";
    CRM_Core_DAO::executeQuery($sql);

    //add financial_item entries for contribution  completed / pending pay later / cancelled
    $contributionlineItemSql = "
INSERT INTO civicrm_financial_item
           (transaction_date, contact_id, amount, currency, entity_table, entity_id, description, status_id, financial_account_id, f_trxn_id)

SELECT REPLACE(REPLACE(REPLACE(ft.trxn_date, '-', ''), ':', ''), ' ', ''), con.contact_id,
       IF(ft.total_amount < 0 AND con.contribution_status_id = %3, -li.line_total, li.line_total) as line_total, con.currency, 'civicrm_line_item',
       li.id as line_item_id, li.label as line_item_label,
       IF(con.contribution_status_id = {$pendingStatus}, {$unpaidStatus}, {$paidStatus}) as status_id, efa.financial_account_id as financial_account_id,
       ft.id as f_trxn_id
FROM  civicrm_line_item li
      INNER JOIN civicrm_contribution con
              ON (li.entity_id = con.id AND li.entity_table = 'civicrm_contribution')
      INNER JOIN civicrm_financial_trxn ft
              ON (con.id = ft.contribution_id)
      LEFT  JOIN civicrm_entity_financial_account efa
              ON (li.financial_type_id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
                  AND efa.account_relationship = {$incomeAccountIs})
WHERE con.contribution_status_id IN (%1, %3) OR (con.is_pay_later = 1 AND con.contribution_status_id = %2)";
    CRM_Core_DAO::executeQuery($contributionlineItemSql, $queryParams);

    //add financial_item entries for event
    $participantLineItemSql = "
INSERT INTO civicrm_financial_item
           (transaction_date, contact_id, amount, currency, entity_table, entity_id, description, status_id, financial_account_id, f_trxn_id)

SELECT REPLACE(REPLACE(REPLACE(ft.trxn_date, '-', ''), ':', ''), ' ', ''), con.contact_id,
       IF(ft.total_amount < 0 AND con.contribution_status_id = %3, -li.line_total, li.line_total) as line_total,
         con.currency, 'civicrm_line_item', li.id as line_item_id, li.label as line_item_label,
       IF(con.contribution_status_id = {$pendingStatus}, {$unpaidStatus}, {$paidStatus}) as status_id,
          efa.financial_account_id as financial_account_id, ft.id as f_trxn_id
FROM  civicrm_line_item li
      INNER JOIN civicrm_participant par
              ON (li.entity_id = par.id AND li.entity_table = 'civicrm_participant')
      INNER JOIN civicrm_participant_payment pp
              ON (pp.participant_id = par.id)
      INNER JOIN civicrm_contribution con
              ON (pp.contribution_id = con.id)
      INNER JOIN civicrm_financial_trxn ft
              ON (con.id = ft.contribution_id)
      LEFT  JOIN civicrm_entity_financial_account efa
              ON (li.financial_type_id = efa.entity_id AND
                  efa.entity_table = 'civicrm_financial_type' AND efa.account_relationship = {$incomeAccountIs})
WHERE con.contribution_status_id IN (%1, %3) OR (con.is_pay_later = 1 AND con.contribution_status_id = %2)";
    CRM_Core_DAO::executeQuery($participantLineItemSql, $queryParams);

    //fee handling for contributions
    //insert fee entries in financial_trxn for contributions
    $sql = "ALTER TABLE civicrm_financial_trxn ADD COLUMN is_fee TINYINT DEFAULT NULL";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
INSERT INTO civicrm_financial_trxn
            (contribution_id, payment_instrument_id, currency, total_amount, net_amount, fee_amount, trxn_id, status_id, check_number,
             to_financial_account_id, from_financial_account_id, trxn_date, payment_processor_id, is_fee)

SELECT con.id, ft.payment_instrument_id, ft.currency, ft.fee_amount, NULL, NULL, ft.trxn_id, %1 as status_id,
       ft.check_number, efaFT.financial_account_id as to_financial_account_id, CASE
         WHEN efaPP.financial_account_id IS NOT NULL THEN
              efaPP.financial_account_id
         WHEN tpi.financial_account_id   IS NOT NULL THEN
              tpi.financial_account_id
         ELSE
              {$financialAccountId}
       END    as from_financial_account_id, ft.trxn_date, ft.payment_processor_id, 1 as is_fee
FROM   civicrm_contribution con
       INNER JOIN civicrm_financial_trxn ft
               ON (ft.contribution_id = con.id)
       LEFT  JOIN civicrm_entity_financial_account efaFT
               ON (con.financial_type_id = efaFT.entity_id AND efaFT.entity_table = 'civicrm_financial_type'
                   AND efaFT.account_relationship = {$expenseAccountIs})
       LEFT  JOIN civicrm_entity_financial_account efaPP
               ON (ft.payment_processor_id = efaPP.entity_id AND efaPP.entity_table = 'civicrm_payment_processor'
                   AND efaPP.account_relationship = {$assetAccountIs})
       LEFT  JOIN {$tempTableName1} tpi
               ON ft.payment_instrument_id = tpi.instrument_id
WHERE  ft.fee_amount IS NOT NULL AND ft.fee_amount != 0 AND (con.contribution_status_id IN (%1, %3) OR (con.contribution_status_id =%2 AND con.is_pay_later = 1))
GROUP  BY con.id";
    CRM_Core_DAO::executeQuery($sql, $queryParams);

    //link financial_trxn to contribution
    $sql = "
INSERT INTO civicrm_entity_financial_trxn
            (entity_table, entity_id, financial_trxn_id, amount)
SELECT 'civicrm_contribution', ft.contribution_id, ft.id, ft.total_amount
FROM   civicrm_financial_trxn ft
WHERE ft.is_fee = 1";
    CRM_Core_DAO::executeQuery($sql);

    //add fee related entries to financial item table
    $domainId = CRM_Core_Config::domainID();
    $domainContactId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', $domainId, 'contact_id');
    $sql = "
INSERT INTO civicrm_financial_item
            (transaction_date, contact_id, amount, currency, entity_table, entity_id, description, status_id, financial_account_id, f_trxn_id)
SELECT ft.trxn_date, {$domainContactId} as contact_id, ft.total_amount, ft.currency, 'civicrm_financial_trxn', ft.id,
       'Fee', {$paidStatus} as status_id, ft.to_financial_account_id as financial_account_id, ft.id as f_trxn_id
FROM   civicrm_financial_trxn ft
WHERE  ft.is_fee = 1;";
    CRM_Core_DAO::executeQuery($sql);

    //add entries to entity_financial_trxn table
    $sql = "
INSERT INTO civicrm_entity_financial_trxn (entity_table, entity_id, financial_trxn_id, amount)
SELECT 'civicrm_financial_item' as entity_table, fi.id as entity_id, fi.f_trxn_id as financial_trxn_id, fi.amount
FROM   civicrm_financial_item fi";
    CRM_Core_DAO::executeQuery($sql);

    //drop the temparory columns
    $sql = "ALTER TABLE civicrm_financial_trxn
                  DROP COLUMN contribution_id,
                  DROP COLUMN  is_fee;";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "ALTER TABLE civicrm_financial_item DROP f_trxn_id";
    CRM_Core_DAO::executeQuery($sql);

    return TRUE;
  }

  /**
   * @return array
   */
  public function createDomainContacts() {
    $domainParams = $context = array();
    $query = "
ALTER TABLE civicrm_domain ADD contact_id INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'FK to Contact ID. This is specifically not an FK to avoid circular constraints',
 ADD CONSTRAINT FK_civicrm_domain_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id);";
    CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);

    $query = '
SELECT cd.id, cd.name, ce.email FROM civicrm_domain cd
LEFT JOIN civicrm_loc_block clb ON clb.id = cd. loc_block_id
LEFT JOIN civicrm_email ce ON ce.id = clb.email_id ;
';
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $query = "
SELECT    cc.id FROM civicrm_contact cc
LEFT JOIN civicrm_email ce ON ce.contact_id = cc.id
WHERE     cc.contact_type = 'Organization' AND cc.organization_name = %1
";
      $params = array(1 => array($dao->name, 'String'));
      if ($dao->email) {
        $query .= " AND ce.email = %2 ";
        $params[2] = array($dao->email, 'String');
      }
      $contactID = CRM_Core_DAO::singleValueQuery($query, $params);
      $context[1] = $dao->name;
      if (empty($contactID)) {
        $params = array(
          'sort_name' => $dao->name,
          'display_name' => $dao->name,
          'legal_name' => $dao->name,
          'organization_name' => $dao->name,
          'contact_type' => 'Organization',
        );
        $contact = CRM_Contact_BAO_Contact::add($params);
        $contactID = $contact->id;
        $context[0] = 'added';
      }
      else {
        $context[0] = 'merged';
      }
      $domainParams['contact_id'] = $contactID;
      CRM_Core_BAO_Domain::edit($domainParams, $dao->id);
    }
    return $context;
  }

  public function task_4_3_alpha1_checkDBConstraints() {
    //checking whether the foreign key exists before dropping it CRM-11260
    $config = CRM_Core_Config::singleton();
    $dbUf = DB::parseDSN($config->dsn);
    $tables = array(
      'autorenewal_msg_id' => array(
        'tableName' => 'civicrm_membership_type',
        'fkey' => 'FK_civicrm_membership_autorenewal_msg_id',
      ),
      'to_account_id' => array(
        'tableName' => 'civicrm_financial_trxn',
        'constraintName' => 'civicrm_financial_trxn_ibfk_2',
      ),
      'from_account_id' => array(
        'tableName' => 'civicrm_financial_trxn',
        'constraintName' => 'civicrm_financial_trxn_ibfk_1',
      ),
      'contribution_type_id' => array(
        'tableName' => 'civicrm_contribution_recur',
        'fkey' => 'FK_civicrm_contribution_recur_contribution_type_id',
      ),
    );
    $query = "
SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE table_name = 'civicrm_contribution_recur'
AND constraint_name = 'FK_civicrm_contribution_recur_contribution_type_id'
AND TABLE_SCHEMA = %1
";
    $params = array(1 => array($dbUf['database'], 'String'));
    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
    foreach ($tables as $columnName => $value) {
      if ($value['tableName'] == 'civicrm_membership_type' || $value['tableName'] == 'civicrm_contribution_recur') {
        $foreignKeyExists = CRM_Core_DAO::checkConstraintExists($value['tableName'], $value['fkey']);
        $fKey = $value['fkey'];
      }
      else {
        $foreignKeyExists = CRM_Core_DAO::checkFKConstraintInFormat($value['tableName'], $columnName);
        $fKey = "`FK_{$value['tableName']}_{$columnName}`";
      }
      if ($foreignKeyExists || $value['tableName'] == 'civicrm_financial_trxn') {
        if ($value['tableName'] != 'civicrm_contribution_recur' || ($value['tableName'] == 'civicrm_contribution_recur' && $dao->N)) {
          $constraintName = $foreignKeyExists ? $fKey : $value['constraintName'];
          $query = "ALTER TABLE {$value['tableName']} DROP FOREIGN KEY {$constraintName}";
          CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
        }
        $query = "ALTER TABLE {$value['tableName']} DROP INDEX {$fKey}";
        CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
      }
    }
    // check if column contact_id is present or not in civicrm_financial_account
    $fieldExists = CRM_Core_DAO::checkFieldExists('civicrm_financial_account', 'contact_id', FALSE);
    if (!$fieldExists) {
      $query = "
ALTER TABLE civicrm_financial_account
  ADD contact_id int(10) unsigned DEFAULT NULL COMMENT 'Version identifier of financial_type' AFTER name,
  ADD CONSTRAINT FK_civicrm_financial_account_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id);
";
      CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
    }
  }

  /**
   * Read creation and modification times from civicrm_log; add them to civicrm_contact.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param int $startId
   * @param int $endId
   *
   * @return bool
   */
  public function convertTimestamps(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    $sql = "
      SELECT entity_id, min(modified_date) AS created, max(modified_date) AS modified
      FROM civicrm_log
      WHERE entity_table = 'civicrm_contact'
      AND entity_id BETWEEN %1 AND %2
      GROUP BY entity_id
    ";
    $params = array(
      1 => array($startId, 'Integer'),
      2 => array($endId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      // FIXME civicrm_log.modified_date is DATETIME; civicrm_contact.modified_date is TIMESTAMP
      CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_contact SET created_date = FROM_UNIXTIME(UNIX_TIMESTAMP(%1)), modified_date = FROM_UNIXTIME(UNIX_TIMESTAMP(%2)) WHERE id = %3',
        array(
          1 => array($dao->created, 'String'),
          2 => array($dao->modified, 'String'),
          3 => array($dao->entity_id, 'Integer'),
        )
      );
    }

    return TRUE;
  }

  /**
   * Change index and add missing constraints for civicrm_contribution_recur.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public function addMissingConstraints(CRM_Queue_TaskContext $ctx) {
    $query = "SHOW KEYS FROM `civicrm_contribution_recur` WHERE key_name = 'UI_contrib_payment_instrument_id'";
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->N) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contribution_recur DROP INDEX UI_contrib_payment_instrument_id');
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contribution_recur ADD INDEX UI_contribution_recur_payment_instrument_id (payment_instrument_id)');
    }
    $constraintArray = array(
      'contact_id' => " ADD CONSTRAINT `FK_civicrm_contribution_recur_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE ",
      'payment_processor_id' => " ADD CONSTRAINT `FK_civicrm_contribution_recur_payment_processor_id` FOREIGN KEY (`payment_processor_id`) REFERENCES `civicrm_payment_processor` (`id`) ON DELETE SET NULL ",
      'financial_type_id' => " ADD CONSTRAINT `FK_civicrm_contribution_recur_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`) ON DELETE SET NULL ",
      'campaign_id' => " ADD CONSTRAINT `FK_civicrm_contribution_recur_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `civicrm_campaign` (`id`) ON DELETE SET NULL ",
    );
    $constraint = array();
    foreach ($constraintArray as $constraintKey => $value) {
      $foreignKeyExists = CRM_Core_DAO::checkFKConstraintInFormat('civicrm_contribution_recur', $constraintKey);
      if (!$foreignKeyExists) {
        $constraint[] = $value;
      }
    }
    if (!empty($constraint)) {
      $query = "ALTER TABLE civicrm_contribution_recur " . implode(' , ', $constraint);
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  /**
   * Update financial_account_id for bad data in financial_trxn table.
   * CRM-12844
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public function updateFinancialTrxnData(CRM_Queue_TaskContext $ctx) {
    $upgrade = new CRM_Upgrade_Form();
    $sql = "SELECT cc.id contribution_id, cc.contribution_recur_id, cft.payment_processor_id,
cft.id financial_trxn_id, cfi.entity_table, cft.from_financial_account_id, cft.to_financial_account_id

FROM `civicrm_contribution` cc
LEFT JOIN civicrm_entity_financial_trxn ceft ON ceft.entity_id = cc.id
LEFT JOIN civicrm_financial_trxn cft ON cft.id = ceft.financial_trxn_id
LEFT JOIN civicrm_entity_financial_trxn ceft1 ON ceft1.financial_trxn_id = ceft.financial_trxn_id
LEFT JOIN civicrm_financial_item cfi ON cfi.id = ceft1.entity_id
WHERE ceft.entity_table = 'civicrm_contribution'  AND cc.contribution_recur_id IS NOT NULL
AND ceft1.entity_table = 'civicrm_financial_item' AND cft.id IS NOT NULL AND cft.payment_instrument_id = %1

ORDER BY cft.id ";
    $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $param = array(1 => array(array_search('Credit Card', $paymentInstrument), 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $param);
    $financialTrxn = array();
    $subsequentPayments = array();
    while ($dao->fetch()) {
      if (!array_key_exists($dao->contribution_recur_id, $financialTrxn)) {
        $financialTrxn[$dao->contribution_recur_id] = array(
          'from_financial_account_id' => $dao->to_financial_account_id,
          'payment_processor_id' => $dao->payment_processor_id,
          $dao->contribution_id => 1,
        );
        if (!is_null($dao->from_financial_account_id)) {
          $sql = 'UPDATE civicrm_financial_trxn SET from_financial_account_id = NULL WHERE id = %1';
          $params = array(1 => array($dao->financial_trxn_id, 'Integer'));
          CRM_Core_DAO::executeQuery($sql, $params);
        }
      }
      elseif (!array_key_exists($dao->contribution_id, $financialTrxn[$dao->contribution_recur_id])) {
        if (($dao->entity_table == 'civicrm_line_item' && $dao->to_financial_account_id == $financialTrxn[$dao->contribution_recur_id]['from_financial_account_id'])
          || ($dao->entity_table == 'civicrm_financial_trxn' && $dao->from_financial_account_id == $financialTrxn[$dao->contribution_recur_id]['from_financial_account_id'])
        ) {
          continue;
        }
        $subsequentPayments[$dao->contribution_recur_id][$dao->entity_table][] = $dao->financial_trxn_id;
      }
    }
    foreach ($subsequentPayments as $key => $value) {
      foreach ($value as $table => $val) {
        if ($table == 'civicrm_financial_trxn') {
          $field = 'from_financial_account_id';
        }
        else {
          $field = 'to_financial_account_id';
        }
        $sql = "UPDATE civicrm_financial_trxn SET $field = " . $financialTrxn[$dao->contribution_recur_id]['from_financial_account_id'] . ',
payment_processor_id = ' . $financialTrxn[$dao->contribution_recur_id]['payment_processor_id'] . ' WHERE
id IN (' . implode(',', $val) . ')';
        CRM_Core_DAO::executeQuery($sql);
      }
    }
    return TRUE;
  }

  /**
   * Update financial_account_id for bad data in financial_trxn table.
   * CRM-12844
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public function updateLineItemData(CRM_Queue_TaskContext $ctx) {
    $sql = "SELECT cc.id contribution_id, cc.contribution_recur_id,
cc.financial_type_id contribution_financial_type,
cli.financial_type_id line_financial_type_id,
cli.price_field_id, cli.price_field_value_id, cli.label, cli.id line_item_id,
cfi.financial_account_id
FROM `civicrm_line_item` cli
LEFT JOIN civicrm_contribution cc ON cc.id =  cli.entity_id
LEFT JOIN civicrm_financial_item cfi ON cfi.entity_id = cli.id
LEFT JOIN civicrm_price_field cpf ON cpf.id = cli.price_field_id
LEFT JOIN civicrm_price_set cps ON cps.id = cpf.price_set_id
LEFT JOIN civicrm_price_field_value cpfv ON cpfv.id = cli.price_field_value_id
WHERE cfi.entity_table = 'civicrm_line_item'
AND cli.entity_table = 'civicrm_contribution'
AND cps.is_quick_config = 1 AND cc.contribution_recur_id IS NOT NULL
ORDER BY cli.id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $financialTrxn = $subsequentPayments = array();
    while ($dao->fetch()) {
      if (!array_key_exists($dao->contribution_recur_id, $financialTrxn)) {
        $financialTrxn[$dao->contribution_recur_id] = array(
          'price_field_id' => $dao->price_field_id,
          'price_field_value_id' => $dao->price_field_value_id,
          'label' => strval($dao->label),
          'financial_account_id' => $dao->financial_account_id,
          $dao->contribution_id => 1,
        );
      }
      else {
        if ($dao->price_field_value_id == $financialTrxn[$dao->contribution_recur_id]['price_field_value_id']) {
          continue;
        }
        $subsequentPayments[$dao->contribution_recur_id][] = $dao->line_item_id;
      }
    }
    foreach ($subsequentPayments as $key => $value) {
      $sql = "UPDATE civicrm_line_item cli
LEFT JOIN civicrm_financial_item cfi ON cli.id = cfi.entity_id
SET
cli.label = %1,
cli.price_field_id = %2,
cli.price_field_value_id = %3,
cfi.financial_account_id = %4,
cfi.description = %5,
cli.financial_type_id = %6
WHERE cfi.entity_table = 'civicrm_line_item'
AND cli.entity_table = 'civicrm_contribution' AND cli.id IN (" . implode(',', $value) . ');';
      $params = array(
        1 => array($financialTrxn[$key]['label'], 'String'),
        2 => array($financialTrxn[$key]['price_field_id'], 'Integer'),
        3 => array($financialTrxn[$key]['price_field_value_id'], 'Integer'),
        4 => array($financialTrxn[$key]['financial_account_id'], 'Integer'),
        5 => array($financialTrxn[$key]['label'], 'String'),
        6 => array($dao->contribution_financial_type, 'Integer'),
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    return TRUE;
  }

  /**
   * Replace contribution_type to financial_type in table.
   *
   * Civicrm_saved_search and Structure civicrm_report_instance
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $query
   * @param string $table
   *
   * @return bool
   */
  public function replaceContributionTypeId(CRM_Queue_TaskContext $ctx, $query, $table) {
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $formValues = unserialize($dao->form_values);
      foreach (array('contribution_type_id_op', 'contribution_type_id_value', 'contribution_type_id') as $value) {
        if (array_key_exists($value, $formValues)) {
          $key = preg_replace('/contribution/', 'financial', $value);
          $formValues[$key] = $formValues[$value];
          unset($formValues[$value]);
        }
      }
      if ($table != 'savedSearch') {
        foreach (array('fields', 'group_bys') as $value) {
          if (array_key_exists($value, $formValues)) {
            if (array_key_exists('contribution_type_id', $formValues[$value])) {
              $formValues[$value]['financial_type_id'] = $formValues[$value]['contribution_type_id'];
              unset($formValues[$value]['contribution_type_id']);
            }
            elseif (array_key_exists('contribution_type', $formValues[$value])) {
              $formValues[$value]['financial_type'] = $formValues[$value]['contribution_type'];
              unset($formValues[$value]['contribution_type']);
            }
          }
        }
        if (array_key_exists('order_bys', $formValues)) {
          foreach ($formValues['order_bys'] as $key => $values) {
            if (preg_grep('/contribution_type/', $values)) {
              $formValues['order_bys'][$key]['column'] = preg_replace('/contribution_type/', 'financial_type', $values['column']);
            }
          }
        }
      }

      if ($table == 'savedSearch') {
        $saveDao = new CRM_Contact_DAO_SavedSearch();
      }
      else {
        $saveDao = new CRM_Report_DAO_ReportInstance();
      }
      $saveDao->id = $dao->id;

      if ($table == 'savedSearch') {
        if (array_key_exists('mapper', $formValues)) {
          foreach ($formValues['mapper'] as $key => $values) {
            foreach ($values as $k => $v) {
              if (preg_grep('/contribution_/', $v)) {
                $formValues['mapper'][$key][$k] = preg_replace('/contribution_type/', 'financial_type', $v);
              }
            }
          }
        }
        foreach (array('select_tables', 'where_tables') as $value) {
          if (preg_match('/contribution_type/', $dao->$value)) {
            $tempValue = unserialize($dao->$value);
            if (array_key_exists('civicrm_contribution_type', $tempValue)) {
              $tempValue['civicrm_financial_type'] = $tempValue['civicrm_contribution_type'];
              unset($tempValue['civicrm_contribution_type']);
            }
            $saveDao->$value = serialize($tempValue);
          }
        }
        if (preg_match('/contribution_type/', $dao->where_clause)) {
          $saveDao->where_clause = preg_replace('/contribution_type/', 'financial_type', $dao->where_clause);
        }
      }
      $saveDao->form_values = serialize($formValues);

      $saveDao->save();
    }
    return TRUE;
  }

  /**
   * Add ON DELETE options for constraint if not present.
   * CRM-13088 && CRM-12156
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   */
  public function task_4_3_x_checkConstraints(CRM_Queue_TaskContext $ctx) {
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_financial_account` CHANGE `contact_id` `contact_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL');
    $config = CRM_Core_Config::singleton();
    $dbname = DB::parseDSN($config->dsn);
    $constraintArray = array(
      "'FK_civicrm_financial_account_contact_id'",
      "'FK_civicrm_financial_item_contact_id'",
      "'FK_civicrm_contribution_recur_financial_type_id'",
      "'FK_civicrm_line_item_financial_type_id'",
      "'FK_civicrm_product_financial_type_id'",
      "'FK_civicrm_premiums_product_financial_type_id'",
      "'FK_civicrm_price_field_value_financial_type_id'",
      "'FK_civicrm_contribution_product_financial_type_id'",
      "'FK_civicrm_price_set_financial_type_id'",
      "'FK_civicrm_grant_financial_type_id'",
    );

    $sql = "SELECT DELETE_RULE, TABLE_NAME, CONSTRAINT_NAME
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_NAME IN (" . implode(',', $constraintArray) . ")
AND CONSTRAINT_SCHEMA = %1";
    $params = array(1 => array($dbname['database'], 'String'));
    $onDelete = CRM_Core_DAO::executeQuery($sql, $params, TRUE, FALSE);
    while ($onDelete->fetch()) {
      if (($onDelete->TABLE_NAME != 'civicrm_financial_item' && $onDelete->DELETE_RULE != 'SET NULL') ||
        ($onDelete->TABLE_NAME == 'civicrm_financial_item' && $onDelete->DELETE_RULE != 'CASCADE')
      ) {
        $tableName = 'civicrm_financial_type';
        $onDeleteOption = ' SET NULL ';
        $columnName = 'financial_type_id';
        if (preg_match('/contact_id/', $onDelete->CONSTRAINT_NAME)) {
          $tableName = 'civicrm_contact';
          $columnName = 'contact_id';
          if ($onDelete->TABLE_NAME == 'civicrm_financial_item') {
            $onDeleteOption = 'CASCADE';
          }
        }
      }
      else {
        continue;
      }
      $query = "ALTER TABLE {$onDelete->TABLE_NAME}
        DROP FOREIGN KEY {$onDelete->CONSTRAINT_NAME},
        DROP INDEX {$onDelete->CONSTRAINT_NAME};";
      CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
      $query = " ALTER TABLE  {$onDelete->TABLE_NAME}
        ADD CONSTRAINT  {$onDelete->CONSTRAINT_NAME} FOREIGN KEY (`" . $columnName . "`) REFERENCES {$tableName} (`id`) ON DELETE {$onDeleteOption};
        ";
      CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Check/Add INDEX CRM-12141
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   */
  public function task_4_3_x_checkIndexes(CRM_Queue_TaskContext $ctx) {
    $query = "
SHOW KEYS
FROM civicrm_entity_financial_trxn
WHERE key_name IN ('UI_entity_financial_trxn_entity_table', 'UI_entity_financial_trxn_entity_id')
";
    $dao = CRM_Core_DAO::executeQuery($query);
    if (!$dao->N) {
      $query = "
ALTER TABLE civicrm_entity_financial_trxn
ADD INDEX UI_entity_financial_trxn_entity_table (entity_table),
ADD INDEX UI_entity_financial_trxn_entity_id (entity_id);
";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  /**
   * Update phones CRM-11292
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   */
  public static function phoneNumeric(CRM_Queue_TaskContext $ctx) {
    CRM_Core_DAO::executeQuery(CRM_Contact_BAO_Contact::DROP_STRIP_FUNCTION_43);
    CRM_Core_DAO::executeQuery(CRM_Contact_BAO_Contact::CREATE_STRIP_FUNCTION_43);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_phone SET phone_numeric = civicrm_strip_non_numeric(phone)");
    return TRUE;
  }

}
