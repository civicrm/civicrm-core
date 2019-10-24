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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Upgrade_Incremental_MessageTemplates {

  /**
   * Version we are upgrading to.
   *
   * @var string
   */
  protected $upgradeVersion;

  /**
   * @return string
   */
  public function getUpgradeVersion() {
    return $this->upgradeVersion;
  }

  /**
   * @param string $upgradeVersion
   */
  public function setUpgradeVersion($upgradeVersion) {
    $this->upgradeVersion = $upgradeVersion;
  }

  /**
   * CRM_Upgrade_Incremental_MessageTemplates constructor.
   *
   * @param string $upgradeVersion
   */
  public function __construct($upgradeVersion) {
    $this->setUpgradeVersion($upgradeVersion);
  }

  /**
   * Get any templates that have been updated.
   *
   * @return array
   */
  protected function getTemplateUpdates() {
    return [
      [
        'version' => '5.4.alpha1',
        'upgrade_descriptor' => ts('Use email greeting at top where available'),
        'templates' => [
          ['name' => 'membership_online_receipt', 'type' => 'text'],
          ['name' => 'membership_online_receipt', 'type' => 'html'],
          ['name' => 'contribution_online_receipt', 'type' => 'text'],
          ['name' => 'contribution_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'text'],
          ['name' => 'event_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'subject'],
        ],
      ],
      [
        'version' => '5.7.alpha1',
        'upgrade_descriptor' => ts('Fix invoice number (human readable) instead of id (reference)'),
        'label' => ts('Contributions - Invoice'),
        'templates' => [
          ['name' => 'contribution_invoice_receipt', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.10.alpha1',
        'upgrade_descriptor' => ts('Show recurring cancel/update URLs in receipt based on payment processor capabilities'),
        'label' => ts('Receipts - cancel/update subscription URLs'),
        'templates' => [
          ['name' => 'contribution_online_receipt', 'type' => 'text'],
          ['name' => 'contribution_online_receipt', 'type' => 'html'],
          ['name' => 'contribution_recurring_notify', 'type' => 'text'],
          ['name' => 'contribution_recurring_notify', 'type' => 'html'],
          ['name' => 'membership_online_receipt', 'type' => 'text'],
          ['name' => 'membership_online_receipt', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.12.alpha1',
        'upgrade_descriptor' => ts('Update payment notification to remove print text, use email greeting'),
        'label' => ts('Payment notification'),
        'templates' => [
          ['name' => 'payment_or_refund_notification', 'type' => 'text'],
          ['name' => 'payment_or_refund_notification', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.15.alpha1',
        'upgrade_descriptor' => ts('Use email greeting and fix capitalization'),
        'label' => ts('Pledge acknowledgement'),
        'templates' => [
          ['name' => 'pledge_acknowledge', 'type' => 'text'],
          ['name' => 'pledge_acknowledge', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.20.alpha1',
        'upgrade_descriptor' => ts('Fix missing Email greetings'),
        'templates' => [
          ['name' => 'contribution_dupalert', 'type' => 'subject'],
          ['name' => 'contribution_invoice_receipt', 'type' => 'subject'],
          ['name' => 'contribution_offline_receipt', 'type' => 'html'],
          ['name' => 'contribution_offline_receipt', 'type' => 'subject'],
          ['name' => 'contribution_offline_receipt', 'type' => 'text'],
          ['name' => 'contribution_online_receipt', 'type' => 'subject'],
          ['name' => 'contribution_recurring_billing', 'type' => 'html'],
          ['name' => 'contribution_recurring_billing', 'type' => 'subject'],
          ['name' => 'contribution_recurring_billing', 'type' => 'text'],
          ['name' => 'contribution_recurring_cancelled', 'type' => 'html'],
          ['name' => 'contribution_recurring_cancelled', 'type' => 'subject'],
          ['name' => 'contribution_recurring_cancelled', 'type' => 'text'],
          ['name' => 'contribution_recurring_edit', 'type' => 'html'],
          ['name' => 'contribution_recurring_edit', 'type' => 'subject'],
          ['name' => 'contribution_recurring_edit', 'type' => 'text'],
          ['name' => 'contribution_recurring_notify', 'type' => 'html'],
          ['name' => 'contribution_recurring_notify', 'type' => 'subject'],
          ['name' => 'contribution_recurring_notify', 'type' => 'text'],
          ['name' => 'event_offline_receipt', 'type' => 'html'],
          ['name' => 'event_offline_receipt', 'type' => 'subject'],
          ['name' => 'event_offline_receipt', 'type' => 'text'],
          ['name' => 'event_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'subject'],
          ['name' => 'event_online_receipt', 'type' => 'text'],
          ['name' => 'event_registration_receipt', 'type' => 'html'],
          ['name' => 'event_registration_receipt', 'type' => 'subject'],
          ['name' => 'event_registration_receipt', 'type' => 'text'],
          ['name' => 'membership_autorenew_billing', 'type' => 'html'],
          ['name' => 'membership_autorenew_billing', 'type' => 'subject'],
          ['name' => 'membership_autorenew_billing', 'type' => 'text'],
          ['name' => 'membership_autorenew_cancelled', 'type' => 'html'],
          ['name' => 'membership_autorenew_cancelled', 'type' => 'subject'],
          ['name' => 'membership_autorenew_cancelled', 'type' => 'text'],
          ['name' => 'membership_offline_receipt', 'type' => 'html'],
          ['name' => 'membership_offline_receipt', 'type' => 'subject'],
          ['name' => 'membership_offline_receipt', 'type' => 'text'],
          ['name' => 'membership_online_receipt', 'type' => 'subject'],
          ['name' => 'participant_cancelled', 'type' => 'html'],
          ['name' => 'participant_cancelled', 'type' => 'subject'],
          ['name' => 'participant_cancelled', 'type' => 'text'],
          ['name' => 'participant_confirm', 'type' => 'html'],
          ['name' => 'participant_confirm', 'type' => 'subject'],
          ['name' => 'participant_confirm', 'type' => 'text'],
          ['name' => 'participant_expired', 'type' => 'html'],
          ['name' => 'participant_expired', 'type' => 'subject'],
          ['name' => 'participant_expired', 'type' => 'text'],
          ['name' => 'participant_transferred', 'type' => 'html'],
          ['name' => 'participant_transferred', 'type' => 'subject'],
          ['name' => 'participant_transferred', 'type' => 'text'],
          ['name' => 'payment_or_refund_notification', 'type' => 'html'],
          ['name' => 'payment_or_refund_notification', 'type' => 'subject'],
          ['name' => 'payment_or_refund_notification', 'type' => 'text'],
          ['name' => 'pcp_notify', 'type' => 'subject'],
          ['name' => 'pcp_owner_notify', 'type' => 'html'],
          ['name' => 'pcp_owner_notify', 'type' => 'subject'],
          ['name' => 'pcp_owner_notify', 'type' => 'text'],
          ['name' => 'pcp_status_change', 'type' => 'subject'],
          ['name' => 'pcp_supporter_notify', 'type' => 'html'],
          ['name' => 'pcp_supporter_notify', 'type' => 'subject'],
          ['name' => 'pcp_supporter_notify', 'type' => 'text'],
          ['name' => 'petition_confirmation_needed', 'type' => 'html'],
          ['name' => 'petition_confirmation_needed', 'type' => 'subject'],
          ['name' => 'petition_confirmation_needed', 'type' => 'text'],
          ['name' => 'petition_sign', 'type' => 'html'],
          ['name' => 'petition_sign', 'type' => 'subject'],
          ['name' => 'petition_sign', 'type' => 'text'],
          ['name' => 'pledge_acknowledge', 'type' => 'subject'],
          ['name' => 'pledge_acknowledge', 'type' => 'subject'],
          ['name' => 'pledge_reminder', 'type' => 'html'],
          ['name' => 'pledge_reminder', 'type' => 'subject'],
          ['name' => 'pledge_reminder', 'type' => 'text'],
          ['name' => 'uf_notify', 'type' => 'subject'],
        ],
      ],

    ];
  }

  /**
   * Get any required template updates.
   *
   * @return array
   */
  public function getTemplatesToUpdate() {
    $templates = $this->getTemplateUpdates();
    $return = [];
    foreach ($templates as $templateArray) {
      if ($templateArray['version'] === $this->getUpgradeVersion()) {
        foreach ($templateArray['templates'] as $template) {
          $return[$template['name'] . '_' . $template['type']] = array_merge($template, $templateArray);
        }
      }
    }
    return $return;
  }

  /**
   * Get the upgrade messages.
   */
  public function getUpgradeMessages() {
    $updates = $this->getTemplatesToUpdate();
    $messages = [];
    $templateLabel = '';
    foreach ($updates as $key => $value) {
      try {
        $templateLabel = civicrm_api3('OptionValue', 'getvalue', [
          'return' => 'label',
          'name' => $value['name'],
          'options' => ['limit' => 1],
        ]);
      }
      catch (Exception $e) {
        if (!empty($value['label'])) {
          $templateLabel = $value['label'];
        }
      }
      $messages[$templateLabel] = $value['upgrade_descriptor'];
    }
    return $messages;
  }

  /**
   * Update message templates.
   */
  public function updateTemplates() {
    $templates = $this->getTemplatesToUpdate();
    foreach ($templates as $template) {
      $workFlowID = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) as id FROM civicrm_option_value WHERE name = %1", [
        1 => [$template['name'], 'String'],
      ]);
      $content = file_get_contents(\Civi::paths()->getPath('[civicrm.root]/xml/templates/message_templates/' . $template['name'] . '_' . $template['type'] . '.tpl'));
      $templatesToUpdate = [];
      if (!empty($workFlowID)) {
        $templatesToUpdate[] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_msg_template WHERE workflow_id = $workFlowID AND is_reserved = 1");
        $defaultTemplateID = CRM_Core_DAO::singleValueQuery("
          SELECT default_template.id FROM civicrm_msg_template reserved
          LEFT JOIN civicrm_msg_template default_template
            ON reserved.workflow_id = default_template.workflow_id
          WHERE reserved.workflow_id = $workFlowID
          AND reserved.is_reserved = 1 AND default_template.is_default = 1 AND reserved.id <> default_template.id
          AND reserved.msg_{$template['type']} = default_template.msg_{$template['type']}
        ");
        if ($defaultTemplateID) {
          $templatesToUpdate[] = $defaultTemplateID;
        }

        CRM_Core_DAO::executeQuery("
          UPDATE civicrm_msg_template SET msg_{$template['type']} = %1 WHERE id IN (" . implode(',', $templatesToUpdate) . ")", [
            1 => [$content, 'String'],
          ]
        );
      }
    }
  }

}
