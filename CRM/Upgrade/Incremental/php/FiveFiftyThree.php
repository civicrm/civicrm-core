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
 * Upgrade logic for the 5.53.x series.
 *
 * Each minor version in the series is handled by either a `5.53.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_53_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyThree extends CRM_Upgrade_Incremental_Base {

  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev === '5.53.alpha1') {
      $postUpgradeMessage .= '<div class="messages warning"><p>' . ts("WARNING: CiviCRM has changed the meaning of the date format specifier %1 when using CRM_Utils_Date::customFormat or {crmDate}. It was identical to %2 and the change will help compatibility with php 8.1.", [
        1 => '%A',
        2 => '%P',
      ]) . '</p>';
      $usagesInTemplates = self::getTemplatesUsingPercentA();
      if (!empty($usagesInTemplates)) {
        $postUpgradeMessage .= '<p>' . ts("The following <a %2>message templates</a> appear to be using the %1 specifier. You will need to manually review and update them to use %3 instead.", [
          1 => '%A',
          2 => 'href="' . CRM_Utils_System::url('civicrm/admin/messageTemplates', ['reset' => 1], TRUE) . '" target="_blank"',
          3 => '%P',
        ]) . '</p>';
        $postUpgradeMessage .= '<ul>';
        foreach ($usagesInTemplates as $id => $title) {
          $link = CRM_Utils_System::url('civicrm/admin/messageTemplates/add', ['action' => 'update', 'reset' => 1, 'id' => $id], TRUE);
          $postUpgradeMessage .= "<li><a href='{$link}' target='_blank'>" . htmlspecialchars($title) . '</a></li>';
        }
        $postUpgradeMessage .= '</ul></p>';
      }
      $postUpgradeMessage .= '<p>' . ts("Your <a %1>Date Format</a> settings have been automatically updated.", [
        1 => 'href="' . CRM_Utils_System::url('civicrm/admin/setting/date', ['reset' => 1], TRUE) . '" target="_blank"',
      ]) . '</p>';
      $postUpgradeMessage .= '</div>';
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_53_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Replace %A specifier in date settings.', 'replacePercentA');
    $this->addTask('Add invoice pdf format', 'addInvoicePDFFormat');
    $this->addTask('Add Recent Items Providers', 'addRecentItemsProviders');
  }

  public function upgrade_5_53_beta1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  /**
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function addInvoicePDFFormat(CRM_Queue_TaskContext $ctx) {
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'value' => '{"metric":"px","margin_top":10,"margin_bottom":0,"margin_left":65,"margin_right":0}',
      'name' => 'default_invoice_pdf_format',
      'label' => ts('Invoice PDF Format'),
      'is_reserved' => TRUE,
      'option_group_id' => 'pdf_format',
    ]);
    return TRUE;
  }

  public static function replacePercentA($ctx): bool {
    foreach ([
      'dateformatDatetime',
      'dateformatFull',
      'dateformatPartial',
      'dateformatYear',
      'dateformatTime',
      'dateformatFinancialBatch',
      'dateformatshortdate',
    ] as $setting) {
      $value = \Civi::settings()->get($setting);
      if ($value && (strpos($value, '%A') !== FALSE)) {
        $value = strtr($value, ['%A' => '%P']);
        \Civi::settings()->set($setting, $value);
      }
    }
    return TRUE;
  }

  public static function getTemplatesUsingPercentA(): array {
    $usages = [];
    // is_default has weird meaning - it means the one currently in use, not the default distributed with civi (which is is_reserved).
    // The "NOT LIKE" part is necessary to avoid false positives because the
    // event receipt uses it (correctly) with date_format.
    $dao = CRM_Core_DAO::executeQuery("SELECT id, msg_title FROM civicrm_msg_template WHERE is_default = 1 AND workflow_name <> 'event_online_receipt' AND ((msg_html LIKE BINARY '%\\%A%' AND msg_html NOT LIKE BINARY '%date_format:\"\\%A\"%') OR (msg_text LIKE BINARY '%\\%A%' AND msg_text NOT LIKE BINARY '%date_format:\"\\%A\"%') OR (msg_subject LIKE BINARY '%\\%A%' AND msg_subject NOT LIKE BINARY '%date_format:\"\\%A\"%'))");
    while ($dao->fetch()) {
      $usages[$dao->id] = $dao->msg_title;
    }
    return $usages;
  }

  /**
   * dev/core#3783 Add Recent Items Providers.
   * @return bool
   */
  public static function addRecentItemsProviders() {
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'recent_items_providers',
      'title' => ts('Recent Items Providers'),
      'is_reserved' => 0,
    ]);
    $values = [
      'Contact' => ts('Contacts'),
      'Relationship' => ts('Relationships'),
      'Activity' => ts('Activities'),
      'Note' => ts('Notes'),
      'Group' => ts('Groups'),
      'Case' => ts('Cases'),
      'Contribution' => ts('Contributions'),
      'Participant' => ts('Participants'),
      'Membership' => ts('Memberships'),
      'Pledge' => ts('Pledges'),
      'Event' => ts('Events'),
      'Campaign' => ts('Campaigns'),
    ];
    foreach ($values as $name => $label) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'label' => $label,
        'name' => $name,
        'value' => $name,
        'option_group_id' => 'recent_items_providers',
        'is_reserved' => TRUE,
      ]);
    }
    return TRUE;
  }

}
