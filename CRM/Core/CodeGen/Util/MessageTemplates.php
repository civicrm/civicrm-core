<?php
/**
 * Class CRM_Core_CodeGen_Util_MessageTemplate
 */
class CRM_Core_CodeGen_Util_MessageTemplates {

  public static function assignSmartyVariables($smarty): void {
    // Assigns for message templates
    $smarty->assign('optionGroupNames', [
      'case'         => ts('Message Template Workflow for Cases', ['escape' => 'sql']),
      'contribution' => ts('Message Template Workflow for Contributions', ['escape' => 'sql']),
      'event'        => ts('Message Template Workflow for Events', ['escape' => 'sql']),
      'friend'       => ts('Message Template Workflow for Tell-a-Friend', ['escape' => 'sql']),
      'membership'   => ts('Message Template Workflow for Memberships', ['escape' => 'sql']),
      'meta'         => ts('Message Template Workflow for Meta Templates', ['escape' => 'sql']),
      'pledge'       => ts('Message Template Workflow for Pledges', ['escape' => 'sql']),
      'uf'           => ts('Message Template Workflow for Profiles', ['escape' => 'sql']),
      'petition'     => ts('Message Template Workflow for Petition', ['escape' => 'sql']),
    ]);

    $templates = [
      'case_activity' => [
        'option_group_name' => 'case',
        'title' => ts('Cases - Send Copy of an Activity', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'contribution_dupalert'  => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Duplicate Organization Alert', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'contribution_offline_receipt' => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Receipt (off-line)', ['escape' => 'sql']),
        'weight' => 2,
        'value' => 2,
      ],
      'contribution_online_receipt' => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Receipt (on-line)', ['escape' => 'sql']),
        'weight' => 3,
        'value' => 3,
      ],
      'contribution_invoice_receipt' => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Invoice', ['escape' => 'sql']),
        'weight' => 4,
        'value' => 4,
      ],
      'contribution_recurring_notify' => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Recurring Start and End Notification', ['escape' => 'sql']),
        'weight' => 5,
        'value' => 5,
      ],
      'contribution_recurring_cancelled' => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Recurring Cancellation Notification', ['escape' => 'sql']),
        'weight' => 6,
        'value' => 6,
      ],
      'contribution_recurring_billing' => [
        'option_group_name' => 'contribution',
        'title' => ts('Contributions - Recurring Billing Updates', ['escape' => 'sql']),
        'weight' => 7,
        'value' => 7,
      ],
      'contribution_recurring_edit'    => [
        'title' => ts('Contributions - Recurring Updates', ['escape' => 'sql']),
        'option_group_name' => 'contribution',
        'weight' => 8,
        'value' => 8,
      ],
      'pcp_notify' => [
        'title' => ts('Personal Campaign Pages - Admin Notification', ['escape' => 'sql']),
        'option_group_name' => 'contribution',
        'weight' => 9,
        'value' => 9,
      ],
      'pcp_status_change' => [
        'title' => ts('Personal Campaign Pages - Supporter Status Change Notification', ['escape' => 'sql']),
        'option_group_name' => 'contribution',
        'weight' => 10,
        'value' => 10,
      ],
      'pcp_supporter_notify' => [
        'title' => ts('Personal Campaign Pages - Supporter Welcome', ['escape' => 'sql']),
        'option_group_name' => 'contribution',
        'weight' => 11,
        'value' => 11,
      ],
      'pcp_owner_notify' => [
        'title' => ts('Personal Campaign Pages - Owner Notification', ['escape' => 'sql']),
        'option_group_name' => 'contribution',
        'weight' => 12,
        'value' => 12,
      ],
      'payment_or_refund_notification' => [
        'title' => ts('Additional Payment Receipt or Refund Notification', ['escape' => 'sql']),
        'option_group_name' => 'contribution',
        'weight' => 13,
        'value' => 13,
      ],
      'event_offline_receipt' => [
        'option_group_name' => 'event',
        'title' => ts('Events - Registration Confirmation and Receipt (off-line)', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'event_online_receipt' => [
        'option_group_name' => 'event',
        'title' => ts('Events - Registration Confirmation and Receipt (on-line)', ['escape' => 'sql']),
        'weight' => 2,
        'value' => 2,
      ],
      'participant_cancelled' => [
        'option_group_name' => 'event',
        'title' => ts('Events - Registration Cancellation Notice', ['escape' => 'sql']),
        'weight' => 4,
        'value' => 4,
      ],
      'participant_confirm' => [
        'option_group_name' => 'event',
        'title' => ts('Events - Registration Confirmation Invite', ['escape' => 'sql']),
        'weight' => 5,
        'value' => 5,
      ],
      'participant_expired' => [
        'option_group_name' => 'event',
        'title' => ts('Events - Pending Registration Expiration Notice', ['escape' => 'sql']),
        'weight' => 6,
        'value' => 6,
      ],
      'participant_transferred' => [
        'option_group_name' => 'event',
        'title' => ts('Events - Registration Transferred Notice', ['escape' => 'sql']),
        'weight' => 7,
        'value' => 7,
      ],
      'friend' => [
        'option_group_name' => 'friend',
        'title' => ts('Tell-a-Friend Email', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'membership_offline_receipt' => [
        'option_group_name' => 'membership',
        'title' => ts('Memberships - Signup and Renewal Receipts (off-line)', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'membership_online_receipt'  => [
        'option_group_name' => 'membership',
        'title' => ts('Memberships - Receipt (on-line)', ['escape' => 'sql']),
        'weight' => 2,
        'value' => 2,
      ],
      'membership_autorenew_cancelled' => [
        'option_group_name' => 'membership',
        'title' => ts('Memberships - Auto-renew Cancellation Notification', ['escape' => 'sql']),
        'weight' => 3,
        'value' => 3,
      ],
      'membership_autorenew_billing' => [
        'option_group_name' => 'membership',
        'title' => ts('Memberships - Auto-renew Billing Updates', ['escape' => 'sql']),
        'weight' => 4,
        'value' => 4,
      ],
      'test_preview' => [
        'option_group_name' => 'meta',
        'title' => ts('Test-drive - Receipt Header', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'pledge_acknowledge' => [
        'option_group_name' => 'pledge',
        'title' => ts('Pledges - Acknowledgement', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'pledge_reminder'    => [
        'option_group_name' => 'pledge',
        'title' => ts('Pledges - Payment Reminder', ['escape' => 'sql']),
        'weight' => 2,
        'value' => 2,
      ],
      'uf_notify' => [
        'option_group_name' => 'uf',
        'title' => ts('Profiles - Admin Notification', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'petition_sign' => [
        'option_group_name' => 'petition',
        'title' => ts('Petition - signature added', ['escape' => 'sql']),
        'weight' => 1,
        'value' => 1,
      ],
      'petition_confirmation_needed' => [
        'option_group_name' => 'petition',
        'title' => ts('Petition - need verification', ['escape' => 'sql']),
        'weight' => 2,
        'value' => 2,
      ],
    ];
    // @todo - if used mgd files we could also use revert (& would not need the reserved version in the db).
    $directory = self::getDirectory($smarty);
    foreach (array_keys($templates) as $name) {
      $templates[$name]['msg_html'] = file_get_contents($directory . '/' . $name . '_html.tpl');
      $templates[$name]['subject'] = file_get_contents($directory . '/' . $name . '_subject.tpl');
      $templates[$name]['name'] = $name;
    }
    $templates['Sample CiviMail Newsletter Template'] = [
      'title' => ts('Sample CiviMail Newsletter Template', ['escape' => 'sql']),
      'subject' => ts('Sample CiviMail Newsletter', ['escape' => 'sql']),
      'msg_html' => file_get_contents($directory . '/sample/Sample CiviMail Newsletter.tpl'),
      'weight' => 1,
      'name' => 'Sample CiviMail Newsletter',
      'option_group_name' => '',
    ];
    $templates['Sample Responsive Design Newsletter - Single Column Template'] = [
      'title' => ts('Sample Responsive Design Newsletter - Single Column Template', ['escape' => 'sql']),
      'subject' => ts('Sample Responsive Design Newsletter - Single Column', ['escape' => 'sql']),
      'msg_html' => file_get_contents($directory . '/sample/Sample Responsive Design Newsletter - Single Column.tpl'),
      'weight' => 2,
      'name' => 'Sample Responsive Design Newsletter - Single Column',
      'option_group_name' => '',
    ];
    $templates['Sample Responsive Design Newsletter - Two Column Template'] = [
      'title' => ts('Sample Responsive Design Newsletter - Two Column Template', ['escape' => 'sql']),
      'subject' => ts('Sample Responsive Design Newsletter - Two Column', ['escape' => 'sql']),
      'msg_html' => file_get_contents($directory . '/sample/Sample Responsive Design Newsletter - Two Column.tpl'),
      'weight' => 3,
      'name' => 'Sample Responsive Design Newsletter - Two Column',
      'option_group_name' => '',
    ];
    $smarty->assign('templates', $templates);
  }

  /**
   * Transition function to get the directory as we switch from Smarty 2 to 3.
   * @param $smarty
   *
   * @return string
   */
  protected static function getDirectory($smarty) {
    $directories = $smarty->getTemplateDir();
    foreach ($directories as $directory) {
      if (file_exists($directory . '/message_templates/')) {
        return $directory . '/message_templates/';
      }
    }
    return $directory . '/message_templates/';
  }

}
