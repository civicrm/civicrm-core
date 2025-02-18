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
 * Upgrade logic for FiveThirtyFour
 */
class CRM_Upgrade_Incremental_php_FiveThirtyFour extends CRM_Upgrade_Incremental_Base {

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
    if ($rev === '5.34.alpha1') {
      $xoauth2Value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP_XOAUTH2');
      if (!empty($xoauth2Value)) {
        if ($this->isXOAUTH2InUse($xoauth2Value)) {
          $preUpgradeMessage .= '<p>' . $this->getXOAuth2Warning() . '</p>';
        }
      }
    }

    if ($rev === '5.34.alpha1') {
      $smtpPasswords = self::findSmtpPasswords();
      $cryptoRegistry = \Civi\Crypto\CryptoRegistry::createDefaultRegistry();
      if (extension_loaded('mcrypt') && !empty($smtpPasswords)) {
        // NOTE: We don't re-encrypt automatically because the old "civicrm.settings.php" lacks a good key, and we don't keep the old encryption because the format is ambiguous.
        // The admin may forget to re-enable. That's OK -- this only affects 1 field, this is a secondary defense, and (in the future) we can remind the admin via status-checks.
        $preUpgradeMessage .= '<p>' . ts('This system has an encrypted SMTP password. Encryption in v5.34+ requires CIVICRM_CRED_KEYS. You may <a href="%1" target="_blank">setup CIVICRM_CRED_KEYS</a> before or after upgrading. If you choose to wait, then the SMTP password will be stored as plain-text until you setup CIVICRM_CRED_KEYS.', [
          1 => 'https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#smtp-password',
        ]) . '</p>';
      }
      foreach ($smtpPasswords as $setting) {
        $settingValue = unserialize($setting['value']);
        $canBeStored = self::canBeStored($settingValue['smtpPassword'], $cryptoRegistry);
        $isSmtpActive = (((int) $settingValue['outBound_option'] ?? -1) == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP);

        $msg = NULL;
        if (!$canBeStored && !$isSmtpActive) {
          $prose = ts('The SMTP password (%1) was previously stored in the database. It has unexpected content which cannot be migrated automatically. However, it appears to be inactive, so the upgrader will drop this value.', [
            1 => "setting#" . $setting['id'],
          ]);
          $preUpgradeMessage .= '<p>' . $prose . '</p>';
        }
        elseif (!$canBeStored && $isSmtpActive) {
          // "Misconfiguration" ==> Ex: 'mcrypt' was enabled, used to setup the password, and then disabled, leaving us with unreadable ciphertext.
          // "Misconfiguration" ==> Ex: 'mcrypt' was enabled, used to setup the password, but the SITE_KEY was changed, leaving us with unreadable ciphertext.
          // "Unrecognized character" ==> Ex: When using plain-text and utf8mb4, it fails to write some characters (chr(128)-chr(255)).
          // To date, we have only seen reports where this arose from misconfiguration.
          $prose = ts('The SMTP password (%1) has unusual content that cannot be stored as plain-text. It may be unreadable due to a previous misconfiguration, or it may rely on an unclear character-set. Your options are:', [
            1 => "setting#" . $setting['id'],
          ]);
          $option_1 = ts('<a href="%1" target="_blank">Setup CIVICRM_CRED_KEYS</a> before upgrading. This warning will go away, and the current SMTP password will ultimately be preserved during upgrade.', [
            1 => 'https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#smtp-password',
          ]);
          $option_2 = ts('Proceed with upgrading in the current configuration. The SMTP password will be lost, but you may re-configure it after upgrade.');
          $preUpgradeMessage .= "<p>$prose</p><ul><li>$option_1</li><li>$option_2</li></ul>";
        }
      }
      foreach ($GLOBALS['civicrm_setting'] ?? [] as $entity => $overrides) {
        if (extension_loaded('mcrypt') && !empty($overrides['mailing_backend']['smtpPassword']) && $overrides['mailing_backend']['outBound_option'] == 0) {
          // This is a fairly unlikely situation. I'm sure it's *useful* to set smtpPassword via $civicrm_setting (eg for dev or multitenant).
          // But historically it had to follow the rules of CRM_Utils_Crypt:
          // - For non-mcrypt servers, that was easy/plaintext. That'll work just as well going forward. We don't show any warnings about that.
          // - For mcrypt servers, the value had to be encrypted. It's not easy to pick the right value for that. Maybe someone with multitenant would have had
          //   enough incentive to figure this out... but they'd probably get stymied by the fact that each tenant has a different SITE_KEY.
          // All of which is to say: if someone has gotten into a valid+working scenario of overriding smtpPassword on an mcrypt-enabled system, then they're
          // savvy enough to figure out the migration details. We just need to point them at the problem.
          $settingPath = sprintf('$civicrm_setting[%s][%s][%s]', var_export($entity, 1), var_export('mailing_backend', 1), var_export('smtpPassword', 1));
          $prose = ts('This system has a PHP override for the SMTP password (%1). The override was most likely encrypted with an old mechanism. After upgrading, you must verify and/or revise this setting.', [
            1 => $settingPath,
          ]);
          $preUpgradeMessage = '<p>' . $prose . '</p>';
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
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev === '5.34.alpha1') {
      $xoauth2Value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP_XOAUTH2');
      if (!empty($xoauth2Value)) {
        if ($this->isXOAUTH2InUse($xoauth2Value)) {
          $postUpgradeMessage .= '<div class="crm-error"><ul><li>' . $this->getXOAuth2Warning() . '</li></ul></div>';
        }
      }
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_34_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    if (!empty(self::findSmtpPasswords())) {
      $this->addTask('Migrate SMTP password', 'migrateSmtpPasswords');
    }

    $this->addTask('dev/core#365 - Add created_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'created_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the schedule reminder created.'");

    $this->addTask('dev/core#365 - Add modified_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'modified_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the schedule reminder created.'");

    $this->addTask('dev/core#365 - Add effective_start_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'effective_start_date', "timestamp NULL COMMENT 'Earliest date to consider start events from.'");

    $this->addTask('dev/core#365 - Add effective_end_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'effective_end_date', "timestamp NULL COMMENT 'Latest date to consider end events from.'");

    $this->addTask('Set defaults and required on financial type boolean fields', 'updateFinancialTypeTable');
    $this->addTask('Set defaults and required on pledge fields', 'updatePledgeTable');

    $this->addTask('Remove never used IMAP_XOAUTH2 option value', 'removeUnusedXOAUTH2');
  }

  /**
   * @return array
   *   A list of "civicrm_setting" records which have
   *   SMTP passwords, or NULL.
   */
  protected static function findSmtpPasswords() {
    $query = CRM_Utils_SQL_Select::from('civicrm_setting')
      ->where('name = "mailing_backend"');

    $matches = [];
    foreach ($query->execute()->fetchAll() as $setting) {
      $value = unserialize($setting['value']);
      if (!empty($value['smtpPassword'])) {
        $matches[] = $setting;
      }
    }

    return $matches;
  }

  /**
   * Find any SMTP passwords. Remove the CRM_Utils_Crypt encryption.
   *
   * Note: This task is only enqueued if mcrypt is active.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function migrateSmtpPasswords(CRM_Queue_TaskContext $ctx) {
    $settings = self::findSmtpPasswords();
    $cryptoRegistry = \Civi\Crypto\CryptoRegistry::createDefaultRegistry();
    $cryptoToken = new \Civi\Crypto\CryptoToken($cryptoRegistry);

    foreach ($settings as $setting) {
      $new = $old = unserialize($setting['value']);
      if (!self::canBeStored($old['smtpPassword'], $cryptoRegistry)) {
        $new['smtpPassword'] = '';
      }
      else {
        $plain = CRM_Utils_Crypt::decrypt($old['smtpPassword']);
        $new['smtpPassword'] = $cryptoToken->encrypt($plain, 'CRED');
      }
      CRM_Core_DAO::executeQuery('UPDATE civicrm_setting SET value = %2 WHERE id = %1', [
        1 => [$setting['id'], 'Positive'],
        2 => [serialize($new), 'String'],
      ]);
    }
    return TRUE;
  }

  /**
   * If you decode an old value of smtpPassword, will it be possible to store that
   * password in the updated format?
   *
   * If you actually have encryption enabled, then it's straight-yes. But if you
   * have to write in plain-text, then you're working within the constraints
   * of php-mysqli-utf8mb4, and it does not accept anything > chr(128).
   *
   * Note: This could potentially change in the future if we updated `CryptToken`
   * to put difficult strings into `^CTK?k=plain&t={base64}` format.
   *
   * @param string $oldCipherText
   * @param \Civi\Crypto\CryptoRegistry $registry
   * @return bool
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  protected static function canBeStored($oldCipherText, \Civi\Crypto\CryptoRegistry $registry) {
    $plainText = CRM_Utils_Crypt::decrypt($oldCipherText);
    $activeKey = $registry->findKey('CRED');
    $isPrintable = ctype_print($plainText);
    if ($activeKey['suite'] === 'plain' && !$isPrintable) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Update financial type table to reflect recent schema changes.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateFinancialTypeTable(CRM_Queue_TaskContext $ctx): bool {
    // Make sure there are no existing NULL values in the fields we are about to make required.
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_financial_type
      SET is_active = COALESCE(is_active, 0),
          is_reserved = COALESCE(is_reserved, 0),
          is_deductible = COALESCE(is_deductible, 0)
      WHERE is_reserved IS NULL OR is_active IS NULL OR is_deductible IS NULL
    ');
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_financial_type
      MODIFY COLUMN `is_deductible` tinyint(4) DEFAULT 0 NOT NULL COMMENT 'Is this financial type tax-deductible? If true, contributions of this type may be fully OR partially deductible - non-deductible amount is stored in the Contribution record.',
      MODIFY COLUMN `is_reserved` tinyint(4) DEFAULT 0 NOT NULL COMMENT 'Is this a predefined system object?',
      MODIFY COLUMN `is_active` tinyint(4) DEFAULT 1 NOT NULL COMMENT 'Is this property active?'
    ", [], TRUE, NULL, FALSE, FALSE);

    return TRUE;
  }

  /**
   * Update pledge table to reflect recent schema changes making fields required.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updatePledgeTable(CRM_Queue_TaskContext $ctx): bool {
    // Make sure there are no existing NULL values in the fields we are about to make required.
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_pledge
      SET is_test = COALESCE(is_test, 0),
          frequency_unit = COALESCE(frequency_unit, "month"),
          # Cannot imagine this would be null but if it were...
          installments = COALESCE(installments, 0),
          # this does not seem plausible either.
          status_id = COALESCE(status_id, 1)
      WHERE is_test IS NULL OR frequency_unit IS NULL OR installments IS NULL OR status_id IS NULL
    ');
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_pledge
      MODIFY COLUMN `frequency_unit` varchar(8) DEFAULT 'month' NOT NULL COMMENT 'Time units for recurrence of pledge payments.',
      MODIFY COLUMN `installments` int(10) unsigned DEFAULT 1 NOT NULL COMMENT 'Total number of payments to be made.',
      MODIFY COLUMN `status_id` int(10) unsigned NOT NULL COMMENT 'Implicit foreign key to civicrm_option_values in the pledge_status option group.',
      MODIFY COLUMN `is_test` tinyint(4) DEFAULT 0 NOT NULL
    ");
    return TRUE;
  }

  /**
   * This option value was never used, but check anyway if someone happens
   * to be using it and then ask them to report what they're doing with it.
   * There's no way to send a message to the user during the task, so we have
   * to check it here and also as a pre/post upgrade message.
   * Similar to removeGooglePlusOption from 5.23 except there we know some
   * people would have used it.
   */
  public static function removeUnusedXOAUTH2(CRM_Queue_TaskContext $ctx) {
    $xoauth2Value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP_XOAUTH2');
    if (!empty($xoauth2Value)) {
      if (!self::isXOAUTH2InUse($xoauth2Value)) {
        CRM_Core_DAO::executeQuery("DELETE ov FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og
ON (og.name = 'mail_protocol' AND ov.option_group_id = og.id)
WHERE ov.value = %1",
          [1 => [$xoauth2Value, 'Positive']]);
      }
    }
    return TRUE;
  }

  /**
   * Determine if option value is enabled or used in mail settings.
   * @return bool
   */
  private static function isXOAUTH2InUse($xoauth2Value) {
    $enabled = (bool) CRM_Core_DAO::SingleValueQuery("SELECT ov.is_active FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og
ON (og.name = 'mail_protocol' AND ov.option_group_id = og.id)
WHERE ov.value = %1",
      [1 => [$xoauth2Value, 'Positive']]);
    $usedInMailSettings = (bool) CRM_Core_DAO::SingleValueQuery("SELECT id FROM civicrm_mail_settings WHERE protocol = %1", [1 => [$xoauth2Value, 'Positive']]);
    return $enabled || $usedInMailSettings;
  }

  /**
   * @return string
   */
  private function getXOAuth2Warning():string {
    // Leaving out ts() since it's unlikely this message will ever
    // be displayed to anyone.
    return strtr(
      'This system has enabled "IMAP_XOAUTH2" which was experimentally declared in CiviCRM v5.24. CiviCRM v5.33+ includes a supported replacement ("oauth-client"), and the experimental "IMAP_XOAUTH2" should be removed. Please visit %1 to discuss.',
      [
        '%1' => '<a target="_blank" href="https://lab.civicrm.org/dev/core/-/issues/2264">dev/core#2264</a>',
      ]
    );
  }

}
