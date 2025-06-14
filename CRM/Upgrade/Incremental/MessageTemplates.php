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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
          ['name' => 'contribution_online_receipt', 'type' => 'html'],
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
          ['name' => 'membership_online_receipt', 'type' => 'html'],
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
          ['name' => 'pcp_status_change', 'type' => 'html'],
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
          ['name' => 'pledge_acknowledge', 'type' => 'html'],
          ['name' => 'pledge_acknowledge', 'type' => 'text'],
          ['name' => 'pledge_reminder', 'type' => 'html'],
          ['name' => 'pledge_reminder', 'type' => 'subject'],
          ['name' => 'pledge_reminder', 'type' => 'text'],
          ['name' => 'uf_notify', 'type' => 'subject'],
          ['name' => 'uf_notify', 'type' => 'html'],
          ['name' => 'case_activity', 'type' => 'html'],
          ['name' => 'contribution_dupalert', 'type' => 'html'],
          ['name' => 'friend', 'type' => 'html'],
          ['name' => 'test_preview', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.21.beta1',
        'upgrade_descriptor' => ts('Fix Membership Receipt'),
        'templates' => [
          ['name' => 'membership_online_receipt', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.23.alpha1',
        'upgrade_descriptor' => ts('Add Contributor Name to Offline Contribution receipts; fix bad event self-service URL'),
        'templates' => [
          ['name' => 'contribution_offline_receipt', 'type' => 'text'],
          ['name' => 'contribution_offline_receipt', 'type' => 'html'],
          ['name' => 'participant_confirm', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.24.alpha1',
        'upgrade_descriptor' => ts('Layout fixes for the Contribution templates'),
        'templates' => [
          ['name' => 'contribution_invoice_receipt', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.30.alpha1',
        'upgrade_descriptor' => ts('Support negative hours for cancellation/transfer'),
        'templates' => [
          ['name' => 'participant_confirm', 'type' => 'html'],
          ['name' => 'participant_confirm', 'type' => 'text'],
          ['name' => 'event_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'text'],
        ],
      ],
      [
        'version' => '5.30.beta1',
        'upgrade_descriptor' => ts('Ensure that amount paid is shown even when fully paid'),
        'templates' => [
          ['name' => 'contribution_invoice_receipt', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.38.alpha1',
        'upgrade_descriptor' => ts('Fix Petition Confirmation email having a blank space at the end of url'),
        'templates' => [
          ['name' => 'petition_confirmation_needed', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.38.alpha1',
        'upgrade_descriptor' => ts('Fix Pledge and PCP urls to go to the front end site rather than backend site'),
        'templates' => [
          ['name' => 'pcp_notify', 'type' => 'html'],
          ['name' => 'pcp_notify', 'type' => 'text'],
          ['name' => 'pledge_reminder', 'type' => 'html'],
          ['name' => 'pledge_reminder', 'type' => 'text'],
        ],
      ],
      [
        'version' => '5.43.alpha1',
        'upgrade_descriptor' => ts('Missed templates from earlier versions'),
        'templates' => [
          ['name' => 'contribution_online_receipt', 'type' => 'text'],
          ['name' => 'case_activity', 'type' => 'html'],
          ['name' => 'case_activity', 'type' => 'text'],
          ['name' => 'case_activity', 'type' => 'subject'],
        ],
      ],
      [
        'version' => '5.44.alpha1',
        'upgrade_descriptor' => ts('Use domain tokens instead of smarty values'),
        'label' => ts('Pledge acknowledgement'),
        'templates' => [
          ['name' => 'pledge_acknowledge', 'type' => 'text'],
          ['name' => 'pledge_acknowledge', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.44.alpha1',
        'upgrade_descriptor' => ts('Use domain and contact tokens instead of smarty values'),
        'label' => ts('Pledge reminder'),
        'templates' => [
          ['name' => 'pledge_reminder', 'type' => 'text'],
          ['name' => 'pledge_reminder', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.44.alpha1',
        'upgrade_descriptor' => ts('Use domain and contact tokens instead of smarty values'),
        'label' => ts('Participant Transferred'),
        'templates' => [
          ['name' => 'participant_transferred', 'type' => 'text'],
          ['name' => 'participant_transferred', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.44.alpha1',
        'upgrade_descriptor' => ts('Use domain and contact tokens instead of smarty values'),
        'label' => ts('Participant Cancelled'),
        'templates' => [
          ['name' => 'participant_cancelled', 'type' => 'text'],
          ['name' => 'participant_cancelled', 'type' => 'html'],
        ],
      ],

      [
        'version' => '5.44.alpha1',
        'upgrade_descriptor' => ts('Use domain and contact tokens instead of smarty values'),
        'label' => ts('Participant Expired'),
        'templates' => [
          ['name' => 'participant_expired', 'type' => 'text'],
          ['name' => 'participant_expired', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.44.alpha1',
        'upgrade_descriptor' => ts('Use domain and contact tokens instead of smarty values'),
        'label' => ts('Participant Confirmed'),
        'templates' => [
          ['name' => 'participant_confirm', 'type' => 'text'],
          ['name' => 'participant_confirm', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.48.alpha1',
        'upgrade_descriptor' => ts('Replace {receipt_text_renewal} with {receipt_text}'),
        'templates' => [
          ['name' => 'membership_offline_receipt', 'type' => 'html'],
          ['name' => 'membership_offline_receipt', 'type' => 'text'],
        ],
      ],
      [
        'version' => '5.48.beta2',
        'upgrade_descriptor' => ts('Revert time zone for Event dates'),
        'templates' => [
          ['name' => 'event_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'text'],
          ['name' => 'event_offline_receipt', 'type' => 'html'],
          ['name' => 'event_offline_receipt', 'type' => 'text'],
          ['name' => 'participant_cancelled', 'type' => 'html'],
          ['name' => 'participant_cancelled', 'type' => 'text'],
          ['name' => 'participant_confirm', 'type' => 'html'],
          ['name' => 'participant_confirm', 'type' => 'text'],
          ['name' => 'participant_expired', 'type' => 'html'],
          ['name' => 'participant_expired', 'type' => 'text'],
          ['name' => 'participant_transferred', 'type' => 'html'],
          ['name' => 'participant_transferred', 'type' => 'text'],
          ['name' => 'payment_or_refund_notification', 'type' => 'html'],
          ['name' => 'payment_or_refund_notification', 'type' => 'text'],
        ],
      ],
      [
        'version' => '5.53.alpha1',
        'upgrade_descriptor' => ts('Update to new smarty variables for line items, tax'),
        'templates' => [
          ['name' => 'contribution_offline_receipt', 'type' => 'text'],
          ['name' => 'contribution_offline_receipt', 'type' => 'html'],
        ],
      ],
      [
        'version' => '5.65.alpha1',
        'upgrade_descriptor' => ts('Update to use tokens'),
        'templates' => [
          ['name' => 'petition_sign', 'type' => 'text'],
          ['name' => 'petition_sign', 'type' => 'html'],
          ['name' => 'petition_sign', 'type' => 'subject'],
        ],
      ],
      [
        'version' => '5.68.alpha1',
        'upgrade_descriptor' => ts('Significant changes to the template and available variables. Text version is discontinued'),
        'templates' => [
          ['name' => 'event_offline_receipt', 'type' => 'text'],
          ['name' => 'event_offline_receipt', 'type' => 'html'],
          ['name' => 'event_offline_receipt', 'type' => 'subject'],
        ],
      ],
      [
        'version' => '5.69.alpha1',
        'upgrade_descriptor' => ts('Significant changes to the template and available variables. Text version is discontinued'),
        'templates' => [
          ['name' => 'membership_online_receipt', 'type' => 'text'],
          ['name' => 'membership_online_receipt', 'type' => 'html'],
          ['name' => 'membership_online_receipt', 'type' => 'subject'],
        ],
      ],
      [
        'version' => '5.74.alpha1',
        'upgrade_descriptor' => ts('Minor space issue in string'),
        'templates' => [
          ['name' => 'event_online_receipt', 'type' => 'text'],
          ['name' => 'event_online_receipt', 'type' => 'html'],
        ],
      ],
    ];
  }

  /**
   * Get any required template updates.
   *
   * @return array
   */
  public function getTemplatesToUpdate(): array {
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
   * Replace a token with the new preferred option.
   *
   * @param string $workflowName
   * @param string $old
   * @param string $new
   */
  public function replaceTokenInTemplate(string $workflowName, string $old, string $new): void {
    $oldToken = '{' . $old . '}';
    $newToken = '{' . $new . '}';
    CRM_Core_DAO::executeQuery("UPDATE civicrm_msg_template
      SET
        msg_text = REPLACE(msg_text, '$oldToken', '$newToken'),
        msg_subject = REPLACE(msg_subject, '$oldToken', '$newToken'),
        msg_html = REPLACE(msg_html, '$oldToken', '$newToken')
      WHERE workflow_name = '$workflowName'
    ");
  }

  /**
   * Replace a token with the new preferred option in non-workflow templates.
   *
   * @param string $old
   * @param string $new
   */
  public function replaceTokenInMessageTemplates(string $old, string $new): void {
    $oldToken = '{' . $old . '}';
    $newToken = '{' . $new . '}';
    CRM_Core_DAO::executeQuery("UPDATE civicrm_msg_template
      SET
        msg_text = REPLACE(msg_text, '$oldToken', '$newToken'),
        msg_subject = REPLACE(msg_subject, '$oldToken', '$newToken'),
        msg_html = REPLACE(msg_html, '$oldToken', '$newToken')
      WHERE workflow_name IS NULL
    ");
  }

  /**
   * Replace a token with the new preferred option.
   *
   * @param string $old
   * @param string $new
   */
  public function replaceTokenInActionSchedule(string $old, string $new): void {
    $oldToken = '{' . $old . '}';
    $newToken = '{' . $new . '}';
    CRM_Core_DAO::executeQuery("UPDATE civicrm_action_schedule
      SET
        body_text = REPLACE(body_text, '$oldToken', '$newToken'),
        subject = REPLACE(subject, '$oldToken', '$newToken'),
        body_html = REPLACE(body_html, '$oldToken', '$newToken')
    ");
  }

  /**
   * Replace a token with the new preferred option in a print label.
   *
   * @param string $old
   * @param string $new
   */
  public function replaceTokenInPrintLabel(string $old, string $new): void {
    $oldToken = '{' . $old . '}';
    $newToken = '{' . $new . '}';
    CRM_Core_DAO::executeQuery("UPDATE civicrm_print_label
      SET
        data = REPLACE(data, '$oldToken', '$newToken')
    ");
  }

  /**
   * Replace a token with the new preferred option in a print label.
   *
   * @param string $old
   * @param string $new
   *
   * @throws \CRM_Core_Exception
   */
  public function replaceTokenInGreetingOptions(string $old, string $new): void {
    $oldToken = '{' . $old . '}';
    $newToken = '{' . $new . '}';
    $options = (array) Civi\Api4\OptionValue::get(FALSE)
      ->addWhere('option_group_id:name', 'IN', ['email_greeting', 'postal_greeting', 'addressee'])
      ->setSelect(['id'])->execute()->indexBy('id');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_option_value
      SET
        label = REPLACE(label, '$oldToken', '$newToken'),
        name = REPLACE(name, '$oldToken', '$newToken')
      WHERE id IN (" . implode(',', array_keys($options)) . ')'
    );
  }

  /**
   * Get the upgrade messages.
   *
   * @fromVer version we are upgrading from
   */
  public function getUpgradeMessages($fromVer) {
    $updates = $this->getTemplatesToUpdate();

    $uneditedTemplates = [];
    // workflow_name is not available pre-5.26, so we won't check if templates are changed or not pre-5.26
    if (version_compare($fromVer, 5.26, '>=')) {
      $uneditedTemplates = $this->getUneditedTemplates();
    }

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
      if (!(array_key_exists($key, $uneditedTemplates))) {
        $messages[$templateLabel] = $value['upgrade_descriptor'];
      }
    }
    return $messages;
  }

  /**
   * Update message templates.
   */
  public function updateTemplates(): void {
    $templates = $this->getTemplatesToUpdate();
    foreach ($templates as $template) {
      $workFlowID = CRM_Core_DAO::singleValueQuery('SELECT MAX(id) as id FROM civicrm_option_value WHERE name = %1', [
        1 => [$template['name'], 'String'],
      ]);
      if ($template['type'] === 'text') {
        // We no longer ship text templates.
        $content = '';
      }
      else {
        $content = file_get_contents(\Civi::paths()
          ->getPath('[civicrm.root]/xml/templates/message_templates/' . $template['name'] . '_' . $template['type'] . '.tpl'));
      }
      $templatesToUpdate = [];
      if (!empty($workFlowID)) {
        // This could be empty if the template was deleted. It should not happen,
        // but has been seen in the wild (ex: marketing/civicrm-website#163).
        $id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_msg_template WHERE workflow_id = $workFlowID AND is_reserved = 1");
        if ($id) {
          $templatesToUpdate[] = $id;
        }
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

        if (!empty($templatesToUpdate)) {
          CRM_Core_DAO::executeQuery("
            UPDATE civicrm_msg_template SET msg_{$template['type']} = %1 WHERE id IN (" . implode(',', $templatesToUpdate) . ")", [
              1 => [$content, 'String'],
            ]
          );
        }
      }
    }
  }

  /**
   * Make sure *all* reserved ones get updated. Might be inefficient because we either already updated or
   * there were no changes to a given template, but there's only about 30.
   * This runs near the final steps of the upgrade, otherwise the earlier checks that run during the
   * individual revisions wouldn't accurately be checking against the right is_reserved version to see
   * if it had changed.
   * @todo - do we still need those earlier per-version runs? e.g. the token replacement functions should still work as-is?
   *
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function updateReservedAndMaybeDefaultTemplates(CRM_Queue_TaskContext $ctx): bool {
    // This has to come first otherwise it would be checking against is_reserved we already updated.
    $uneditedTemplates = self::getUneditedTemplates();

    $dao = CRM_Core_DAO::executeQuery('SELECT id, workflow_id, workflow_name FROM civicrm_msg_template WHERE is_reserved=1');
    while ($dao->fetch()) {
      foreach (['html', 'text', 'subject'] as $type) {
        $filePath = \Civi::paths()->getPath('[civicrm.root]/xml/templates/message_templates/' . $dao->workflow_name . '_' . $type . '.tpl');
        if (!file_exists($filePath)) {
          // The query may have picked up some non-core templates that will not have files to find.
          continue;
        }
        $content = file_get_contents($filePath);
        if ($content !== FALSE) {
          CRM_Core_DAO::executeQuery(
            "UPDATE civicrm_msg_template SET msg_{$type} = %1 WHERE id = %2", [
              1 => [$content, 'String'],
              2 => [$dao->id, 'Integer'],
            ]
          );

          // If the same workflow_id and type appears in our list of unedited templates, update it too.
          // There's probably a more efficient way to look this up but simple for now.
          foreach ($uneditedTemplates as $uneditedTemplate) {
            if ($uneditedTemplate['workflow_id'] === $dao->workflow_id && $uneditedTemplate['type'] === $type) {
              CRM_Core_DAO::executeQuery(
                "UPDATE civicrm_msg_template SET msg_{$type} = %1 WHERE id = %2", [
                  1 => [$content, 'String'],
                  2 => [$uneditedTemplate['id'], 'Integer'],
                ]
              );
              break;
            }
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Get all the is_default templates that are the unchanged from their is_reserved counterpart, which
   * at the time this runs was the version shipped with core when it was last changed.
   *
   * This cannot be used before 5.26, as workflow_name was only added in 5.26.
   *
   * @return array
   */
  public static function getUneditedTemplates(): array {
    $templates = [];
    foreach (['html', 'text', 'subject'] as $type) {
      $dao = CRM_Core_DAO::executeQuery("
        SELECT default_template.id, default_template.workflow_id, default_template.workflow_name FROM civicrm_msg_template reserved
        LEFT JOIN civicrm_msg_template default_template
          ON reserved.workflow_id = default_template.workflow_id
        WHERE reserved.is_reserved = 1 AND default_template.is_default = 1 AND reserved.id <> default_template.id
        AND reserved.msg_{$type} = default_template.msg_{$type}
      ");
      while ($dao->fetch()) {
        // Note the same id can appear multiple times, e.g. you might change the html but not the subject.
        $templates[$dao->workflow_name . '_' . $type] = [
          'id' => $dao->id,
          'type' => $type,
          'workflow_id' => $dao->workflow_id,
          'workflow_name' => $dao->workflow_name,
        ];
      }
    }
    return $templates;
  }

}
